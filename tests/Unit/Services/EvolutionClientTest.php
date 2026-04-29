<?php

declare(strict_types=1);

use App\Services\Evolution\EvolutionClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->client = new EvolutionClient(
        baseUrl: 'http://evolution.test',
        apiKey: 'test-api-key',
        timeout: 5,
        retry: 1,
    );
});

it('createInstance posts to /instance/create with apikey header and payload', function (): void {
    Http::fake([
        'http://evolution.test/instance/create' => Http::response(['ok' => true], 200),
    ]);

    $response = $this->client->createInstance(
        instanceName: 'sgcrm-abc',
        webhookUrl: 'http://app.test/webhooks/evolution/42',
        webhookSecret: 'secret-xyz',
    );

    expect($response->successful())->toBeTrue();

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'http://evolution.test/instance/create'
            && $request->method() === 'POST'
            && $request->header('apikey')[0] === 'test-api-key'
            && $request['instanceName'] === 'sgcrm-abc'
            && $request['qrcode'] === true
            && $request['integration'] === 'WHATSAPP-BAILEYS'
            && $request['webhook']['url'] === 'http://app.test/webhooks/evolution/42'
            && $request['webhook']['headers']['X-Webhook-Secret'] === 'secret-xyz'
            && in_array('CONNECTION_UPDATE', $request['webhook']['events'], true)
            && in_array('MESSAGES_UPSERT', $request['webhook']['events'], true);
    });
});

it('fetchQr GETs /instance/connect/{name}', function (): void {
    Http::fake([
        'http://evolution.test/instance/connect/sgcrm-abc' => Http::response(['base64' => 'iVBORw0K...'], 200),
    ]);

    $response = $this->client->fetchQr('sgcrm-abc');

    expect($response->json('base64'))->toBe('iVBORw0K...');

    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_ends_with($r->url(), '/instance/connect/sgcrm-abc'));
});

it('sendTextMessage posts to /message/sendText/{name}', function (): void {
    Http::fake([
        'http://evolution.test/message/sendText/sgcrm-abc' => Http::response(['key' => ['id' => 'msg-123']], 200),
    ]);

    $response = $this->client->sendTextMessage('sgcrm-abc', '+5511999999999', 'Hi there');

    expect($response->json('key.id'))->toBe('msg-123');

    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && str_ends_with($r->url(), '/message/sendText/sgcrm-abc')
        && $r['number'] === '+5511999999999'
        && $r['text'] === 'Hi there');
});

it('disconnect DELETEs /instance/logout/{name}', function (): void {
    Http::fake([
        'http://evolution.test/instance/logout/sgcrm-abc' => Http::response(['ok' => true], 200),
    ]);

    $response = $this->client->disconnect('sgcrm-abc');

    expect($response->successful())->toBeTrue();

    Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/instance/logout/sgcrm-abc'));
});

it('fetchStatus GETs /instance/connectionState/{name}', function (): void {
    Http::fake([
        'http://evolution.test/instance/connectionState/sgcrm-abc' => Http::response(['state' => 'open'], 200),
    ]);

    $response = $this->client->fetchStatus('sgcrm-abc');

    expect($response->json('state'))->toBe('open');

    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_ends_with($r->url(), '/instance/connectionState/sgcrm-abc'));
});

it('exposes baseUrl', function (): void {
    expect($this->client->baseUrl())->toBe('http://evolution.test');
});
