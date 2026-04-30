<?php

declare(strict_types=1);

use App\Mail\AccountCreatedMail;
use App\Mail\InviteMail;
use App\Mail\PasswordResetConfirmationMail;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeMail;
use App\Models\Company;
use App\Models\Invite;
use App\Models\InviteStatus;
use App\Models\User;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\InviteStatusSeeder::class);
});

it('WelcomeMail has correct subject and body', function (): void {
    $user = User::factory()->businessOwner()->create(['name' => 'Alice', 'email' => 'alice@example.com']);

    $mail = (new WelcomeMail($user))->render();

    expect((new WelcomeMail($user))->envelope()->subject)->toBe('Welcome to sgCrm')
        ->and($mail)->toContain('Welcome to sgCrm, Alice')
        ->and($mail)->toContain('The sgCrm team');
});

it('WelcomeMail is queueable', function (): void {
    $user = User::factory()->businessOwner()->create();

    expect(new WelcomeMail($user))->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('InviteMail subject includes company name + body has accept link', function (): void {
    $company = Company::factory()->create(['name' => 'Acme Inc']);
    $owner = User::factory()->businessOwner()->forCompany($company)->create();
    $pending = InviteStatus::where('slug', InviteStatus::PENDING)->firstOrFail();

    $invite = Invite::factory()->create([
        'company_id' => $company->id,
        'invited_by_user_id' => $owner->id,
        'status_id' => $pending->id,
        'name' => 'Bob',
        'email' => 'bob@example.com',
        'token' => 'tok-abc',
    ]);

    $mailable = new InviteMail($invite);
    $body = $mailable->render();

    expect($mailable->envelope()->subject)->toBe('You have been invited to Acme Inc')
        ->and($body)->toContain("You're invited, Bob")
        ->and($body)->toContain('Acme Inc')
        ->and($body)->toContain(route('invites.accept', ['token' => 'tok-abc']));
});

it('InviteMail is queueable', function (): void {
    $invite = Invite::factory()->create();
    expect(new InviteMail($invite))->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('AccountCreatedMail body includes email + temporary password + login url', function (): void {
    $user = User::factory()->salesperson()->create(['name' => 'Carol', 'email' => 'carol@example.com']);

    $mail = new AccountCreatedMail($user, 'TempPass123!');
    $body = $mail->render();

    expect($mail->envelope()->subject)->toBe('Your sgCrm account is ready')
        ->and($body)->toContain('Welcome to sgCrm, Carol')
        ->and($body)->toContain('carol@example.com')
        ->and($body)->toContain('TempPass123!')
        ->and($body)->toContain(route('login'));
});

it('AccountCreatedMail is queueable', function (): void {
    $user = User::factory()->salesperson()->create();
    expect(new AccountCreatedMail($user, 'x'))->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('PasswordResetMail body includes branded reset link', function (): void {
    $user = User::factory()->salesperson()->create(['name' => 'Dan', 'email' => 'dan@example.com']);

    $mail = new PasswordResetMail($user, 'reset-token-xyz');
    $body = $mail->render();

    expect($mail->envelope()->subject)->toBe('Reset your sgCrm password')
        ->and($body)->toContain('Hi Dan')
        ->and($body)->toContain('dan@example.com')
        ->and($body)->toContain(route('password.reset', ['token' => 'reset-token-xyz', 'email' => 'dan@example.com']));
});

it('PasswordResetMail is queueable', function (): void {
    $user = User::factory()->salesperson()->create();
    expect(new PasswordResetMail($user, 'x'))->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('PasswordResetConfirmationMail body warns about unauthorized changes', function (): void {
    $user = User::factory()->salesperson()->create(['name' => 'Eve', 'email' => 'eve@example.com']);

    $mail = new PasswordResetConfirmationMail($user);
    $body = $mail->render();

    expect($mail->envelope()->subject)->toBe('Your sgCrm password was changed')
        ->and($body)->toContain('Password changed')
        ->and($body)->toContain('Hi Eve')
        ->and($body)->toContain('eve@example.com');
});

it('PasswordResetConfirmationMail is queueable', function (): void {
    $user = User::factory()->salesperson()->create();
    expect(new PasswordResetConfirmationMail($user))->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('all five mailables are branded with the sgCrm signature', function (): void {
    $user = User::factory()->salesperson()->create();
    $invite = Invite::factory()->create();

    $mails = [
        (new WelcomeMail($user))->render(),
        (new InviteMail($invite))->render(),
        (new AccountCreatedMail($user, 'pw'))->render(),
        (new PasswordResetMail($user, 'tok'))->render(),
        (new PasswordResetConfirmationMail($user))->render(),
    ];

    foreach ($mails as $body) {
        expect($body)->toContain('The sgCrm team');
    }
});
