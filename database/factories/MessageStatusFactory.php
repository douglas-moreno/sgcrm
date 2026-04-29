<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MessageStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageStatus>
 */
final class MessageStatusFactory extends Factory
{
    protected $model = MessageStatus::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'name' => str_replace('-', ' ', $slug),
            'slug' => $slug,
        ];
    }
}
