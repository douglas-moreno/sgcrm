<?php

declare(strict_types=1);

use App\Livewire\Deals\AddDeal;
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

it('creates additional deal for existing lead with inherited owner and new_lead stage', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    Livewire::actingAs($this->seller)
        ->test(AddDeal::class, ['lead' => $lead])
        ->set('title', 'Second Deal')
        ->set('value', '1500.50')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('deal-added');

    $deal = Deal::query()->where('lead_id', $lead->id)->where('title', 'Second Deal')->firstOrFail();
    $newStageId = PipelineStage::where('slug', PipelineStage::NEW_LEAD)->value('id');

    expect($deal->owner_user_id)->toBe($this->seller->id)
        ->and($deal->stage_id)->toBe($newStageId)
        ->and((float) $deal->value)->toBe(1500.50);
});

it('requires title', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    Livewire::actingAs($this->seller)
        ->test(AddDeal::class, ['lead' => $lead])
        ->set('title', '')
        ->set('value', '100')
        ->call('save')
        ->assertHasErrors('title');
});

it('rejects negative value', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    Livewire::actingAs($this->seller)
        ->test(AddDeal::class, ['lead' => $lead])
        ->set('title', 'Bad Value')
        ->set('value', '-10')
        ->call('save')
        ->assertHasErrors('value');
});

it('non-owning Salesperson cannot add deal', function (): void {
    $other = User::factory()->salesperson()->forCompany($this->company)->create();
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($other)->create();

    Livewire::actingAs($this->seller)
        ->test(AddDeal::class, ['lead' => $lead])
        ->assertForbidden();
});
