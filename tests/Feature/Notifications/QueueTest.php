<?php

declare(strict_types=1);

use App\Jobs\SendInviteJob;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Team\CreateUser;
use App\Mail\AccountCreatedMail;
use App\Mail\InviteMail;
use App\Mail\PasswordResetConfirmationMail;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeMail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\InviteStatusSeeder::class);
});

it('Register queues WelcomeMail', function (): void {
    Mail::fake();

    Livewire::test(Register::class)
        ->set('name', 'Alice')
        ->set('email', 'alice@example.com')
        ->set('password', 'secret-password')
        ->set('password_confirmation', 'secret-password')
        ->set('company_name', 'Acme')
        ->call('register');

    Mail::assertQueued(WelcomeMail::class, fn ($mail) => $mail->user->email === 'alice@example.com');
});

it('CreateUser queues AccountCreatedMail', function (): void {
    Mail::fake();

    $company = Company::factory()->create();
    $owner = User::factory()->businessOwner()->forCompany($company)->create();

    Livewire::actingAs($owner)
        ->test(CreateUser::class)
        ->set('name', 'Bob')
        ->set('email', 'bob@example.com')
        ->set('temporary_password', 'TempPass123!')
        ->call('save');

    Mail::assertQueued(AccountCreatedMail::class, fn ($mail) => $mail->user->email === 'bob@example.com'
        && $mail->temporaryPassword === 'TempPass123!');
});

it('ForgotPassword queues PasswordResetMail (via overridden notification)', function (): void {
    Mail::fake();

    $user = User::factory()->salesperson()->create(['email' => 'reset-me@example.com']);

    Livewire::test(ForgotPassword::class)
        ->set('email', 'reset-me@example.com')
        ->call('sendLink');

    Mail::assertQueued(PasswordResetMail::class, fn ($mail) => $mail->user->id === $user->id);
});

it('ResetPassword queues PasswordResetConfirmationMail', function (): void {
    Mail::fake();

    $user = User::factory()->salesperson()->create(['email' => 'change@example.com']);
    $token = Password::createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', 'change@example.com')
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('resetPassword');

    Mail::assertQueued(PasswordResetConfirmationMail::class, fn ($mail) => $mail->user->id === $user->id);
});

it('SendInviteJob is queueable + dispatches via the queue', function (): void {
    Bus::fake();

    SendInviteJob::dispatch(App\Models\Invite::factory()->create());

    Bus::assertDispatched(SendInviteJob::class);
});

it('SendInviteJob handle sends InviteMail (queued via ShouldQueue)', function (): void {
    Mail::fake();

    $invite = App\Models\Invite::factory()->create(['email' => 'invite-target@example.com']);

    (new SendInviteJob($invite))->handle();

    Mail::assertQueued(InviteMail::class, fn ($mail) => $mail->hasTo('invite-target@example.com'));
});
