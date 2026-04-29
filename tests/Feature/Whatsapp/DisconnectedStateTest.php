<?php

declare(strict_types=1);

use App\Livewire\Whatsapp\ConnectionPanel;
use App\Livewire\Whatsapp\Conversation;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Message;
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

    $this->connection = app(ConnectionService::class)->connect($this->seller);
    app(ConnectionService::class)->markStatus($this->connection, WhatsappConnectionStatus::CONNECTED, '+5511999999999');
    $this->connection->refresh();
});

it('webhook flips status to disconnected', function (): void {
    $this->postJson(route('webhooks.evolution', ['user' => $this->seller->id]), [
        'event' => 'CONNECTION_UPDATE',
        'data' => ['state' => 'close'],
    ], ['X-Webhook-Secret' => $this->connection->webhook_secret])->assertOk();

    expect($this->connection->fresh()->status->slug)->toBe(WhatsappConnectionStatus::DISCONNECTED);
});

it('send returns validation error when disconnected', function (): void {
    app(ConnectionService::class)->markStatus($this->connection->fresh(), WhatsappConnectionStatus::DISCONNECTED);

    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['phone' => '+5511888888888']);

    Livewire::actingAs($this->seller)
        ->test(Conversation::class, ['lead' => $lead])
        ->set('body', 'Will block')
        ->call('send')
        ->assertHasErrors('body');

    expect(Message::query()->withoutGlobalScopes()->where('lead_id', $lead->id)->count())->toBe(0);
});

it('disconnected banner present in conversation when status disconnected', function (): void {
    app(ConnectionService::class)->markStatus($this->connection->fresh(), WhatsappConnectionStatus::DISCONNECTED);

    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['phone' => '+5511888888888']);

    Livewire::actingAs($this->seller)
        ->test(Conversation::class, ['lead' => $lead])
        ->assertSee('conversation-disconnected-banner', escape: false)
        ->assertSee('WhatsApp disconnected', escape: false);
});

it('connection panel shows disconnected banner with reconnect button', function (): void {
    app(ConnectionService::class)->markStatus($this->connection->fresh(), WhatsappConnectionStatus::DISCONNECTED);

    Livewire::actingAs($this->seller)
        ->test(ConnectionPanel::class)
        ->assertSee('whatsapp-disconnected-banner', escape: false)
        ->assertSee('whatsapp-reconnect', escape: false);
});
