<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Message;
use App\Models\MessageDirection;
use App\Models\MessageStatus;
use App\Models\MessageType;
use App\Models\WhatsappConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
final class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $lead = Lead::factory()->create();
        $connection = WhatsappConnection::factory()->create([
            'user_id' => $lead->owner_user_id,
            'company_id' => $lead->company_id,
        ]);

        $direction = MessageDirection::firstOrCreate(
            ['slug' => MessageDirection::OUTBOUND],
            ['name' => 'Outbound'],
        );
        $status = MessageStatus::firstOrCreate(
            ['slug' => MessageStatus::SENT],
            ['name' => 'Sent'],
        );
        $type = MessageType::firstOrCreate(
            ['slug' => MessageType::TEXT],
            ['name' => 'Text'],
        );

        return [
            'company_id' => $lead->company_id,
            'whatsapp_connection_id' => $connection->id,
            'lead_id' => $lead->id,
            'user_id' => $lead->owner_user_id,
            'direction_id' => $direction->id,
            'status_id' => $status->id,
            'message_type_id' => $type->id,
            'body' => fake()->sentence(),
            'sent_at' => now(),
        ];
    }

    public function inbound(): self
    {
        return $this->state(function () {
            $direction = MessageDirection::firstOrCreate(
                ['slug' => MessageDirection::INBOUND],
                ['name' => 'Inbound'],
            );

            return [
                'direction_id' => $direction->id,
                'user_id' => null,
                'sent_at' => null,
            ];
        });
    }
}
