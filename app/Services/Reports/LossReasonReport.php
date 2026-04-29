<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Deal;
use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

final class LossReasonReport
{
    /**
     * @param  array{from?: ?string, to?: ?string, salesperson_id?: ?int, company_id: int}  $filters
     * @return list<array{reason: string, count: int}>
     */
    public function buckets(array $filters): array
    {
        $query = $this->baseQuery($filters);

        $rows = $query->get(['loss_reason']);

        $buckets = [];

        foreach ($rows as $row) {
            $key = mb_strtolower(mb_trim((string) $row->loss_reason));

            if ($key === '') {
                continue;
            }

            $buckets[$key] = ($buckets[$key] ?? 0) + 1;
        }

        arsort($buckets);

        return array_map(
            static fn (string $reason, int $count): array => ['reason' => $reason, 'count' => $count],
            array_keys($buckets),
            array_values($buckets),
        );
    }

    /**
     * @param  array{from?: ?string, to?: ?string, salesperson_id?: ?int, company_id: int}  $filters
     */
    public function deals(array $filters, string $reason): Collection
    {
        $query = $this->baseQuery($filters);

        return $query
            ->whereRaw('LOWER(TRIM(loss_reason)) = ?', [mb_strtolower(mb_trim($reason))])
            ->with(['owner', 'lead'])
            ->orderByDesc('lost_at')
            ->get();
    }

    /**
     * @param  array{from?: ?string, to?: ?string, salesperson_id?: ?int, company_id: int}  $filters
     */
    private function baseQuery(array $filters)
    {
        $lostStageId = PipelineStage::query()->where('slug', PipelineStage::LOST)->value('id');

        $query = Deal::query()->withoutGlobalScopes()
            ->where('company_id', $filters['company_id'])
            ->where('stage_id', $lostStageId)
            ->whereNotNull('loss_reason');

        if (! empty($filters['salesperson_id'])) {
            $query->where('owner_user_id', $filters['salesperson_id']);
        }

        if (! empty($filters['from'])) {
            $query->where('lost_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if (! empty($filters['to'])) {
            $query->where('lost_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        return $query;
    }
}
