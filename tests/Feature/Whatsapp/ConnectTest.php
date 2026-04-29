<?php

declare(strict_types=1);

use App\Livewire\Whatsapp\ConnectionPanel;
use App\Models\Company;
use App\Models\User;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionStatus;
use App\Services\Whatsapp\ConnectionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\WhatsappConnectionStatusSeeder::class);

    $this->company = Company::factory()->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();

    config()->set('services.evolution.url', 'http://evolution.test');
    config()->set('services.evolution.key', 'test-key');
});

it('Connect provisions instance and persists pending status', function (): void {
    Http::fake([
        'http://evolution.test/instance/create' => Http::response(['qrcode' => ['base64' => 'data:image/png;base64,abc']], 200),
    ]);

    Livewire::actingAs($this->seller)
        ->test(ConnectionPanel::class)
        ->call('connect')
        ->assertDispatched('whatsapp-connected');

    $connection = WhatsappConnection::query()->where('user_id', $this->seller->id)->firstOrFail();

    expect($connection->status->slug)->toBe(WhatsappConnectionStatus::PENDING)
        ->and($connection->qr_code_path)->toBe('data:image/png;base64,abc')
        ->and($connection->webhook_secret)->not->toBeEmpty()
        ->and($connection->instance_name)->toStartWith('sgcrm-');
});

it('renders QR code when API returns one', function (): void {
    Http::fake([
        'http://evolution.test/instance/create' => Http::response(['qrcode' => ['base64' => 'data:image/png;base64,xyz']], 200),
    ]);

    Livewire::actingAs($this->seller)
        ->test(ConnectionPanel::class)
        ->call('connect')
        ->assertSee('data:image/png;base64,xyz', escape: false);
});

it('webhook transitions status to connected', function (): void {
    Http::fake([
        'http://evolution.test/*' => Http::response(['ok' => true], 200),
    ]);

    $service = app(ConnectionService::class);
    $connection = $service->connect($this->seller);

    $this->postJson(route('webhooks.evolution', ['user' => $this->seller->id]), [
        'event' => 'CONNECTION_UPDATE',
        'data' => ['state' => 'open', 'phoneNumber' => '+5511999999999'],
    ], ['X-Webhook-Secret' => $connection->webhook_secret])
        ->assertOk();

    $connection->refresh();

    expect($connection->status->slug)->toBe(WhatsappConnectionStatus::CONNECTED)
        ->and($connection->phone_number)->toBe('+5511999999999')
        ->and($connection->connected_at)->not->toBeNull();
});

it('disconnect clears connection state', function (): void {
    Http::fake([
        'http://evolution.test/*' => Http::response(['ok' => true], 200),
    ]);

    $service = app(ConnectionService::class);
    $connection = $service->connect($this->seller);

    Livewire::actingAs($this->seller)
        ->test(ConnectionPanel::class)
        ->call('disconnect')
        ->assertDispatched('whatsapp-disconnected');

    $connection->refresh();

    expect($connection->status->slug)->toBe(WhatsappConnectionStatus::DISCONNECTED)
        ->and($connection->qr_code_path)->toBeNull()
        ->and($connection->disconnected_at)->not->toBeNull();
});

it('enforces one connection per user via DB unique constraint', function (): void {
    Http::fake([
        'http://evolution.test/*' => Http::response(['ok' => true], 200),
    ]);

    $service = app(ConnectionService::class);
    $first = $service->connect($this->seller);

    $second = $service->connect($this->seller);

    expect($second->id)->toBe($first->id);

    expect(fn () => WhatsappConnection::query()->withoutGlobalScopes()->create([
        'user_id' => $this->seller->id,
        'company_id' => $this->company->id,
        'status_id' => WhatsappConnectionStatus::query()->where('slug', WhatsappConnectionStatus::PENDING)->value('id'),
        'instance_name' => 'sgcrm-other',
    ]))->toThrow(QueryException::class);
});
