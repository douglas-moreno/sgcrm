<?php

declare(strict_types=1);

use App\Livewire\Deals\Show;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Deal;
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

it('shows all sections', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(Show::class, ['deal' => $deal])
        ->assertSee('deal-header-section', escape: false)
        ->assertSee('lead-info-section', escape: false)
        ->assertSee('owner-section', escape: false)
        ->assertSee('notes-section', escape: false)
        ->assertSee('activity-section', escape: false)
        ->assertSee('chat-shortcut-section', escape: false);
});

it('updates title and value records value_changed activity', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create([
        'title' => 'Old Title',
        'value' => 100,
    ]);

    Livewire::actingAs($this->seller)
        ->test(Show::class, ['deal' => $deal])
        ->set('title', 'New Title')
        ->set('value', '500')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('deal-updated');

    $deal->refresh();
    expect($deal->title)->toBe('New Title')
        ->and((float) $deal->value)->toBe(500.0);

    $valueChangedTypeId = ActivityType::where('slug', ActivityType::VALUE_CHANGED)->value('id');
    $titleChangedTypeId = ActivityType::where('slug', ActivityType::DEAL_UPDATED)->value('id');

    expect(Activity::query()->withoutGlobalScopes()->where('deal_id', $deal->id)->where('activity_type_id', $valueChangedTypeId)->count())->toBe(1)
        ->and(Activity::query()->withoutGlobalScopes()->where('deal_id', $deal->id)->where('activity_type_id', $titleChangedTypeId)->count())->toBe(1);
});

it('won deal is not editable', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->won()->create();

    Livewire::actingAs($this->seller)
        ->test(Show::class, ['deal' => $deal])
        ->set('title', 'Try Edit')
        ->call('save')
        ->assertForbidden();

    expect($deal->fresh()->title)->not->toBe('Try Edit');
});

it('cross-company deal returns 404 via route binding', function (): void {
    $foreignSeller = User::factory()->salesperson()->forCompany($this->otherCompany)->create();
    $foreignLead = Lead::factory()->forCompany($this->otherCompany)->ownedBy($foreignSeller)->create();
    $foreignDeal = Deal::factory()->forLead($foreignLead)->create();

    $this->actingAs($this->seller)
        ->get(route('deals.show', $foreignDeal))
        ->assertNotFound();
});

it('non-owning Salesperson returns 403', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->otherSeller)
        ->test(Show::class, ['deal' => $deal])
        ->assertForbidden();
});

it('Owner can edit any deal except won', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->owner)
        ->test(Show::class, ['deal' => $deal])
        ->set('title', 'Owner Edit')
        ->call('save')
        ->assertHasNoErrors();

    expect($deal->fresh()->title)->toBe('Owner Edit');
});

it('rejects negative value', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(Show::class, ['deal' => $deal])
        ->set('value', '-5')
        ->call('save')
        ->assertHasErrors('value');
});
