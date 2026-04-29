<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

it('blocks unverified users from app routes and redirects to verification notice', function (): void {
    $user = User::factory()->salesperson()->unverified()->create();

    $this->actingAs($user)
        ->get(route('kanban'))
        ->assertRedirect(route('verification.notice'));
});

it('verifies a user via the signed verification URL', function (): void {
    Event::fake();

    $user = User::factory()->salesperson()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)->get($url)->assertRedirect(route('kanban'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('lets verified users reach the protected kanban route', function (): void {
    $user = User::factory()->salesperson()->create();

    $this->actingAs($user)
        ->get(route('kanban'))
        ->assertOk();
});
