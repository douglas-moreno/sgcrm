<?php

declare(strict_types=1);

use App\Livewire\Leads\CreateLead;
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
    $this->otherCompany = Company::factory()->create();

    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
});

it('creates lead and deal in single submission', function (): void {
    Livewire::actingAs($this->seller)
        ->test(CreateLead::class)
        ->call('openModal')
        ->set('name', 'Acme Corp')
        ->set('email', 'lead@acme.test')
        ->set('phone', '+11 555 1234')
        ->set('notes', 'Hot lead')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('lead-created');

    $lead = Lead::query()->where('email', 'lead@acme.test')->firstOrFail();

    expect($lead->company_id)->toBe($this->company->id)
        ->and($lead->owner_user_id)->toBe($this->seller->id)
        ->and($lead->phone)->toBe('+11 555 1234');

    $deal = Deal::query()->where('lead_id', $lead->id)->firstOrFail();
    $newStageId = PipelineStage::where('slug', PipelineStage::NEW_LEAD)->value('id');

    expect($deal->stage_id)->toBe($newStageId)
        ->and($deal->title)->toBe('Acme Corp')
        ->and((float) $deal->value)->toBe(0.0)
        ->and($deal->owner_user_id)->toBe($this->seller->id);

    $leadCreatedTypeId = ActivityType::where('slug', ActivityType::LEAD_CREATED)->value('id');
    expect(Activity::query()->withoutGlobalScopes()->where('lead_id', $lead->id)->where('activity_type_id', $leadCreatedTypeId)->exists())->toBeTrue();
});

it('rejects duplicate email in same company', function (): void {
    Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['email' => 'dup@acme.test']);

    Livewire::actingAs($this->seller)
        ->test(CreateLead::class)
        ->call('openModal')
        ->set('name', 'Dup')
        ->set('email', 'dup@acme.test')
        ->call('save')
        ->assertHasErrors('email');
});

it('allows same email in different company', function (): void {
    $otherSeller = User::factory()->salesperson()->forCompany($this->otherCompany)->create();
    Lead::factory()->forCompany($this->otherCompany)->ownedBy($otherSeller)->create(['email' => 'shared@acme.test']);

    Livewire::actingAs($this->seller)
        ->test(CreateLead::class)
        ->call('openModal')
        ->set('name', 'Shared')
        ->set('email', 'shared@acme.test')
        ->call('save')
        ->assertHasNoErrors();

    expect(Lead::query()->withoutGlobalScopes()->where('email', 'shared@acme.test')->count())->toBe(2);
});

it('sets owner to current user for Salesperson', function (): void {
    Livewire::actingAs($this->seller)
        ->test(CreateLead::class)
        ->call('openModal')
        ->set('name', 'Mine')
        ->set('email', 'mine@acme.test')
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::query()->where('email', 'mine@acme.test')->firstOrFail();
    expect($lead->owner_user_id)->toBe($this->seller->id);
});

it('Owner can assign lead to a Salesperson', function (): void {
    Livewire::actingAs($this->owner)
        ->test(CreateLead::class)
        ->call('openModal')
        ->set('name', 'For Alice')
        ->set('email', 'alice@acme.test')
        ->set('ownerUserId', $this->seller->id)
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::query()->where('email', 'alice@acme.test')->firstOrFail();
    expect($lead->owner_user_id)->toBe($this->seller->id);
});

it('rolls back transaction on failure', function (): void {
    $service = app(App\Services\Leads\CreateLeadWithDeal::class);
    $foreignOwner = User::factory()->salesperson()->forCompany($this->otherCompany)->create();

    expect(fn () => $service->create(
        $this->owner,
        $foreignOwner,
        ['name' => 'Should Fail', 'email' => 'rollback@acme.test'],
    ))->toThrow(InvalidArgumentException::class);

    expect(Lead::query()->withoutGlobalScopes()->where('email', 'rollback@acme.test')->exists())->toBeFalse()
        ->and(Deal::query()->withoutGlobalScopes()->whereHas('lead', fn ($q) => $q->where('email', 'rollback@acme.test'))->exists())->toBeFalse();
});
