<?php

declare(strict_types=1);

use App\Livewire\Leads\LeadList;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);

    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();

    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create([
        'name' => 'Owner Person',
    ]);
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create([
        'name' => 'Alice Seller',
    ]);
    $this->otherSeller = User::factory()->salesperson()->forCompany($this->company)->create([
        'name' => 'Bob Seller',
    ]);
    $this->foreignSeller = User::factory()->salesperson()->forCompany($this->otherCompany)->create([
        'name' => 'Foreign Seller',
    ]);
});

it('renders the leads index route for authenticated users', function (): void {
    $this->actingAs($this->seller)
        ->get(route('leads.index'))
        ->assertOk()
        ->assertSee('Leads');
});

it('Owner sees company leads with owner names and deal counts', function (): void {
    $alpha = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'name' => 'Alpha Lead',
        'email' => 'alpha@example.test',
        'phone' => '111-1111',
    ]);
    Deal::factory()->forLead($alpha)->count(2)->create();

    $bravo = Lead::factory()->forCompany($this->company)->ownedBy($this->otherSeller)->create([
        'name' => 'Bravo Lead',
        'email' => 'bravo@example.test',
    ]);
    Deal::factory()->forLead($bravo)->create();

    Lead::factory()->forCompany($this->otherCompany)->ownedBy($this->foreignSeller)->create([
        'name' => 'Foreign Lead',
    ]);

    Livewire::actingAs($this->owner)
        ->test(LeadList::class)
        ->assertSee('Alpha Lead')
        ->assertSee('Bravo Lead')
        ->assertDontSee('Foreign Lead')
        ->assertSee('Alice Seller')
        ->assertSee('Bob Seller')
        ->assertSee('2')
        ->assertSee('1');
});

it('Salesperson sees only owned leads', function (): void {
    Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'name' => 'My Lead',
    ]);
    Lead::factory()->forCompany($this->company)->ownedBy($this->otherSeller)->create([
        'name' => 'Hidden Lead',
    ]);

    Livewire::actingAs($this->seller)
        ->test(LeadList::class)
        ->assertSee('My Lead')
        ->assertDontSee('Hidden Lead');
});

it('search filters leads by lead fields and owner name', function (): void {
    Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'name' => 'Northwind',
        'email' => 'northwind@example.test',
        'phone' => '555-0101',
    ]);
    Lead::factory()->forCompany($this->company)->ownedBy($this->otherSeller)->create([
        'name' => 'Contoso',
        'email' => 'contoso@example.test',
    ]);

    Livewire::actingAs($this->owner)
        ->test(LeadList::class)
        ->set('search', '555-0101')
        ->assertSee('Northwind')
        ->assertDontSee('Contoso');

    Livewire::actingAs($this->owner)
        ->test(LeadList::class)
        ->set('search', 'Bob Seller')
        ->assertSee('Contoso')
        ->assertDontSee('Northwind');
});
