<?php

declare(strict_types=1);

it('resolves Evolution API config keys', function (): void {
    config()->set('services.evolution', [
        'url' => 'http://evolution.test',
        'key' => 'test-key',
        'webhook_secret' => 'shh',
        'timeout' => 5,
        'retry' => 1,
    ]);

    expect(config('services.evolution.url'))->toBe('http://evolution.test');
    expect(config('services.evolution.key'))->toBe('test-key');
    expect(config('services.evolution.webhook_secret'))->toBe('shh');
    expect(config('services.evolution.timeout'))->toBe(5);
    expect(config('services.evolution.retry'))->toBe(1);
});

it('declares Evolution config keys via the services config file', function (): void {
    $services = require base_path('config/services.php');

    expect($services)->toHaveKey('evolution');
    expect($services['evolution'])
        ->toHaveKeys(['url', 'key', 'webhook_secret', 'timeout', 'retry']);
});

it('exposes the expected core config defaults', function (): void {
    expect(config('app.name'))->toBeString();
    expect(config('database.default'))->toBeString();
    expect(config('mail.default'))->toBeString();
});
