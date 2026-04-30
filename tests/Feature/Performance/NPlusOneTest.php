<?php

declare(strict_types=1);

use App\Livewire\Deals\Show as DealShow;
use App\Livewire\Kanban\Board;
use App\Livewire\Reports\PipelineOverview;
use App\Livewire\Reports\SalespersonPerformance;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\ActivityTypeSeeder::class);

    Model::preventLazyLoading(true);

    $this->company = Company::factory()->create();
    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
});

afterEach(function (): void {
    Model::preventLazyLoading(false);
});

it('Kanban board renders without lazy loading (15 deals across stages)', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->count(5)->create();
    Deal::factory()->forLead($lead)->inStage(PipelineStage::CONTACTED)->count(5)->create();
    Deal::factory()->forLead($lead)->inStage(PipelineStage::PROPOSAL_SENT)->count(5)->create();

    Livewire::actingAs($this->owner)
        ->test(Board::class)
        ->assertOk();
});

it('Pipeline overview report renders without lazy loading', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->count(10)->create();
    Deal::factory()->forLead($lead)->inStage(PipelineStage::WON)->count(5)->create(['won_at' => now()]);

    Livewire::actingAs($this->owner)
        ->test(PipelineOverview::class)
        ->assertOk();
});

it('Salesperson performance report renders without lazy loading', function (): void {
    $a = User::factory()->salesperson()->forCompany($this->company)->create();
    $b = User::factory()->salesperson()->forCompany($this->company)->create();
    foreach ([$a, $b] as $seller) {
        $lead = Lead::factory()->forCompany($this->company)->ownedBy($seller)->create();
        Deal::factory()->forLead($lead)->inStage(PipelineStage::WON)->count(3)->create(['won_at' => now()]);
        Deal::factory()->forLead($lead)->inStage(PipelineStage::LOST)->count(2)->create(['lost_at' => now(), 'loss_reason' => 'r']);
    }

    Livewire::actingAs($this->owner)
        ->test(SalespersonPerformance::class)
        ->assertOk();
});

it('Deal detail renders without lazy loading', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(DealShow::class, ['deal' => $deal])
        ->assertOk();
});
