<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
final class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    public function sendLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('Too many reset attempts. Try again in :seconds seconds.', [
                    'seconds' => RateLimiter::availableIn($key),
                ]),
            ]);
        }

        RateLimiter::hit($key, 60);

        Password::sendResetLink(['email' => $this->email]);

        $this->status = __('If an account matches that email, a reset link has been sent.');
        $this->reset('email');
    }

    public function render(): mixed
    {
        return view('livewire.auth.forgot-password');
    }

    private function throttleKey(): string
    {
        return 'pwd-reset|'.Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
