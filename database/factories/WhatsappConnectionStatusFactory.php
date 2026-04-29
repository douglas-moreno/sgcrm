<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WhatsappConnectionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappConnectionStatus>
 */
final class WhatsappConnectionStatusFactory extends Factory
{
    protected $model = WhatsappConnectionStatus::class;

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
