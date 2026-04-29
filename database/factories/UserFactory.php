<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'role_id' => Role::factory()->salesperson(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
            'must_change_password' => false,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    public function businessOwner(): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(
                ['slug' => Role::BUSINESS_OWNER],
                ['name' => 'Business Owner', 'is_active' => true],
            )->id,
        ]);
    }

    public function salesperson(): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(
                ['slug' => Role::SALESPERSON],
                ['name' => 'Salesperson', 'is_active' => true],
            )->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn () => ['company_id' => $company->id]);
    }
}
