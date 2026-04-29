<div data-testid="notes-panel">
    <form wire:submit="add" class="space-y-3" data-testid="add-note-form">
        <x-ui.textarea wire:model="body" name="body" label="Add note" :error="$errors->first('body')" />
        <div class="flex justify-end">
            <x-ui.button type="submit" data-testid="save-note">Add</x-ui.button>
        </div>
    </form>

    <ul class="mt-4 space-y-3" data-testid="notes-list">
        @forelse ($this->notes as $note)
            <li class="rounded-md border border-outline bg-surface-soft p-3" data-testid="note-row">
                <div class="flex items-center justify-between text-xs text-ink-muted">
                    <span>{{ $note->user?->name ?? 'Unknown' }}</span>
                    <span>{{ $note->created_at->diffForHumans() }}</span>
                </div>
                <p class="mt-2 text-sm text-ink whitespace-pre-line">{{ $note->body }}</p>
            </li>
        @empty
            <li class="text-xs text-ink-muted">No notes yet.</li>
        @endforelse
    </ul>
</div>
