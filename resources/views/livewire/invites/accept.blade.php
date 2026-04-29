<x-layouts.guest title="Accept invitation" heading="Join your team" subheading="{{ $invite?->company?->name ? 'Set your password to access '.$invite->company->name.'.' : 'Set your password to continue.' }}">
    @if ($errorMessage)
        <div class="rounded-md bg-danger/10 text-danger px-4 py-3 text-sm" data-testid="invite-error">
            {{ $errorMessage }}
        </div>
        <p class="text-sm text-ink-muted">
            <a href="{{ route('login') }}" class="text-primary-500 font-medium" wire:navigate>Back to login</a>
        </p>
    @else
        <form wire:submit="accept" class="space-y-4" data-testid="accept-invite-form">
            <x-ui.input value="{{ $invite->name }}" name="display_name" label="Name" disabled />
            <x-ui.input value="{{ $invite->email }}" name="display_email" label="Email" disabled />
            <x-ui.input wire:model="password" name="password" type="password" label="Password" :error="$errors->first('password')" />
            <x-ui.input wire:model="password_confirmation" name="password_confirmation" type="password" label="Confirm password" />

            <x-ui.button type="submit" class="w-full" data-testid="accept-submit">Activate account</x-ui.button>
        </form>
    @endif
</x-layouts.guest>
