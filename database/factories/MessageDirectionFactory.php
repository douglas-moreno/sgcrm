<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MessageDirection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageDirection>
 */
final class MessageDirectionFactory extends Factory
{
    protected $model = MessageDirection::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'name' => str_replace('-', ' ', $slug),
            'slug' => $slug,
        ];
    }
}
