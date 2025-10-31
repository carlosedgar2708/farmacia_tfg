<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lote;
use App\Models\Producto;
use Carbon\Carbon;

class LoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productos = Producto::all();

        foreach ($productos as $producto) {
            // Cada producto tendrá de 1 a 3 lotes
            for ($i = 1; $i <= rand(1, 3); $i++) {
                Lote::create([
                    'producto_id' => $producto->id,
                    'nro_lote' => strtoupper($producto->codigo) . '-L' . $i,
                    'fecha_vencimiento' => Carbon::now()->addMonths(rand(6, 36)),
                    'costo_unitario' => rand(10, 100),
                    'stock' => rand(5, 50),
                ]);
            }
        }
    }
}
