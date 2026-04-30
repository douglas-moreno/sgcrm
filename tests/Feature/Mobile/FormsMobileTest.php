<?php

declare(strict_types=1);

use App\Livewire\Deals\Show as DealShow;
use App\Livewire\Kanban\Board;
use App\Livewire\Leads\CreateLead;
use App\Livewire\Leads\EditLead;
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

it('CreateLead modal is full-screen on mobile + autofocuses email', function (): void {
    $html = Livewire::actingAs($this->seller)
        ->test(CreateLead::class)
        ->set('open', true)
        ->html();

    expect($html)->toContain('items-end sm:items-center')
        ->and($html)->toContain('h-full sm:h-auto')
        ->and($html)->toContain('sm:rounded-lg')
        ->and($html)->toContain('autofocus')
        ->and($html)->toContain('type="email"')
        ->and($html)->toContain('inputmode="tel"');
});

it('EditLead form autofocuses name', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    $html = Livewire::actingAs($this->seller)
        ->test(EditLead::class, ['lead' => $lead])
        ->html();

    expect($html)->toContain('autofocus')
        ->and($html)->toContain('inputmode="tel"');
});

it('Loss-reason modal on Kanban is full-screen on mobile + autofocuses textarea', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->inStage(PipelineStage::NEW_LEAD)->create();

    $component = Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('updateStage', $deal->id, PipelineStage::LOST);

    $html = $component->html();

    expect($html)->toContain('loss-reason-modal')
        ->and($html)->toContain('items-end sm:items-center')
        ->and($html)->toContain('h-full sm:h-auto')
        ->and($html)->toContain('autofocus');
});

it('Loss-reason modal on Deal Show is full-screen on mobile + autofocuses', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    $html = Livewire::actingAs($this->seller)
        ->test(DealShow::class, ['deal' => $deal])
        ->call('openLostModal')
        ->html();

    expect($html)->toContain('lost-modal')
        ->and($html)->toContain('items-end sm:items-center')
        ->and($html)->toContain('h-full sm:h-auto')
        ->and($html)->toContain('autofocus');
});

it('Deal Show value field uses numeric input', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    $html = Livewire::actingAs($this->seller)
        ->test(DealShow::class, ['deal' => $deal])
        ->html();

    expect($html)->toContain('type="number"')
        ->and($html)->toContain('step="0.01"');
});
