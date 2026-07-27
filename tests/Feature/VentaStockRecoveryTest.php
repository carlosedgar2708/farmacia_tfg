<?php

use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('stock insuficiente ya no produce una pagina de error generica', function () {
    // ventas.store no tiene middleware de permiso propio.
    $user = crearUsuarioDePrueba();
    $producto = crearProductoDePrueba('VSR-001');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(30)->toDateString(), 3);

    $response = $this->actingAs($user)->from('/ventas/create')->post('/ventas', [
        'items' => [
            ['producto_id' => $producto->id, 'cantidad' => 10, 'precio' => 5, 'descuento' => 0],
        ],
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['items.0.cantidad']);
    expect(session('errors')->first('items.0.cantidad'))
        ->toBe("Stock insuficiente para {$producto->nombre}. Disponible: 3 unidades.");
});

test('stock insuficiente: la vista reconstruye la fila con el error y old() al volver', function () {
    $user = crearUsuarioDePrueba();
    $producto = crearProductoDePrueba('VSR-002');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(30)->toDateString(), 3);

    $response = $this->actingAs($user)->from('/ventas/create')->followingRedirects()->post('/ventas', [
        'items' => [
            ['producto_id' => $producto->id, 'cantidad' => 10, 'precio' => 5, 'descuento' => 0],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertSee('Stock insuficiente', false);
    $response->assertSee($producto->nombre, false);
    expect(Venta::count())->toBe(0);
    // el stock del lote no se tocó (rollback de la transacción)
    expect($producto->lotes()->first()->stock)->toBe(3);
});

test('venta exitosa no deja restos de OLD_ITEMS en el formulario', function () {
    $user = crearUsuarioDePrueba();
    $producto = crearProductoDePrueba('VSR-003');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(30)->toDateString(), 10);

    $this->actingAs($user)->post('/ventas', [
        'items' => [
            ['producto_id' => $producto->id, 'cantidad' => 2, 'precio' => 5, 'descuento' => 0],
        ],
    ])->assertRedirect(route('ventas.index'));

    expect(Venta::count())->toBe(1);

    $response = $this->actingAs($user)->get('/ventas/create');
    $response->assertSee('const OLD_ITEMS    = [];', false);
});
