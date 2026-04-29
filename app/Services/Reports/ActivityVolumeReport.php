<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Activity;
use App\Models\ActivityType;
use Illuminate\Support\Carbon;

final class ActivityVolumeReport
{
    public const TRACKED_TYPES = [
        ActivityType::MESSAGE_SENT,
        ActivityType::MESSAGE_RECEIVED,
        ActivityType::NOTE_ADDED,
        ActivityType::STAGE_CHANGED,
    ];

    /**
     * @param  array{from?: ?string, to?: ?string, company_id: int}  $filters
     * @return array<string, int>
     */
    public function totalsByType(array $filters): array
    {
        $rows = $this->baseQuery($filters)
            ->join('activity_types', 'activities.activity_type_id', '=', 'activity_types.id')
            ->whereIn('activity_types.slug', self::TRACKED_TYPES)
            ->selectRaw('activity_types.slug, COUNT(*) as total')
            ->groupBy('activity_types.slug')
            ->pluck('total', 'slug')
            ->all();

        $totals = [];

        foreach (self::TRACKED_TYPES as $slug) {
            $totals[$slug] = (int) ($rows[$slug] ?? 0);
        }

        return $totals;
    }

    /**
     * @param  array{from?: ?string, to?: ?string, company_id: int}  $filters
     * @return list<array{user_id:int, total:int, by_type:array<string,int>}>
     */
    public function bySalesperson(array $filters): array
    {
        $rows = $this->baseQuery($filters)
            ->join('activity_types', 'activities.activity_type_id', '=', 'activity_types.id')
            ->whereIn('activity_types.slug', self::TRACKED_TYPES)
            ->whereNotNull('activities.user_id')
            ->selectRaw('activities.user_id, activity_types.slug, COUNT(*) as total')
            ->groupBy('activities.user_id', 'activity_types.slug')
            ->get();

        $bucket = [];

        foreach ($rows as $row) {
            $userId = (int) $row->user_id;
            $bucket[$userId]['user_id'] = $userId;
            $bucket[$userId]['total'] = ($bucket[$userId]['total'] ?? 0) + (int) $row->total;
            $bucket[$userId]['by_type'][$row->slug] = (int) $row->total;
        }

        return array_values($bucket);
    }

    /**
     * @param  array{from?: ?string, to?: ?string, company_id: int}  $filters
     * @return array<string, int>  YYYY-MM-DD => count
     */
    public function timeSeries(array $filters): array
    {
        $rows = $this->baseQuery($filters)
            ->selectRaw('DATE(activities.created_at) as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('total', 'bucket')
            ->all();

        return array_map(static fn ($v) => (int) $v, $rows);
    }

    /**
     * @param  array{from?: ?string, to?: ?string, company_id: int}  $filters
     */
    private function baseQuery(array $filters)
    {
        $query = Activity::query()->withoutGlobalScopes()
            ->where('activities.company_id', $filters['company_id']);

        if (! empty($filters['from'])) {
            $query->where('activities.created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if (! empty($filters['to'])) {
            $query->where('activities.created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        return $query;
    }
}
