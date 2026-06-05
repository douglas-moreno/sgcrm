<div data-testid="kanban-root">
    <x-ui.page-header title="Pipeline" subtitle="Mova negociações através das etapas.">
        <x-slot:actions>
            <livewire:leads.create-lead />
        
            @if ($this->canFilter())
                <select
                    wire:model.live="ownerFilter"
                    class="rounded-md h-9 bg-surface px-3 text-sm"
                    data-testid="owner-filter"
                >
                <option value="">Todos os vendedores</option>
                    @foreach ($this->salespeople as $person)
                        <option value="{{ $person->id }}"> {{ $person->name }} </option>
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
                class="min-w-70 flex-1 flex flex-col rounded-lg bg-surface border border-outline"
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
                    wire:sort="updateStage"
                    wire:sort:group="pipeline-stages"
                    wire:sort:group-id="{{ $stage->slug }}"
                    class="flex-1 p-3 space-y-3 min-h-30"
                    data-testid="column-list"
                >
                    @foreach ($stageDeals as $deal)
                        <li
                            wire:key="deal-{{ $deal->id }}"
                            wire:sort:item="{{ $deal->id }}"
                            class="rounded-md border border-outline bg-surface-soft p-3 shadow-sm min-h-11 touch-manipulation"
                            data-testid="deal-card"
                            data-deal-id="{{ $deal->id }}"
                        >
                            <button
                                type="button"
                                wire:click="openDeal({{ $deal->id }})"
                                class="block w-full text-left"
                                data-testid="open-deal-drawer"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <h3
                                        wire:sort:handle
                                        class="cursor-grab truncate text-sm font-medium text-ink active:cursor-grabbing"
                                        data-testid="deal-drag-handle"
                                    >
                                        {{ $deal->title }}
                                    </h3>
                                    <span class="text-xs text-ink-muted">${{ number_format((float) $deal->value, 2) }}</span>
                                </div>
                                <p class="text-xs text-ink-muted mt-1" data-testid="deal-lead">{{ $deal->lead?->name }}</p>
                                <p class="text-[10px] text-ink-muted mt-1">Atualizado {{ $deal->updated_at?->diffForHumans() }}</p>
                                @if (auth()->user()?->isBusinessOwner())
                                    <div class="mt-2">
                                        <x-ui.badge data-testid="deal-owner-badge">{{ $deal->owner?->name }}</x-ui.badge>
                                    </div>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    @if ($this->selectedDeal)
        <div class="fixed inset-0 z-40 flex justify-end bg-ink/35" data-testid="deal-drawer-overlay">
            <button type="button" wire:click="closeDeal" class="flex-1 cursor-default" aria-label="Close deal drawer"></button>

            <aside
                class="relative h-full w-full max-w-2xl overflow-y-auto border-l border-outline bg-surface shadow-2xl"
                data-testid="deal-drawer"
                role="dialog"
                aria-modal="true"
                aria-label="Deal details"
            >
                <div class="sticky top-0 z-10 border-b border-outline bg-surface/95 backdrop-blur">
                    <div class="flex items-start justify-between gap-4 px-5 py-4">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-ink-muted">Deal details</p>
                            <h2 class="mt-1 truncate text-xl font-semibold text-ink" data-testid="drawer-deal-title">{{ $this->selectedDeal->title }}</h2>
                            <p class="mt-1 text-sm text-ink-muted">
                                {{ $this->selectedDeal->stage?->name }} · {{ $this->selectedDeal->lead?->name }}
                            </p>
                        </div>

                        <button
                            type="button"
                            wire:click="closeDeal"
                            class="rounded-full border border-outline p-2 text-ink-muted transition hover:text-ink"
                            data-testid="close-deal-drawer"
                            aria-label="Close deal drawer"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.7 4.7a1 1 0 0 1 1.4 0L10 8.6l3.9-3.9a1 1 0 1 1 1.4 1.4L11.4 10l3.9 3.9a1 1 0 0 1-1.4 1.4L10 11.4l-3.9 3.9a1 1 0 1 1-1.4-1.4L8.6 10 4.7 6.1a1 1 0 0 1 0-1.4z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>

                    <div class="flex gap-2 px-5 pb-4" data-testid="deal-drawer-tabs">
                        @foreach (['overview' => 'Visão Geral', 'notes' => 'Notas', 'whatsapp' => 'WhatsApp'] as $tab => $label)
                            <button
                                type="button"
                                wire:click="setDrawerTab('{{ $tab }}')"
                                class="rounded-full border px-3 py-1.5 text-sm transition {{ $activeDrawerTab === $tab ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-outline text-ink-muted hover:text-ink' }}"
                                data-testid="deal-drawer-tab-{{ $tab }}"
                                aria-pressed="{{ $activeDrawerTab === $tab ? 'true' : 'false' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach

                        <a
                            href="{{ route('deals.show', $this->selectedDeal) }}"
                            class="ml-auto text-sm font-medium text-primary-600 hover:text-primary-700"
                            data-testid="deal-drawer-full-page-link"
                        >
                            Abrir página completa
                        </a>
                    </div>
                </div>

                <div class="space-y-5 px-5 py-5">
                    @if ($activeDrawerTab === 'overview')
                        <section class="grid gap-4 md:grid-cols-2" data-testid="deal-drawer-overview">
                            <div class="rounded-xl border border-outline bg-surface-soft p-4">
                                <h3 class="text-sm font-semibold text-ink">Deal</h3>
                                <dl class="mt-3 space-y-3 text-sm">
                                    <div>
                                        <dt class="text-ink-muted">Título</dt>
                                        <dd class="font-medium text-ink">{{ $this->selectedDeal->title }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-ink-muted">Etapa</dt>
                                        <dd class="text-ink">{{ $this->selectedDeal->stage?->name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-ink-muted">Value</dt>
                                        <dd class="text-ink">${{ number_format((float) $this->selectedDeal->value, 2) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-ink-muted">Last updated</dt>
                                        <dd class="text-ink">{{ $this->selectedDeal->updated_at?->diffForHumans() }}</dd>
                                    </div>
                                    @if ($this->selectedDeal->loss_reason)
                                        <div>
                                            <dt class="text-ink-muted">Loss reason</dt>
                                            <dd class="whitespace-pre-line text-ink">{{ $this->selectedDeal->loss_reason }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>

                            <div class="rounded-xl border border-outline bg-surface-soft p-4">
                                <h3 class="text-sm font-semibold text-ink">Lead</h3>
                                <dl class="mt-3 space-y-3 text-sm">
                                    <div>
                                        <dt class="text-ink-muted">Name</dt>
                                        <dd class="text-ink">{{ $this->selectedDeal->lead?->name ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-ink-muted">Email</dt>
                                        <dd class="text-ink">{{ $this->selectedDeal->lead?->email ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-ink-muted">Phone</dt>
                                        <dd class="text-ink">{{ $this->selectedDeal->lead?->phone ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-ink-muted">Owner</dt>
                                        <dd class="text-ink">{{ $this->selectedDeal->owner?->name ?? '—' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </section>

                        <section class="rounded-xl border border-outline bg-surface-soft p-4" data-testid="deal-drawer-activity">
                            <h3 class="text-sm font-semibold text-ink">Activity</h3>
                            <div class="mt-4">
                                <livewire:deals.timeline :deal="$this->selectedDeal" :wire:key="'drawer-timeline-'.$this->selectedDeal->id" />
                            </div>
                        </section>
                    @endif

                    @if ($activeDrawerTab === 'notes')
                        <section class="rounded-xl border border-outline bg-surface-soft p-4" data-testid="deal-drawer-notes">
                            <livewire:deals.notes-panel :deal="$this->selectedDeal" :wire:key="'drawer-notes-'.$this->selectedDeal->id" />
                        </section>
                    @endif

                    @if ($activeDrawerTab === 'whatsapp')
                        <section class="rounded-xl border border-outline bg-surface-soft p-4" data-testid="deal-drawer-whatsapp">
                            <livewire:whatsapp.conversation :lead="$this->selectedDeal->lead" :deal="$this->selectedDeal->id" :embedded="true" :wire:key="'drawer-whatsapp-'.$this->selectedDeal->id" />
                        </section>
                    @endif
                </div>
            </aside>
        </div>
    @endif

    @if ($pendingLostDealId !== null)
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-ink/40 sm:px-4" data-testid="loss-reason-modal">
            <div class="w-full sm:max-w-md h-full sm:h-auto bg-surface p-6 shadow-lg sm:rounded-lg flex flex-col">
                <h3 class="text-base font-semibold text-ink">Marcar negociação como perdida</h3>
                <p class="text-sm text-ink-muted mt-1">Nos diga o motivo pelo qual esta negociação foi perdida.</p>
                <form wire:submit="confirmLoss" class="mt-4 space-y-4 flex-1 flex flex-col">
                    <x-ui.textarea wire:model="lossReason" name="lossReason" label="Motivo" autofocus :error="$errors->first('lossReason')" />
                    <div class="mt-auto flex justify-end gap-2">
                        <x-ui.button type="button" variant="ghost" wire:click="cancelLoss" data-testid="loss-cancel">Cancelar</x-ui.button>
                        <x-ui.button type="submit" variant="danger" data-testid="loss-confirm">Marcar como perdida</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
