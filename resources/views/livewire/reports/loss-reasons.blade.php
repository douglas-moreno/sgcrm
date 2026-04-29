<div data-testid="report-loss-reasons" class="space-y-4">
    <x-ui.page-header title="Loss reasons" subtitle="Frequency-ranked, drill down for deals." />

    <div class="grid gap-3 sm:grid-cols-3">
        <x-ui.input wire:model.live="from" name="from" type="date" label="From" />
        <x-ui.input wire:model.live="to" name="to" type="date" label="To" />
        <x-ui.select wire:model.live="salespersonId" name="salespersonId" label="Salesperson" placeholder="All" :options="$this->salespeople->pluck('name', 'id')->all()" />
    </div>

    <x-ui.card>
        <ul class="divide-y divide-outline" data-testid="loss-buckets">
            @forelse ($this->buckets as $bucket)
                <li class="py-3">
                    <button type="button" class="w-full text-left flex items-center justify-between" wire:click="expand('{{ $bucket['reason'] }}')" data-testid="bucket" data-reason="{{ $bucket['reason'] }}">
                        <span class="text-sm">{{ $bucket['reason'] }}</span>
                        <span class="text-xs text-ink-muted">{{ $bucket['count'] }}</span>
                    </button>

                    @if ($expandedReason === $bucket['reason'])
                        <ul class="mt-2 space-y-2 ml-4" data-testid="loss-deals">
                            @foreach ($this->expandedDeals as $deal)
                                <li class="text-xs text-ink-muted" data-deal-id="{{ $deal->id }}">
                                    {{ $deal->title }} · ${{ number_format((float) $deal->value, 2) }} · {{ $deal->owner?->name }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @empty
                <li class="py-3 text-xs text-ink-muted">No lost deals.</li>
            @endforelse
        </ul>
    </x-ui.card>
</div>
