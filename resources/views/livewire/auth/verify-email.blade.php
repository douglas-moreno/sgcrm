<div>
    <h1 class="text-2xl font-bold text-ink leading-tight">Verify your email</h1>
    <p class="text-sm text-ink-muted mt-2">We sent you a verification link. Click it to continue. Need a new one?</p>

    @if ($status)
        <div class="mt-4">
            <x-ui.toast variant="success">{{ $status }}</x-ui.toast>
        </div>
    @endif

    @if (session('status') === 'verification-link-sent')
        <div class="mt-4">
            <x-ui.toast variant="success">A new verification link has been sent.</x-ui.toast>
        </div>
    @endif

    <div class="flex items-center gap-3 mt-6">
        <x-ui.button wire:click="resend" data-testid="resend">Resend email</x-ui.button>
        <x-ui.button wire:click="logout" variant="ghost" data-testid="verify-logout">Sign out</x-ui.button>
    </div>
</div>
