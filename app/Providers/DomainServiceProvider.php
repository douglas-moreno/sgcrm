<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Activity\ActivityRecorder;
use App\Services\Evolution\EvolutionClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EvolutionClient::class, function (Application $app): EvolutionClient {
            $config = $app['config']->get('services.evolution', []);

            return new EvolutionClient(
                baseUrl: (string) ($config['url'] ?? ''),
                apiKey: (string) ($config['key'] ?? ''),
                timeout: (int) ($config['timeout'] ?? 10),
                retry: (int) ($config['retry'] ?? 2),
            );
        });

        $this->app->singleton(ActivityRecorder::class);
    }
}
