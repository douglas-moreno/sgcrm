<div
    data-testid="conversation"
    class="flex flex-col {{ $embedded ? 'min-h-128' : 'h-[calc(100vh-7rem)]' }}"
    x-data="{
        scrollBottom() { const el = this.$refs.messages; if (el) el.scrollTop = el.scrollHeight; },
    }"
    x-init="scrollBottom()"
    x-on:message-sent.window="$nextTick(() => scrollBottom())"
>
    @unless ($embedded)
        <x-ui.page-header :title="'Chat com '.$lead->name" :subtitle="$lead->phone ?? 'Sem número de telefone'" />
    @else
        <div class="mb-3">
            <h3 class="text-sm font-semibold text-ink">Conversa com WhatsApp</h3>
            <p class="text-xs text-ink-muted">{{ $lead->name }} · {{ $lead->phone ?? 'Sem número de telefone' }}</p>
        </div>
    @endunless

    @if (! $this->isConnected)
        <div class="rounded-md bg-warning/10 text-warning px-3 py-2 text-sm" data-testid="conversation-disconnected-banner">
            WhatsApp desconectado. Reconecte a partir das Configurações para enviar mensagens.
        </div>
    @endif

    <ul x-ref="messages" class="mt-4 flex-1 space-y-2 overflow-y-auto" data-testid="messages">
        @forelse ($this->messageList as $message)
            <li class="max-w-md {{ $message->direction?->slug === \App\Models\MessageDirection::OUTBOUND ? 'ml-auto bg-primary-50' : 'bg-surface-soft' }} rounded-md border border-outline p-3" data-testid="message" data-message-id="{{ $message->id }}" data-direction="{{ $message->direction?->slug }}">
                <p class="text-sm text-ink whitespace-pre-line">{{ $message->body }}</p>
                <p class="text-[10px] text-ink-muted mt-1">{{ $message->status?->name }} · {{ $message->created_at?->diffForHumans() }}</p>
            </li>
        @empty
            <li class="text-xs text-ink-muted">Sem mensagens ainda.</li>
        @endforelse
    </ul>

    <form wire:submit="send" class="mt-4 flex items-end gap-2 border-outline bg-surface pt-2 {{ $embedded ? 'border-t' : 'sticky bottom-0 sm:static border-t sm:border-t-0' }}" data-testid="send-form">
        <div class="flex-1">
            <x-ui.textarea wire:model="body" name="body" label="Message" :error="$errors->first('body')" />
        </div>
        <x-ui.button type="submit" data-testid="send-button" :disabled="! $this->isConnected">Enviar</x-ui.button>
    </form>
</div>
