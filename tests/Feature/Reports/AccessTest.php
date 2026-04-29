<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\ActivityTypeSeeder::class);

    $this->company = Company::factory()->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
});

it('Salesperson is forbidden on every report route', function (string $route): void {
    $this->actingAs($this->seller)->get(route($route))->assertForbidden();
})->with([
    'reports.pipeline',
    'reports.salesperson',
    'reports.loss-reasons',
    'reports.activity',
]);

it('Business Owner can access every report route', function (string $route): void {
    $this->actingAs($this->owner)->get(route($route))->assertOk();
})->with([
    'reports.pipeline',
    'reports.salesperson',
    'reports.loss-reasons',
    'reports.activity',
]);

it('reports.index redirects to pipeline', function (): void {
    $this->actingAs($this->owner)
        ->get(route('reports.index'))
        ->assertRedirect('/reports/pipeline');
});

it('guest is redirected to login', function (): void {
    $this->get(route('reports.pipeline'))->assertRedirect(route('login'));
});
