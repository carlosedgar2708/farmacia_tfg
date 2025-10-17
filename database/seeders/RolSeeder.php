<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\Permiso;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Crear roles
        $roles = [
            ['slug' => 'admin',    'nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema.'],
            ['slug' => 'vendedor', 'nombre' => 'Vendedor',      'descripcion' => 'Ventas y consulta de productos.'],
        ];

        foreach ($roles as $r) {
            Rol::updateOrCreate(
                ['slug' => $r['slug']],
                ['nombre' => $r['nombre'], 'descripcion' => $r['descripcion']]
            );
        }

        // 2) Asignar permisos a cada rol
        $admin    = Rol::where('slug', 'admin')->first();
        $vendedor = Rol::where('slug', 'vendedor')->first();

        if ($admin) {
            // Admin => todos los permisos
            $admin->permisos()->sync(Permiso::pluck('id')->toArray());
        }

        if ($vendedor) {
            // Vendedor => permisos operativos básicos
            $slugs = [
                'ventas.ver', 'ventas.crear',
                'productos.ver',
                'devoluciones.registrar',
                // si quieres que vea reportes de sus ventas:
                // 'reportes.ver',
            ];

            $permIds = Permiso::whereIn('slug', $slugs)->pluck('id')->toArray();
            $vendedor->permisos()->sync($permIds);
        }
    }
}
