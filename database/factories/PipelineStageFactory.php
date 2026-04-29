<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PipelineStage>
 */
final class PipelineStageFactory extends Factory
{
    protected $model = PipelineStage::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'name' => str_replace('-', ' ', $slug),
            'slug' => $slug,
            'position' => fake()->numberBetween(1, 99),
            'is_terminal' => false,
            'is_won' => false,
            'is_lost' => false,
            'is_active' => true,
        ];
    }

    public function newLead(): self
    {
        return $this->state(fn () => [
            'name' => 'New Lead',
            'slug' => PipelineStage::NEW_LEAD,
            'position' => 1,
        ]);
    }

    public function won(): self
    {
        return $this->state(fn () => [
            'name' => 'Won',
            'slug' => PipelineStage::WON,
            'position' => 90,
            'is_terminal' => true,
            'is_won' => true,
        ]);
    }

    public function lost(): self
    {
        return $this->state(fn () => [
            'name' => 'Lost',
            'slug' => PipelineStage::LOST,
            'position' => 99,
            'is_terminal' => true,
            'is_lost' => true,
        ]);
    }
}
