<?php

declare(strict_types=1);

use App\Livewire\Kanban\Board;
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
    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
    $this->alice = User::factory()->salesperson()->forCompany($this->company)->create(['name' => 'Alice']);
    $this->bob = User::factory()->salesperson()->forCompany($this->company)->create(['name' => 'Bob']);
});

it('filter scopes board to selected Salesperson', function (): void {
    $aliceLead = Lead::factory()->forCompany($this->company)->ownedBy($this->alice)->create();
    $bobLead = Lead::factory()->forCompany($this->company)->ownedBy($this->bob)->create();

    Deal::factory()->forLead($aliceLead)->create(['title' => 'Alice Deal']);
    Deal::factory()->forLead($bobLead)->create(['title' => 'Bob Deal']);

    Livewire::actingAs($this->owner)
        ->test(Board::class)
        ->set('ownerFilter', $this->alice->id)
        ->assertSee('Alice Deal')
        ->assertDontSee('Bob Deal');
});

it('null filter returns all company deals', function (): void {
    $aliceLead = Lead::factory()->forCompany($this->company)->ownedBy($this->alice)->create();
    $bobLead = Lead::factory()->forCompany($this->company)->ownedBy($this->bob)->create();

    Deal::factory()->forLead($aliceLead)->create(['title' => 'Alice Deal']);
    Deal::factory()->forLead($bobLead)->create(['title' => 'Bob Deal']);

    Livewire::actingAs($this->owner)
        ->test(Board::class)
        ->set('ownerFilter', null)
        ->assertSee('Alice Deal')
        ->assertSee('Bob Deal');
});

it('non-Owner does not see filter and cannot set it', function (): void {
    Livewire::actingAs($this->alice)
        ->test(Board::class)
        ->assertSet('ownerFilter', null)
        ->assertDontSee('owner-filter')
        ->call('setOwnerFilter', $this->bob->id)
        ->assertHasErrors('ownerFilter');
});

it('mount strips ownerFilter for non-owners', function (): void {
    Livewire::actingAs($this->alice)
        ->withQueryParams(['owner' => $this->bob->id])
        ->test(Board::class)
        ->assertSet('ownerFilter', null);
});

it('owner filter state persists in URL', function (): void {
    $aliceLead = Lead::factory()->forCompany($this->company)->ownedBy($this->alice)->create();
    Deal::factory()->forLead($aliceLead)->create(['title' => 'Alice Deal']);

    Livewire::actingAs($this->owner)
        ->withQueryParams(['owner' => $this->alice->id])
        ->test(Board::class)
        ->assertSet('ownerFilter', $this->alice->id)
        ->assertSee('Alice Deal');
});
