<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Models\Role;
use App\Models\User;
use App\Services\Reports\PipelineOverviewReport;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class PipelineOverview extends Component
{
    public ?string $from = null;

    public ?string $to = null;

    public ?int $salespersonId = null;

    #[Computed]
    public function rows(): array
    {
        return app(PipelineOverviewReport::class)->rows([
            'company_id' => auth()->user()->company_id,
            'from' => $this->from,
            'to' => $this->to,
            'salesperson_id' => $this->salespersonId,
        ]);
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

    public function render(): View
    {
        return view('livewire.reports.pipeline-overview');
    }
}
