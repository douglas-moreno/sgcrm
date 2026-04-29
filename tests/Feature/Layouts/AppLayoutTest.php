<?php

declare(strict_types=1);

use App\Support\Navigation;
use Illuminate\Support\Facades\Blade;

it('renders the app layout shell with sidebar, topbar and main', function (): void {
    $html = Blade::render('<x-layouts.app page-title="Pipeline">CONTENT</x-layouts.app>');

    expect($html)
        ->toContain('data-layout="app"')
        ->toContain('data-testid="sidebar"')
        ->toContain('data-testid="topbar"')
        ->toContain('data-testid="app-main"')
        ->toContain('data-testid="mobile-drawer"')
        ->toContain('data-testid="mobile-toggle"')
        ->toContain('CONTENT')
        ->toContain('Pipeline');
});

it('hides Owner-only nav items for Salesperson role', function (): void {
    $html = Blade::render(
        '<x-layouts.app :nav-role="$role">x</x-layouts.app>',
        ['role' => Navigation::ROLE_SALESPERSON],
    );

    expect($html)
        ->toContain('data-nav-key="kanban"')
        ->toContain('data-nav-key="leads"')
        ->toContain('data-nav-key="settings"')
        ->not->toContain('data-nav-key="reports"')
        ->not->toContain('data-nav-key="team"')
        ->toContain('data-role="salesperson"');
});

it('shows the full nav including Reports and Team for Business Owner', function (): void {
    $html = Blade::render(
        '<x-layouts.app :nav-role="$role">x</x-layouts.app>',
        ['role' => Navigation::ROLE_BUSINESS_OWNER],
    );

    expect($html)
        ->toContain('data-nav-key="kanban"')
        ->toContain('data-nav-key="leads"')
        ->toContain('data-nav-key="reports"')
        ->toContain('data-nav-key="team"')
        ->toContain('data-nav-key="settings"')
        ->toContain('data-role="business_owner"');
});

it('renders no nav items when there is no role', function (): void {
    $html = Blade::render('<x-layouts.app>x</x-layouts.app>');

    expect($html)
        ->toContain('data-role="guest"')
        ->not->toContain('data-nav-key=');
});
