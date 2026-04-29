<div data-testid="create-lead">
    <x-ui.button type="button" wire:click="openModal" data-testid="new-lead-button">+ New Lead</x-ui.button>

    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 px-4" data-testid="create-lead-modal">
            <div class="w-full max-w-lg rounded-lg bg-surface p-6 shadow-lg">
                <h3 class="text-base font-semibold text-ink">New lead</h3>

                <form wire:submit="save" class="mt-4 space-y-4" data-testid="create-lead-form">
                    <x-ui.input wire:model.live.debounce.500ms="email" name="email" type="email" label="Email" :error="$errors->first('email')" />

                    @if ($matchedLeadId !== null)
                        <div class="rounded-md border border-outline bg-surface-soft p-3 text-sm" data-testid="match-card">
                            @if ($matchVisible)
                                <p class="font-medium text-ink">Existing lead: {{ $matchLeadName }}</p>
                                <p class="text-xs text-ink-muted">Owner: {{ $matchOwnerName }}</p>
                                <div class="mt-3 flex gap-2">
                                    <x-ui.button type="button" variant="outline" wire:click="reuseExisting" data-testid="reuse-existing">Add deal to this lead</x-ui.button>
                                </div>
                            @else
                                <p class="text-warning" data-testid="match-blocked">Lead exists; contact your manager.</p>
                            @endif
                        </div>
                    @endif

                    <x-ui.input wire:model="name" name="name" label="Name" :error="$errors->first('name')" />
                    <x-ui.input wire:model="phone" name="phone" label="Phone" inputmode="tel" :error="$errors->first('phone')" />
                    <x-ui.textarea wire:model="notes" name="notes" label="Notes" :error="$errors->first('notes')" />

                    @if (auth()->user()?->isBusinessOwner())
                        <x-ui.select
                            wire:model="ownerUserId"
                            name="ownerUserId"
                            label="Owner"
                            :options="$this->salespeople->pluck('name', 'id')->all()"
                            :error="$errors->first('ownerUserId')"
                        />
                    @endif

                    <div class="flex justify-end gap-2">
                        <x-ui.button type="button" variant="ghost" wire:click="closeModal" data-testid="cancel">Cancel</x-ui.button>
                        <x-ui.button type="submit" data-testid="save">Create</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
