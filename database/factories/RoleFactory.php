<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
final class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'name' => str_replace('-', ' ', $slug),
            'slug' => $slug,
            'description' => null,
            'is_active' => true,
        ];
    }

    public function businessOwner(): self
    {
        return $this->state(fn () => [
            'name' => 'Business Owner',
            'slug' => Role::BUSINESS_OWNER,
        ]);
    }

    public function salesperson(): self
    {
        return $this->state(fn () => [
            'name' => 'Salesperson',
            'slug' => Role::SALESPERSON,
        ]);
    }
}
