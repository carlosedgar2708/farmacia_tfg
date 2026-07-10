<?php

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

test('el valor de un lote es stock por costo unitario', function () {
    $producto = crearProductoDePrueba('SV-001');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(10)->toDateString(), 10, 2.5);

    $response = $this->actingAs($this->admin)->get('/reportes/stock-valorizado');
    $lotes = $response->viewData('lotes');

    $fila = collect($lotes->items())->firstWhere('nro_lote', 'L-1');
    expect($fila)->not->toBeNull();
    expect((float) $fila->valor)->toBe(25.0);
});

test('lote con stock cero no aparece en el reporte', function () {
    $producto = crearProductoDePrueba('SV-002');
    crearLoteDePrueba($producto, 'L-SIN-STOCK', now()->addDays(10)->toDateString(), 0, 5);

    $response = $this->actingAs($this->admin)->get('/reportes/stock-valorizado');
    $nros = collect($response->viewData('lotes')->items())->pluck('nro_lote');

    expect($nros)->not->toContain('L-SIN-STOCK');
});

test('filtro por producto_id limita los resultados a ese producto', function () {
    $productoA = crearProductoDePrueba('SV-003A');
    $productoB = crearProductoDePrueba('SV-003B');
    crearLoteDePrueba($productoA, 'L-A', now()->addDays(10)->toDateString(), 5, 3);
    crearLoteDePrueba($productoB, 'L-B', now()->addDays(10)->toDateString(), 5, 3);

    $response = $this->actingAs($this->admin)->get("/reportes/stock-valorizado?producto_id={$productoA->id}");
    $nros = collect($response->viewData('lotes')->items())->pluck('nro_lote');

    expect($nros)->toContain('L-A');
    expect($nros)->not->toContain('L-B');
    expect((float) $response->viewData('total'))->toBe(15.0);
});

test('el total general coincide con la suma de los valores filtrados', function () {
    $producto = crearProductoDePrueba('SV-004');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(10)->toDateString(), 10, 2);
    crearLoteDePrueba($producto, 'L-2', now()->addDays(10)->toDateString(), 4, 5);
    // stock cero no debe sumar al total
    crearLoteDePrueba($producto, 'L-3', now()->addDays(10)->toDateString(), 0, 100);

    $response = $this->actingAs($this->admin)->get('/reportes/stock-valorizado');

    expect((float) $response->viewData('total'))->toBe(40.0); // (10*2) + (4*5)
});

test('el orden por defecto es por valor descendente', function () {
    $producto = crearProductoDePrueba('SV-005');
    crearLoteDePrueba($producto, 'L-BAJO', now()->addDays(10)->toDateString(), 1, 1);
    crearLoteDePrueba($producto, 'L-ALTO', now()->addDays(10)->toDateString(), 10, 10);
    crearLoteDePrueba($producto, 'L-MEDIO', now()->addDays(10)->toDateString(), 5, 5);

    $response = $this->actingAs($this->admin)->get('/reportes/stock-valorizado');
    $orden = collect($response->viewData('lotes')->items())->pluck('nro_lote')->values()->all();

    expect($orden)->toBe(['L-ALTO', 'L-MEDIO', 'L-BAJO']);
});

test('filtros se conservan tras la busqueda', function () {
    $producto = crearProductoDePrueba('SV-006');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(10)->toDateString(), 5, 2);

    $response = $this->actingAs($this->admin)->get("/reportes/stock-valorizado?producto_id={$producto->id}");

    expect($response->viewData('filtros')['producto_id'])->toBe($producto->id);
});

test('sin resultados muestra un mensaje amigable en vez de tabla vacia', function () {
    $producto = crearProductoDePrueba('SV-007');

    $response = $this->actingAs($this->admin)->get("/reportes/stock-valorizado?producto_id={$producto->id}");

    $response->assertStatus(200);
    $response->assertSee('No hay lotes con stock que coincidan con este filtro');
});

test('pagina fuera de rango no produce error 500', function () {
    $response = $this->actingAs($this->admin)->get('/reportes/stock-valorizado?page=999');

    $response->assertStatus(200);
});

test('el indice de reportes lista ambos reportes con enlaces validos', function () {
    $response = $this->actingAs($this->admin)->get('/reportes');

    $response->assertStatus(200);
    $response->assertSee(route('reportes.vencimientos'), false);
    $response->assertSee(route('reportes.stockValorizado'), false);
});

test('acceso sin permiso reportes.ver es bloqueado con 403', function () {
    $response = $this->actingAs($this->vendedor)->get('/reportes/stock-valorizado');

    $response->assertStatus(403);
});

test('acceso con permiso reportes.ver responde 200', function () {
    $response = $this->actingAs($this->admin)->get('/reportes/stock-valorizado');

    $response->assertStatus(200);
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/reportes/stock-valorizado');

    $response->assertRedirect('/login');
});
