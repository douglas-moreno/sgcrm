<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Mail\WelcomeMail;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

final class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $company_name = '';

    public function register(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'company_name' => ['required', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $company = Company::create(['name' => $data['company_name']]);

            $role = Role::firstOrCreate(
                ['slug' => Role::BUSINESS_OWNER],
                ['name' => 'Business Owner', 'is_active' => true],
            );

            return User::create([
                'company_id' => $company->id,
                'role_id' => $role->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);
        });

        Mail::to($user->email)->queue(new WelcomeMail($user));

        $user->sendEmailVerificationNotification();

        Auth::login($user);

        $this->redirect(route('kanban'), navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.auth.register');
    }
}
