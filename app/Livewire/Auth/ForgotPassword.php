<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
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

        Password::sendResetLink(['email' => $this->email]);

        $this->status = __('If an account matches that email, a reset link has been sent.');
        $this->reset('email');
    }

    public function render(): mixed
    {
        return view('livewire.auth.forgot-password');
    }
}
