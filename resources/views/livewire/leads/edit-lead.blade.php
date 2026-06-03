<div data-testid="edit-lead">
    <x-ui.page-header :title="$lead->name" subtitle="Detalhe do Lead" />

    <x-ui.card>
        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2" data-testid="edit-lead-form">
            <x-ui.input wire:model="name" name="name" label="Nome" autofocus :error="$errors->first('name')" />

            @if (auth()->user()->can('updateEmail', $lead))
                <x-ui.input wire:model="email" name="email" type="email" label="Email" :error="$errors->first('email')" />
            @else
                <x-ui.input value="{{ $email }}" name="email_display" label="Email" disabled hint="Somente o proprietário pode editar." />
            @endif

            <x-ui.input wire:model="phone" name="phone" label="Celular" inputmode="tel" :error="$errors->first('phone')" />

            <div class="sm:col-span-2">
                <x-ui.textarea wire:model="notes" name="notes" label="Notas" :error="$errors->first('notes')" />
            </div>

            <div class="sm:col-span-2 flex justify-end">
                <x-ui.button type="submit" data-testid="save-lead">Salvar</x-ui.button>
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
