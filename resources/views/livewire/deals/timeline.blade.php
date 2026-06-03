<div data-testid="timeline">
    <ul class="space-y-3" data-testid="timeline-list">
        @forelse ($this->activities as $activity)
            <li class="rounded-md border border-outline bg-surface-soft p-3" data-testid="timeline-row" data-activity-id="{{ $activity->id }}">
                <div class="flex items-center justify-between text-xs text-ink-muted">
                    <span data-testid="activity-type">{{ $activity->type?->name ?? $activity->type?->slug }}</span>
                    <span>{{ $activity->created_at?->diffForHumans() }}</span>
                </div>
                <p class="mt-1 text-sm text-ink">
                    por {{ $activity->user?->name ?? 'system' }}
                    @if ($activity->before_value !== null && $activity->after_value !== null)
                        — <span class="text-ink-muted">{{ $activity->before_value }}</span>
                        → <span class="text-ink">{{ $activity->after_value }}</span>
                    @endif
                </p>
            </li>
        @empty
            <li class="text-xs text-ink-muted">Sem atividade ainda.</li>
        @endforelse
    </ul>

    <div class="mt-4">
        {{ $this->activities->links() }}
    </div>
</div>
