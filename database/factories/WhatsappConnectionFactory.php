<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WhatsappConnection>
 */
final class WhatsappConnectionFactory extends Factory
{
    protected $model = WhatsappConnection::class;

    public function definition(): array
    {
        $user = User::factory()->salesperson()->create();
        $status = WhatsappConnectionStatus::firstOrCreate(
            ['slug' => WhatsappConnectionStatus::DISCONNECTED],
            ['name' => 'Disconnected', 'is_active' => true],
        );

        return [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'status_id' => $status->id,
            'instance_name' => 'sgcrm-'.Str::random(10),
        ];
    }

    public function connected(): self
    {
        return $this->state(function () {
            $status = WhatsappConnectionStatus::firstOrCreate(
                ['slug' => WhatsappConnectionStatus::CONNECTED],
                ['name' => 'Connected', 'is_active' => true],
            );

            return [
                'status_id' => $status->id,
                'connected_at' => now(),
                'phone_number' => fake()->phoneNumber(),
            ];
        });
    }
}
