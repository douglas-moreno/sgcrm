<div>
    <h1 class="text-2xl font-bold text-ink leading-tight">Reset your password</h1>
    <p class="text-sm text-ink-muted mt-2">Choose a new password for your account.</p>

    <form wire:submit="resetPassword" class="space-y-4 mt-6" data-testid="reset-password-form">
        <x-ui.input wire:model="email" name="email" type="email" label="Email" :error="$errors->first('email')" />
        <x-ui.input wire:model="password" name="password" type="password" label="New password" :error="$errors->first('password')" />
        <x-ui.input wire:model="password_confirmation" name="password_confirmation" type="password" label="Confirm new password" />

        <x-ui.button type="submit" class="w-full" data-testid="submit">Reset password</x-ui.button>
    </form>
</div>
