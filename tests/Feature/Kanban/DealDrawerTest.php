<?php

declare(strict_types=1);

use App\Livewire\Kanban\Board;
use App\Models\Company;
use App\Models\Deal;
use App\Models\DealNote;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsappConnection;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\ActivityTypeSeeder::class);

    $this->company = Company::factory()->create();
    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
    $this->otherSeller = User::factory()->salesperson()->forCompany($this->company)->create();
});

it('opens the deal drawer with overview details', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'name' => 'Acme Contact',
        'email' => 'lead@example.com',
        'phone' => '5511999999999',
    ]);
    $deal = Deal::factory()->forLead($lead)->create([
        'title' => 'Drawer Deal',
        'value' => 4200,
    ]);

    $component = Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('openDeal', $deal->id)
        ->assertSet('selectedDealId', $deal->id)
        ->assertSet('activeDrawerTab', 'overview');

    expect($component->html())
        ->toContain('data-testid="deal-drawer"')
        ->toContain('Drawer Deal')
        ->toContain('Acme Contact')
        ->toContain('lead@example.com')
        ->toContain('data-testid="deal-drawer-overview"');
});

it('renders notes inside the drawer notes tab', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    DealNote::factory()->create([
        'deal_id' => $deal->id,
        'user_id' => $this->seller->id,
        'body' => 'Drawer note body',
    ]);

    $component = Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('openDeal', $deal->id, 'notes')
        ->assertSet('activeDrawerTab', 'notes');

    expect($component->html())
        ->toContain('data-testid="deal-drawer-notes"')
        ->toContain('Drawer note body');
});

it('renders whatsapp messages inside the drawer whatsapp tab', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'phone' => '5511888888888',
    ]);
    $deal = Deal::factory()->forLead($lead)->create();

    $connection = WhatsappConnection::factory()
        ->connected()
        ->create([
            'user_id' => $this->seller->id,
            'company_id' => $this->company->id,
        ]);

    Message::factory()->create([
        'company_id' => $this->company->id,
        'whatsapp_connection_id' => $connection->id,
        'lead_id' => $lead->id,
        'deal_id' => $deal->id,
        'user_id' => $this->seller->id,
        'body' => 'Drawer WhatsApp message',
    ]);

    $component = Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('openDeal', $deal->id, 'whatsapp')
        ->assertSet('activeDrawerTab', 'whatsapp');

    expect($component->html())
        ->toContain('data-testid="deal-drawer-whatsapp"')
        ->toContain('Drawer WhatsApp message')
        ->toContain('WhatsApp conversation');
});

it('closes the drawer and resets the active tab', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(Board::class)
        ->call('openDeal', $deal->id, 'notes')
        ->call('closeDeal')
        ->assertSet('selectedDealId', null)
        ->assertSet('activeDrawerTab', 'overview');
});

it('forbids opening a deal drawer for a deal owned by another salesperson', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->otherSeller)
        ->test(Board::class)
        ->call('openDeal', $deal->id)
        ->assertForbidden();
});
