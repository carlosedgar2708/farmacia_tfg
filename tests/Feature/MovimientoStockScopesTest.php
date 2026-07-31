<?php

use App\Models\Lote;
use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('scopeEntradas y scopeSalidas filtran por tipo, no por el signo de cantidad (BUG-06)', function () {
    $producto = Producto::create([
        'codigo' => 'PR-MOV',
        'nombre' => 'Producto para movimientos',
        'es_inyectable' => false,
        'precio_venta' => 10,
    ]);
    $lote = Lote::create([
        'producto_id' => $producto->id,
        'nro_lote' => 'L-MOV',
        'fecha_vencimiento' => null,
        'costo_unitario' => 5,
        'stock' => 100,
    ]);

    MovimientoStock::create([
        'lote_id' => $lote->id, 'fecha' => now(), 'tipo' => 'Entrada',
        'motivo' => 'Compra', 'cantidad' => 20, 'referencia' => null,
    ]);
    MovimientoStock::create([
        'lote_id' => $lote->id, 'fecha' => now(), 'tipo' => 'Salida',
        'motivo' => 'Venta', 'cantidad' => 5, 'referencia' => null,
    ]);

    expect(MovimientoStock::entradas()->count())->toBe(1);
    expect(MovimientoStock::salidas()->count())->toBe(1);
    expect(MovimientoStock::entradas()->first()->tipo)->toBe('Entrada');
    expect(MovimientoStock::salidas()->first()->tipo)->toBe('Salida');
});
