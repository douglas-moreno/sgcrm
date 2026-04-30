<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\InviteStatusSeeder::class);
    $this->seed(Database\Seeders\WhatsappConnectionStatusSeeder::class);
    $this->seed(Database\Seeders\MessageDirectionSeeder::class);
    $this->seed(Database\Seeders\MessageStatusSeeder::class);
    $this->seed(Database\Seeders\MessageTypeSeeder::class);
    $this->seed(Database\Seeders\ActivityTypeSeeder::class);

    $this->company = Company::factory()->create();
    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
});

it('guest routes return 200', function (string $route): void {
    $this->get($route)->assertOk();
})->with([
    '/login',
    '/register',
    '/forgot-password',
]);

it('authed Salesperson routes return 200', function (): void {
    $this->actingAs($this->seller)->get(route('kanban'))->assertOk();
    $this->actingAs($this->seller)->get(route('settings.index'))->assertOk();

    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    $this->actingAs($this->seller)->get(route('leads.edit', $lead))->assertOk();
    $this->actingAs($this->seller)->get(route('deals.show', $deal))->assertOk();
});

it('Owner-only routes return 200 for Owner', function (string $name): void {
    $this->actingAs($this->owner)->get(route($name))->assertOk();
})->with([
    'team.index',
    'team.invites',
    'reports.pipeline',
    'reports.salesperson',
    'reports.loss-reasons',
    'reports.activity',
]);

it('health endpoint /up returns 200', function (): void {
    $this->get('/up')->assertOk();
});
