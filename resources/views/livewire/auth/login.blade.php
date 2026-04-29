<x-layouts.guest title="Sign in" heading="Welcome back" subheading="Sign in to keep going.">
    <form wire:submit="login" class="space-y-4" data-testid="login-form">
        <x-ui.input wire:model="email" name="email" type="email" label="Email" :error="$errors->first('email')" />
        <x-ui.input wire:model="password" name="password" type="password" label="Password" :error="$errors->first('password')" />

        <x-ui.checkbox wire:model="remember" name="remember" label="Remember me" />

        <x-ui.button type="submit" class="w-full" data-testid="submit">Sign in</x-ui.button>

        <div class="flex items-center justify-between text-sm">
            <a href="{{ route('password.request') }}" class="text-primary-500 font-medium" wire:navigate>Forgot password?</a>
            <a href="{{ route('register') }}" class="text-ink-muted" wire:navigate>Create account</a>
        </div>
    </form>
</x-layouts.guest>
