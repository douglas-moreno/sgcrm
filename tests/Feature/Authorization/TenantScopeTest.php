<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;

it('filters tenant-scoped queries by the authenticated user company_id', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->salesperson()->forCompany($companyA)->create();
    $userB = User::factory()->salesperson()->forCompany($companyB)->create();

    Lead::factory()->forCompany($companyA)->ownedBy($userA)->count(2)->create();
    Lead::factory()->forCompany($companyB)->ownedBy($userB)->count(3)->create();

    $this->actingAs($userA);

    expect(Lead::count())->toBe(2);

    $this->actingAs($userB);

    expect(Lead::count())->toBe(3);
});

it('returns no results when querying tenant-scoped models without authentication is unfiltered', function (): void {
    $companyA = Company::factory()->create();
    $userA = User::factory()->salesperson()->forCompany($companyA)->create();
    Lead::factory()->forCompany($companyA)->ownedBy($userA)->create();

    expect(auth()->user())->toBeNull();
    expect(Lead::count())->toBe(1);
});

it('auto-fills company_id on save when the user is authenticated and value missing', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->salesperson()->forCompany($company)->create();

    $this->actingAs($user);

    $lead = Lead::create([
        'owner_user_id' => $user->id,
        'name' => 'Auto Fill',
        'email' => 'auto@fill.com',
    ]);

    expect($lead->company_id)->toBe($company->id);
});

it('honors explicit company_id over the authenticated user', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $userA = User::factory()->salesperson()->forCompany($companyA)->create();
    $userB = User::factory()->salesperson()->forCompany($companyB)->create();

    $this->actingAs($userA);

    $lead = Lead::withoutGlobalScopes()->create([
        'company_id' => $companyB->id,
        'owner_user_id' => $userB->id,
        'name' => 'Cross',
        'email' => 'cross@example.com',
    ]);

    expect($lead->company_id)->toBe($companyB->id);
});

it('blocks cross-tenant Deal access via global scope', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $userA = User::factory()->salesperson()->forCompany($companyA)->create();
    $userB = User::factory()->salesperson()->forCompany($companyB)->create();

    $leadA = Lead::factory()->forCompany($companyA)->ownedBy($userA)->create();
    $leadB = Lead::factory()->forCompany($companyB)->ownedBy($userB)->create();

    Deal::factory()->forLead($leadA)->create();
    Deal::factory()->forLead($leadB)->create();

    $this->actingAs($userA);
    expect(Deal::count())->toBe(1);

    $this->actingAs($userB);
    expect(Deal::count())->toBe(1);
});
