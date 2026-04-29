<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
final class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        $company = Company::factory()->create();
        $owner = User::factory()->salesperson()->forCompany($company)->create();

        return [
            'company_id' => $company->id,
            'owner_user_id' => $owner->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'notes' => null,
        ];
    }

    public function forCompany(Company $company): self
    {
        return $this->state(fn () => ['company_id' => $company->id]);
    }

    public function ownedBy(User $user): self
    {
        return $this->state(fn () => [
            'company_id' => $user->company_id,
            'owner_user_id' => $user->id,
        ]);
    }
}
