<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Deal;
use App\Models\Lead;
use Illuminate\Support\Facades\Auth;

final class ActivityRecorder
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(string $typeSlug, array $payload = []): ?Activity
    {
        $type = ActivityType::query()->where('slug', $typeSlug)->first();

        if ($type === null) {
            return null;
        }

        $companyId = $payload['company_id'] ?? null;

        if ($companyId === null) {
            $lead = $payload['lead'] ?? null;
            $deal = $payload['deal'] ?? null;
            $companyId = $deal?->company_id ?? $lead?->company_id ?? Auth::user()?->company_id;
        }

        if ($companyId === null) {
            return null;
        }

        return Activity::create([
            'company_id' => $companyId,
            'activity_type_id' => $type->id,
            'user_id' => $payload['user_id'] ?? Auth::id(),
            'lead_id' => $this->resolveId($payload['lead'] ?? null, $payload['lead_id'] ?? null, Lead::class),
            'deal_id' => $this->resolveId($payload['deal'] ?? null, $payload['deal_id'] ?? null, Deal::class),
            'before_value' => $payload['before'] ?? null,
            'after_value' => $payload['after'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
            'created_at' => now(),
        ]);
    }

    private function resolveId(mixed $model, mixed $id, string $expected): ?int
    {
        if ($model instanceof $expected) {
            return $model->id;
        }

        if (is_int($id)) {
            return $id;
        }

        return null;
    }
}
