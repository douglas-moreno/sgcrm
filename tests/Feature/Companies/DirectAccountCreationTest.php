<?php

declare(strict_types=1);

use App\Livewire\Auth\ChangePassword;
use App\Livewire\Auth\Login;
use App\Livewire\Team\CreateUser;
use App\Mail\AccountCreatedMail;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);

    $this->company = Company::factory()->create();
    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();

    Mail::fake();
});

it('owner creates Salesperson with temporary password and email is queued', function (): void {
    Livewire::actingAs($this->owner)
        ->test(CreateUser::class)
        ->set('name', 'New Hire')
        ->set('email', 'hire@acme.test')
        ->set('temporary_password', 'temp-pass-1234')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('user-created');

    $user = User::query()->where('email', 'hire@acme.test')->firstOrFail();

    expect($user->company_id)->toBe($this->company->id)
        ->and($user->role->slug)->toBe(Role::SALESPERSON)
        ->and($user->is_active)->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and(Hash::check('temp-pass-1234', $user->password))->toBeTrue();

    Mail::assertQueued(
        AccountCreatedMail::class,
        fn (AccountCreatedMail $mail) => $mail->hasTo('hire@acme.test')
            && $mail->temporaryPassword === 'temp-pass-1234'
    );
});

it('non-owner cannot create users', function (): void {
    Livewire::actingAs($this->seller)
        ->test(CreateUser::class)
        ->assertForbidden();
});

it('rejects duplicate email in same company', function (): void {
    Livewire::actingAs($this->owner)
        ->test(CreateUser::class)
        ->set('name', 'Dup')
        ->set('email', $this->seller->email)
        ->set('temporary_password', 'temp-pass-1234')
        ->call('save')
        ->assertHasErrors('email');
});

it('first login redirects to forced password change', function (): void {
    $newUser = User::factory()
        ->salesperson()
        ->forCompany($this->company)
        ->create([
            'email' => 'first@acme.test',
            'password' => Hash::make('temp-pass-1234'),
            'must_change_password' => true,
        ]);

    Livewire::test(Login::class)
        ->set('email', $newUser->email)
        ->set('password', 'temp-pass-1234')
        ->call('login');

    $this->actingAs($newUser->fresh())
        ->get(route('kanban'))
        ->assertRedirect(route('password.change'));
});

it('password change clears must_change_password flag', function (): void {
    $user = User::factory()
        ->salesperson()
        ->forCompany($this->company)
        ->create([
            'password' => Hash::make('temp-pass-1234'),
            'must_change_password' => true,
        ]);

    Livewire::actingAs($user)
        ->test(ChangePassword::class)
        ->set('current_password', 'temp-pass-1234')
        ->set('password', 'brand-new-secret')
        ->set('password_confirmation', 'brand-new-secret')
        ->call('update')
        ->assertHasNoErrors()
        ->assertRedirect(route('kanban'));

    $user->refresh();
    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check('brand-new-secret', $user->password))->toBeTrue();
});
