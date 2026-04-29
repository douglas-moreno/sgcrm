<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Models\Lead;
use App\Models\Message;
use App\Models\MessageDirection;
use App\Models\MessageStatus;
use App\Models\MessageType;
use App\Models\User;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionStatus;
use App\Services\Whatsapp\ConnectionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

final class EvolutionWebhookController
{
    public function __construct(private ConnectionService $service) {}

    public function __invoke(Request $request, int $user, ConnectionService $service): Response
    {
        $userModel = User::query()->find($user);

        if ($userModel === null) {
            return response('user not found', 404);
        }

        $connection = WhatsappConnection::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user)
            ->first();

        if ($connection === null) {
            return response('no connection', 404);
        }

        $secret = $request->header('X-Webhook-Secret');

        if ($connection->webhook_secret === null || ! hash_equals((string) $connection->webhook_secret, (string) $secret)) {
            return response('invalid signature', 401);
        }

        $event = (string) $request->input('event');
        $data = (array) $request->input('data', []);

        switch ($event) {
            case 'CONNECTION_UPDATE':
                $this->handleConnectionUpdate($connection, $data, $service);
                break;
            case 'MESSAGES_UPSERT':
                $this->handleInboundMessage($connection, $data);
                break;
            case 'MESSAGES_UPDATE':
                $this->handleMessageStatus($connection, $data);
                break;
            default:
                Log::info('evolution.webhook.ignored', ['event' => $event]);
        }

        return response('ok', 200);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleConnectionUpdate(WhatsappConnection $connection, array $data, ConnectionService $service): void
    {
        $state = (string) ($data['state'] ?? $data['status'] ?? '');
        $phone = $data['phoneNumber'] ?? $data['number'] ?? null;

        $slug = match ($state) {
            'open', 'connected' => WhatsappConnectionStatus::CONNECTED,
            'connecting' => WhatsappConnectionStatus::PENDING,
            'close', 'disconnected' => WhatsappConnectionStatus::DISCONNECTED,
            default => null,
        };

        if ($slug !== null) {
            $service->markStatus($connection, $slug, $phone);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleInboundMessage(WhatsappConnection $connection, array $data): void
    {
        $externalId = (string) ($data['key']['id'] ?? $data['id'] ?? '');

        if ($externalId === '') {
            return;
        }

        $existing = Message::query()
            ->withoutGlobalScopes()
            ->where('external_id', $externalId)
            ->first();

        if ($existing !== null) {
            return;
        }

        $remote = (string) ($data['key']['remoteJid'] ?? $data['from'] ?? '');
        $phone = $this->normalizePhone($remote);
        $body = (string) ($data['message']['conversation'] ?? $data['body'] ?? '');

        $lead = $this->findLeadByPhone($connection, $phone);

        if ($lead === null) {
            Log::info('evolution.webhook.lead_not_found', [
                'phone' => $phone,
                'connection_id' => $connection->id,
            ]);

            return;
        }

        $direction = MessageDirection::query()->where('slug', MessageDirection::INBOUND)->firstOrFail();
        $status = MessageStatus::query()->where('slug', MessageStatus::DELIVERED)->firstOrFail();
        $type = MessageType::query()->where('slug', MessageType::TEXT)->firstOrFail();

        Message::create([
            'company_id' => $connection->company_id,
            'whatsapp_connection_id' => $connection->id,
            'lead_id' => $lead->id,
            'user_id' => null,
            'direction_id' => $direction->id,
            'status_id' => $status->id,
            'message_type_id' => $type->id,
            'external_id' => $externalId,
            'body' => $body,
            'delivered_at' => Carbon::now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleMessageStatus(WhatsappConnection $connection, array $data): void
    {
        $externalId = (string) ($data['key']['id'] ?? $data['id'] ?? '');
        $status = (string) ($data['status'] ?? '');

        if ($externalId === '' || $status === '') {
            return;
        }

        $message = Message::query()
            ->withoutGlobalScopes()
            ->where('external_id', $externalId)
            ->first();

        if ($message === null) {
            return;
        }

        $slug = match ($status) {
            'DELIVERY_ACK', 'delivered' => MessageStatus::DELIVERED,
            'READ', 'read' => MessageStatus::READ,
            'FAILED', 'failed' => MessageStatus::FAILED,
            default => null,
        };

        if ($slug === null) {
            return;
        }

        $statusId = MessageStatus::query()->where('slug', $slug)->value('id');

        $payload = ['status_id' => $statusId];

        if ($slug === MessageStatus::DELIVERED) {
            $payload['delivered_at'] = Carbon::now();
        }
        if ($slug === MessageStatus::READ) {
            $payload['read_at'] = Carbon::now();
        }
        if ($slug === MessageStatus::FAILED) {
            $payload['failed_at'] = Carbon::now();
        }

        $message->update($payload);
    }

    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        return $digits;
    }

    private function findLeadByPhone(WhatsappConnection $connection, string $phone): ?Lead
    {
        if ($phone === '') {
            return null;
        }

        return Lead::query()
            ->withoutGlobalScopes()
            ->where('company_id', $connection->company_id)
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', ''), '(', '') LIKE ?", ['%'.$phone.'%'])
            ->first();
    }
}
