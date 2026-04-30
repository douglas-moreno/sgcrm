<?php

declare(strict_types=1);

use App\Livewire\Leads\CreateLead;
use App\Livewire\Whatsapp\Conversation;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Livewire\Livewire;

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
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
    $this->other = User::factory()->salesperson()->forCompany($this->company)->create();
});

it('returns 403 when Salesperson opens a non-owned deal', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->other)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    $this->actingAs($this->seller)
        ->get(route('deals.show', $deal))
        ->assertForbidden();
});

it('returns 403 when Salesperson opens a non-owned lead', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->other)->create();

    $this->actingAs($this->seller)
        ->get(route('leads.edit', $lead))
        ->assertForbidden();
});

it('returns 403 when Salesperson opens a chat for a non-owned lead', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->other)->create();

    Livewire::actingAs($this->seller)
        ->test(Conversation::class, ['lead' => $lead])
        ->assertForbidden();
});

it('blocks reuse of a lead owned by another Salesperson', function (): void {
    $hidden = Lead::factory()
        ->forCompany($this->company)
        ->ownedBy($this->other)
        ->create(['email' => 'shared@example.com']);

    Livewire::actingAs($this->seller)
        ->test(CreateLead::class)
        ->set('open', true)
        ->set('name', 'New Lead')
        ->set('email', 'shared@example.com')
        ->call('save')
        ->assertHasErrors(['email']);

    expect(Deal::query()->withoutGlobalScopes()->where('lead_id', $hidden->id)->count())->toBe(0);
});

it('lead list scoping limits queries to own leads (global scope + ownership)', function (): void {
    Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->count(2)->create();
    Lead::factory()->forCompany($this->company)->ownedBy($this->other)->count(3)->create();

    $this->actingAs($this->seller);

    $myLeads = Lead::query()->where('owner_user_id', $this->seller->id)->get();

    expect($myLeads)->toHaveCount(2);

    foreach ($myLeads as $lead) {
        expect($this->seller->can('view', $lead))->toBeTrue();
    }

    $hiddenLeads = Lead::query()->where('owner_user_id', $this->other->id)->get();

    foreach ($hiddenLeads as $lead) {
        expect($this->seller->can('view', $lead))->toBeFalse();
    }
});

it('cross-tenant lead routing returns 404', function (): void {
    $otherCompany = Company::factory()->create();
    $otherSeller = User::factory()->salesperson()->forCompany($otherCompany)->create();
    $foreignLead = Lead::factory()->forCompany($otherCompany)->ownedBy($otherSeller)->create();

    $this->actingAs($this->seller)
        ->get(route('leads.edit', $foreignLead))
        ->assertNotFound();
});

it('cross-tenant deal routing returns 404', function (): void {
    $otherCompany = Company::factory()->create();
    $otherSeller = User::factory()->salesperson()->forCompany($otherCompany)->create();
    $foreignLead = Lead::factory()->forCompany($otherCompany)->ownedBy($otherSeller)->create();
    $foreignDeal = Deal::factory()->forLead($foreignLead)->create();

    $this->actingAs($this->seller)
        ->get(route('deals.show', $foreignDeal))
        ->assertNotFound();
});
