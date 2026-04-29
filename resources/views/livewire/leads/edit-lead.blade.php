<div data-testid="edit-lead">
    <x-ui.page-header :title="$lead->name" subtitle="Lead detail" />

    <x-ui.card>
        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2" data-testid="edit-lead-form">
            <x-ui.input wire:model="name" name="name" label="Name" :error="$errors->first('name')" />

            @if (auth()->user()->can('updateEmail', $lead))
                <x-ui.input wire:model="email" name="email" type="email" label="Email" :error="$errors->first('email')" />
            @else
                <x-ui.input value="{{ $email }}" name="email_display" label="Email" disabled hint="Only Owner can edit." />
            @endif

            <x-ui.input wire:model="phone" name="phone" label="Phone" inputmode="tel" :error="$errors->first('phone')" />

            <div class="sm:col-span-2">
                <x-ui.textarea wire:model="notes" name="notes" label="Notes" :error="$errors->first('notes')" />
            </div>

            <div class="sm:col-span-2 flex justify-end">
                <x-ui.button type="submit" data-testid="save-lead">Save</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    @if (auth()->user()->can('reassign', $lead))
        <div class="mt-6">
            <x-ui.card title="Reassign owner">
                <livewire:leads.reassign-lead :lead="$lead" :wire:key="'reassign-'.$lead->id" />
            </x-ui.card>
        </div>
    @endif
</div>
