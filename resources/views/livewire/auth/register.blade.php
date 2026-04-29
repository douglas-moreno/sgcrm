<x-layouts.guest title="Sign up" heading="Welcome to sgCrm" subheading="Create your workspace and invite your team.">
    <form wire:submit="register" class="space-y-4" data-testid="register-form">
        <x-ui.input wire:model="name" name="name" label="Full name" :error="$errors->first('name')" />
        <x-ui.input wire:model="email" name="email" type="email" label="Email" :error="$errors->first('email')" />
        <x-ui.input wire:model="company_name" name="company_name" label="Company name" :error="$errors->first('company_name')" />
        <x-ui.input wire:model="password" name="password" type="password" label="Password" :error="$errors->first('password')" />
        <x-ui.input wire:model="password_confirmation" name="password_confirmation" type="password" label="Confirm password" />

        <x-ui.button type="submit" class="w-full" data-testid="submit">Create workspace</x-ui.button>

        <p class="text-sm text-ink-muted text-center">
            Already have an account?
            <a href="{{ route('login') }}" class="text-primary-500 font-medium" wire:navigate>Sign in</a>
        </p>
    </form>
</x-layouts.guest>
