<?php

declare(strict_types=1);

use App\Livewire\Deals\Timeline;
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
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
});

it('lists all activity types for a deal sorted DESC by created_at', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    $types = [
        ActivityType::DEAL_CREATED,
        ActivityType::STAGE_CHANGED,
        ActivityType::VALUE_CHANGED,
        ActivityType::OWNERSHIP_CHANGED,
        ActivityType::NOTE_ADDED,
        ActivityType::MESSAGE_SENT,
    ];

    foreach ($types as $i => $slug) {
        $type = ActivityType::where('slug', $slug)->first();
        Activity::create([
            'company_id' => $this->company->id,
            'activity_type_id' => $type->id,
            'user_id' => $this->seller->id,
            'lead_id' => $lead->id,
            'deal_id' => $deal->id,
            'metadata' => [],
            'created_at' => now()->subMinutes(10 - $i),
        ]);
    }

    $component = Livewire::actingAs($this->seller)->test(Timeline::class, ['deal' => $deal]);

    $activities = $component->instance()->activities;
    expect($activities->total())->toBe(6);

    $createdAtList = $activities->pluck('created_at')->all();
    $sorted = collect($createdAtList)->sortDesc()->values()->all();
    expect($createdAtList)->toEqual($sorted);
});

it('paginates activities', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();
    $type = ActivityType::where('slug', ActivityType::DEAL_UPDATED)->first();

    for ($i = 0; $i < 25; $i++) {
        Activity::create([
            'company_id' => $this->company->id,
            'activity_type_id' => $type->id,
            'user_id' => $this->seller->id,
            'lead_id' => $lead->id,
            'deal_id' => $deal->id,
            'metadata' => ['n' => $i],
            'created_at' => now()->subMinutes($i),
        ]);
    }

    $component = Livewire::actingAs($this->seller)->test(Timeline::class, ['deal' => $deal]);

    expect($component->instance()->activities->perPage())->toBe(15)
        ->and($component->instance()->activities->total())->toBe(25)
        ->and(count($component->instance()->activities->items()))->toBe(15);
});

it('cannot edit or delete activities via policy', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();
    $type = ActivityType::where('slug', ActivityType::NOTE_ADDED)->first();

    $activity = Activity::create([
        'company_id' => $this->company->id,
        'activity_type_id' => $type->id,
        'user_id' => $this->seller->id,
        'lead_id' => $lead->id,
        'deal_id' => $deal->id,
        'metadata' => [],
        'created_at' => now(),
    ]);

    expect($this->seller->can('update', $activity))->toBeFalse()
        ->and($this->seller->can('delete', $activity))->toBeFalse()
        ->and($this->seller->can('create', Activity::class))->toBeFalse();
});

it('non-owning Salesperson forbidden on mount', function (): void {
    $other = User::factory()->salesperson()->forCompany($this->company)->create();
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($other)
        ->test(Timeline::class, ['deal' => $deal])
        ->assertForbidden();
});
