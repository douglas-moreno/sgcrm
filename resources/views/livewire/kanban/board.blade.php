<div data-testid="kanban-root">
    <x-ui.page-header title="Pipeline" subtitle="Drag deals across stages.">
        <x-slot:actions>
            <livewire:leads.create-lead />
            @if ($this->canFilter())
                <select
                    wire:model.live="ownerFilter"
                    class="h-9 rounded-md border border-outline bg-surface px-3 text-sm"
                    data-testid="owner-filter"
                >
                    <option value="">All Salespeople</option>
                    @foreach ($this->salespeople as $person)
                        <option value="{{ $person->id }}">{{ $person->name }}</option>
                    @endforeach
                </select>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.button type="submit" variant="ghost" data-testid="logout">Logout</x-ui.button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex gap-4 overflow-x-auto pb-4 lg:overflow-visible" data-testid="board">
        @foreach ($this->stages as $stage)
            @php
                $stageDeals = $this->dealsByStage[$stage->id] ?? collect();
                $totalValue = collect($stageDeals)->sum(fn ($d) => (float) $d->value);
            @endphp
            <section
                class="min-w-[280px] flex-1 flex flex-col rounded-lg bg-surface border border-outline"
                data-testid="column"
                data-stage-slug="{{ $stage->slug }}"
                data-stage-id="{{ $stage->id }}"
            >
                <header class="px-4 py-3 border-b border-outline sticky top-0 bg-surface z-10">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-ink">{{ $stage->name }}</h2>
                        <span class="text-xs text-ink-muted" data-testid="column-count">{{ count($stageDeals) }}</span>
                    </div>
                    <p class="text-xs text-ink-muted mt-1" data-testid="column-total">${{ number_format($totalValue, 2) }}</p>
                </header>

                <ul
                    wire:sort="updateStage($item, '{{ $stage->slug }}')"
                    class="flex-1 p-3 space-y-3 min-h-[120px]"
                    data-testid="column-list"
                >
                    @foreach ($stageDeals as $deal)
                        <li
                            wire:key="deal-{{ $deal->id }}"
                            wire:sort.item="{{ $deal->id }}"
                            class="rounded-md border border-outline bg-surface-soft p-3 shadow-sm cursor-grab"
                            data-testid="deal-card"
                            data-deal-id="{{ $deal->id }}"
                        >
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-ink truncate">{{ $deal->title }}</h3>
                                <span class="text-xs text-ink-muted">${{ number_format((float) $deal->value, 2) }}</span>
                            </div>
                            <p class="text-xs text-ink-muted mt-1" data-testid="deal-lead">{{ $deal->lead?->name }}</p>
                            <p class="text-[10px] text-ink-muted mt-1">Updated {{ $deal->updated_at?->diffForHumans() }}</p>
                            @if (auth()->user()?->isBusinessOwner())
                                <div class="mt-2">
                                    <x-ui.badge data-testid="deal-owner-badge">{{ $deal->owner?->name }}</x-ui.badge>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    @if ($pendingLostDealId !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 px-4" data-testid="loss-reason-modal">
            <div class="w-full max-w-md rounded-lg bg-surface p-6 shadow-lg">
                <h3 class="text-base font-semibold text-ink">Mark deal as lost</h3>
                <p class="text-sm text-ink-muted mt-1">Tell us why this deal was lost.</p>
                <form wire:submit="confirmLoss" class="mt-4 space-y-4">
                    <x-ui.textarea wire:model="lossReason" name="lossReason" label="Reason" :error="$errors->first('lossReason')" />
                    <div class="flex justify-end gap-2">
                        <x-ui.button type="button" variant="ghost" wire:click="cancelLoss" data-testid="loss-cancel">Cancel</x-ui.button>
                        <x-ui.button type="submit" variant="danger" data-testid="loss-confirm">Mark lost</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
