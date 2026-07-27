<?php

use App\Models\DetalleVenta;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function crearVentaDePruebaIndex(): Venta
{
    $producto = crearProductoDePrueba('VI-' . uniqid());
    $lote = crearLoteDePrueba($producto, 'L-' . uniqid(), null, 100);

    $venta = Venta::create([
        'fecha_venta' => now(),
        'user_id' => crearUsuarioDePrueba()->id,
        'estado' => 'confirmada',
    ]);

    DetalleVenta::create([
        'venta_id' => $venta->id,
        'producto_id' => $producto->id,
        'lote_id' => $lote->id,
        'cantidad' => 4,
        'precio_unitario' => 10,
    ]);

    return $venta;
}

test('sin ventas se muestra el estado vacio del Design System', function () {
    $user = crearUsuarioDePrueba();

    $response = $this->actingAs($user)->get('/ventas');

    $response->assertStatus(200);
    $response->assertSee('No hay ventas registradas.');
});

test('con ventas la tabla muestra el total calculado', function () {
    $user = crearUsuarioDePrueba();
    crearVentaDePruebaIndex();

    $response = $this->actingAs($user)->get('/ventas');

    $response->assertSee('Bs. 40.00', false);
});

test('la vista no deja ningun componente Blade sin resolver', function () {
    $user = crearUsuarioDePrueba();
    crearVentaDePruebaIndex();

    $response = $this->actingAs($user)->get('/ventas');

    $response->assertDontSee('<x-', false);
});

test('el chip de estado se sigue viendo como neutral con el estado actual "confirmada"', function () {
    // Hallazgo documentado en docs/pendientes.md: VentaController::store() guarda
    // 'confirmada', que no está en el mapa ok/warn/bad de la vista.
    $user = crearUsuarioDePrueba();
    crearVentaDePruebaIndex();

    $response = $this->actingAs($user)->get('/ventas');

    $response->assertSee('class="chip chip-neutral"', false);
    $response->assertSee('Confirmada');
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/ventas');

    $response->assertRedirect('/login');
});
