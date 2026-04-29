<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Deal;
use App\Models\DealNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealNote>
 */
final class DealNoteFactory extends Factory
{
    protected $model = DealNote::class;

    public function definition(): array
    {
        $deal = Deal::factory()->create();

        return [
            'deal_id' => $deal->id,
            'user_id' => $deal->owner_user_id,
            'body' => fake()->sentence(),
        ];
    }
}
