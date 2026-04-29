<?php

declare(strict_types=1);

use App\Livewire\Kanban\Board;
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
    $this->otherCompany = Company::factory()->create();

    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
    $this->otherSeller = User::factory()->salesperson()->forCompany($this->company)->create();
    $this->foreignSeller = User::factory()->salesperson()->forCompany($this->otherCompany)->create();
});

it('Salesperson sees only own deals', function (): void {
    $myLead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $otherLead = Lead::factory()->forCompany($this->company)->ownedBy($this->otherSeller)->create();

    $myDeal = Deal::factory()->forLead($myLead)->create(['title' => 'My Deal']);
    Deal::factory()->forLead($otherLead)->create(['title' => 'Other Deal']);

    Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->assertSee('My Deal')
        ->assertDontSee('Other Deal');
});

it('Owner sees all company deals', function (): void {
    $leadA = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $leadB = Lead::factory()->forCompany($this->company)->ownedBy($this->otherSeller)->create();

    Deal::factory()->forLead($leadA)->create(['title' => 'Deal A']);
    Deal::factory()->forLead($leadB)->create(['title' => 'Deal B']);

    Livewire::actingAs($this->owner)
        ->test(Board::class)
        ->assertSee('Deal A')
        ->assertSee('Deal B');
});

it('cross-company deals never visible', function (): void {
    $foreignLead = Lead::factory()->forCompany($this->otherCompany)->ownedBy($this->foreignSeller)->create();
    Deal::factory()->forLead($foreignLead)->create(['title' => 'Foreign Deal']);

    Livewire::actingAs($this->owner)
        ->test(Board::class)
        ->assertDontSee('Foreign Deal');

    Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->assertDontSee('Foreign Deal');
});

it('column counts and totals are correct', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create(['value' => 1000]);
    Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create(['value' => 500]);
    Deal::factory()->forLead($lead)->inStage(PipelineStage::CONTACTED)->create(['value' => 250]);

    $component = Livewire::actingAs($this->seller)->test(Board::class);

    $newStage = PipelineStage::where('slug', PipelineStage::NEW_LEAD)->first();
    $contactedStage = PipelineStage::where('slug', PipelineStage::CONTACTED)->first();

    $byStage = $component->instance()->dealsByStage;

    expect(count($byStage[$newStage->id] ?? []))->toBe(2)
        ->and(count($byStage[$contactedStage->id] ?? []))->toBe(1)
        ->and(collect($byStage[$newStage->id])->sum(fn ($d) => (float) $d->value))->toBe(1500.0)
        ->and(collect($byStage[$contactedStage->id])->sum(fn ($d) => (float) $d->value))->toBe(250.0);
});

it('renders all active pipeline stages', function (): void {
    Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->assertSee('New Lead')
        ->assertSee('Contacted')
        ->assertSee('Proposal Sent')
        ->assertSee('Negotiation')
        ->assertSee('Won')
        ->assertSee('Lost');
});
