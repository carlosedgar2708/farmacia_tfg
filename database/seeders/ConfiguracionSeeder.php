<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        Configuracion::updateOrCreate(
            ['clave' => 'dias_alerta_vencimiento'],
            [
                'valor'       => '90',
                'descripcion' => 'Días de antelación para alertar sobre lotes próximos a vencer.',
            ]
        );
    }
}
