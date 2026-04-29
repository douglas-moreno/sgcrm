<?php

declare(strict_types=1);

use App\Livewire\Leads\CreateLead;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use App\Services\Leads\LeadLookupService;
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

it('returns existing lead reference for duplicate email', function (): void {
    $existing = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'email' => 'dup@acme.test',
        'name' => 'Existing Co',
    ]);

    Livewire::actingAs($this->seller)
        ->test(CreateLead::class)
        ->call('openModal')
        ->set('email', 'dup@acme.test')
        ->assertSet('matchedLeadId', $existing->id)
        ->assertSet('matchVisible', true)
        ->assertSet('matchLeadName', 'Existing Co');
});

it('Salesperson cannot reuse a lead owned by another Salesperson', function (): void {
    Lead::factory()->forCompany($this->company)->ownedBy($this->otherSeller)->create([
        'email' => 'theirs@acme.test',
    ]);

    Livewire::actingAs($this->seller)
        ->test(CreateLead::class)
        ->call('openModal')
        ->set('email', 'theirs@acme.test')
        ->assertSet('matchVisible', false)
        ->set('name', 'Trying')
        ->call('reuseExisting')
        ->assertHasErrors('email');

    expect(Deal::query()->whereHas('lead', fn ($q) => $q->where('email', 'theirs@acme.test'))->count())->toBe(0);
});

it('Owner can reuse any lead in company', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'email' => 'shared@acme.test',
    ]);

    Livewire::actingAs($this->owner)
        ->test(CreateLead::class)
        ->call('openModal')
        ->set('email', 'shared@acme.test')
        ->assertSet('matchVisible', true)
        ->set('name', 'Second Deal')
        ->call('reuseExisting')
        ->assertDispatched('lead-reused');

    expect(Deal::query()->where('lead_id', $lead->id)->count())->toBe(1);
});

it('reuse path creates deal but no second lead', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'email' => 'reuse@acme.test',
    ]);

    $countBefore = Lead::query()->withoutGlobalScopes()->count();

    Livewire::actingAs($this->seller)
        ->test(CreateLead::class)
        ->call('openModal')
        ->set('email', 'reuse@acme.test')
        ->set('name', 'Another Deal')
        ->call('reuseExisting');

    expect(Lead::query()->withoutGlobalScopes()->count())->toBe($countBefore)
        ->and(Deal::query()->where('lead_id', $lead->id)->count())->toBe(1);
});

it('LeadLookupService finds case-insensitive match in company', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'email' => 'Case@Acme.Test',
    ]);

    $service = app(LeadLookupService::class);
    $found = $service->findInCompany($this->seller, ' case@acme.test ');

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($lead->id);
});
