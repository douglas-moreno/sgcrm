<?php

declare(strict_types=1);

use App\Mail\WelcomeMail;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function (): void {
    Mail::fake();
    Notification::fake();
});

it('registers a new Business Owner and creates the company', function (): void {
    Livewire::test(App\Livewire\Auth\Register::class)
        ->set('name', 'Ana Souza')
        ->set('email', 'ana@acme.test')
        ->set('company_name', 'Acme Corp')
        ->set('password', 'super-secret-password')
        ->set('password_confirmation', 'super-secret-password')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('kanban'));

    $user = User::where('email', 'ana@acme.test')->firstOrFail();

    expect(Company::where('name', 'Acme Corp')->exists())->toBeTrue();
    expect($user->company->name)->toBe('Acme Corp');
    expect($user->role->slug)->toBe(Role::BUSINESS_OWNER);
    expect($user->isBusinessOwner())->toBeTrue();
    expect(auth()->id())->toBe($user->id);
});

it('rejects duplicate emails globally', function (): void {
    User::factory()->businessOwner()->create(['email' => 'taken@example.com']);

    Livewire::test(App\Livewire\Auth\Register::class)
        ->set('name', 'Bruno')
        ->set('email', 'taken@example.com')
        ->set('company_name', 'Bruno Co')
        ->set('password', 'super-secret-password')
        ->set('password_confirmation', 'super-secret-password')
        ->call('register')
        ->assertHasErrors(['email']);
});

it('enforces minimum password length', function (): void {
    Livewire::test(App\Livewire\Auth\Register::class)
        ->set('name', 'Carla')
        ->set('email', 'carla@example.com')
        ->set('company_name', 'Carla Co')
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('register')
        ->assertHasErrors(['password']);
});

it('queues the welcome mail and sends the email verification notification', function (): void {
    Livewire::test(App\Livewire\Auth\Register::class)
        ->set('name', 'Diana')
        ->set('email', 'diana@example.com')
        ->set('company_name', 'Diana Co')
        ->set('password', 'super-secret-password')
        ->set('password_confirmation', 'super-secret-password')
        ->call('register');

    Mail::assertQueued(WelcomeMail::class, fn ($mail) => $mail->user->email === 'diana@example.com');

    $user = User::where('email', 'diana@example.com')->firstOrFail();
    Notification::assertSentTo($user, VerifyEmail::class);
});
