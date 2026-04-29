<?php

declare(strict_types=1);

use App\Livewire\Leads\EditLead;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
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

it('updates name, phone, and notes', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'name' => 'Old Name',
        'phone' => '111-1111',
        'notes' => 'Old notes',
    ]);

    Livewire::actingAs($this->seller)
        ->test(EditLead::class, ['lead' => $lead])
        ->set('name', 'New Name')
        ->set('phone', '222-2222')
        ->set('notes', 'New notes')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('lead-updated');

    $lead->refresh();
    expect($lead->name)->toBe('New Name')
        ->and($lead->phone)->toBe('222-2222')
        ->and($lead->notes)->toBe('New notes');
});

it('Salesperson cannot edit email', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'email' => 'original@acme.test',
    ]);

    Livewire::actingAs($this->seller)
        ->test(EditLead::class, ['lead' => $lead])
        ->set('email', 'changed@acme.test')
        ->set('name', 'Same Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($lead->fresh()->email)->toBe('original@acme.test');
});

it('Owner can edit email', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'email' => 'original@acme.test',
    ]);

    Livewire::actingAs($this->owner)
        ->test(EditLead::class, ['lead' => $lead])
        ->set('email', 'updated@acme.test')
        ->call('save')
        ->assertHasNoErrors();

    expect($lead->fresh()->email)->toBe('updated@acme.test');
});

it('rejects duplicate email on update', function (): void {
    Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'email' => 'taken@acme.test',
    ]);
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'email' => 'mine@acme.test',
    ]);

    Livewire::actingAs($this->owner)
        ->test(EditLead::class, ['lead' => $lead])
        ->set('email', 'taken@acme.test')
        ->call('save')
        ->assertHasErrors('email');

    expect($lead->fresh()->email)->toBe('mine@acme.test');
});

it('writes lead_updated activity per changed field', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'name' => 'Old Name',
        'phone' => '111-1111',
        'notes' => 'Old notes',
        'email' => 'original@acme.test',
    ]);

    Livewire::actingAs($this->owner)
        ->test(EditLead::class, ['lead' => $lead])
        ->set('name', 'New Name')
        ->set('phone', '222-2222')
        ->set('notes', 'New notes')
        ->set('email', 'new@acme.test')
        ->call('save')
        ->assertHasNoErrors();

    $typeId = ActivityType::where('slug', ActivityType::LEAD_UPDATED)->value('id');

    $activities = Activity::query()->withoutGlobalScopes()
        ->where('lead_id', $lead->id)
        ->where('activity_type_id', $typeId)
        ->get();

    expect($activities)->toHaveCount(4);

    $byField = $activities->keyBy(fn (Activity $a) => $a->metadata['field'] ?? '');

    expect($byField['name']->before_value)->toBe('Old Name')
        ->and($byField['name']->after_value)->toBe('New Name')
        ->and($byField['phone']->after_value)->toBe('222-2222')
        ->and($byField['notes']->after_value)->toBe('New notes')
        ->and($byField['email']->after_value)->toBe('new@acme.test');
});

it('does not write activity for unchanged fields', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'name' => 'Same',
        'phone' => '555',
    ]);

    Livewire::actingAs($this->seller)
        ->test(EditLead::class, ['lead' => $lead])
        ->call('save')
        ->assertHasNoErrors();

    $typeId = ActivityType::where('slug', ActivityType::LEAD_UPDATED)->value('id');
    expect(Activity::query()->withoutGlobalScopes()->where('lead_id', $lead->id)->where('activity_type_id', $typeId)->count())->toBe(0);
});

it('non-owning Salesperson cannot view edit page', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();

    Livewire::actingAs($this->otherSeller)
        ->test(EditLead::class, ['lead' => $lead])
        ->assertForbidden();
});
