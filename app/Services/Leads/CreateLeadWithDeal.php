<?php

declare(strict_types=1);

namespace App\Services\Leads;

use App\Models\ActivityType;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\Activity\ActivityRecorder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CreateLeadWithDeal
{
    public function __construct(private ActivityRecorder $recorder) {}

    /**
     * @param  array{name: string, email: string, phone?: ?string, notes?: ?string}  $leadData
     */
    public function create(User $actor, User $owner, array $leadData, ?string $dealTitle = null, float $dealValue = 0.0): array
    {
        if ($owner->company_id !== $actor->company_id) {
            throw new InvalidArgumentException('Owner must belong to the same company.');
        }

        return DB::transaction(function () use ($actor, $owner, $leadData, $dealTitle, $dealValue): array {
            $stage = PipelineStage::query()->where('slug', PipelineStage::NEW_LEAD)->firstOrFail();

            $lead = Lead::create([
                'company_id' => $actor->company_id,
                'owner_user_id' => $owner->id,
                'name' => $leadData['name'],
                'email' => $leadData['email'],
                'phone' => $leadData['phone'] ?? null,
                'notes' => $leadData['notes'] ?? null,
            ]);

            $deal = Deal::create([
                'company_id' => $actor->company_id,
                'lead_id' => $lead->id,
                'owner_user_id' => $owner->id,
                'stage_id' => $stage->id,
                'title' => $dealTitle ?? $lead->name,
                'value' => $dealValue,
            ]);

            $this->recorder->record(ActivityType::LEAD_CREATED, [
                'company_id' => $lead->company_id,
                'user_id' => $actor->id,
                'lead' => $lead,
                'metadata' => ['email' => $lead->email],
            ]);

            $this->recorder->record(ActivityType::DEAL_CREATED, [
                'company_id' => $deal->company_id,
                'user_id' => $actor->id,
                'lead' => $lead,
                'deal' => $deal,
            ]);

            return ['lead' => $lead, 'deal' => $deal];
        });
    }

    public function addDealForLead(User $actor, Lead $lead, string $title, float $value = 0.0): Deal
    {
        if ($actor->company_id !== $lead->company_id) {
            throw new InvalidArgumentException('Lead must belong to the same company.');
        }

        return DB::transaction(function () use ($actor, $lead, $title, $value): Deal {
            $stage = PipelineStage::query()->where('slug', PipelineStage::NEW_LEAD)->firstOrFail();

            $deal = Deal::create([
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'owner_user_id' => $lead->owner_user_id,
                'stage_id' => $stage->id,
                'title' => $title,
                'value' => $value,
            ]);

            $this->recorder->record(ActivityType::DEAL_CREATED, [
                'company_id' => $deal->company_id,
                'user_id' => $actor->id,
                'lead' => $lead,
                'deal' => $deal,
            ]);

            return $deal;
        });
    }
}
