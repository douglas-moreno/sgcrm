<?php

declare(strict_types=1);

namespace App\Livewire\Team;

use App\Mail\AccountCreatedMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

final class CreateUser extends Component
{
    public string $name = '';

    public string $email = '';

    public string $temporary_password = '';

    public function mount(): void
    {
        Gate::authorize('create', User::class);
    }

    public function save(): void
    {
        Gate::authorize('create', User::class);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, callable $fail): void {
                    $companyId = auth()->user()->company_id;

                    if (User::query()->where('company_id', $companyId)->where('email', $value)->exists()) {
                        $fail('A user with this email already exists.');
                    }
                },
            ],
            'temporary_password' => ['required', 'string', Password::min(8)],
        ]);

        $role = Role::where('slug', Role::SALESPERSON)->firstOrFail();

        $user = User::create([
            'company_id' => auth()->user()->company_id,
            'role_id' => $role->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['temporary_password']),
            'is_active' => true,
            'must_change_password' => true,
            'email_verified_at' => now(),
        ]);

        Mail::to($user->email)->queue(new AccountCreatedMail($user, $data['temporary_password']));

        $this->reset(['name', 'email', 'temporary_password']);
        $this->dispatch('user-created', userId: $user->id);
    }

    public function render(): View
    {
        return view('livewire.team.create-user');
    }
}
