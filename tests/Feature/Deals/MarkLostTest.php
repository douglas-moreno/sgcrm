<?php

declare(strict_types=1);

use App\Livewire\Deals\Show;
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
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
});

it('rejects empty reason', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();
    $originalStageId = $deal->stage_id;

    Livewire::actingAs($this->seller)
        ->test(Show::class, ['deal' => $deal])
        ->call('openLostModal')
        ->assertSet('showLostModal', true)
        ->call('confirmLost')
        ->assertHasErrors('lossReason');

    expect($deal->fresh()->stage_id)->toBe($originalStageId);
});

it('persists loss_reason, lost_at, sets stage to lost', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(Show::class, ['deal' => $deal])
        ->call('openLostModal')
        ->set('lossReason', 'Budget cut')
        ->call('confirmLost')
        ->assertHasNoErrors()
        ->assertSet('showLostModal', false);

    $lostStageId = PipelineStage::where('slug', PipelineStage::LOST)->value('id');
    $deal->refresh();

    expect($deal->stage_id)->toBe($lostStageId)
        ->and($deal->loss_reason)->toBe('Budget cut')
        ->and($deal->lost_at)->not->toBeNull();
});

it('records deal_lost activity with reason in metadata', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(Show::class, ['deal' => $deal])
        ->call('openLostModal')
        ->set('lossReason', 'Picked competitor')
        ->call('confirmLost');

    $typeId = ActivityType::where('slug', ActivityType::DEAL_LOST)->value('id');
    $activity = Activity::query()->withoutGlobalScopes()
        ->where('deal_id', $deal->id)
        ->where('activity_type_id', $typeId)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata['loss_reason'])->toBe('Picked competitor');
});

it('cancel does not change stage', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();
    $originalStageId = $deal->stage_id;

    Livewire::actingAs($this->seller)
        ->test(Show::class, ['deal' => $deal])
        ->call('openLostModal')
        ->set('lossReason', 'maybe')
        ->call('cancelLost')
        ->assertSet('showLostModal', false)
        ->assertSet('lossReason', '');

    expect($deal->fresh()->stage_id)->toBe($originalStageId);
});
