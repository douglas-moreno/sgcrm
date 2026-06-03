<div>
    <h1 class="text-2xl font-bold text-ink leading-tight">Esqueceu a senha?</h1>
    <p class="text-sm text-ink-muted mt-2">Digite seu email e enviaremos um link para redefinir sua senha.</p>

    @if ($status)
        <div class="mt-4">
            <x-ui.toast variant="success">{{ $status }}</x-ui.toast>
        </div>
    @endif

    <form wire:submit="sendLink" class="space-y-4 mt-6" data-testid="forgot-password-form">
        <x-ui.input wire:model="email" name="email" type="email" label="Email" :error="$errors->first('email')" />

        <x-ui.button type="submit" class="w-full" data-testid="submit">Enviar link de redefinição</x-ui.button>

        <p class="text-sm text-ink-muted text-center">
            <a href="{{ route('login') }}" class="text-primary-500 font-medium" wire:navigate>Voltar para o login</a>
        </p>
    </form>
</div>
