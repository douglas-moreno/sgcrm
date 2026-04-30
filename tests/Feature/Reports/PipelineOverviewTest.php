<?php

declare(strict_types=1);

use App\Livewire\Reports\PipelineOverview;
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

it('aggregates deals per stage', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->count(2)->create(['value' => 100]);
    Deal::factory()->forLead($lead)->inStage(PipelineStage::CONTACTED)->count(1)->create(['value' => 500]);
    Deal::factory()->forLead($lead)->inStage(PipelineStage::WON)->count(1)->create(['value' => 1000, 'won_at' => now()]);

    $rows = collect(
        Livewire::actingAs($this->owner)
            ->test(PipelineOverview::class)
            ->instance()
            ->rows
    )->keyBy('stage_slug');

    expect($rows[PipelineStage::NEW_LEAD]['count'])->toBe(2)
        ->and($rows[PipelineStage::NEW_LEAD]['total_value'])->toBe(200.0)
        ->and($rows[PipelineStage::CONTACTED]['count'])->toBe(1)
        ->and($rows[PipelineStage::CONTACTED]['total_value'])->toBe(500.0)
        ->and($rows[PipelineStage::WON]['count'])->toBe(1)
        ->and($rows[PipelineStage::WON]['total_value'])->toBe(1000.0)
        ->and($rows[PipelineStage::LOST]['count'])->toBe(0);
});

it('filters by salesperson', function (): void {
    $other = User::factory()->salesperson()->forCompany($this->company)->create();

    $sellerLead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $otherLead = Lead::factory()->forCompany($this->company)->ownedBy($other)->create();

    Deal::factory()->forLead($sellerLead)->inStage(PipelineStage::NEW_LEAD)->count(2)->create(['value' => 100]);
    Deal::factory()->forLead($otherLead)->inStage(PipelineStage::NEW_LEAD)->count(3)->create(['value' => 200]);

    $component = Livewire::actingAs($this->owner)
        ->test(PipelineOverview::class)
        ->set('salespersonId', $this->seller->id);

    $rows = collect($component->instance()->rows)->keyBy('stage_slug');

    expect($rows[PipelineStage::NEW_LEAD]['count'])->toBe(2)
        ->and($rows[PipelineStage::NEW_LEAD]['total_value'])->toBe(200.0);
});

it('filters by date range', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create([
        'value' => 100,
        'created_at' => now()->subDays(30),
    ]);
    Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create([
        'value' => 200,
        'created_at' => now()->subDays(2),
    ]);

    $component = Livewire::actingAs($this->owner)
        ->test(PipelineOverview::class)
        ->set('from', now()->subDays(7)->toDateString())
        ->set('to', now()->toDateString());

    $rows = collect($component->instance()->rows)->keyBy('stage_slug');

    expect($rows[PipelineStage::NEW_LEAD]['count'])->toBe(1)
        ->and($rows[PipelineStage::NEW_LEAD]['total_value'])->toBe(200.0);
});

it('never leaks cross-company data', function (): void {
    $otherCompany = Company::factory()->create();
    $otherSeller = User::factory()->salesperson()->forCompany($otherCompany)->create();
    $otherLead = Lead::factory()->forCompany($otherCompany)->ownedBy($otherSeller)->create();

    Deal::factory()->forLead($otherLead)->inStage(PipelineStage::NEW_LEAD)->count(5)->create(['value' => 999]);

    $rows = collect(
        Livewire::actingAs($this->owner)
            ->test(PipelineOverview::class)
            ->instance()
            ->rows
    )->keyBy('stage_slug');

    foreach ($rows as $row) {
        expect($row['count'])->toBe(0)
            ->and($row['total_value'])->toBe(0.0);
    }
});

it('renders all six pipeline stages', function (): void {
    $rows = Livewire::actingAs($this->owner)
        ->test(PipelineOverview::class)
        ->instance()
        ->rows;

    $slugs = collect($rows)->pluck('stage_slug')->all();

    expect($slugs)->toContain(
        PipelineStage::NEW_LEAD,
        PipelineStage::CONTACTED,
        PipelineStage::PROPOSAL_SENT,
        PipelineStage::NEGOTIATION,
        PipelineStage::WON,
        PipelineStage::LOST,
    );
});
