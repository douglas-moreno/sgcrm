<?php

declare(strict_types=1);

use App\Livewire\Reports\LossReasons;
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

it('normalizes reasons (lowercase + trim) into one bucket', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->create(['loss_reason' => 'Price', 'lost_at' => now()]);
    Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->create(['loss_reason' => '  price', 'lost_at' => now()]);
    Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->create(['loss_reason' => 'PRICE', 'lost_at' => now()]);
    Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->create(['loss_reason' => 'timing', 'lost_at' => now()]);

    $buckets = collect(
        Livewire::actingAs($this->owner)
            ->test(LossReasons::class)
            ->instance()
            ->buckets
    )->keyBy('reason');

    expect($buckets['price']['count'])->toBe(3)
        ->and($buckets['timing']['count'])->toBe(1);
});

it('orders buckets by frequency descending', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->count(2)->create(['loss_reason' => 'budget', 'lost_at' => now()]);
    Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->count(5)->create(['loss_reason' => 'timing', 'lost_at' => now()]);
    Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->count(1)->create(['loss_reason' => 'features', 'lost_at' => now()]);

    $reasons = collect(
        Livewire::actingAs($this->owner)
            ->test(LossReasons::class)
            ->instance()
            ->buckets
    )->pluck('reason')->all();

    expect($reasons)->toEqual(['timing', 'budget', 'features']);
});

it('filters buckets by salesperson', function (): void {
    $other = User::factory()->salesperson()->forCompany($this->company)->create();
    $sellerLead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $otherLead = Lead::factory()->forCompany($this->company)->ownedBy($other)->create();

    Deal::factory()->forLead($sellerLead)->inStage(PipelineStage::LOST)->count(2)->create(['loss_reason' => 'price', 'lost_at' => now()]);
    Deal::factory()->forLead($otherLead)->inStage(PipelineStage::LOST)->count(3)->create(['loss_reason' => 'timing', 'lost_at' => now()]);

    $buckets = collect(
        Livewire::actingAs($this->owner)
            ->test(LossReasons::class)
            ->set('salespersonId', $this->seller->id)
            ->instance()
            ->buckets
    );

    expect($buckets->pluck('reason')->all())->toEqual(['price'])
        ->and($buckets->first()['count'])->toBe(2);
});

it('filters by date range on lost_at', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->create([
        'loss_reason' => 'old',
        'lost_at' => now()->subDays(30),
    ]);
    Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->create([
        'loss_reason' => 'recent',
        'lost_at' => now()->subDays(2),
    ]);

    $reasons = collect(
        Livewire::actingAs($this->owner)
            ->test(LossReasons::class)
            ->set('from', now()->subDays(7)->toDateString())
            ->set('to', now()->toDateString())
            ->instance()
            ->buckets
    )->pluck('reason')->all();

    expect($reasons)->toEqual(['recent']);
});

it('drill-down returns matching deals (case-insensitive)', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    $d1 = Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->create(['loss_reason' => 'Price', 'lost_at' => now()]);
    $d2 = Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->create(['loss_reason' => '  PRICE ', 'lost_at' => now()->subHour()]);
    Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->create(['loss_reason' => 'timing', 'lost_at' => now()]);

    $component = Livewire::actingAs($this->owner)
        ->test(LossReasons::class)
        ->call('expand', 'price');

    $deals = $component->instance()->expandedDeals;

    expect($deals->pluck('id')->all())->toEqual([$d1->id, $d2->id]);
});

it('expand toggles the same reason off', function (): void {
    $component = Livewire::actingAs($this->owner)
        ->test(LossReasons::class)
        ->call('expand', 'price');

    expect($component->get('expandedReason'))->toBe('price');

    $component->call('expand', 'price');

    expect($component->get('expandedReason'))->toBeNull();
});

it('does not include cross-company lost deals', function (): void {
    $other = Company::factory()->create();
    $otherSeller = User::factory()->salesperson()->forCompany($other)->create();
    $otherLead = Lead::factory()->forCompany($other)->ownedBy($otherSeller)->create();

    Deal::factory()->forLead($otherLead)->inStage(PipelineStage::LOST)->count(10)->create(['loss_reason' => 'leak', 'lost_at' => now()]);

    $buckets = Livewire::actingAs($this->owner)
        ->test(LossReasons::class)
        ->instance()
        ->buckets;

    expect($buckets)->toBeEmpty();
});
