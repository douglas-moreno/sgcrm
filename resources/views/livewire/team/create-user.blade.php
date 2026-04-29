<div data-testid="create-user">
    <x-ui.card title="Create user" subtitle="Salesperson with a temporary password.">
        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2" data-testid="create-user-form">
            <x-ui.input wire:model="name" name="name" label="Name" :error="$errors->first('name')" />
            <x-ui.input wire:model="email" name="email" type="email" label="Email" :error="$errors->first('email')" />
            <div class="sm:col-span-2">
                <x-ui.input wire:model="temporary_password" name="temporary_password" type="text" label="Temporary password" :error="$errors->first('temporary_password')" hint="The user must change this on first login." />
            </div>
            <div class="sm:col-span-2 flex justify-end">
                <x-ui.button type="submit" data-testid="create-user-submit">Create account</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
