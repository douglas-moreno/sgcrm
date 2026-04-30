<?php

declare(strict_types=1);

use App\Mail\PasswordResetConfirmationMail;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(function (): void {
    Mail::fake();
});

it('sends a reset link for a known email and shows a generic success message', function (): void {
    $user = User::factory()->salesperson()->create(['email' => 'known@example.com']);

    Livewire::test(App\Livewire\Auth\ForgotPassword::class)
        ->set('email', 'known@example.com')
        ->call('sendLink')
        ->assertSet('status', __('If an account matches that email, a reset link has been sent.'));

    Mail::assertQueued(PasswordResetMail::class, fn ($mail) => $mail->user->id === $user->id);
});

it('returns the same generic message for unknown emails (no enumeration)', function (): void {
    Livewire::test(App\Livewire\Auth\ForgotPassword::class)
        ->set('email', 'ghost@example.com')
        ->call('sendLink')
        ->assertSet('status', __('If an account matches that email, a reset link has been sent.'));

    Mail::assertNotQueued(PasswordResetMail::class);
});

it('resets the password with a valid token and queues the confirmation email', function (): void {
    $user = User::factory()->salesperson()->create(['email' => 'reset@example.com']);
    $token = Password::createToken($user);

    Livewire::test(App\Livewire\Auth\ResetPassword::class, ['token' => $token])
        ->set('email', 'reset@example.com')
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    $user->refresh();
    expect(Hash::check('brand-new-password', $user->password))->toBeTrue();

    Mail::assertQueued(PasswordResetConfirmationMail::class, fn ($mail) => $mail->user->id === $user->id);
});

it('rejects an invalid reset token', function (): void {
    User::factory()->salesperson()->create(['email' => 'reset@example.com']);

    Livewire::test(App\Livewire\Auth\ResetPassword::class, ['token' => 'invalid-token'])
        ->set('email', 'reset@example.com')
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('resetPassword')
        ->assertHasErrors(['email']);
});
