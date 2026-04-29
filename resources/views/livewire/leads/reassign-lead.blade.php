<div data-testid="reassign-lead">
    <form wire:submit="reassign" class="flex items-end gap-3" data-testid="reassign-form">
        <div class="flex-1">
            <x-ui.select
                wire:model="newOwnerId"
                name="newOwnerId"
                label="Reassign to"
                placeholder="Select Salesperson"
                :options="$this->salespeople->pluck('name', 'id')->all()"
                :error="$errors->first('newOwnerId')"
            />
        </div>
        <x-ui.button type="submit" data-testid="reassign-submit">Reassign</x-ui.button>
    </form>
</div>
