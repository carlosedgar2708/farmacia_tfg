<?php

use App\Models\Compra;
use App\Models\Configuracion;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
use App\Models\MovimientoStock;
use App\Models\Proveedor;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    // El cache "array" no se resetea entre tests dentro del mismo proceso
    // (RefreshDatabase solo revierte la BD), así que se limpia explícitamente
    // (mismo patrón que ReporteStockBajoTest).
    Cache::forget('configuracion.stock_bajo_umbral');
    Cache::forget('configuracion.dias_alerta_vencimiento');

    $this->user = crearUsuarioDePrueba();
});

function crearProveedorDePruebaDashboard(): Proveedor
{
    return Proveedor::create(['nombre' => 'Proveedor ' . uniqid()]);
}

function crearVentaConDetalleDashboard(string $fecha, int $cantidad, float $precioUnitario): Venta
{
    $producto = crearProductoDePrueba('V-' . uniqid());
    $lote = crearLoteDePrueba($producto, 'L-' . uniqid(), null, 100);

    $venta = Venta::create([
        'fecha_venta' => $fecha,
        'user_id' => crearUsuarioDePrueba()->id,
        'estado' => 'confirmada',
    ]);

    DetalleVenta::create([
        'venta_id' => $venta->id,
        'producto_id' => $producto->id,
        'lote_id' => $lote->id,
        'cantidad' => $cantidad,
        'precio_unitario' => $precioUnitario,
    ]);

    return $venta;
}

function crearCompraConDetalleDashboard(string $fecha, int $cantidad, float $costoUnitario): Compra
{
    $producto = crearProductoDePrueba('C-' . uniqid());
    $lote = crearLoteDePrueba($producto, 'L-' . uniqid(), null, 100);

    $compra = Compra::create([
        'fecha' => $fecha,
        'proveedor_id' => crearProveedorDePruebaDashboard()->id,
        'user_id' => crearUsuarioDePrueba()->id,
    ]);

    DetalleCompra::create([
        'compra_id' => $compra->id,
        'lote_id' => $lote->id,
        'cantidad' => $cantidad,
        'costo_unitario' => $costoUnitario,
    ]);

    return $compra;
}

// ===================== Qué pasó hoy =====================

test('ventas de hoy suman cantidad y monto correctos, ignorando ventas de otros dias', function () {
    crearVentaConDetalleDashboard(now()->toDateString(), 3, 10);
    crearVentaConDetalleDashboard(now()->subDay()->toDateString(), 5, 20);

    $response = $this->actingAs($this->user)->get('/inicio');

    expect($response->viewData('ventasHoy')['cantidad'])->toBe(1);
    expect($response->viewData('ventasHoy')['monto'])->toBe(30.0);
});

test('compras de hoy suman cantidad y monto correctos, ignorando compras de otros dias', function () {
    crearCompraConDetalleDashboard(now()->toDateString(), 4, 15);
    crearCompraConDetalleDashboard(now()->subDay()->toDateString(), 2, 8);

    $response = $this->actingAs($this->user)->get('/inicio');

    expect($response->viewData('comprasHoy')['cantidad'])->toBe(1);
    expect($response->viewData('comprasHoy')['monto'])->toBe(60.0);
});

test('sin ventas ni compras hoy los totales son cero', function () {
    $response = $this->actingAs($this->user)->get('/inicio');

    expect($response->viewData('ventasHoy'))->toBe(['cantidad' => 0, 'monto' => 0.0]);
    expect($response->viewData('comprasHoy'))->toBe(['cantidad' => 0, 'monto' => 0.0]);
});

// ===================== Stock bajo =====================

test('stock bajo del dashboard respeta el umbral configurado y muestra maximo 5', function () {
    Configuracion::establecer('stock_bajo_umbral', 10);

    foreach (range(1, 6) as $i) {
        $producto = crearProductoDePrueba("SB-DASH-{$i}");
        crearLoteDePrueba($producto, 'L-1', now()->addDays(10)->toDateString(), $i);
    }
    // Por encima del umbral: no debe aparecer.
    $productoAlto = crearProductoDePrueba('SB-DASH-ALTO');
    crearLoteDePrueba($productoAlto, 'L-1', now()->addDays(10)->toDateString(), 50);

    $response = $this->actingAs($this->user)->get('/inicio');
    $stockBajo = $response->viewData('stockBajo');

    expect($stockBajo)->toHaveCount(5);
    expect($stockBajo->pluck('codigo'))->not->toContain('SB-DASH-ALTO');
});

test('producto sin lotes se marca como sin_stock en el dashboard', function () {
    $producto = crearProductoDePrueba('SB-DASH-SIN');

    $response = $this->actingAs($this->user)->get('/inicio');
    $fila = $response->viewData('stockBajo')->firstWhere('codigo', 'SB-DASH-SIN');

    expect($fila->estado_stock)->toBe('sin_stock');
});

// ===================== Últimos movimientos =====================

test('ultimos movimientos se listan del mas reciente al mas antiguo y limitados a 10', function () {
    $producto = crearProductoDePrueba('MOV-DASH-1');
    $lote = crearLoteDePrueba($producto, 'L-1', null, 100);

    foreach (range(1, 12) as $i) {
        MovimientoStock::create([
            'lote_id' => $lote->id,
            'fecha' => now()->subDays(12 - $i)->toDateString(),
            'tipo' => 'Entrada',
            'motivo' => 'Compra',
            'cantidad' => 1,
        ]);
    }

    $response = $this->actingAs($this->user)->get('/inicio');
    $movimientos = $response->viewData('ultimosMovimientos');

    expect($movimientos)->toHaveCount(10);
    expect($movimientos->first()->fecha->isToday())->toBeTrue();
});

// ===================== Vista / Design System =====================

test('el dashboard sin datos muestra los estados vacios del Design System', function () {
    $response = $this->actingAs($this->user)->get('/inicio');

    $response->assertStatus(200);
    $response->assertSee('No hay productos vencidos.');
    $response->assertSee('No hay productos próximos a vencer.');
    $response->assertSee('No hay productos con stock bajo.');
    $response->assertSee('Todavía no hay movimientos registrados.');
});

test('el boton de registrar devolucion se renderiza deshabilitado', function () {
    $response = $this->actingAs($this->user)->get('/inicio');

    $response->assertSee('Registrar devolución', false);
    $response->assertSee('disabled', false);
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/inicio');

    $response->assertRedirect('/login');
});
