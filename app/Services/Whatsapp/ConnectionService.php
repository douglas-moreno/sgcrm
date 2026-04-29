<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Models\User;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionStatus;
use App\Services\Evolution\EvolutionClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class ConnectionService
{
    public function __construct(private EvolutionClient $client) {}

    public function instanceNameFor(User $user): string
    {
        return 'sgcrm-'.mb_substr(sha1((string) $user->id), 0, 16);
    }

    public function connect(User $user): WhatsappConnection
    {
        $statusId = WhatsappConnectionStatus::query()->where('slug', WhatsappConnectionStatus::PENDING)->value('id');
        $instanceName = $this->instanceNameFor($user);

        $connection = WhatsappConnection::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->first();

        $secret = $connection?->webhook_secret ?? Str::random(64);

        $webhookUrl = route('webhooks.evolution', ['user' => $user->id]);

        $response = $this->client->createInstance(
            instanceName: $instanceName,
            webhookUrl: $webhookUrl,
            webhookSecret: $secret,
        );

        $qrPath = $response->json('qrcode.base64') ?? $response->json('base64') ?? null;

        if ($connection === null) {
            $connection = WhatsappConnection::create([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'status_id' => $statusId,
                'instance_name' => $instanceName,
                'qr_code_path' => $qrPath,
                'webhook_secret' => $secret,
                'last_checked_at' => Carbon::now(),
            ]);
        } else {
            $connection->update([
                'status_id' => $statusId,
                'instance_name' => $instanceName,
                'qr_code_path' => $qrPath,
                'webhook_secret' => $secret,
                'last_checked_at' => Carbon::now(),
                'disconnected_at' => null,
            ]);
        }

        return $connection->fresh();
    }

    public function refreshQr(WhatsappConnection $connection): WhatsappConnection
    {
        $response = $this->client->fetchQr($connection->instance_name);
        $qrPath = $response->json('qrcode.base64') ?? $response->json('base64') ?? null;

        $connection->update([
            'qr_code_path' => $qrPath,
            'last_checked_at' => Carbon::now(),
        ]);

        return $connection->fresh();
    }

    public function disconnect(WhatsappConnection $connection): WhatsappConnection
    {
        $this->client->disconnect($connection->instance_name);

        $statusId = WhatsappConnectionStatus::query()->where('slug', WhatsappConnectionStatus::DISCONNECTED)->value('id');

        $connection->update([
            'status_id' => $statusId,
            'qr_code_path' => null,
            'disconnected_at' => Carbon::now(),
        ]);

        return $connection->fresh();
    }

    public function markStatus(WhatsappConnection $connection, string $statusSlug, ?string $phoneNumber = null): WhatsappConnection
    {
        $statusId = WhatsappConnectionStatus::query()->where('slug', $statusSlug)->value('id');

        if ($statusId === null) {
            return $connection;
        }

        $payload = [
            'status_id' => $statusId,
            'last_checked_at' => Carbon::now(),
        ];

        if ($statusSlug === WhatsappConnectionStatus::CONNECTED) {
            $payload['connected_at'] = Carbon::now();
            $payload['disconnected_at'] = null;
            $payload['qr_code_path'] = null;
            if ($phoneNumber !== null) {
                $payload['phone_number'] = $phoneNumber;
            }
        }

        if ($statusSlug === WhatsappConnectionStatus::DISCONNECTED) {
            $payload['disconnected_at'] = Carbon::now();
        }

        $connection->update($payload);

        return $connection->fresh();
    }
}
