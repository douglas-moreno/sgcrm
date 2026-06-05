<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
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

        $company = Company::create([
            'name' => 'SG Tecnologia',
        ]);

        User::create([
            'name' => 'Ariane Melo',
            'email' => 'amelo@sgtecnologia.com.br',
            'password' => bcrypt('101010'),
            'role_id' => 1,
            'email_verified_at' => now(),
            'company_id' => $company->id,
        ]);

        User::create([
            'name' => 'Andreia Barros',
            'email' => 'abarros@sgtecnologia.com.br',
            'password' => bcrypt('101010'),
            'role_id' => 2,
            'email_verified_at' => now(),
            'company_id' => $company->id,
        ]);

            User::create([
            'name' => 'Dayana Veronica',
            'email' => 'dveronica@sgtecnologia.com.br',
            'password' => bcrypt('101010'),
            'role_id' => 2,
            'email_verified_at' => now(),
            'company_id' => $company->id,
        ]);
    }
}
