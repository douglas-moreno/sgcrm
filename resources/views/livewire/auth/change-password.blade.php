<x-layouts.guest title="Alterar senha" heading="Alterar sua senha" subheading="Defina uma nova senha para continuar.">
    <form wire:submit="update" class="space-y-4" data-testid="change-password-form">
        <x-ui.input wire:model="current_password" name="current_password" type="password" label="Senha atual" :error="$errors->first('current_password')" />
        <x-ui.input wire:model="password" name="password" type="password" label="Nova senha" :error="$errors->first('password')" />
        <x-ui.input wire:model="password_confirmation" name="password_confirmation" type="password" label="Confirmar nova senha" />

        <x-ui.button type="submit" class="w-full" data-testid="change-password-submit">Alterar senha</x-ui.button>
    </form>
</x-layouts.guest>
