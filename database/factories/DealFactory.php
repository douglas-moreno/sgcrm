<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
final class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        $lead = Lead::factory()->create();
        $stage = PipelineStage::firstOrCreate(
            ['slug' => PipelineStage::NEW_LEAD],
            ['name' => 'New Lead', 'position' => 1, 'is_active' => true],
        );

        return [
            'company_id' => $lead->company_id,
            'lead_id' => $lead->id,
            'owner_user_id' => $lead->owner_user_id,
            'stage_id' => $stage->id,
            'title' => fake()->sentence(3),
            'value' => fake()->randomFloat(2, 100, 50000),
        ];
    }

    public function forLead(Lead $lead): self
    {
        return $this->state(fn () => [
            'company_id' => $lead->company_id,
            'lead_id' => $lead->id,
            'owner_user_id' => $lead->owner_user_id,
        ]);
    }

    public function inStage(string $slug): self
    {
        return $this->state(function () use ($slug) {
            $stage = PipelineStage::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => str_replace('_', ' ', $slug),
                    'position' => 50,
                    'is_active' => true,
                    'is_won' => $slug === PipelineStage::WON,
                    'is_lost' => $slug === PipelineStage::LOST,
                    'is_terminal' => in_array($slug, [PipelineStage::WON, PipelineStage::LOST], true),
                ],
            );

            return ['stage_id' => $stage->id];
        });
    }

    public function withLossReason(string $reason = 'Price too high'): self
    {
        return $this->inStage(PipelineStage::LOST)->state(fn () => [
            'loss_reason' => $reason,
            'lost_at' => now(),
        ]);
    }

    public function won(): self
    {
        return $this->inStage(PipelineStage::WON)->state(fn () => [
            'won_at' => now(),
        ]);
    }
}
