<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permiso;

class PermisoSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // Usuarios y roles
            ['nombre' => 'Ver Usuarios', 'slug' => 'usuarios.index'],
            ['nombre' => 'Crear Usuario', 'slug' => 'usuarios.create'],
            ['nombre' => 'Editar Usuario', 'slug' => 'usuarios.edit'],
            ['nombre' => 'Eliminar Usuario', 'slug' => 'usuarios.destroy'],

            // Ventas
            ['nombre' => 'Ver Ventas', 'slug' => 'ventas.index'],
            ['nombre' => 'Crear Venta', 'slug' => 'ventas.create'],
            ['nombre' => 'Anular Venta', 'slug' => 'ventas.destroy'],

            // Compras
            ['nombre' => 'Ver Compras', 'slug' => 'compras.index'],
            ['nombre' => 'Registrar Compra', 'slug' => 'compras.create'],

            // Inventario
            ['nombre' => 'Ver Productos', 'slug' => 'productos.index'],
            ['nombre' => 'Gestionar Stock', 'slug' => 'stock.manage'],

            // Devoluciones
            ['nombre' => 'Registrar Devolución', 'slug' => 'devoluciones.create'],
        ];

        foreach ($permisos as $permiso) {
            Permiso::create($permiso);
        }
    }
}
