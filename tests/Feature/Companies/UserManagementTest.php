<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Livewire\Team\UserList;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);

    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();

    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create([
        'name' => 'Owner Person',
        'email' => 'owner@acme.test',
    ]);
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create([
        'name' => 'Alice Seller',
        'email' => 'alice@acme.test',
    ]);
    $this->otherSeller = User::factory()->salesperson()->forCompany($this->company)->create([
        'name' => 'Bob Seller',
        'email' => 'bob@acme.test',
    ]);
    $this->foreignUser = User::factory()->salesperson()->forCompany($this->otherCompany)->create([
        'name' => 'Foreign User',
        'email' => 'foreign@other.test',
    ]);
});

it('lists only users from the same company', function (): void {
    Livewire::actingAs($this->owner)
        ->test(UserList::class)
        ->assertSee('Alice Seller')
        ->assertSee('Bob Seller')
        ->assertSee('Owner Person')
        ->assertDontSee('Foreign User');
});

it('search filters by name or email', function (): void {
    Livewire::actingAs($this->owner)
        ->test(UserList::class)
        ->set('search', 'Alice')
        ->assertSee('Alice Seller')
        ->assertDontSee('Bob Seller');

    Livewire::actingAs($this->owner)
        ->test(UserList::class)
        ->set('search', 'bob@acme.test')
        ->assertSee('Bob Seller')
        ->assertDontSee('Alice Seller');
});

it('deactivate blocks login', function (): void {
    Livewire::actingAs($this->owner)
        ->test(UserList::class)
        ->call('deactivate', $this->seller->id);

    expect($this->seller->fresh()->is_active)->toBeFalse();

    Livewire::test(Login::class)
        ->set('email', $this->seller->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');
});

it('reactivate restores login', function (): void {
    $this->seller->update(['is_active' => false, 'password' => Hash::make('password')]);

    Livewire::actingAs($this->owner)
        ->test(UserList::class)
        ->call('reactivate', $this->seller->id);

    expect($this->seller->fresh()->is_active)->toBeTrue();

    Livewire::test(Login::class)
        ->set('email', $this->seller->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors();
});

it('owner cannot deactivate self', function (): void {
    Livewire::actingAs($this->owner)
        ->test(UserList::class)
        ->call('deactivate', $this->owner->id)
        ->assertForbidden();

    expect($this->owner->fresh()->is_active)->toBeTrue();
});

it('non-owner forbidden', function (): void {
    Livewire::actingAs($this->seller)
        ->test(UserList::class)
        ->assertForbidden();
});

it('owner cannot deactivate user from another company', function (): void {
    Livewire::actingAs($this->owner)
        ->test(UserList::class)
        ->call('deactivate', $this->foreignUser->id)
        ->assertForbidden();
});
