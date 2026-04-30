<?php

declare(strict_types=1);

use App\Livewire\Deals\Show as DealShow;
use App\Livewire\Leads\ReassignLead;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
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

it('Owner can edit any non-won deal in the company', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create(['title' => 'Original', 'value' => 100]);

    Livewire::actingAs($this->owner)
        ->test(DealShow::class, ['deal' => $deal])
        ->set('title', 'Owner Edit')
        ->set('value', '750')
        ->call('save')
        ->assertHasNoErrors();

    expect($deal->fresh()->title)->toBe('Owner Edit')
        ->and((float) $deal->fresh()->value)->toBe(750.0);
});

it('Owner can view any deal in the company', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    $this->actingAs($this->owner)
        ->get(route('deals.show', $deal))
        ->assertOk();
});

it('Owner can reassign leads', function (): void {
    $other = User::factory()->salesperson()->forCompany($this->company)->create();
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    Deal::factory()->forLead($lead)->count(2)->create();

    Livewire::actingAs($this->owner)
        ->test(ReassignLead::class, ['lead' => $lead])
        ->set('newOwnerId', $other->id)
        ->call('reassign')
        ->assertHasNoErrors();

    expect($lead->fresh()->owner_user_id)->toBe($other->id);

    $deals = Deal::query()->withoutGlobalScopes()->where('lead_id', $lead->id)->get();
    foreach ($deals as $deal) {
        expect($deal->owner_user_id)->toBe($other->id);
    }
});

it('Owner edits write activities tagged with the Owner user_id', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create(['title' => 'Old', 'value' => 100]);

    Livewire::actingAs($this->owner)
        ->test(DealShow::class, ['deal' => $deal])
        ->set('title', 'Owner Title')
        ->set('value', '200')
        ->call('save');

    $updated = ActivityType::where('slug', ActivityType::DEAL_UPDATED)->firstOrFail();
    $valueChanged = ActivityType::where('slug', ActivityType::VALUE_CHANGED)->firstOrFail();

    $titleAct = Activity::query()
        ->where('deal_id', $deal->id)
        ->where('activity_type_id', $updated->id)
        ->first();
    $valAct = Activity::query()
        ->where('deal_id', $deal->id)
        ->where('activity_type_id', $valueChanged->id)
        ->first();

    expect($titleAct?->user_id)->toBe($this->owner->id)
        ->and($valAct?->user_id)->toBe($this->owner->id);
});

it('Owner reassign writes activities tagged with the Owner', function (): void {
    $other = User::factory()->salesperson()->forCompany($this->company)->create();
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->owner)
        ->test(ReassignLead::class, ['lead' => $lead])
        ->set('newOwnerId', $other->id)
        ->call('reassign');

    $reassigned = ActivityType::where('slug', ActivityType::LEAD_REASSIGNED)->firstOrFail();
    $ownerChange = ActivityType::where('slug', ActivityType::OWNERSHIP_CHANGED)->firstOrFail();

    $leadAct = Activity::query()
        ->where('lead_id', $lead->id)
        ->where('activity_type_id', $reassigned->id)
        ->first();
    $dealAct = Activity::query()
        ->where('deal_id', $deal->id)
        ->where('activity_type_id', $ownerChange->id)
        ->first();

    expect($leadAct?->user_id)->toBe($this->owner->id)
        ->and($dealAct?->user_id)->toBe($this->owner->id);
});

it('Owner is locked out of editing won deals (parity with Salesperson)', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $wonDeal = Deal::factory()->forLead($lead)->inStage(PipelineStage::WON)->create(['won_at' => now()]);

    Livewire::actingAs($this->owner)
        ->test(DealShow::class, ['deal' => $wonDeal])
        ->set('title', 'cannot save')
        ->call('save')
        ->assertForbidden();
});

it('Owner cannot reach a deal in another company (404 via route binding)', function (): void {
    $other = Company::factory()->create();
    $otherSeller = User::factory()->salesperson()->forCompany($other)->create();
    $foreignLead = Lead::factory()->forCompany($other)->ownedBy($otherSeller)->create();
    $foreignDeal = Deal::factory()->forLead($foreignLead)->create();

    $this->actingAs($this->owner)
        ->get(route('deals.show', $foreignDeal))
        ->assertNotFound();
});
