<div
    data-testid="conversation"
    class="flex flex-col h-[calc(100vh-7rem)]"
    x-data="{
        scrollBottom() { const el = this.$refs.messages; if (el) el.scrollTop = el.scrollHeight; },
    }"
    x-init="scrollBottom()"
    x-on:message-sent.window="$nextTick(() => scrollBottom())"
>
    <x-ui.page-header :title="'Chat with '.$lead->name" :subtitle="$lead->phone ?? 'No phone'" />

    @if (! $this->isConnected)
        <div class="rounded-md bg-warning/10 text-warning px-3 py-2 text-sm" data-testid="conversation-disconnected-banner">
            WhatsApp disconnected. Reconnect from Settings to send messages.
        </div>
    @endif

    <ul x-ref="messages" class="flex-1 overflow-y-auto space-y-2 mt-4" data-testid="messages">
        @forelse ($this->messageList as $message)
            <li class="max-w-md {{ $message->direction?->slug === \App\Models\MessageDirection::OUTBOUND ? 'ml-auto bg-primary-50' : 'bg-surface-soft' }} rounded-md border border-outline p-3" data-testid="message" data-message-id="{{ $message->id }}" data-direction="{{ $message->direction?->slug }}">
                <p class="text-sm text-ink whitespace-pre-line">{{ $message->body }}</p>
                <p class="text-[10px] text-ink-muted mt-1">{{ $message->status?->name }} · {{ $message->created_at?->diffForHumans() }}</p>
            </li>
        @empty
            <li class="text-xs text-ink-muted">No messages yet.</li>
        @endforelse
    </ul>

    <form wire:submit="send" class="mt-4 flex gap-2 items-end sticky bottom-0 sm:static bg-surface pt-2 sm:pt-0 border-t sm:border-t-0 border-outline" data-testid="send-form">
        <div class="flex-1">
            <x-ui.textarea wire:model="body" name="body" label="Message" :error="$errors->first('body')" />
        </div>
        <x-ui.button type="submit" data-testid="send-button" :disabled="! $this->isConnected">Send</x-ui.button>
    </form>
</div>
