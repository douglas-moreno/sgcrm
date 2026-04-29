<?php

declare(strict_types=1);

use App\Services\Activity\ActivityRecorder;
use App\Services\Evolution\EvolutionClient;

uses(Tests\TestCase::class);

it('resolves the EvolutionClient as a singleton from the container', function (): void {
    config()->set('services.evolution', [
        'url' => 'http://evolution.test',
        'key' => 'k',
        'webhook_secret' => 's',
        'timeout' => 7,
        'retry' => 3,
    ]);

    $first = app(EvolutionClient::class);
    $second = app(EvolutionClient::class);

    expect($first)
        ->toBeInstanceOf(EvolutionClient::class)
        ->and($first->baseUrl())->toBe('http://evolution.test')
        ->and($second)->toBe($first);
});

it('resolves the ActivityRecorder as a singleton from the container', function (): void {
    $first = app(ActivityRecorder::class);
    $second = app(ActivityRecorder::class);

    expect($first)
        ->toBeInstanceOf(ActivityRecorder::class)
        ->and($second)->toBe($first);
});
