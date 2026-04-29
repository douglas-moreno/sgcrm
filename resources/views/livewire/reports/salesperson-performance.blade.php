<div data-testid="report-salesperson-performance" class="space-y-4">
    <x-ui.page-header title="Salesperson performance" subtitle="Won, lost, conversion, value." />

    <div class="grid gap-3 sm:grid-cols-2">
        <x-ui.input wire:model.live="from" name="from" type="date" label="From" />
        <x-ui.input wire:model.live="to" name="to" type="date" label="To" />
    </div>

    <x-ui.card>
        <table class="min-w-full text-sm" data-testid="performance-table">
            <thead>
                <tr class="text-left text-ink-muted">
                    @foreach ([
                        'name' => 'Salesperson',
                        'won' => 'Won',
                        'lost' => 'Lost',
                        'conversion' => 'Conversion %',
                        'total_won_value' => 'Total won $',
                        'avg_deal_size' => 'Avg deal $',
                    ] as $col => $label)
                        <th class="py-2">
                            <button type="button" wire:click="sortBy('{{ $col }}')" data-testid="sort-{{ $col }}">
                                {{ $label }}
                                @if ($sort === $col)<span>{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
                            </button>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($this->rows as $row)
                    <tr class="border-t border-outline" data-user-id="{{ $row['user_id'] }}">
                        <td class="py-2">{{ $row['name'] }}</td>
                        <td class="py-2">{{ $row['won'] }}</td>
                        <td class="py-2">{{ $row['lost'] }}</td>
                        <td class="py-2">{{ $row['conversion'] }}%</td>
                        <td class="py-2">${{ number_format($row['total_won_value'], 2) }}</td>
                        <td class="py-2">${{ number_format($row['avg_deal_size'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-ui.card>
</div>
