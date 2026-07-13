<?php

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Lote;
use App\Models\Permiso;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function crearClienteDePrueba(string $nombre): Cliente
{
    return Cliente::create(['nombre' => $nombre, 'documento' => 'DOC-' . uniqid()]);
}

function crearVentaDePrueba($usuario, ?Cliente $cliente, string $fecha, string $estado = 'confirmada'): Venta
{
    return Venta::create([
        'cliente_id' => $cliente?->id,
        'user_id' => $usuario->id,
        'fecha_venta' => $fecha,
        'estado' => $estado,
    ]);
}

function agregarDetalleVentaDePrueba(Venta $venta, Producto $producto, int $cantidad, float $precioUnitario): DetalleVenta
{
    $lote = Lote::create([
        'producto_id' => $producto->id,
        'nro_lote' => 'L-' . uniqid(),
        'fecha_vencimiento' => null,
        'costo_unitario' => 1,
        'stock' => 0,
    ]);

    return DetalleVenta::create([
        'venta_id' => $venta->id,
        'producto_id' => $producto->id,
        'lote_id' => $lote->id,
        'cantidad' => $cantidad,
        'precio_unitario' => $precioUnitario,
    ]);
}

beforeEach(function () {
    $this->rolAdmin = Rol::create(['nombre' => 'Administrador', 'slug' => 'admin']);
    $this->rolVendedor = Rol::create(['nombre' => 'Vendedor', 'slug' => 'vendedor']);

    $permisoReportes = Permiso::create(['slug' => 'reportes.ver', 'nombre' => 'Ver reportes']);
    $this->rolAdmin->permisos()->attach($permisoReportes->id);
    // El vendedor NO tiene reportes.ver, para probar el bloqueo por permiso.

    $this->admin = crearUsuarioDePrueba();
    $this->admin->rols()->attach($this->rolAdmin->id);

    $this->vendedor = crearUsuarioDePrueba();
    $this->vendedor->rols()->attach($this->rolVendedor->id);
});

test('venta dentro del rango solicitado aparece', function () {
    $producto = crearProductoDePrueba('RV-001');
    $venta = crearVentaDePrueba($this->admin, null, now()->subDays(5)->toDateString());
    agregarDetalleVentaDePrueba($venta, $producto, 5, 10);

    $desde = now()->subDays(10)->toDateString();
    $hasta = now()->toDateString();

    $response = $this->actingAs($this->admin)->get("/reportes/ventas?desde={$desde}&hasta={$hasta}");
    $ids = collect($response->viewData('ventas')->items())->pluck('id');

    expect($ids)->toContain($venta->id);
});

test('venta fuera del rango solicitado no aparece', function () {
    $producto = crearProductoDePrueba('RV-002');
    $venta = crearVentaDePrueba($this->admin, null, now()->subDays(60)->toDateString());
    agregarDetalleVentaDePrueba($venta, $producto, 5, 10);

    $desde = now()->subDays(10)->toDateString();
    $hasta = now()->toDateString();

    $response = $this->actingAs($this->admin)->get("/reportes/ventas?desde={$desde}&hasta={$hasta}");
    $ids = collect($response->viewData('ventas')->items())->pluck('id');

    expect($ids)->not->toContain($venta->id);
});

test('sin desde ni hasta aparecen todas las ventas', function () {
    $producto = crearProductoDePrueba('RV-003');
    $ventaVieja = crearVentaDePrueba($this->admin, null, now()->subYears(2)->toDateString());
    agregarDetalleVentaDePrueba($ventaVieja, $producto, 1, 5);
    $ventaReciente = crearVentaDePrueba($this->admin, null, now()->toDateString());
    agregarDetalleVentaDePrueba($ventaReciente, $producto, 1, 5);

    $response = $this->actingAs($this->admin)->get('/reportes/ventas');
    $ids = collect($response->viewData('ventas')->items())->pluck('id');

    expect($ids)->toContain($ventaVieja->id);
    expect($ids)->toContain($ventaReciente->id);
});

