<x-layouts.guest title="Change password" heading="Change your password" subheading="Set a new password to continue.">
    <form wire:submit="update" class="space-y-4" data-testid="change-password-form">
        <x-ui.input wire:model="current_password" name="current_password" type="password" label="Current password" :error="$errors->first('current_password')" />
        <x-ui.input wire:model="password" name="password" type="password" label="New password" :error="$errors->first('password')" />
        <x-ui.input wire:model="password_confirmation" name="password_confirmation" type="password" label="Confirm new password" />

        <x-ui.button type="submit" class="w-full" data-testid="change-password-submit">Update password</x-ui.button>
    </form>
</x-layouts.guest>
