<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InviteStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InviteStatus>
 */
final class InviteStatusFactory extends Factory
{
    protected $model = InviteStatus::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'name' => str_replace('-', ' ', $slug),
            'slug' => $slug,
            'is_active' => true,
        ];
    }
}
