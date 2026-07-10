<?php

use App\Models\Configuracion;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function crearUsuario(): User
{
    return User::create([
        'username' => 'user-' . uniqid(),
        'name' => 'Usuario de Prueba',
        'email' => uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'activo' => true,
    ]);
}

function crearProducto(string $codigo): Producto
{
    return Producto::create([
        'codigo' => $codigo,
        'nombre' => "Producto {$codigo}",
        'es_inyectable' => false,
        'precio_venta' => 10,
    ]);
}

function crearLote(Producto $producto, string $nroLote, ?string $fechaVencimiento, int $stock): Lote
{
    return Lote::create([
        'producto_id' => $producto->id,
        'nro_lote' => $nroLote,
        'fecha_vencimiento' => $fechaVencimiento,
        'costo_unitario' => 5,
        'stock' => $stock,
    ]);
}

beforeEach(function () {
    $this->user = crearUsuario();
    Configuracion::establecer('dias_alerta_vencimiento', 90);
});

test('producto con un lote vencido y otro proximo aparece una sola vez como VENCIDO', function () {
    $producto = crearProducto('P-001');
    crearLote($producto, 'L-VENCIDO', now()->subDays(5)->toDateString(), 10);
    crearLote($producto, 'L-PROXIMO', now()->addDays(10)->toDateString(), 10);

    $response = $this->actingAs($this->user)->get('/inicio');
    $proximos = $response->viewData('proximosVencer');

    $delProducto = $proximos->where('id', $producto->id);

    expect($delProducto)->toHaveCount(1);
    expect($delProducto->first()->estado_vencimiento)->toBe('vencido');
    expect($delProducto->first()->lote_relevante->nro_lote)->toBe('L-VENCIDO');
});

test('producto con varios lotes proximos a vencer muestra solo el mas proximo', function () {
    $producto = crearProducto('P-002');
    crearLote($producto, 'L-LEJANO', now()->addDays(80)->toDateString(), 10);
    crearLote($producto, 'L-CERCANO', now()->addDays(10)->toDateString(), 10);
    crearLote($producto, 'L-MEDIO', now()->addDays(40)->toDateString(), 10);

    $response = $this->actingAs($this->user)->get('/inicio');
    $proximos = $response->viewData('proximosVencer');

    $delProducto = $proximos->where('id', $producto->id);

    expect($delProducto)->toHaveCount(1);
    expect($delProducto->first()->estado_vencimiento)->toBe('proximo');
    expect($delProducto->first()->lote_relevante->nro_lote)->toBe('L-CERCANO');
});

test('producto con lote vencido sin stock y lote vigente proximo no se marca como VENCIDO', function () {
    $producto = crearProducto('P-003');
    crearLote($producto, 'L-VENCIDO-SIN-STOCK', now()->subDays(5)->toDateString(), 0);
    crearLote($producto, 'L-VIGENTE-PROXIMO', now()->addDays(10)->toDateString(), 10);

    $response = $this->actingAs($this->user)->get('/inicio');
    $proximos = $response->viewData('proximosVencer');

    $delProducto = $proximos->where('id', $producto->id);

    expect($delProducto)->toHaveCount(1);
    expect($delProducto->first()->estado_vencimiento)->toBe('proximo');
    expect($delProducto->first()->lote_relevante->nro_lote)->toBe('L-VIGENTE-PROXIMO');
});

test('producto sin alertas no aparece en el widget', function () {
    $producto = crearProducto('P-004');
    crearLote($producto, 'L-LEJANO', now()->addDays(200)->toDateString(), 10);
    crearLote($producto, 'L-SIN-FECHA', null, 10);

    $response = $this->actingAs($this->user)->get('/inicio');
    $proximos = $response->viewData('proximosVencer');

    expect($proximos->pluck('id'))->not->toContain($producto->id);
});

test('cambiar dias_alerta_vencimiento actualiza inmediatamente el dashboard', function () {
    $producto = crearProducto('P-005');
    crearLote($producto, 'L-45D', now()->addDays(45)->toDateString(), 10);

    Configuracion::establecer('dias_alerta_vencimiento', 30);
    $response1 = $this->actingAs($this->user)->get('/inicio');
    expect($response1->viewData('proximosVencer')->pluck('id'))->not->toContain($producto->id);

    Configuracion::establecer('dias_alerta_vencimiento', 60);
    $response2 = $this->actingAs($this->user)->get('/inicio');
    expect($response2->viewData('proximosVencer')->pluck('id'))->toContain($producto->id);
});
