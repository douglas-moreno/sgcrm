<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renders the page header with title, subtitle and actions slot', function (): void {
    $html = Blade::render(<<<'HTML'
        <x-ui.page-header title="Leads" subtitle="Showing 24 leads">
            <x-slot:actions>
                <button>New</button>
            </x-slot:actions>
        </x-ui.page-header>
    HTML);

    expect($html)
        ->toContain('ui-page-header')
        ->toContain('Leads')
        ->toContain('Showing 24 leads')
        ->toContain('<button>New</button>');
});

it('renders the empty state with title, description and icon', function (): void {
    $html = Blade::render(<<<'HTML'
        <x-ui.empty-state title="No deals yet" description="Create your first deal to get started.">
            <x-slot:actions>
                <a href="#">New deal</a>
            </x-slot:actions>
        </x-ui.empty-state>
    HTML);

    expect($html)
        ->toContain('ui-empty-state')
        ->toContain('No deals yet')
        ->toContain('Create your first deal to get started.')
        ->toContain('<a href="#">New deal</a>');
});

it('renders the skeleton with the requested number of lines', function (): void {
    $html = Blade::render('<x-ui.skeleton :lines="5" />');

    expect($html)
        ->toContain('ui-skeleton')
        ->toContain('animate-pulse');

    expect(mb_substr_count($html, 'animate-pulse'))->toBe(5);
});

it('renders the card-shaped skeleton variant', function (): void {
    $html = Blade::render('<x-ui.skeleton shape="card" />');

    expect($html)
        ->toContain('ui-skeleton')
        ->toContain('h-32');
});
