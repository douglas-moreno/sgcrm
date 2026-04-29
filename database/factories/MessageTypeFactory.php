<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MessageType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageType>
 */
final class MessageTypeFactory extends Factory
{
    protected $model = MessageType::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'name' => str_replace('-', ' ', $slug),
            'slug' => $slug,
        ];
    }
}
