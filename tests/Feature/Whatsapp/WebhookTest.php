<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Lead;
use App\Models\Message;
use App\Models\MessageDirection;
use App\Models\User;
use App\Models\WhatsappConnectionStatus;
use App\Services\Whatsapp\ConnectionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    Http::fake([
        'http://evolution.test/*' => Http::response(['ok' => true], 200),
    ]);

    $this->company = Company::factory()->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
    $this->connection = app(ConnectionService::class)->connect($this->seller);
});

it('rejects invalid signature with 401', function (): void {
    $this->postJson(route('webhooks.evolution', ['user' => $this->seller->id]), [
        'event' => 'MESSAGES_UPSERT',
        'data' => ['key' => ['id' => 'msg-1', 'remoteJid' => '5511999999999@s.whatsapp.net']],
    ], ['X-Webhook-Secret' => 'wrong-secret'])
        ->assertStatus(401);
});

it('persists new inbound message', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create([
        'phone' => '+55 11 99999-9999',
    ]);

    $response = $this->postJson(route('webhooks.evolution', ['user' => $this->seller->id]), [
        'event' => 'MESSAGES_UPSERT',
        'data' => [
            'key' => ['id' => 'msg-abc', 'remoteJid' => '5511999999999@s.whatsapp.net'],
            'message' => ['conversation' => 'Olá!'],
        ],
    ], ['X-Webhook-Secret' => $this->connection->webhook_secret]);

    $response->assertOk();

    $message = Message::query()->withoutGlobalScopes()->where('external_id', 'msg-abc')->firstOrFail();

    expect($message->lead_id)->toBe($lead->id)
        ->and($message->body)->toBe('Olá!')
        ->and($message->direction->slug)->toBe(MessageDirection::INBOUND)
        ->and($message->company_id)->toBe($this->company->id);
});

it('idempotent on duplicate external_id', function (): void {
    Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['phone' => '+5511999999999']);

    $payload = [
        'event' => 'MESSAGES_UPSERT',
        'data' => [
            'key' => ['id' => 'dup-1', 'remoteJid' => '5511999999999@s.whatsapp.net'],
            'message' => ['conversation' => 'first'],
        ],
    ];
    $headers = ['X-Webhook-Secret' => $this->connection->webhook_secret];

    $this->postJson(route('webhooks.evolution', ['user' => $this->seller->id]), $payload, $headers)->assertOk();
    $this->postJson(route('webhooks.evolution', ['user' => $this->seller->id]), $payload, $headers)->assertOk();

    expect(Message::query()->withoutGlobalScopes()->where('external_id', 'dup-1')->count())->toBe(1);
});

it('logs and drops when phone unknown (no lead created in MVP)', function (): void {
    Log::shouldReceive('info')->atLeast()->once()->with('evolution.webhook.lead_not_found', Mockery::any());

    $this->postJson(route('webhooks.evolution', ['user' => $this->seller->id]), [
        'event' => 'MESSAGES_UPSERT',
        'data' => [
            'key' => ['id' => 'unknown-1', 'remoteJid' => '5599888888888@s.whatsapp.net'],
            'message' => ['conversation' => 'no lead'],
        ],
    ], ['X-Webhook-Secret' => $this->connection->webhook_secret])->assertOk();

    expect(Message::query()->withoutGlobalScopes()->where('external_id', 'unknown-1')->exists())->toBeFalse();
});

it('CONNECTION_UPDATE webhook flips status', function (): void {
    $this->postJson(route('webhooks.evolution', ['user' => $this->seller->id]), [
        'event' => 'CONNECTION_UPDATE',
        'data' => ['state' => 'close'],
    ], ['X-Webhook-Secret' => $this->connection->webhook_secret])->assertOk();

    expect($this->connection->fresh()->status->slug)->toBe(WhatsappConnectionStatus::DISCONNECTED);
});

it('returns 404 when no connection for user', function (): void {
    $stranger = User::factory()->salesperson()->forCompany($this->company)->create();

    $this->postJson(route('webhooks.evolution', ['user' => $stranger->id]), [
        'event' => 'CONNECTION_UPDATE',
        'data' => ['state' => 'open'],
    ], ['X-Webhook-Secret' => 'whatever'])->assertNotFound();
});
