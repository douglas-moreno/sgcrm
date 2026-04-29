<div data-testid="report-activity-volume" class="space-y-4">
    <x-ui.page-header title="Activity volume" subtitle="Messages, notes, stage moves over time." />

    <div class="grid gap-3 sm:grid-cols-2">
        <x-ui.input wire:model.live="from" name="from" type="date" label="From" />
        <x-ui.input wire:model.live="to" name="to" type="date" label="To" />
    </div>

    <x-ui.card title="Totals">
        <ul class="grid gap-2 sm:grid-cols-2 text-sm" data-testid="totals">
            @foreach ($this->totals as $slug => $count)
                <li data-type="{{ $slug }}"><span class="text-ink-muted">{{ $slug }}:</span> {{ $count }}</li>
            @endforeach
        </ul>
    </x-ui.card>

    <x-ui.card title="By Salesperson">
        <table class="min-w-full text-sm" data-testid="by-salesperson-table">
            <thead>
                <tr class="text-left text-ink-muted">
                    <th class="py-2">Salesperson</th>
                    <th class="py-2">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->bySalesperson as $row)
                    <tr class="border-t border-outline" data-user-id="{{ $row['user_id'] }}">
                        <td class="py-2">{{ $row['name'] }}</td>
                        <td class="py-2">{{ $row['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-ui.card>

    <x-ui.card title="Time series">
        <ul class="text-xs space-y-1" data-testid="time-series">
            @foreach ($this->timeSeries as $bucket => $count)
                <li><span class="text-ink-muted">{{ $bucket }}:</span> {{ $count }}</li>
            @endforeach
        </ul>
    </x-ui.card>
</div>
