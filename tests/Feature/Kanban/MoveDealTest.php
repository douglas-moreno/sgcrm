<?php

declare(strict_types=1);

use App\Livewire\Kanban\Board;
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
    $this->otherSeller = User::factory()->salesperson()->forCompany($this->company)->create();
});

it('moves deal to allowed stage and writes stage_changed activity', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create();

    Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('updateStage', $deal->id, PipelineStage::CONTACTED);

    $contacted = PipelineStage::where('slug', PipelineStage::CONTACTED)->first();
    expect($deal->fresh()->stage_id)->toBe($contacted->id);

    $stageChangedTypeId = ActivityType::where('slug', ActivityType::STAGE_CHANGED)->value('id');
    $activity = Activity::query()->withoutGlobalScopes()
        ->where('deal_id', $deal->id)
        ->where('activity_type_id', $stageChangedTypeId)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->before_value)->toBe(PipelineStage::NEW_LEAD)
        ->and($activity->after_value)->toBe(PipelineStage::CONTACTED)
        ->and($activity->metadata['from_stage_id'])->toBeInt()
        ->and($activity->metadata['to_stage_id'])->toBe($contacted->id);
});

it('rejects move on a deal not owned by current Salesperson', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->otherSeller)->create();
    $deal = Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create();

    Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('updateStage', $deal->id, PipelineStage::CONTACTED)
        ->assertForbidden();

    expect($deal->fresh()->stage_id)->toBe(PipelineStage::where('slug', PipelineStage::NEW_LEAD)->value('id'));
});

it('move to lost without reason fails validation', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create();

    Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('updateStage', $deal->id, PipelineStage::LOST)
        ->assertSet('pendingLostDealId', $deal->id)
        ->call('confirmLoss')
        ->assertHasErrors('lossReason');

    expect($deal->fresh()->stage_id)->not->toBe(PipelineStage::where('slug', PipelineStage::LOST)->value('id'));
});

it('move to lost with reason persists loss_reason and lost_at', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create();

    Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('updateStage', $deal->id, PipelineStage::LOST)
        ->set('lossReason', 'Price too high')
        ->call('confirmLoss')
        ->assertHasNoErrors();

    $lostStageId = PipelineStage::where('slug', PipelineStage::LOST)->value('id');
    $deal->refresh();

    expect($deal->stage_id)->toBe($lostStageId)
        ->and($deal->loss_reason)->toBe('Price too high')
        ->and($deal->lost_at)->not->toBeNull();

    $dealLostTypeId = ActivityType::where('slug', ActivityType::DEAL_LOST)->value('id');
    $activity = Activity::query()->withoutGlobalScopes()
        ->where('deal_id', $deal->id)
        ->where('activity_type_id', $dealLostTypeId)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata['loss_reason'])->toBe('Price too high');
});

it('cancelLoss reverts pending state and does not change stage', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create();
    $originalStageId = $deal->stage_id;

    Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('updateStage', $deal->id, PipelineStage::LOST)
        ->call('cancelLoss')
        ->assertSet('pendingLostDealId', null)
        ->assertSet('lossReason', '');

    expect($deal->fresh()->stage_id)->toBe($originalStageId);
});

it('move to won sets won_at and locks future edits', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->inStage(PipelineStage::NEGOTIATION)->create();

    Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('updateStage', $deal->id, PipelineStage::WON);

    $deal->refresh();
    $wonStageId = PipelineStage::where('slug', PipelineStage::WON)->value('id');

    expect($deal->stage_id)->toBe($wonStageId)
        ->and($deal->won_at)->not->toBeNull()
        ->and($this->seller->can('update', $deal))->toBeFalse();

    $dealWonTypeId = ActivityType::where('slug', ActivityType::DEAL_WON)->value('id');
    expect(Activity::query()->withoutGlobalScopes()->where('deal_id', $deal->id)->where('activity_type_id', $dealWonTypeId)->exists())->toBeTrue();
});

it('returns requires_loss_reason flag when moving to lost', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create();

    Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('updateStage', $deal->id, PipelineStage::LOST)
        ->assertReturned(['status' => 'requires_loss_reason'])
        ->assertSet('pendingLostDealId', $deal->id);
});
