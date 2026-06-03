<x-layouts.guest title="Cadastrar" heading="Bem-vindo ao sgCrm" subheading="Crie seu workspace e convide sua equipe.">
    <form wire:submit="register" class="space-y-4" data-testid="register-form">
        <x-ui.input wire:model="name" name="name" label="Nome completo" :error="$errors->first('name')" />
        <x-ui.input wire:model="email" name="email" type="email" label="Email" :error="$errors->first('email')" />
        <x-ui.input wire:model="company_name" name="company_name" label="Nome da empresa" :error="$errors->first('company_name')" />
        <x-ui.input wire:model="password" name="password" type="password" label="Senha" :error="$errors->first('password')" />
        <x-ui.input wire:model="password_confirmation" name="password_confirmation" type="password" label="Confirmar senha" />

        <x-ui.button type="submit" class="w-full" data-testid="submit">Criar workspace</x-ui.button>

        <p class="text-sm text-ink-muted text-center">
            Já tem uma conta?
            <a href="{{ route('login') }}" class="text-primary-500 font-medium" wire:navigate>Entrar</a>
        </p>
    </form>
</x-layouts.guest>
