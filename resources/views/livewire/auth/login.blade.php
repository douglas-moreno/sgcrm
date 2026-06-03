<x-layouts.guest title="Entrar" heading="Bem-vindo de volta" subheading="Entre para continuar.">
    <form wire:submit="login" class="space-y-4" data-testid="login-form">
        <x-ui.input wire:model="email" name="email" type="email" label="Email" :error="$errors->first('email')" />
        <x-ui.input wire:model="password" name="password" type="password" label="Senha" :error="$errors->first('password')" />

        <x-ui.checkbox wire:model="remember" name="remember" label="Lembrar-me" />

        <x-ui.button type="submit" class="w-full" data-testid="submit">Entrar</x-ui.button>

        <div class="flex items-center justify-between text-sm">
            <a href="{{ route('password.request') }}" class="text-primary-500 font-medium" wire:navigate>Esqueceu a senha?</a>
            {{-- <a href="{{ route('register') }}" class="text-ink-muted" wire:navigate>Criar conta</a> --}}
        </div>
    </form>
</x-layouts.guest>
