<?php

declare(strict_types=1);

use App\Livewire\Reports\ActivityVolume;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\ActivityTypeSeeder::class);

    $this->company = Company::factory()->create();
    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
});

function makeActivity(int $companyId, int $userId, int $leadId, int $dealId, string $slug, $createdAt): void
{
    $type = ActivityType::where('slug', $slug)->firstOrFail();
    Activity::create([
        'company_id' => $companyId,
        'activity_type_id' => $type->id,
        'user_id' => $userId,
        'lead_id' => $leadId,
        'deal_id' => $dealId,
        'metadata' => [],
        'created_at' => $createdAt,
    ]);
}

it('counts totals by tracked activity type', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::MESSAGE_SENT, now());
    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::MESSAGE_SENT, now());
    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::MESSAGE_RECEIVED, now());
    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::NOTE_ADDED, now());
    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::STAGE_CHANGED, now());
    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::STAGE_CHANGED, now());
    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::STAGE_CHANGED, now());
    // untracked types do not appear in totals
    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::DEAL_CREATED, now());

    $totals = Livewire::actingAs($this->owner)
        ->test(ActivityVolume::class)
        ->instance()
        ->totals;

    expect($totals[ActivityType::MESSAGE_SENT])->toBe(2)
        ->and($totals[ActivityType::MESSAGE_RECEIVED])->toBe(1)
        ->and($totals[ActivityType::NOTE_ADDED])->toBe(1)
        ->and($totals[ActivityType::STAGE_CHANGED])->toBe(3)
        ->and($totals)->not->toHaveKey(ActivityType::DEAL_CREATED);
});

it('breaks down per salesperson', function (): void {
    $alice = User::factory()->salesperson()->forCompany($this->company)->create(['name' => 'Alice']);
    $bob = User::factory()->salesperson()->forCompany($this->company)->create(['name' => 'Bob']);

    $aliceLead = Lead::factory()->forCompany($this->company)->ownedBy($alice)->create();
    $aliceDeal = Deal::factory()->forLead($aliceLead)->create();
    $bobLead = Lead::factory()->forCompany($this->company)->ownedBy($bob)->create();
    $bobDeal = Deal::factory()->forLead($bobLead)->create();

    makeActivity($this->company->id, $alice->id, $aliceLead->id, $aliceDeal->id, ActivityType::MESSAGE_SENT, now());
    makeActivity($this->company->id, $alice->id, $aliceLead->id, $aliceDeal->id, ActivityType::NOTE_ADDED, now());
    makeActivity($this->company->id, $bob->id, $bobLead->id, $bobDeal->id, ActivityType::MESSAGE_SENT, now());
    makeActivity($this->company->id, $bob->id, $bobLead->id, $bobDeal->id, ActivityType::STAGE_CHANGED, now());
    makeActivity($this->company->id, $bob->id, $bobLead->id, $bobDeal->id, ActivityType::MESSAGE_RECEIVED, now());

    $rows = collect(
        Livewire::actingAs($this->owner)
            ->test(ActivityVolume::class)
            ->instance()
            ->bySalesperson
    )->keyBy('user_id');

    expect($rows[$alice->id]['total'])->toBe(2)
        ->and($rows[$alice->id]['by_type'][ActivityType::MESSAGE_SENT])->toBe(1)
        ->and($rows[$alice->id]['by_type'][ActivityType::NOTE_ADDED])->toBe(1)
        ->and($rows[$alice->id]['name'])->toBe('Alice')
        ->and($rows[$bob->id]['total'])->toBe(3)
        ->and($rows[$bob->id]['name'])->toBe('Bob');
});

it('buckets a time series by day', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    $today = now()->startOfDay();
    $yesterday = now()->subDay()->startOfDay();

    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::MESSAGE_SENT, $today);
    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::MESSAGE_SENT, $today);
    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::NOTE_ADDED, $yesterday);

    $series = Livewire::actingAs($this->owner)
        ->test(ActivityVolume::class)
        ->instance()
        ->timeSeries;

    expect($series[$today->toDateString()])->toBe(2)
        ->and($series[$yesterday->toDateString()])->toBe(1);
});

it('filters by date range', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::MESSAGE_SENT, now()->subDays(30));
    makeActivity($this->company->id, $this->seller->id, $lead->id, $deal->id, ActivityType::MESSAGE_SENT, now()->subDays(2));

    $totals = Livewire::actingAs($this->owner)
        ->test(ActivityVolume::class)
        ->set('from', now()->subDays(7)->toDateString())
        ->set('to', now()->toDateString())
        ->instance()
        ->totals;

    expect($totals[ActivityType::MESSAGE_SENT])->toBe(1);
});

it('does not include cross-company activities', function (): void {
    $other = Company::factory()->create();
    $otherSeller = User::factory()->salesperson()->forCompany($other)->create();
    $otherLead = Lead::factory()->forCompany($other)->ownedBy($otherSeller)->create();
    $otherDeal = Deal::factory()->forLead($otherLead)->create();

    makeActivity($other->id, $otherSeller->id, $otherLead->id, $otherDeal->id, ActivityType::MESSAGE_SENT, now());
    makeActivity($other->id, $otherSeller->id, $otherLead->id, $otherDeal->id, ActivityType::NOTE_ADDED, now());

    $totals = Livewire::actingAs($this->owner)
        ->test(ActivityVolume::class)
        ->instance()
        ->totals;

    foreach ($totals as $count) {
        expect($count)->toBe(0);
    }
});
