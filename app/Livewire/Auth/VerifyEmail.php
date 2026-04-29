<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
final class VerifyEmail extends Component
{
    public ?string $status = null;

    public function resend(): void
    {
        Auth::user()->sendEmailVerificationNotification();
        $this->status = __('A new verification link has been sent.');
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('login'), navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.auth.verify-email');
    }
}
