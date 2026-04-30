<?php

declare(strict_types=1);

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    RateLimiter::clear('throttle');
});

it('locks out login after 5 failed attempts', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->businessOwner()->forCompany($company)->create([
        'email' => 'limited@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    $component = Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('login');

    $errors = $component->errors()->get('email');

    expect($errors)->not->toBeEmpty()
        ->and($errors[0])->toContain('Too many login attempts');
});

it('locks out password reset after 5 attempts on the same email+IP', function (): void {
    for ($i = 0; $i < 5; $i++) {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'reset@example.com')
            ->call('sendLink')
            ->assertHasNoErrors();
    }

    $component = Livewire::test(ForgotPassword::class)
        ->set('email', 'reset@example.com')
        ->call('sendLink');

    $errors = $component->errors()->get('email');

    expect($errors)->not->toBeEmpty()
        ->and($errors[0])->toContain('Too many reset attempts');
});

it('webhook route has 60-per-minute throttle middleware', function (): void {
    $route = Illuminate\Support\Facades\Route::getRoutes()->getByName('webhooks.evolution');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('throttle:60,1');
});
