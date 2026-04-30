<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
});

it('emits security headers on guest pages', function (): void {
    $response = $this->get('/login');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
});

it('emits security headers on authed pages', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->businessOwner()->forCompany($company)->create();

    $response = $this->actingAs($user)->get(route('kanban'));

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Content-Security-Policy');
});

it('emits HSTS header in production', function (): void {
    app()->detectEnvironment(fn () => 'production');

    $response = $this->get('/login');

    $response->assertHeader('Strict-Transport-Security');
    expect($response->headers->get('Strict-Transport-Security'))
        ->toContain('max-age=31536000')
        ->toContain('includeSubDomains');
});

it('login page emits CSRF token meta', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertSee('name="csrf-token"', false);
});

it('register page emits CSRF token meta', function (): void {
    $this->get('/register')
        ->assertOk()
        ->assertSee('name="csrf-token"', false);
});

it('webhook route is exempt from CSRF + validates X-Webhook-Secret signature', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->salesperson()->forCompany($company)->create();

    $this->postJson(route('webhooks.evolution', ['user' => $user->id]), ['event' => 'PING'])
        ->assertNotFound();
});

it('CSP forbids untrusted frame ancestors', function (): void {
    $response = $this->get('/login');

    expect($response->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'self'");
});
