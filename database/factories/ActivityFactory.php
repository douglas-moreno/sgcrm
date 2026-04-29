<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
final class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        $deal = Deal::factory()->create();
        $type = ActivityType::firstOrCreate(
            ['slug' => ActivityType::DEAL_CREATED],
            ['name' => 'Deal Created', 'is_active' => true],
        );

        return [
            'company_id' => $deal->company_id,
            'activity_type_id' => $type->id,
            'user_id' => $deal->owner_user_id,
            'lead_id' => $deal->lead_id,
            'deal_id' => $deal->id,
            'metadata' => [],
            'created_at' => now(),
        ];
    }
}
