<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permiso;

class PermisoSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [

            // USUARIOS
            ['slug' => 'usuarios.ver',      'nombre' => 'Ver usuarios'],
            ['slug' => 'usuarios.crear',    'nombre' => 'Crear usuario'],
            ['slug' => 'usuarios.editar',   'nombre' => 'Editar usuario'],
            ['slug' => 'usuarios.eliminar', 'nombre' => 'Eliminar usuario'],

            // ROLES
            ['slug' => 'rols.ver',      'nombre' => 'Ver roles'],
            ['slug' => 'rols.crear',    'nombre' => 'Crear rol'],
            ['slug' => 'rols.editar',   'nombre' => 'Editar rol'],
            ['slug' => 'rols.eliminar', 'nombre' => 'Eliminar rol'],

            // PROVEEDORES
            ['slug' => 'proveedores.ver',      'nombre' => 'Ver proveedores'],
            ['slug' => 'proveedores.crear',    'nombre' => 'Crear proveedor'],
            ['slug' => 'proveedores.editar',   'nombre' => 'Editar proveedor'],
            ['slug' => 'proveedores.eliminar', 'nombre' => 'Eliminar proveedor'],

            // PRODUCTOS / STOCK
            ['slug' => 'productos.ver',     'nombre' => 'Ver productos'],
            ['slug' => 'productos.crear',   'nombre' => 'Crear producto'],
            ['slug' => 'productos.editar',  'nombre' => 'Editar producto'],
            ['slug' => 'productos.eliminar','nombre' => 'Eliminar producto'],
            ['slug' => 'productos.stock',   'nombre' => 'Gestionar stock / ajustes'],

            // COMPRAS (ADMIN)
            ['slug' => 'compras.ver',   'nombre' => 'Ver compras'],
            ['slug' => 'compras.crear', 'nombre' => 'Registrar compra'],
            ['slug' => 'compras.anular','nombre' => 'Anular compra'],

            // VENTAS
            ['slug' => 'ventas.ver',   'nombre' => 'Ver ventas'],
            ['slug' => 'ventas.crear', 'nombre' => 'Registrar venta'],
            ['slug' => 'ventas.anular','nombre' => 'Anular venta'],

            // DEVOLUCIONES
            ['slug' => 'devoluciones.registrar', 'nombre' => 'Registrar devolución'],

            // REPORTES
            ['slug' => 'reportes.ver', 'nombre' => 'Ver reportes'],
        ];

        foreach ($permisos as $p) {
            Permiso::updateOrCreate(
                ['slug' => $p['slug']],
                ['nombre' => $p['nombre']]
            );
        }
    }
}
