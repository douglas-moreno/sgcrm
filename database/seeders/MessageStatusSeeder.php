<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MessageStatus;
use Illuminate\Database\Seeder;

final class MessageStatusSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            MessageStatus::PENDING => 'Pending',
            MessageStatus::SENT => 'Sent',
            MessageStatus::DELIVERED => 'Delivered',
            MessageStatus::READ => 'Read',
            MessageStatus::FAILED => 'Failed',
        ] as $slug => $name) {
            MessageStatus::updateOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
