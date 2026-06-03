<div>
    <h1 class="text-2xl font-bold text-ink leading-tight">Verificar seu email</h1>
    <p class="text-sm text-ink-muted mt-2">Enviamos um link de verificação para o seu email. Clique nele para continuar. Precisa de um novo link?</p>

    @if ($status)
        <div class="mt-4">
            <x-ui.toast variant="success">{{ $status }}</x-ui.toast>
        </div>
    @endif

    @if (session('status') === 'verification-link-sent')
        <div class="mt-4">
            <x-ui.toast variant="success">Um novo link de verificação foi enviado.</x-ui.toast>
        </div>
    @endif

    <div class="flex items-center gap-3 mt-6">
        <x-ui.button wire:click="resend" data-testid="resend">Reenviar email</x-ui.button>
        <x-ui.button wire:click="logout" variant="ghost" data-testid="verify-logout">Sair</x-ui.button>
    </div>
</div>
