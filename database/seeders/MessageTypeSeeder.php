<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MessageType;
use Illuminate\Database\Seeder;

final class MessageTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            MessageType::TEXT => 'Text',
            MessageType::IMAGE => 'Image',
            MessageType::AUDIO => 'Audio',
            MessageType::VIDEO => 'Video',
            MessageType::DOCUMENT => 'Document',
            MessageType::LOCATION => 'Location',
        ] as $slug => $name) {
            MessageType::updateOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
