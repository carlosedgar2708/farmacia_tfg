<?php

use App\Models\Lote;
use App\Models\Producto;
use App\Models\Recibo;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = crearUsuarioDePrueba();
    $this->producto = Producto::create([
        'codigo' => 'PR-RECIBO', 'nombre' => 'Producto recibo',
        'es_inyectable' => false, 'precio_venta' => 20,
    ]);
    Lote::create([
        'producto_id' => $this->producto->id, 'nro_lote' => 'L1',
        'fecha_vencimiento' => null, 'costo_unitario' => 10, 'stock' => 50,
    ]);
});

test('registrar una venta crea y persiste el recibo (PEND-01)', function () {
    $response = $this->actingAs($this->user)->post('/ventas', [
        'items' => [
            ['producto_id' => $this->producto->id, 'cantidad' => 3],
        ],
    ]);

    $response->assertRedirect(route('ventas.index'));

    $venta = Venta::first();
    $this->assertDatabaseHas('recibos', [
        'venta_id' => $venta->id,
        'monto' => 60.00,
    ]);
});

test('la relacion Venta::recibo() es legible sin lanzar excepcion', function () {
    $this->actingAs($this->user)->post('/ventas', [
        'items' => [
            ['producto_id' => $this->producto->id, 'cantidad' => 2],
        ],
    ]);

    $venta = Venta::first();

    expect($venta->recibo)->not->toBeNull();
    expect((float) $venta->recibo->monto)->toBe(40.0);
    expect($venta->recibo->venta_id)->toBe($venta->id);
});

test('Recibo::count() no lanza excepcion SQL (regresion del bug de SoftDeletes sin deleted_at)', function () {
    $this->actingAs($this->user)->post('/ventas', [
        'items' => [
            ['producto_id' => $this->producto->id, 'cantidad' => 1],
        ],
    ]);

    expect(Recibo::count())->toBe(1);
});
