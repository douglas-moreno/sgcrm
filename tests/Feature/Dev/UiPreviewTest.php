<?php

declare(strict_types=1);

it('renders the dev UI preview page in non-production environments', function (): void {
    $response = $this->get(route('dev.ui'));

    $response->assertOk();
    $response->assertSee('UI Component Preview', false);
    $response->assertSee('section-buttons', false);
    $response->assertSee('section-inputs', false);
    $response->assertSee('section-selects', false);
    $response->assertSee('section-checks', false);
    $response->assertSee('section-feedback', false);
    $response->assertSee('section-card', false);
    $response->assertSee('section-modal', false);
});

it('returns 404 for the dev UI preview when running in production', function (): void {
    app()->detectEnvironment(fn () => 'production');

    try {
        $this->get(route('dev.ui'))->assertNotFound();
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }
});
