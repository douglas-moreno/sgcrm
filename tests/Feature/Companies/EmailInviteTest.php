<?php

declare(strict_types=1);

use App\Jobs\SendInviteJob;
use App\Livewire\Invites\AcceptInvite;
use App\Livewire\Team\InviteList;
use App\Mail\InviteMail;
use App\Models\Company;
use App\Models\Invite;
use App\Models\InviteStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\InviteStatusSeeder::class);

    $this->company = Company::factory()->create();
    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
});

it('owner creates invite which queues mail and persists token', function (): void {
    Bus::fake();

    Livewire::actingAs($this->owner)
        ->test(InviteList::class)
        ->set('name', 'New Person')
        ->set('email', 'new@acme.test')
        ->call('send')
        ->assertHasNoErrors()
        ->assertDispatched('invite-sent');

    $invite = Invite::query()->where('email', 'new@acme.test')->firstOrFail();

    expect($invite->company_id)->toBe($this->company->id)
        ->and($invite->token)->not->toBeEmpty()
        ->and($invite->expires_at->isFuture())->toBeTrue()
        ->and($invite->status->slug)->toBe(InviteStatus::PENDING)
        ->and($invite->role->slug)->toBe(Role::SALESPERSON);

    Bus::assertDispatched(SendInviteJob::class, fn (SendInviteJob $job) => $job->invite->id === $invite->id);
});

it('non-owner cannot create invite', function (): void {
    Livewire::actingAs($this->seller)
        ->test(InviteList::class)
        ->assertForbidden();
});

it('rejects duplicate user email and existing pending invite', function (): void {
    Bus::fake();

    Livewire::actingAs($this->owner)
        ->test(InviteList::class)
        ->set('name', 'Dup')
        ->set('email', $this->seller->email)
        ->call('send')
        ->assertHasErrors('email');

    Invite::factory()->create([
        'company_id' => $this->company->id,
        'invited_by_user_id' => $this->owner->id,
        'email' => 'pending@acme.test',
    ]);

    Livewire::actingAs($this->owner)
        ->test(InviteList::class)
        ->set('name', 'Pending')
        ->set('email', 'pending@acme.test')
        ->call('send')
        ->assertHasErrors('email');
});

it('Salesperson opens link, sets password, account created', function (): void {
    $invite = Invite::factory()->create([
        'company_id' => $this->company->id,
        'invited_by_user_id' => $this->owner->id,
        'email' => 'newhire@acme.test',
        'name' => 'New Hire',
    ]);

    Livewire::test(AcceptInvite::class, ['token' => $invite->token])
        ->set('password', 'super-secret-password')
        ->set('password_confirmation', 'super-secret-password')
        ->call('accept')
        ->assertHasNoErrors()
        ->assertRedirect(route('kanban'));

    $user = User::query()->where('email', 'newhire@acme.test')->firstOrFail();

    expect($user->company_id)->toBe($this->company->id)
        ->and($user->role->slug)->toBe(Role::SALESPERSON)
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check('super-secret-password', $user->password))->toBeTrue();

    $invite->refresh();
    expect($invite->status->slug)->toBe(InviteStatus::ACCEPTED)
        ->and($invite->accepted_user_id)->toBe($user->id)
        ->and($invite->accepted_at)->not->toBeNull();
});

it('rejects expired token', function (): void {
    $invite = Invite::factory()->create([
        'company_id' => $this->company->id,
        'invited_by_user_id' => $this->owner->id,
        'expires_at' => now()->subDay(),
    ]);

    Livewire::test(AcceptInvite::class, ['token' => $invite->token])
        ->assertSet('errorMessage', 'This invitation has expired.');

    expect($invite->fresh()->status->slug)->toBe(InviteStatus::EXPIRED);
});

it('rejects revoked token', function (): void {
    $revoked = InviteStatus::where('slug', InviteStatus::REVOKED)->firstOrFail();
    $invite = Invite::factory()->create([
        'company_id' => $this->company->id,
        'invited_by_user_id' => $this->owner->id,
        'status_id' => $revoked->id,
    ]);

    Livewire::test(AcceptInvite::class, ['token' => $invite->token])
        ->assertSet('errorMessage', 'This invitation has been revoked.');
});

it('resend regenerates token and dispatches mail', function (): void {
    Bus::fake();

    $invite = Invite::factory()->create([
        'company_id' => $this->company->id,
        'invited_by_user_id' => $this->owner->id,
    ]);
    $oldToken = $invite->token;

    Livewire::actingAs($this->owner)
        ->test(InviteList::class)
        ->call('resend', $invite->id);

    $invite->refresh();
    expect($invite->token)->not->toBe($oldToken)
        ->and($invite->expires_at->isFuture())->toBeTrue();

    Bus::assertDispatched(SendInviteJob::class);
});

it('owner can revoke pending invite', function (): void {
    $invite = Invite::factory()->create([
        'company_id' => $this->company->id,
        'invited_by_user_id' => $this->owner->id,
    ]);

    Livewire::actingAs($this->owner)
        ->test(InviteList::class)
        ->call('revoke', $invite->id);

    expect($invite->fresh()->status->slug)->toBe(InviteStatus::REVOKED);
});

it('queued job sends invite mail', function (): void {
    Mail::fake();

    $invite = Invite::factory()->create([
        'company_id' => $this->company->id,
        'invited_by_user_id' => $this->owner->id,
    ]);

    (new SendInviteJob($invite))->handle();

    Mail::assertQueued(InviteMail::class, fn (InviteMail $mail) => $mail->hasTo($invite->email));
});
