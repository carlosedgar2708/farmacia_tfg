<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productos = [
            [
                'codigo' => 'P001',
                'nombre' => 'Paracetamol 500mg',
                'es_inyectable' => false,
                'description' => 'Analgésico y antipirético de uso común.'
            ],
            [
                'codigo' => 'P002',
                'nombre' => 'Amoxicilina 500mg cápsulas',
                'es_inyectable' => false,
                'description' => 'Antibiótico de amplio espectro.'
            ],
            [
                'codigo' => 'P003',
                'nombre' => 'Diclofenaco Sódico 75mg inyectable',
                'es_inyectable' => true,
                'description' => 'Antiinflamatorio no esteroideo (AINE) para uso inyectable.'
            ],
            [
                'codigo' => 'P004',
                'nombre' => 'Omeprazol 20mg cápsulas',
                'es_inyectable' => false,
                'description' => 'Inhibidor de la bomba de protones, reduce la acidez estomacal.'
            ],
            [
                'codigo' => 'P005',
                'nombre' => 'Loratadina 10mg tabletas',
                'es_inyectable' => false,
                'description' => 'Antihistamínico para el tratamiento de alergias.'
            ],
        ];

        foreach ($productos as $p) {
            Producto::create($p);
        }
    }
}
