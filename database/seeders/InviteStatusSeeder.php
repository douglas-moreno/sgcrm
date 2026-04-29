<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\InviteStatus;
use Illuminate\Database\Seeder;

final class InviteStatusSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            InviteStatus::PENDING => 'Pending',
            InviteStatus::ACCEPTED => 'Accepted',
            InviteStatus::EXPIRED => 'Expired',
            InviteStatus::REVOKED => 'Revoked',
        ] as $slug => $name) {
            InviteStatus::updateOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true]);
        }
    }
}
