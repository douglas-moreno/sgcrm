<?php

declare(strict_types=1);

namespace App\Services\Evolution;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class EvolutionClient
{
    public function __construct(
        private string $baseUrl,
        private string $apiKey,
        private int $timeout = 10,
        private int $retry = 2,
    ) {}

    public function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders(['apikey' => $this->apiKey])
            ->timeout($this->timeout)
            ->retry($this->retry, 200);
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Create an Evolution instance for a user.
     *
     * @param  array<string, mixed>  $extras
     */
    public function createInstance(string $instanceName, ?string $webhookUrl = null, ?string $webhookSecret = null, array $extras = []): Response
    {
        $payload = array_merge([
            'instanceName' => $instanceName,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
        ], $extras);

        if ($webhookUrl !== null) {
            $webhook = [
                'url' => $webhookUrl,
                'webhookByEvents' => false,
                'events' => [
                    'CONNECTION_UPDATE',
                    'MESSAGES_UPSERT',
                    'MESSAGES_UPDATE',
                ],
            ];

            if ($webhookSecret !== null) {
                $webhook['headers'] = ['X-Webhook-Secret' => $webhookSecret];
            }

            $payload['webhook'] = $webhook;
        }

        return $this->request()->post('/instance/create', $payload);
    }

    public function fetchQr(string $instanceName): Response
    {
        return $this->request()->get("/instance/connect/{$instanceName}");
    }

    public function fetchStatus(string $instanceName): Response
    {
        return $this->request()->get("/instance/connectionState/{$instanceName}");
    }

    public function sendTextMessage(string $instanceName, string $phoneNumber, string $body): Response
    {
        return $this->request()->post("/message/sendText/{$instanceName}", [
            'number' => $phoneNumber,
            'text' => $body,
        ]);
    }

    public function disconnect(string $instanceName): Response
    {
        return $this->request()->delete("/instance/logout/{$instanceName}");
    }
}
