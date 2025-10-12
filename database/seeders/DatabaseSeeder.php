<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            PermisoSeeder::class,
            UserSeeder::class,
            ProveedorSeeder::class,
        ]);
    }
}
