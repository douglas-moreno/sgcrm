<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['slug' => Role::BUSINESS_OWNER, 'name' => 'Business Owner', 'description' => 'Full access within the company.'],
            ['slug' => Role::SALESPERSON, 'name' => 'Salesperson', 'description' => 'Access scoped to assigned leads and deals.'],
        ] as $row) {
            Role::updateOrCreate(['slug' => $row['slug']], $row + ['is_active' => true]);
        }
    }
}
