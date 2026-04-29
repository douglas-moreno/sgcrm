<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Invite;
use App\Models\InviteStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invite>
 */
final class InviteFactory extends Factory
{
    protected $model = Invite::class;

    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'invited_by_user_id' => User::factory()->businessOwner()->forCompany(
                $company instanceof Company ? $company : Company::factory()->create(),
            ),
            'role_id' => Role::firstOrCreate(
                ['slug' => Role::SALESPERSON],
                ['name' => 'Salesperson', 'is_active' => true],
            )->id,
            'status_id' => InviteStatus::firstOrCreate(
                ['slug' => InviteStatus::PENDING],
                ['name' => 'Pending', 'is_active' => true],
            )->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'token' => Str::random(48),
            'expires_at' => now()->addDays(7),
        ];
    }

    public function accepted(): self
    {
        return $this->state(fn () => [
            'status_id' => InviteStatus::firstOrCreate(
                ['slug' => InviteStatus::ACCEPTED],
                ['name' => 'Accepted', 'is_active' => true],
            )->id,
            'accepted_at' => now(),
        ]);
    }
}
