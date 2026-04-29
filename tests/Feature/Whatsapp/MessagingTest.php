<?php

declare(strict_types=1);

use App\Livewire\Whatsapp\Conversation;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Message;
use App\Models\MessageDirection;
use App\Models\MessageStatus;
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

    $this->company = Company::factory()->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
    $this->otherSeller = User::factory()->salesperson()->forCompany($this->company)->create();

    Http::fake([
        'http://evolution.test/instance/create' => Http::response(['qrcode' => ['base64' => 'q']], 200),
        'http://evolution.test/instance/logout/*' => Http::response(['ok' => true], 200),
        'http://evolution.test/message/sendText/*' => Http::response(['key' => ['id' => 'remote-1']], 200),
    ]);

    $this->connection = app(ConnectionService::class)->connect($this->seller);
    app(ConnectionService::class)->markStatus($this->connection, WhatsappConnectionStatus::CONNECTED, '+5511999999999');
    $this->connection->refresh();
});

it('send persists message with pending then sent after API ack', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['phone' => '+5511888888888']);
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(Conversation::class, ['lead' => $lead, 'deal' => $deal->id])
        ->set('body', 'Hello there')
        ->call('send')
        ->assertHasNoErrors()
        ->assertDispatched('message-sent');

    $message = Message::query()->withoutGlobalScopes()->where('lead_id', $lead->id)->latest('id')->firstOrFail();

    expect($message->body)->toBe('Hello there')
        ->and($message->status->slug)->toBe(MessageStatus::SENT)
        ->and($message->direction->slug)->toBe(MessageDirection::OUTBOUND)
        ->and($message->external_id)->toBe('remote-1')
        ->and($message->sent_at)->not->toBeNull();
});

it('inbound message via webhook appears in conversation', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['phone' => '+5511888888888']);

    $this->postJson(route('webhooks.evolution', ['user' => $this->seller->id]), [
        'event' => 'MESSAGES_UPSERT',
        'data' => [
            'key' => ['id' => 'inb-1', 'remoteJid' => '5511888888888@s.whatsapp.net'],
            'message' => ['conversation' => 'reply from lead'],
        ],
    ], ['X-Webhook-Secret' => $this->connection->webhook_secret])->assertOk();

    Livewire::actingAs($this->seller)
        ->test(Conversation::class, ['lead' => $lead])
        ->assertSee('reply from lead');
});

it('Salesperson cannot view conversation of lead owned by another', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->otherSeller)->create(['phone' => '+5511777777777']);

    Livewire::actingAs($this->seller)
        ->test(Conversation::class, ['lead' => $lead])
        ->assertForbidden();
});

it('records message_sent activity when sending with deal context', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['phone' => '+5511888888888']);
    $deal = Deal::factory()->forLead($lead)->create();

    Livewire::actingAs($this->seller)
        ->test(Conversation::class, ['lead' => $lead, 'deal' => $deal->id])
        ->set('body', 'Tracked message')
        ->call('send');

    $typeId = ActivityType::where('slug', ActivityType::MESSAGE_SENT)->value('id');

    expect(Activity::query()->withoutGlobalScopes()->where('deal_id', $deal->id)->where('activity_type_id', $typeId)->exists())->toBeTrue();
});

it('updates outbound message via webhook MESSAGES_UPDATE', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['phone' => '+5511888888888']);

    Livewire::actingAs($this->seller)
        ->test(Conversation::class, ['lead' => $lead])
        ->set('body', 'track me')
        ->call('send');

    $message = Message::query()->withoutGlobalScopes()->where('lead_id', $lead->id)->latest('id')->firstOrFail();

    $this->postJson(route('webhooks.evolution', ['user' => $this->seller->id]), [
        'event' => 'MESSAGES_UPDATE',
        'data' => ['key' => ['id' => $message->external_id], 'status' => 'READ'],
    ], ['X-Webhook-Secret' => $this->connection->webhook_secret])->assertOk();

    $message->refresh();
    expect($message->status->slug)->toBe(MessageStatus::READ)
        ->and($message->read_at)->not->toBeNull();
});

it('messages sorted oldest-first', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create(['phone' => '+5511888888888']);
    $direction = MessageDirection::query()->where('slug', MessageDirection::OUTBOUND)->firstOrFail();
    $status = MessageStatus::query()->where('slug', MessageStatus::SENT)->firstOrFail();
    $type = App\Models\MessageType::query()->where('slug', App\Models\MessageType::TEXT)->firstOrFail();

    foreach (['first', 'second', 'third'] as $i => $body) {
        Message::create([
            'company_id' => $this->company->id,
            'whatsapp_connection_id' => $this->connection->id,
            'lead_id' => $lead->id,
            'user_id' => $this->seller->id,
            'direction_id' => $direction->id,
            'status_id' => $status->id,
            'message_type_id' => $type->id,
            'body' => $body,
            'created_at' => now()->subMinutes(10 - $i),
        ]);
    }

    $component = Livewire::actingAs($this->seller)->test(Conversation::class, ['lead' => $lead]);

    $bodies = $component->instance()->messageList->pluck('body')->all();

    expect($bodies)->toBe(['first', 'second', 'third']);
});
