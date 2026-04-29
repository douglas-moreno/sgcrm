<?php

declare(strict_types=1);

namespace App\Services\Leads;

use App\Models\ActivityType;
use App\Models\Lead;
use App\Models\User;
use App\Services\Activity\ActivityRecorder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ReassignLeadService
{
    public function __construct(private ActivityRecorder $recorder) {}

    public function reassign(Lead $lead, User $newOwner, ?User $actor = null): Lead
    {
        if ($newOwner->company_id !== $lead->company_id) {
            throw new InvalidArgumentException('New owner must belong to the same company.');
        }

        $previousOwnerId = $lead->owner_user_id;

        if ($previousOwnerId === $newOwner->id) {
            return $lead;
        }

        DB::transaction(function () use ($lead, $newOwner, $previousOwnerId, $actor): void {
            $lead->update(['owner_user_id' => $newOwner->id]);

            $deals = $lead->deals()->get();

            foreach ($deals as $deal) {
                $dealPrevious = $deal->owner_user_id;
                $deal->update(['owner_user_id' => $newOwner->id]);

                $this->recorder->record(ActivityType::OWNERSHIP_CHANGED, [
                    'company_id' => $deal->company_id,
                    'user_id' => $actor?->id,
                    'lead' => $lead,
                    'deal' => $deal,
                    'before' => (string) $dealPrevious,
                    'after' => (string) $newOwner->id,
                    'metadata' => [
                        'previous_owner_id' => $dealPrevious,
                        'new_owner_id' => $newOwner->id,
                    ],
                ]);
            }

            $this->recorder->record(ActivityType::LEAD_REASSIGNED, [
                'company_id' => $lead->company_id,
                'user_id' => $actor?->id,
                'lead' => $lead,
                'before' => (string) $previousOwnerId,
                'after' => (string) $newOwner->id,
                'metadata' => [
                    'previous_owner_id' => $previousOwnerId,
                    'new_owner_id' => $newOwner->id,
                ],
            ]);
        });

        return $lead->fresh();
    }
}
