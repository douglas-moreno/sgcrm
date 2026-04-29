<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WhatsappConnectionStatus;
use Illuminate\Database\Seeder;

final class WhatsappConnectionStatusSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            WhatsappConnectionStatus::DISCONNECTED => 'Disconnected',
            WhatsappConnectionStatus::PENDING => 'Pending',
            WhatsappConnectionStatus::CONNECTED => 'Connected',
            WhatsappConnectionStatus::FAILED => 'Failed',
        ] as $slug => $name) {
            WhatsappConnectionStatus::updateOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true]);
        }
    }
}
