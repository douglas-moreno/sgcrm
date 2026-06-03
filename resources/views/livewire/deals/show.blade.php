<div data-testid="deal-detail">
    <x-ui.page-header :title="$deal->title" :subtitle="'Etapa: '.$deal->stage?->name">
        <x-slot:actions>
            @if ($deal->stage?->slug !== \App\Models\PipelineStage::WON && $deal->stage?->slug !== \App\Models\PipelineStage::LOST)
                <x-ui.button type="button" variant="danger" wire:click="openLostModal" data-testid="open-lost-modal">Marcar como perdida</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="lg:col-span-2 space-y-4">
            <x-ui.card title="Deal" data-testid="deal-header-section">
                @if ($this->canEdit())
                    <form wire:submit="save" class="grid gap-4 sm:grid-cols-2" data-testid="deal-edit-form">
                        <x-ui.input wire:model="title" name="title" label="Título" :error="$errors->first('title')" />
                        <x-ui.input wire:model="value" name="value" type="number" min="0" step="0.01" label="Valor" :error="$errors->first('value')" />
                        <div class="sm:col-span-2 flex justify-end">
                            <x-ui.button type="submit" data-testid="save-deal">Salvar</x-ui.button>
                        </div>
                    </form>
                @else
                    <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                        <div>
                            <dt class="text-ink-muted">Título</dt>
                            <dd class="text-ink font-medium">{{ $deal->title }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-muted">Valor</dt>
                            <dd class="text-ink font-medium">${{ number_format((float) $deal->value, 2) }}</dd>
                        </div>
                    </dl>
                    @if ($deal->won_at)
                        <p class="mt-3 text-xs text-success" data-testid="deal-locked-notice"> Negociação vencida — somente leitura.</p>
                    @endif
                @endif
            </x-ui.card>

            <x-ui.card title="Lead" data-testid="lead-info-section">
                <dl class="grid gap-2 text-sm">
                    <div><dt class="text-ink-muted">Nome</dt><dd>{{ $deal->lead?->name }}</dd></div>
                    <div><dt class="text-ink-muted">Email</dt><dd>{{ $deal->lead?->email }}</dd></div>
                    <div><dt class="text-ink-muted">Celular</dt><dd>{{ $deal->lead?->phone ?? '—' }}</dd></div>
                </dl>
            </x-ui.card>

            <x-ui.card title="Notas" data-testid="notes-section">
                <livewire:deals.notes-panel :deal="$deal" :wire:key="'notes-'.$deal->id" />
            </x-ui.card>

            <x-ui.card title="Atividade" data-testid="activity-section">
                <livewire:deals.timeline :deal="$deal" :wire:key="'timeline-'.$deal->id" />
            </x-ui.card>
        </section>

        <aside class="space-y-4">
            <x-ui.card title="Vendedor" data-testid="owner-section">
                <p class="text-sm">{{ $deal->owner?->name }}</p>
            </x-ui.card>

            <x-ui.card title="Chat" data-testid="chat-shortcut-section">
                @php
                    $whatsappConnection = auth()->user()->whatsappConnection;
                    $isWhatsappConnected = $whatsappConnection && $whatsappConnection->status?->slug === \App\Models\WhatsappConnectionStatus::CONNECTED;
                    $hasPhone = filled($deal->lead?->phone);
                @endphp

                @if ($isWhatsappConnected && $hasPhone)
                    <a href="{{ route('whatsapp.conversation', ['lead' => $deal->lead_id, 'deal' => $deal->id]) }}" class="text-sm text-primary-500" data-testid="chat-link">Abrir chat do WhatsApp</a>
                @elseif (! $isWhatsappConnected)
                    <p class="text-xs text-ink-muted" data-testid="chat-blocked-disconnected">WhatsApp não conectado.</p>
                @else
                    <p class="text-xs text-ink-muted" data-testid="chat-blocked-no-phone">Nenhum telefone no lead.</p>
                @endif
            </x-ui.card>
        </aside>
    </div>

    @if ($showLostModal)
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-ink/40 sm:px-4" data-testid="lost-modal">
            <div class="w-full sm:max-w-md h-full sm:h-auto bg-surface p-6 shadow-lg sm:rounded-lg flex flex-col">
                <h3 class="text-base font-semibold text-ink">Marcar negociação como perdida</h3>
                <form wire:submit="confirmLost" class="mt-4 space-y-4 flex-1 flex flex-col">
                    <x-ui.textarea wire:model="lossReason" name="lossReason" label="Motivo" autofocus :error="$errors->first('lossReason')" />
                    <div class="mt-auto flex justify-end gap-2">
                        <x-ui.button type="button" variant="ghost" wire:click="cancelLost" data-testid="lost-cancel">Cancelar</x-ui.button>
                        <x-ui.button type="submit" variant="danger" data-testid="lost-confirm">Marcar como perdida</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