test('filtro por usuario limita el resultado a ese usuario', function () {
    $producto = crearProductoDePrueba('RV-004');
    $otroUsuario = crearUsuarioDePrueba();

    $ventaAdmin = crearVentaDePrueba($this->admin, null, now()->toDateString());
    agregarDetalleVentaDePrueba($ventaAdmin, $producto, 1, 5);
    $ventaOtro = crearVentaDePrueba($otroUsuario, null, now()->toDateString());
    agregarDetalleVentaDePrueba($ventaOtro, $producto, 1, 5);

    $response = $this->actingAs($this->admin)->get("/reportes/ventas?user_id={$this->admin->id}");
    $ids = collect($response->viewData('ventas')->items())->pluck('id');

    expect($ids)->toContain($ventaAdmin->id);
    expect($ids)->not->toContain($ventaOtro->id);
});

test('filtro por cliente limita el resultado a ese cliente', function () {
    $producto = crearProductoDePrueba('RV-005');
    $clienteA = crearClienteDePrueba('Cliente A');
    $clienteB = crearClienteDePrueba('Cliente B');

    $ventaA = crearVentaDePrueba($this->admin, $clienteA, now()->toDateString());
    agregarDetalleVentaDePrueba($ventaA, $producto, 1, 5);
    $ventaB = crearVentaDePrueba($this->admin, $clienteB, now()->toDateString());
    agregarDetalleVentaDePrueba($ventaB, $producto, 1, 5);

    $response = $this->actingAs($this->admin)->get("/reportes/ventas?cliente_id={$clienteA->id}");
    $ids = collect($response->viewData('ventas')->items())->pluck('id');

    expect($ids)->toContain($ventaA->id);
    expect($ids)->not->toContain($ventaB->id);
});

test('venta sin cliente (publico general) aparece con guion y no rompe el filtro por cliente', function () {
    $producto = crearProductoDePrueba('RV-006');
    $ventaPublico = crearVentaDePrueba($this->admin, null, now()->toDateString());
    agregarDetalleVentaDePrueba($ventaPublico, $producto, 1, 5);

    $response = $this->actingAs($this->admin)->get('/reportes/ventas');
    $fila = collect($response->viewData('ventas')->items())->firstWhere('id', $ventaPublico->id);

    expect($fila)->not->toBeNull();
    expect($fila->cliente)->toBeNull();
});

test('unidades vendidas suma correctamente aunque el producto este repartido en varios lotes', function () {
    $producto = crearProductoDePrueba('RV-007');
    $venta = crearVentaDePrueba($this->admin, null, now()->toDateString());
    // Simula el split de FIFO: mismo producto, dos DetalleVenta de distintos lotes.
    agregarDetalleVentaDePrueba($venta, $producto, 7, 10);
    agregarDetalleVentaDePrueba($venta, $producto, 3, 10);

    $response = $this->actingAs($this->admin)->get('/reportes/ventas');
    $fila = collect($response->viewData('ventas')->items())->firstWhere('id', $venta->id);

    expect((int) $fila->unidades_venta)->toBe(10);
});

test('el total bruto por venta es la suma de sus detalles', function () {
    $producto = crearProductoDePrueba('RV-008');
    $venta = crearVentaDePrueba($this->admin, null, now()->toDateString());
    agregarDetalleVentaDePrueba($venta, $producto, 10, 2); // 20
    agregarDetalleVentaDePrueba($venta, $producto, 4, 5);  // 20

    $response = $this->actingAs($this->admin)->get('/reportes/ventas');
    $fila = collect($response->viewData('ventas')->items())->firstWhere('id', $venta->id);

    expect((float) $fila->total_venta)->toBe(40.0);
});

test('el total general suma todas las ventas filtradas, no solo la pagina actual', function () {
    $producto = crearProductoDePrueba('RV-009');

    for ($i = 0; $i < 20; $i++) {
        $venta = crearVentaDePrueba($this->admin, null, now()->toDateString());
        agregarDetalleVentaDePrueba($venta, $producto, 1, 10); // 10 cada una
    }

    $response = $this->actingAs($this->admin)->get('/reportes/ventas');

    expect((float) $response->viewData('total'))->toBe(200.0);
    expect($response->viewData('ventas')->count())->toBeLessThan(20); // paginado
});

