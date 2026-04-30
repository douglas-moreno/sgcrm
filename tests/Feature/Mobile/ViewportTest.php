<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\WhatsappConnectionStatusSeeder::class);
    $this->seed(Database\Seeders\MessageDirectionSeeder::class);
    $this->seed(Database\Seeders\MessageStatusSeeder::class);
    $this->seed(Database\Seeders\MessageTypeSeeder::class);
    $this->seed(Database\Seeders\ActivityTypeSeeder::class);

    $this->company = Company::factory()->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
});

it('login page emits viewport meta', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('width=device-width, initial-scale=1.0', false);
});

it('kanban page emits viewport meta + mobile drawer', function (): void {
    $this->actingAs($this->seller)
        ->get(route('kanban'))
        ->assertOk()
        ->assertSee('width=device-width, initial-scale=1.0', false)
        ->assertSee('mobile-toggle', false)
        ->assertSee('mobile-drawer', false);
});

it('deal page emits viewport meta', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    $this->actingAs($this->seller)
        ->get(route('deals.show', $deal))
        ->assertOk()
        ->assertSee('width=device-width, initial-scale=1.0', false);
});

it('settings page emits viewport meta', function (): void {
    $this->actingAs($this->seller)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertSee('width=device-width, initial-scale=1.0', false);
});
