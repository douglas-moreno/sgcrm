<?php

declare(strict_types=1);

use App\Models\User;

it('logs out an authenticated user and redirects to login', function (): void {
    $user = User::factory()->salesperson()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

it('redirects guests away from the logout endpoint', function (): void {
    $this->post(route('logout'))
        ->assertRedirect(route('login'));
});