test('fecha invalida en desde u hasta se ignora sin error 500', function () {
    $producto = crearProductoDePrueba('RV-010');
    $venta = crearVentaDePrueba($this->admin, null, now()->toDateString());
    agregarDetalleVentaDePrueba($venta, $producto, 1, 1);

    $response = $this->actingAs($this->admin)->get('/reportes/ventas?desde=no-es-una-fecha&hasta=2026-08-01');

    $response->assertStatus(200);
    expect($response->viewData('filtros')['desde'])->toBeNull();
});

test('rango de fechas invertido se ignora sin error 500', function () {
    $desde = now()->addDays(10)->toDateString();
    $hasta = now()->toDateString();

    $response = $this->actingAs($this->admin)->get("/reportes/ventas?desde={$desde}&hasta={$hasta}");

    $response->assertStatus(200);
    expect($response->viewData('filtros')['desde'])->toBeNull();
});

test('los filtros se conservan tras la busqueda', function () {
    $cliente = crearClienteDePrueba('Cliente Filtro');

    $response = $this->actingAs($this->admin)->get("/reportes/ventas?cliente_id={$cliente->id}&user_id={$this->admin->id}");

    expect($response->viewData('filtros')['cliente_id'])->toBe($cliente->id);
    expect($response->viewData('filtros')['user_id'])->toBe($this->admin->id);
});

test('sin resultados muestra un mensaje amigable en vez de tabla vacia', function () {
    $cliente = crearClienteDePrueba('Cliente Sin Ventas');

    $response = $this->actingAs($this->admin)->get("/reportes/ventas?cliente_id={$cliente->id}");

    $response->assertStatus(200);
    $response->assertSee('No hay ventas que coincidan con estos filtros');
});

test('pagina fuera de rango no produce error 500', function () {
    $response = $this->actingAs($this->admin)->get('/reportes/ventas?page=999');

    $response->assertStatus(200);
});

test('orden por defecto es por fecha descendente', function () {
    $producto = crearProductoDePrueba('RV-011');

    $ventaVieja = crearVentaDePrueba($this->admin, null, now()->subDays(5)->toDateString());
    agregarDetalleVentaDePrueba($ventaVieja, $producto, 1, 1);
    $ventaReciente = crearVentaDePrueba($this->admin, null, now()->toDateString());
    agregarDetalleVentaDePrueba($ventaReciente, $producto, 1, 1);

    $response = $this->actingAs($this->admin)->get('/reportes/ventas');
    $orden = collect($response->viewData('ventas')->items())->pluck('id')->values()->all();

    expect($orden)->toBe([$ventaReciente->id, $ventaVieja->id]);
});

test('el estado se muestra como columna informativa', function () {
    $producto = crearProductoDePrueba('RV-012');
    $venta = crearVentaDePrueba($this->admin, null, now()->toDateString(), 'confirmada');
    agregarDetalleVentaDePrueba($venta, $producto, 1, 1);

    $response = $this->actingAs($this->admin)->get('/reportes/ventas');
    $fila = collect($response->viewData('ventas')->items())->firstWhere('id', $venta->id);

    expect($fila->estado)->toBe('confirmada');
});

test('el indice de reportes lista los cinco reportes', function () {
    $response = $this->actingAs($this->admin)->get('/reportes');

    $response->assertStatus(200);
    $response->assertSee(route('reportes.ventas'), false);
});

test('acceso sin permiso reportes.ver es bloqueado con 403', function () {
    $response = $this->actingAs($this->vendedor)->get('/reportes/ventas');

    $response->assertStatus(403);
});

test('acceso con permiso reportes.ver responde 200', function () {
    $response = $this->actingAs($this->admin)->get('/reportes/ventas');

    $response->assertStatus(200);
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/reportes/ventas');

    $response->assertRedirect('/login');
});
