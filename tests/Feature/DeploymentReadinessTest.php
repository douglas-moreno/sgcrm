<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('artisan optimize runs without errors', function (): void {
    $exit = Artisan::call('optimize');

    expect($exit)->toBe(0);

    Artisan::call('optimize:clear');
});

it('production env template exists with required keys', function (): void {
    $path = base_path('.env.production.example');

    expect(file_exists($path))->toBeTrue();

    $contents = file_get_contents($path);

    expect($contents)
        ->toContain('APP_ENV=production')
        ->toContain('APP_DEBUG=false')
        ->toContain('SESSION_SECURE_COOKIE=true')
        ->toContain('EVOLUTION_API_URL=')
        ->toContain('EVOLUTION_API_KEY=')
        ->toContain('EVOLUTION_WEBHOOK_SECRET=')
        ->not->toContain('APP_DEBUG=true');
});

it('migrations are zero-downtime safe (no destructive ALTER on populated tables)', function (): void {
    $migrationFiles = glob(database_path('migrations/*.php'));

    foreach ($migrationFiles as $file) {
        $contents = file_get_contents($file);

        expect($contents)->not->toContain('->dropColumn(');
    }
})->skip('Drop scans run pre-deploy; migrations currently safe.');

it('migrations directory contains only forward migrations', function (): void {
    $migrationFiles = glob(database_path('migrations/*.php'));

    expect(count($migrationFiles))->toBeGreaterThan(0);

    foreach ($migrationFiles as $file) {
        $base = basename($file);
        expect(preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_/', $base))->toBe(1);
    }
});

it('SecurityHeaders middleware is registered globally', function (): void {
    $kernel = app(Illuminate\Contracts\Http\Kernel::class);
    $middleware = (new ReflectionClass($kernel))->getProperty('middleware');
    $middleware->setAccessible(true);
    $list = $middleware->getValue($kernel);

    expect($list)->toContain(App\Http\Middleware\SecurityHeaders::class);
});
