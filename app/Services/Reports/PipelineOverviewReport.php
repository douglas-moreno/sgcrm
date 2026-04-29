<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Deal;
use App\Models\PipelineStage;
use Illuminate\Support\Carbon;

final class PipelineOverviewReport
{
    /**
     * @param  array{from?: ?string, to?: ?string, salesperson_id?: ?int, company_id: int}  $filters
     * @return list<array{stage_id: int, stage_slug: string, stage_name: string, count: int, total_value: float}>
     */
    public function rows(array $filters): array
    {
        $query = Deal::query()->withoutGlobalScopes();

        $query->where('company_id', $filters['company_id']);

        if (! empty($filters['salesperson_id'])) {
            $query->where('owner_user_id', $filters['salesperson_id']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        $aggregates = (clone $query)
            ->selectRaw('stage_id, COUNT(*) as deals_count, COALESCE(SUM(value), 0) as total_value')
            ->groupBy('stage_id')
            ->pluck('deals_count', 'stage_id');

        $values = (clone $query)
            ->selectRaw('stage_id, COALESCE(SUM(value), 0) as total_value')
            ->groupBy('stage_id')
            ->pluck('total_value', 'stage_id');

        return PipelineStage::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->map(fn (PipelineStage $stage) => [
                'stage_id' => $stage->id,
                'stage_slug' => $stage->slug,
                'stage_name' => $stage->name,
                'count' => (int) ($aggregates[$stage->id] ?? 0),
                'total_value' => (float) ($values[$stage->id] ?? 0),
            ])
            ->all();
    }
}
