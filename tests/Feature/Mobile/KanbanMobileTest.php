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
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
});

it('renders horizontally scrollable column row on small viewports', function (): void {
    $html = Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->html();

    expect($html)->toContain('overflow-x-auto')
        ->and($html)->toContain('lg:overflow-visible')
        ->and($html)->toContain('min-w-[280px]');
});

it('renders sticky stage headers (mobile)', function (): void {
    $html = Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->html();

    expect($html)->toContain('sticky top-0');
});

it('cards meet 44px touch target with touch-manipulation', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create(['title' => 'Touch Card']);

    $html = Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->html();

    expect($html)->toContain('min-h-11')
        ->and($html)->toContain('touch-manipulation')
        ->and($html)->toContain('Touch Card');
});

it('uses Livewire wire:sort (no external DnD lib markers)', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create();

    $html = Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->html();

    expect($html)->toContain('wire:sort')
        ->and($html)->toContain('wire:sort:group')
        ->and($html)->toContain('wire:sort:group-id')
        ->and($html)->toContain('wire:sort:item')
        ->and($html)->toContain('wire:sort:handle')
        ->and($html)->toContain('open-deal-drawer')
        ->and($html)->not->toContain('SortableJS')
        ->and($html)->not->toContain('sortablejs');
});

it('move via Livewire (touch path semantics) persists stage', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create();

    Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('updateStage', $deal->id, 0, PipelineStage::CONTACTED);

    $contactedId = PipelineStage::where('slug', PipelineStage::CONTACTED)->value('id');
    expect($deal->fresh()->stage_id)->toBe($contactedId);
});
