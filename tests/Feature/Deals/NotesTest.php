<?php

declare(strict_types=1);

use App\Livewire\Deals\NotesPanel;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Deal;
use App\Models\DealNote;
use App\Models\Lead;
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
});

it('owner of deal adds note and writes note_added activity', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(NotesPanel::class, ['deal' => $deal])
        ->set('body', 'Called the customer, very interested.')
        ->call('add')
        ->assertHasNoErrors()
        ->assertDispatched('note-added');

    expect(DealNote::query()->where('deal_id', $deal->id)->count())->toBe(1);

    $typeId = ActivityType::where('slug', ActivityType::NOTE_ADDED)->value('id');
    expect(Activity::query()->withoutGlobalScopes()->where('deal_id', $deal->id)->where('activity_type_id', $typeId)->count())->toBe(1);
});

it('non-owner Salesperson cannot add note', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->otherSeller)
        ->test(NotesPanel::class, ['deal' => $deal])
        ->assertForbidden();
});

it('cross-company forbidden', function (): void {
    $foreignSeller = User::factory()->salesperson()->forCompany($this->otherCompany)->create();
    $foreignLead = Lead::factory()->forCompany($this->otherCompany)->ownedBy($foreignSeller)->create();
    $foreignDeal = Deal::factory()->forLead($foreignLead)->create();

    Livewire::actingAs($this->seller)
        ->test(NotesPanel::class, ['deal' => $foreignDeal])
        ->assertForbidden();
});

it('Owner can add note to any deal in company', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->owner)
        ->test(NotesPanel::class, ['deal' => $deal])
        ->set('body', 'Owner here.')
        ->call('add')
        ->assertHasNoErrors();

    expect(DealNote::query()->where('deal_id', $deal->id)->where('user_id', $this->owner->id)->exists())->toBeTrue();
});

it('rejects empty note body', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(NotesPanel::class, ['deal' => $deal])
        ->set('body', '')
        ->call('add')
        ->assertHasErrors('body');
});
