<?php

declare(strict_types=1);

use App\Livewire\Leads\ReassignLead;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use App\Services\Leads\ReassignLeadService;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\ActivityTypeSeeder::class);

    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();

    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
    $this->originalOwner = User::factory()->salesperson()->forCompany($this->company)->create();
    $this->newOwner = User::factory()->salesperson()->forCompany($this->company)->create();
    $this->foreignSeller = User::factory()->salesperson()->forCompany($this->otherCompany)->create();
});

it('reassigns lead and all of its deals', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->originalOwner)->create();
    Deal::factory()->forLead($lead)->count(3)->create();

    $service = app(ReassignLeadService::class);
    $service->reassign($lead, $this->newOwner, $this->owner);

    expect($lead->fresh()->owner_user_id)->toBe($this->newOwner->id);

    foreach ($lead->fresh()->deals as $deal) {
        expect($deal->owner_user_id)->toBe($this->newOwner->id);
    }
});

it('writes lead_reassigned and ownership_changed activities', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->originalOwner)->create();
    Deal::factory()->forLead($lead)->count(2)->create();

    $service = app(ReassignLeadService::class);
    $service->reassign($lead, $this->newOwner, $this->owner);

    $reassignedTypeId = ActivityType::where('slug', ActivityType::LEAD_REASSIGNED)->value('id');
    $ownershipTypeId = ActivityType::where('slug', ActivityType::OWNERSHIP_CHANGED)->value('id');

    expect(Activity::query()->withoutGlobalScopes()->where('lead_id', $lead->id)->where('activity_type_id', $reassignedTypeId)->count())->toBe(1)
        ->and(Activity::query()->withoutGlobalScopes()->where('lead_id', $lead->id)->where('activity_type_id', $ownershipTypeId)->count())->toBe(2);

    $reassign = Activity::query()->withoutGlobalScopes()
        ->where('lead_id', $lead->id)
        ->where('activity_type_id', $reassignedTypeId)
        ->first();

    expect($reassign->before_value)->toBe((string) $this->originalOwner->id)
        ->and($reassign->after_value)->toBe((string) $this->newOwner->id)
        ->and($reassign->user_id)->toBe($this->owner->id);
});

it('previous owner cannot view lead after reassignment', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->originalOwner)->create();

    expect($this->originalOwner->can('view', $lead))->toBeTrue();

    app(ReassignLeadService::class)->reassign($lead, $this->newOwner, $this->owner);

    $lead = $lead->fresh();
    expect($this->originalOwner->can('view', $lead))->toBeFalse()
        ->and($this->newOwner->can('view', $lead))->toBeTrue();
});

it('only owner can reassign via livewire', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->originalOwner)->create();

    Livewire::actingAs($this->originalOwner)
        ->test(ReassignLead::class, ['lead' => $lead])
        ->assertForbidden();

    Livewire::actingAs($this->owner)
        ->test(ReassignLead::class, ['lead' => $lead])
        ->set('newOwnerId', $this->newOwner->id)
        ->call('reassign')
        ->assertHasNoErrors()
        ->assertDispatched('lead-reassigned');

    expect($lead->fresh()->owner_user_id)->toBe($this->newOwner->id);
});

it('rejects new owner from another company', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->originalOwner)->create();

    expect(fn () => app(ReassignLeadService::class)->reassign($lead, $this->foreignSeller, $this->owner))
        ->toThrow(InvalidArgumentException::class);
});
