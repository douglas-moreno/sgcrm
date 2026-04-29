<div data-testid="add-deal">
    <form wire:submit="save" class="grid gap-3 sm:grid-cols-3" data-testid="add-deal-form">
        <x-ui.input wire:model="title" name="title" label="Deal title" :error="$errors->first('title')" />
        <x-ui.input wire:model="value" name="value" type="number" min="0" step="0.01" label="Value" :error="$errors->first('value')" />
        <div class="flex items-end">
            <x-ui.button type="submit" data-testid="add-deal-submit">Add deal</x-ui.button>
        </div>
    </form>
</div>
