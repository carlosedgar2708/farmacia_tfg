<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;

class RolSeeder extends Seeder
{
    /**
     * Ejecuta el seeder de roles base.
     */
    public function run(): void
    {
        Rol::create([
            'nombre' => 'Administrador',
            'slug' => 'admin',
            'descripcion' => 'Acceso total al sistema (control total de módulos y usuarios).',
        ]);

        Rol::create([
            'nombre' => 'Cajero',
            'slug' => 'cajero',
            'descripcion' => 'Encargado de registrar ventas y devoluciones.',
        ]);

        Rol::create([
            'nombre' => 'Almacenero',
            'slug' => 'almacenero',
            'descripcion' => 'Responsable del inventario, compras y ajustes de stock.',
        ]);
    }
}
