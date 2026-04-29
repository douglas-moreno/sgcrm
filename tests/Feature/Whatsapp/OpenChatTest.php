<?php

declare(strict_types=1);

use App\Livewire\Deals\Show;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsappConnectionStatus;
use App\Services\Whatsapp\ConnectionService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\ActivityTypeSeeder::class);
    $this->seed(Database\Seeders\WhatsappConnectionStatusSeeder::class);
    $this->seed(Database\Seeders\MessageDirectionSeeder::class);
    $this->seed(Database\Seeders\MessageStatusSeeder::class);
    $this->seed(Database\Seeders\MessageTypeSeeder::class);

    config()->set('services.evolution.url', 'http://evolution.test');
    config()->set('services.evolution.key', 'k');

    Http::fake(['http://evolution.test/*' => Http::response(['ok' => true], 200)]);

    $this->company = Company::factory()->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
});

it('chat link visible only when connected and lead has phone', function (): void {
    $connection = app(ConnectionService::class)->connect($this->seller);
    app(ConnectionService::class)->markStatus($connection, WhatsappConnectionStatus::CONNECTED, '+5511999999999');

    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['phone' => '+5511888888888']);
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(Show::class, ['deal' => $deal])
        ->assertSee('chat-link', escape: false)
        ->assertDontSee('chat-blocked-disconnected', escape: false)
        ->assertDontSee('chat-blocked-no-phone', escape: false);
});

it('chat link hidden when WhatsApp disconnected', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['phone' => '+5511888888888']);
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(Show::class, ['deal' => $deal])
        ->assertSee('chat-blocked-disconnected', escape: false)
        ->assertDontSee('chat-link', escape: false);
});

it('chat link hidden when lead has no phone', function (): void {
    $connection = app(ConnectionService::class)->connect($this->seller);
    app(ConnectionService::class)->markStatus($connection, WhatsappConnectionStatus::CONNECTED, '+5511999999999');

    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['phone' => null]);
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(Show::class, ['deal' => $deal])
        ->assertSee('chat-blocked-no-phone', escape: false)
        ->assertDontSee('chat-link', escape: false);
});

it('chat URL contains lead and deal params', function (): void {
    $connection = app(ConnectionService::class)->connect($this->seller);
    app(ConnectionService::class)->markStatus($connection, WhatsappConnectionStatus::CONNECTED, '+5511999999999');

    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['phone' => '+5511888888888']);
    $deal = Deal::factory()->forLead($lead)->create();

    $expected = route('whatsapp.conversation', ['lead' => $lead->id, 'deal' => $deal->id]);

    Livewire::actingAs($this->seller)
        ->test(Show::class, ['deal' => $deal])
        ->assertSee($expected, escape: false);
});
