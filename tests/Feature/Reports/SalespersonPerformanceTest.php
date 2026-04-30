<?php

declare(strict_types=1);

use App\Livewire\Reports\SalespersonPerformance;
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
});

it('computes metrics correctly', function (): void {
    $alice = User::factory()->salesperson()->forCompany($this->company)->create(['name' => 'Alice']);
    $bob = User::factory()->salesperson()->forCompany($this->company)->create(['name' => 'Bob']);

    $aliceLead = Lead::factory()->forCompany($this->company)->ownedBy($alice)->create();
    $bobLead = Lead::factory()->forCompany($this->company)->ownedBy($bob)->create();

    Deal::factory()->forLead($aliceLead)->inStage(PipelineStage::WON)->count(3)->create(['value' => 1000, 'won_at' => now()]);
    Deal::factory()->forLead($aliceLead)->inStage(PipelineStage::LOST)->count(1)->create(['value' => 500, 'lost_at' => now(), 'loss_reason' => 'price']);

    Deal::factory()->forLead($bobLead)->inStage(PipelineStage::WON)->count(1)->create(['value' => 2000, 'won_at' => now()]);
    Deal::factory()->forLead($bobLead)->inStage(PipelineStage::LOST)->count(1)->create(['value' => 800, 'lost_at' => now(), 'loss_reason' => 'timing']);

    $rows = collect(
        Livewire::actingAs($this->owner)
            ->test(SalespersonPerformance::class)
            ->instance()
            ->rows
    )->keyBy('user_id');

    expect($rows[$alice->id]['won'])->toBe(3)
        ->and($rows[$alice->id]['lost'])->toBe(1)
        ->and($rows[$alice->id]['conversion'])->toBe(75.0)
        ->and($rows[$alice->id]['total_won_value'])->toBe(3000.0)
        ->and($rows[$alice->id]['avg_deal_size'])->toBe(1000.0)
        ->and($rows[$bob->id]['won'])->toBe(1)
        ->and($rows[$bob->id]['lost'])->toBe(1)
        ->and($rows[$bob->id]['conversion'])->toBe(50.0)
        ->and($rows[$bob->id]['total_won_value'])->toBe(2000.0)
        ->and($rows[$bob->id]['avg_deal_size'])->toBe(2000.0);
});

it('handles zero deals gracefully', function (): void {
    User::factory()->salesperson()->forCompany($this->company)->create(['name' => 'NoDeals']);

    $rows = Livewire::actingAs($this->owner)
        ->test(SalespersonPerformance::class)
        ->instance()
        ->rows;

    expect($rows[0]['won'])->toBe(0)
        ->and($rows[0]['lost'])->toBe(0)
        ->and($rows[0]['conversion'])->toBe(0.0)
        ->and($rows[0]['avg_deal_size'])->toBe(0.0);
});

it('sorts by each sortable column', function (string $column): void {
    $alice = User::factory()->salesperson()->forCompany($this->company)->create(['name' => 'Alice']);
    $bob = User::factory()->salesperson()->forCompany($this->company)->create(['name' => 'Bob']);

    $aliceLead = Lead::factory()->forCompany($this->company)->ownedBy($alice)->create();
    $bobLead = Lead::factory()->forCompany($this->company)->ownedBy($bob)->create();

    Deal::factory()->forLead($aliceLead)->inStage(PipelineStage::WON)->count(1)->create(['value' => 100, 'won_at' => now()]);
    Deal::factory()->forLead($aliceLead)->inStage(PipelineStage::LOST)->count(3)->create(['value' => 50, 'lost_at' => now(), 'loss_reason' => 'r']);

    Deal::factory()->forLead($bobLead)->inStage(PipelineStage::WON)->count(5)->create(['value' => 1000, 'won_at' => now()]);
    Deal::factory()->forLead($bobLead)->inStage(PipelineStage::LOST)->count(1)->create(['value' => 100, 'lost_at' => now(), 'loss_reason' => 'r']);

    $component = Livewire::actingAs($this->owner)->test(SalespersonPerformance::class)
        ->set('sort', $column)
        ->set('direction', 'asc');

    $values = collect($component->instance()->rows)->pluck($column)->all();
    $sortedAsc = collect($values)->sort(SORT_REGULAR)->values()->all();

    expect($values)->toEqual($sortedAsc);

    $component->set('direction', 'desc');
    $valuesDesc = collect($component->instance()->rows)->pluck($column)->all();
    $sortedDesc = collect($values)->sortDesc(SORT_REGULAR)->values()->all();

    expect($valuesDesc)->toEqual($sortedDesc);
})->with(['name', 'won', 'lost', 'conversion', 'total_won_value', 'avg_deal_size']);

it('ignores unknown sort column', function (): void {
    User::factory()->salesperson()->forCompany($this->company)->create(['name' => 'A']);

    $component = Livewire::actingAs($this->owner)->test(SalespersonPerformance::class);
    $component->call('sortBy', 'evil_column');

    expect($component->get('sort'))->toBe('name');
});

it('filters by date range', function (): void {
    $alice = User::factory()->salesperson()->forCompany($this->company)->create(['name' => 'Alice']);
    $aliceLead = Lead::factory()->forCompany($this->company)->ownedBy($alice)->create();

    Deal::factory()->forLead($aliceLead)->inStage(PipelineStage::WON)->create([
        'value' => 100,
        'won_at' => now()->subDays(30),
        'created_at' => now()->subDays(30),
    ]);
    Deal::factory()->forLead($aliceLead)->inStage(PipelineStage::WON)->create([
        'value' => 500,
        'won_at' => now(),
        'created_at' => now()->subDays(1),
    ]);

    $component = Livewire::actingAs($this->owner)
        ->test(SalespersonPerformance::class)
        ->set('from', now()->subDays(7)->toDateString())
        ->set('to', now()->toDateString());

    $rows = collect($component->instance()->rows)->keyBy('user_id');

    expect($rows[$alice->id]['won'])->toBe(1)
        ->and($rows[$alice->id]['total_won_value'])->toBe(500.0);
});

it('does not include other companies', function (): void {
    $other = Company::factory()->create();
    $otherSeller = User::factory()->salesperson()->forCompany($other)->create();
    $otherLead = Lead::factory()->forCompany($other)->ownedBy($otherSeller)->create();
    Deal::factory()->forLead($otherLead)->inStage(PipelineStage::WON)->count(10)->create(['value' => 999, 'won_at' => now()]);

    $rows = Livewire::actingAs($this->owner)
        ->test(SalespersonPerformance::class)
        ->instance()
        ->rows;

    expect($rows)->toBeEmpty();
});
