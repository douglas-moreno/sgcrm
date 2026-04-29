<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function (): void {
    RateLimiter::clear('test@example.com|127.0.0.1');
});

it('authenticates a user with valid credentials and redirects to /kanban', function (): void {
    $user = User::factory()->salesperson()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    Livewire::test(App\Livewire\Auth\Login::class)
        ->set('email', 'test@example.com')
        ->set('password', 'correct-password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('kanban'));

    expect(auth()->id())->toBe($user->id);
});

it('shows a generic error on bad credentials and does not enumerate users', function (): void {
    User::factory()->salesperson()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    Livewire::test(App\Livewire\Auth\Login::class)
        ->set('email', 'test@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(auth()->check())->toBeFalse();
});

it('rate limits login after 5 failed attempts', function (): void {
    User::factory()->salesperson()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    $component = Livewire::test(App\Livewire\Auth\Login::class)
        ->set('email', 'test@example.com')
        ->set('password', 'wrong');

    for ($i = 0; $i < 5; $i++) {
        $component->call('login');
    }

    $component->call('login')->assertHasErrors(['email']);

    expect(RateLimiter::tooManyAttempts('test@example.com|127.0.0.1', 5))->toBeTrue();
});

it('blocks login for inactive users', function (): void {
    User::factory()->salesperson()->inactive()->create([
        'email' => 'inactive@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    Livewire::test(App\Livewire\Auth\Login::class)
        ->set('email', 'inactive@example.com')
        ->set('password', 'correct-password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(auth()->check())->toBeFalse();
});
