<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permiso;

class PermisoSeeder extends Seeder
{
    public function run(): void
    {
        // Los slugs de proveedores se sembraron originalmente como 'proveedores.*'
        // pero las rutas (routes/web.php) y el middleware verifican 'proveedors.*'.
        // Se renombran en sitio (no se crean permisos nuevos) para conservar los
        // IDs y no romper asignaciones ya existentes en permiso_rol.
        $renombres = [
            'proveedores.ver'      => 'proveedors.ver',
            'proveedores.crear'    => 'proveedors.crear',
            'proveedores.editar'   => 'proveedors.editar',
            'proveedores.eliminar' => 'proveedors.eliminar',
        ];

        foreach ($renombres as $antiguo => $nuevo) {
            Permiso::where('slug', $antiguo)->update(['slug' => $nuevo]);
        }

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
            ['slug' => 'proveedors.ver',      'nombre' => 'Ver proveedores'],
            ['slug' => 'proveedors.crear',    'nombre' => 'Crear proveedor'],
            ['slug' => 'proveedors.editar',   'nombre' => 'Editar proveedor'],
            ['slug' => 'proveedors.eliminar', 'nombre' => 'Eliminar proveedor'],

            // CLIENTES
            // Los slugs ya eran verificados por routes/web.php (permiso:clientes.*)
            // pero nunca se habían sembrado — el módulo solo funcionaba para
            // administradores por el bypass de esAdmin(). Ver BUG-13.
            ['slug' => 'clientes.ver',      'nombre' => 'Ver clientes'],
            ['slug' => 'clientes.crear',    'nombre' => 'Crear cliente'],
            ['slug' => 'clientes.editar',   'nombre' => 'Editar cliente'],
            ['slug' => 'clientes.eliminar', 'nombre' => 'Eliminar cliente'],

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
