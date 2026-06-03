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
            ['slug' => Role::BUSINESS_OWNER, 'name' => 'Coordenadora', 'description' => 'Acesso completo dentro da empresa.'],
            ['slug' => Role::SALESPERSON, 'name' => 'Vendedora', 'description' => 'Acesso limitado aos leads e negociações atribuídos.'],
        ] as $row) {
            Role::updateOrCreate(['slug' => $row['slug']], $row + ['is_active' => true]);
        }
    }
}
