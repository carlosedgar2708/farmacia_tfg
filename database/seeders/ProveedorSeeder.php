<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Proveedor;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        Proveedor::create([
            'nombre' => 'Laboratorios Medifarma',
            'contacto' => 'Ana Torres',
            'telefono' => '70012345',
        ]);

        Proveedor::create([
            'nombre' => 'Distribuidora FarmaPlus',
            'contacto' => 'Carlos Pérez',
            'telefono' => '70123456',
        ]);

        Proveedor::create([
            'nombre' => 'Farmacéutica Genéricos S.A.',
            'contacto' => 'Laura Gómez',
            'telefono' => '70234567',
        ]);
    }
}
