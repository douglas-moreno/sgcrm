<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Models\Role;
use App\Models\User;
use App\Services\Reports\LossReasonReport;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class LossReasons extends Component
{
    public ?string $from = null;

    public ?string $to = null;

    public ?int $salespersonId = null;

    public ?string $expandedReason = null;

    public function expand(string $reason): void
    {
        $this->expandedReason = $this->expandedReason === $reason ? null : $reason;
    }

    #[Computed]
    public function buckets(): array
    {
        return app(LossReasonReport::class)->buckets($this->filters());
    }

    #[Computed]
    public function expandedDeals()
    {
        if ($this->expandedReason === null) {
            return collect();
        }

        return app(LossReasonReport::class)->deals($this->filters(), $this->expandedReason);
    }

    #[Computed]
    public function salespeople()
    {
        return User::query()
            ->where('company_id', auth()->user()->company_id)
            ->whereHas('role', fn ($q) => $q->where('slug', Role::SALESPERSON))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        return [
            'company_id' => auth()->user()->company_id,
            'from' => $this->from,
            'to' => $this->to,
            'salesperson_id' => $this->salespersonId,
        ];
    }

    public function render(): View
    {
        return view('livewire.reports.loss-reasons');
    }
}
