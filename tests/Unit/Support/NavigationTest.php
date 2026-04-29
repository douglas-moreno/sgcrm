<?php

declare(strict_types=1);

use App\Support\Navigation;

it('returns no items for guest users', function (): void {
    expect(Navigation::items(null))->toBe([]);
});

it('returns Salesperson items without Reports or Team', function (): void {
    $items = Navigation::items(Navigation::ROLE_SALESPERSON);

    $keys = array_column($items, 'key');

    expect($keys)
        ->toContain('kanban')
        ->toContain('leads')
        ->toContain('settings')
        ->not->toContain('reports')
        ->not->toContain('team');
});

it('returns full nav for Business Owner', function (): void {
    $items = Navigation::items(Navigation::ROLE_BUSINESS_OWNER);

    $keys = array_column($items, 'key');

    expect($keys)
        ->toContain('kanban')
        ->toContain('leads')
        ->toContain('reports')
        ->toContain('team')
        ->toContain('settings');
});

it('returns items shaped with key/label/route/icon', function (): void {
    $items = Navigation::items(Navigation::ROLE_BUSINESS_OWNER);

    foreach ($items as $item) {
        expect($item)
            ->toHaveKeys(['key', 'label', 'route', 'icon']);
    }
});
