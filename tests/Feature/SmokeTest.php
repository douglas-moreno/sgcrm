<?php

declare(strict_types=1);

it('redirects guests to the login page from the root URL', function (): void {
    $this->get('/')->assertRedirect(route('login'));
});

it('redirects authenticated users to the kanban from the root URL', function (): void {
    $user = App\Models\User::factory()->salesperson()->create();

    $this->actingAs($user)->get('/')->assertRedirect(route('kanban'));
});
