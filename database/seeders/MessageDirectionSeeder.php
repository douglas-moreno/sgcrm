<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MessageDirection;
use Illuminate\Database\Seeder;

final class MessageDirectionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            MessageDirection::INBOUND => 'Inbound',
            MessageDirection::OUTBOUND => 'Outbound',
        ] as $slug => $name) {
            MessageDirection::updateOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
