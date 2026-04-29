<div data-testid="report-pipeline-overview" class="space-y-4">
    <x-ui.page-header title="Pipeline overview" subtitle="Per-stage counts and total value." />

    <div class="grid gap-3 sm:grid-cols-3">
        <x-ui.input wire:model.live="from" name="from" type="date" label="From" />
        <x-ui.input wire:model.live="to" name="to" type="date" label="To" />
        <x-ui.select wire:model.live="salespersonId" name="salespersonId" label="Salesperson" placeholder="All" :options="$this->salespeople->pluck('name', 'id')->all()" />
    </div>

    <x-ui.card>
        <table class="min-w-full text-sm" data-testid="pipeline-table">
            <thead>
                <tr class="text-left text-ink-muted">
                    <th class="py-2">Stage</th>
                    <th class="py-2">Count</th>
                    <th class="py-2">Total value</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->rows as $row)
                    <tr class="border-t border-outline" data-stage-slug="{{ $row['stage_slug'] }}">
                        <td class="py-2">{{ $row['stage_name'] }}</td>
                        <td class="py-2" data-testid="count">{{ $row['count'] }}</td>
                        <td class="py-2" data-testid="total">${{ number_format($row['total_value'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-ui.card>
</div>
