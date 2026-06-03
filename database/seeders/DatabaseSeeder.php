<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PipelineStageSeeder::class,
            InviteStatusSeeder::class,
            WhatsappConnectionStatusSeeder::class,
            MessageDirectionSeeder::class,
            MessageStatusSeeder::class,
            MessageTypeSeeder::class,
            ActivityTypeSeeder::class,
        ]);

        User::create([
            'name' => 'Ariane Melo',
            'email' => 'amelo@sgtecnologia.com.br',
            'password' => bcrypt('101010'),
            'role_id' => Role::BUSINESS_OWNER,
        ]);
    }
}
