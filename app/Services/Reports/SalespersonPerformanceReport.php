<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Deal;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;

final class SalespersonPerformanceReport
{
    public const SORTABLE = ['name', 'won', 'lost', 'conversion', 'total_won_value', 'avg_deal_size'];

    /**
     * @param  array{from?: ?string, to?: ?string, sort?: string, direction?: string, company_id: int}  $filters
     * @return list<array{user_id:int, name:string, won:int, lost:int, conversion:float, total_won_value:float, avg_deal_size:float}>
     */
    public function rows(array $filters): array
    {
        $sort = in_array($filters['sort'] ?? '', self::SORTABLE, true) ? $filters['sort'] : 'name';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $wonStageId = PipelineStage::query()->where('slug', PipelineStage::WON)->value('id');
        $lostStageId = PipelineStage::query()->where('slug', PipelineStage::LOST)->value('id');

        $sellers = User::query()
            ->where('company_id', $filters['company_id'])
            ->whereHas('role', fn ($q) => $q->where('slug', Role::SALESPERSON))
            ->get();

        $base = Deal::query()->withoutGlobalScopes()->where('company_id', $filters['company_id']);

        if (! empty($filters['from'])) {
            $base->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if (! empty($filters['to'])) {
            $base->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        $rows = $sellers->map(function (User $seller) use ($base, $wonStageId, $lostStageId): array {
            $won = (clone $base)->where('owner_user_id', $seller->id)->where('stage_id', $wonStageId)->count();
            $lost = (clone $base)->where('owner_user_id', $seller->id)->where('stage_id', $lostStageId)->count();
            $totalWonValue = (float) (clone $base)->where('owner_user_id', $seller->id)->where('stage_id', $wonStageId)->sum('value');
            $avg = $won > 0 ? round($totalWonValue / $won, 2) : 0.0;
            $conversion = ($won + $lost) > 0 ? round($won / ($won + $lost) * 100, 2) : 0.0;

            return [
                'user_id' => $seller->id,
                'name' => $seller->name,
                'won' => $won,
                'lost' => $lost,
                'conversion' => $conversion,
                'total_won_value' => $totalWonValue,
                'avg_deal_size' => $avg,
            ];
        });

        return $rows
            ->sortBy(fn ($row) => $row[$sort], SORT_REGULAR, $direction === 'desc')
            ->values()
            ->all();
    }
}
