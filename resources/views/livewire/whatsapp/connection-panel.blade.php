<div data-testid="whatsapp-panel">
    <x-ui.card title="WhatsApp" subtitle="Conecte seu número via código QR.">
        @php $connection = $this->connection; @endphp

        @if ($connection === null)
            <p class="text-sm text-ink-muted">Não conectado.</p>
            <div class="mt-3">
                <x-ui.button type="button" wire:click="connect" data-testid="whatsapp-connect">Conectar</x-ui.button>
            </div>
        @else
            <dl class="grid gap-2 text-sm">
                <div><dt class="text-ink-muted">Status</dt><dd data-testid="whatsapp-status">{{ $connection->status?->name ?? $connection->status?->slug }}</dd></div>
                <div><dt class="text-ink-muted">Instância</dt><dd>{{ $connection->instance_name }}</dd></div>
                @if ($connection->phone_number)
                    <div><dt class="text-ink-muted">Celular</dt><dd>{{ $connection->phone_number }}</dd></div>
                @endif
            </dl>

            @if ($connection->status?->slug === \App\Models\WhatsappConnectionStatus::PENDING && $connection->qr_code_path)
                <div class="mt-4" data-testid="whatsapp-qr">
                    <img src="{{ $connection->qr_code_path }}" alt="QR code" class="max-w-xs border border-outline rounded-md" />
                    <div class="mt-2">
                        <x-ui.button type="button" variant="outline" wire:click="refreshQr" data-testid="whatsapp-refresh-qr">Atualizar QR</x-ui.button>
                    </div>
                </div>
            @endif

            @if ($connection->status?->slug === \App\Models\WhatsappConnectionStatus::DISCONNECTED)
                <div class="mt-4 rounded-md bg-warning/10 text-warning px-3 py-2 text-xs" data-testid="whatsapp-disconnected-banner">
                    WhatsApp desconectado. Reconecte para enviar mensagens.
                </div>
                <div class="mt-3">
                    <x-ui.button type="button" wire:click="connect" data-testid="whatsapp-reconnect">Reconectar</x-ui.button>
                </div>
            @else
                <div class="mt-4">
                    <x-ui.button type="button" variant="danger" wire:click="disconnect" data-testid="whatsapp-disconnect">Desconectar</x-ui.button>
                </div>
            @endif
        @endif
    </x-ui.card>
</div>
