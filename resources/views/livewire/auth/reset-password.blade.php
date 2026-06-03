<div>
    <h1 class="text-2xl font-bold text-ink leading-tight">Redefinir sua senha</h1>
    <p class="text-sm text-ink-muted mt-2">Escolha uma nova senha para sua conta.</p>

    <form wire:submit="resetPassword" class="space-y-4 mt-6" data-testid="reset-password-form">
        <x-ui.input wire:model="email" name="email" type="email" label="Email" :error="$errors->first('email')" />
        <x-ui.input wire:model="password" name="password" type="password" label="Nova senha" :error="$errors->first('password')" />
        <x-ui.input wire:model="password_confirmation" name="password_confirmation" type="password" label="Confirmar nova senha" />

        <x-ui.button type="submit" class="w-full" data-testid="submit">Redefinir senha</x-ui.button>
    </form>
</div>
