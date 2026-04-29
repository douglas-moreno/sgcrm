<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renders the guest layout with brand, heading and slot content', function (): void {
    $html = Blade::render(<<<'HTML'
        <x-layouts.guest title="Sign in" heading="Welcome back" subheading="Sign in to keep going.">
            <p>FORM_PLACEHOLDER</p>
        </x-layouts.guest>
    HTML);

    expect($html)
        ->toContain('data-layout="guest"')
        ->toContain('data-testid="guest-main"')
        ->toContain('data-testid="guest-aside"')
        ->toContain('Welcome back')
        ->toContain('Sign in to keep going.')
        ->toContain('FORM_PLACEHOLDER')
        ->toContain('<title>Sign in</title>');
});

it('exposes the brand mark twice (mobile + desktop)', function (): void {
    $html = Blade::render('<x-layouts.guest>x</x-layouts.guest>');

    expect(mb_substr_count($html, 'data-testid="brand"'))->toBe(2);
});
