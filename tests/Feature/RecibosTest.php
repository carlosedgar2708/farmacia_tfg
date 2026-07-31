<?php

use App\Models\Cliente;
use App\Models\Recibo;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = crearUsuarioDePrueba();
    $this->producto = crearProductoDePrueba('PR-RECIBO');
    crearLoteDePrueba($this->producto, 'L1', null, 50, 8);
    $this->cliente = Cliente::create(['nombre' => 'Cliente de prueba', 'documento' => null, 'telefono' => null]);
});

test('flujo completo: registrar venta crea el recibo y redirige a ventas.index con los botones de accion', function () {
    $response = $this->actingAs($this->user)->post('/ventas', [
        'cliente_id' => $this->cliente->id,
        'items' => [
            ['producto_id' => $this->producto->id, 'cantidad' => 4],
        ],
    ]);

    $venta = Venta::first();
    $recibo = Recibo::where('venta_id', $venta->id)->first();

    // Venta y recibo persistidos correctamente.
    expect($venta)->not->toBeNull();
    expect($recibo)->not->toBeNull();
    expect((float) $recibo->monto)->toBe((float) ($this->producto->precio_venta * 4));

    // Redirige a ventas.index (sin redirección forzada al recibo).
    $response->assertRedirect(route('ventas.index'));
    $response->assertSessionHas('recibo_id', $recibo->id);

    // La siguiente pagina (ventas.index) muestra los botones "Ver recibo" y "Nueva venta".
    $indexResponse = $this->actingAs($this->user)->get('/ventas');
    $indexResponse->assertSee('Ver recibo');
    $indexResponse->assertSee(route('recibos.show', $recibo->id), false);
    $indexResponse->assertSee('Nueva venta');
});

test('el recibo persistido muestra numero, fecha, cliente, vendedor, productos y total', function () {
    $this->actingAs($this->user)->post('/ventas', [
        'cliente_id' => $this->cliente->id,
        'items' => [
            ['producto_id' => $this->producto->id, 'cantidad' => 3],
        ],
    ]);

    $recibo = Recibo::first();

    $response = $this->actingAs($this->user)->get(route('recibos.show', $recibo->id));

    $response->assertStatus(200);
    $response->assertSee('Recibo N° ' . $recibo->id);
    $response->assertSee($recibo->venta->fecha_venta->format('Y-m-d'));
    $response->assertSee('Cliente de prueba');
    $response->assertSee($this->user->name);
    $response->assertSee('Producto PR-RECIBO');
    $response->assertSee(number_format($recibo->monto, 2));
    $response->assertSee('window.print()', false);
    $response->assertDontSee('<x-', false);
});

test('el recibo sin cliente muestra el guion como en el resto del sistema', function () {
    $this->actingAs($this->user)->post('/ventas', [
        'items' => [
            ['producto_id' => $this->producto->id, 'cantidad' => 1],
        ],
    ]);

    $recibo = Recibo::first();
    $response = $this->actingAs($this->user)->get(route('recibos.show', $recibo->id));

    $response->assertSee('—');
});

test('la vista del recibo incluye el media print que oculta sidebar, main-top y botones', function () {
    $this->actingAs($this->user)->post('/ventas', [
        'items' => [
            ['producto_id' => $this->producto->id, 'cantidad' => 1],
        ],
    ]);

    $recibo = Recibo::first();
    $response = $this->actingAs($this->user)->get(route('recibos.show', $recibo->id));

    $response->assertSee('@media print', false);
    $response->assertSee('.sidebar', false);
    $response->assertSee('.main-top', false);
    $response->assertSee('.print-actions', false);
});

test('el boton Imprimir recibo del formulario de venta fue eliminado (estaba inerte)', function () {
    $response = $this->actingAs($this->user)->get('/ventas/create');

    $response->assertDontSee('btnTicket', false);
});

test('sin sesion, ver un recibo redirige a login', function () {
    $venta = Venta::create([
        'cliente_id' => null, 'user_id' => $this->user->id,
        'fecha_venta' => now(), 'estado' => 'confirmada',
    ]);
    $recibo = Recibo::create(['venta_id' => $venta->id, 'monto' => 10]);

    $response = $this->get(route('recibos.show', $recibo->id));

    $response->assertRedirect('/login');
});
