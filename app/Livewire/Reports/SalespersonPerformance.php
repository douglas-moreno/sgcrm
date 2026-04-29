<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Services\Reports\SalespersonPerformanceReport;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class SalespersonPerformance extends Component
{
    public ?string $from = null;

    public ?string $to = null;

    public string $sort = 'name';

    public string $direction = 'asc';

    public function sortBy(string $column): void
    {
        if (! in_array($column, SalespersonPerformanceReport::SORTABLE, true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sort = $column;
        $this->direction = 'asc';
    }

    #[Computed]
    public function rows(): array
    {
        return app(SalespersonPerformanceReport::class)->rows([
            'company_id' => auth()->user()->company_id,
            'from' => $this->from,
            'to' => $this->to,
            'sort' => $this->sort,
            'direction' => $this->direction,
        ]);
    }

    public function render(): View
    {
        return view('livewire.reports.salesperson-performance');
    }
}
