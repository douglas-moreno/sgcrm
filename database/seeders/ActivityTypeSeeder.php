<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ActivityType;
use Illuminate\Database\Seeder;

final class ActivityTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ActivityType::LEAD_CREATED => 'Lead Created',
            ActivityType::LEAD_UPDATED => 'Lead Updated',
            ActivityType::LEAD_REASSIGNED => 'Lead Reassigned',
            ActivityType::DEAL_CREATED => 'Deal Created',
            ActivityType::DEAL_UPDATED => 'Deal Updated',
            ActivityType::STAGE_CHANGED => 'Stage Changed',
            ActivityType::VALUE_CHANGED => 'Value Changed',
            ActivityType::OWNERSHIP_CHANGED => 'Ownership Changed',
            ActivityType::NOTE_ADDED => 'Note Added',
            ActivityType::MESSAGE_SENT => 'Message Sent',
            ActivityType::MESSAGE_RECEIVED => 'Message Received',
            ActivityType::DEAL_WON => 'Deal Won',
            ActivityType::DEAL_LOST => 'Deal Lost',
        ];

        foreach ($types as $slug => $name) {
            ActivityType::updateOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true]);
        }
    }
}
