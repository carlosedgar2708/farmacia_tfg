<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
// database/seeders/DatabaseSeeder.php
    public function run(): void
    {
        $this->call([
            PermisoSeeder::class,
            RolSeeder::class,
            UserSeeder::class,
            ProductoSeeder::class,
            LoteSeeder::class,
      // ProveedorSeeder::class, etc. si aplica
        ]);
    }

}
