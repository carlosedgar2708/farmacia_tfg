<?php

use App\Models\Configuracion;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    // El cache "array" no se resetea entre tests dentro del mismo proceso
    // (RefreshDatabase solo revierte la BD, no el cache), así que se limpia
    // explícitamente para que el fallback a 30 sea determinista.
    Cache::forget('configuracion.stock_bajo_umbral');

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

test('producto con stock total por debajo del umbral por defecto aparece', function () {
    $producto = crearProductoDePrueba('SB-001');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(10)->toDateString(), 5);

    $response = $this->actingAs($this->admin)->get('/reportes/stock-bajo');
    $codigos = collect($response->viewData('productos')->items())->pluck('codigo');

    expect($codigos)->toContain('SB-001');
});

test('producto con stock total igual o por encima del umbral no aparece', function () {
    $producto = crearProductoDePrueba('SB-002');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(10)->toDateString(), 50);

    $response = $this->actingAs($this->admin)->get('/reportes/stock-bajo');
    $codigos = collect($response->viewData('productos')->items())->pluck('codigo');

    expect($codigos)->not->toContain('SB-002');
});

test('producto sin ningun lote registrado aparece como sin stock', function () {
    $producto = crearProductoDePrueba('SB-003');

    $response = $this->actingAs($this->admin)->get('/reportes/stock-bajo');
    $items = collect($response->viewData('productos')->items());
    $fila = $items->firstWhere('codigo', 'SB-003');

    expect($fila)->not->toBeNull();
    expect((int) $fila->stock_total)->toBe(0);
    expect($fila->estado_stock)->toBe('sin_stock');
});

test('producto con stock mayor a cero pero bajo el umbral se marca como stock bajo, no sin stock', function () {
    $producto = crearProductoDePrueba('SB-004');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(10)->toDateString(), 5);

    $response = $this->actingAs($this->admin)->get('/reportes/stock-bajo');
    $fila = collect($response->viewData('productos')->items())->firstWhere('codigo', 'SB-004');

    expect($fila->estado_stock)->toBe('bajo');
});

test('umbral personalizado via query string cambia los resultados', function () {
    $producto = crearProductoDePrueba('SB-005');
    // Por encima del default (30): no debe aparecer sin filtro explícito.
    crearLoteDePrueba($producto, 'L-1', now()->addDays(10)->toDateString(), 35);

    $sinFiltro = $this->actingAs($this->admin)->get('/reportes/stock-bajo');
    expect(collect($sinFiltro->viewData('productos')->items())->pluck('codigo'))->not->toContain('SB-005');

    // Con un umbral mayor al default, 35 sí queda "bajo" ese umbral.
    $conUmbral = $this->actingAs($this->admin)->get('/reportes/stock-bajo?umbral=40');
    expect(collect($conUmbral->viewData('productos')->items())->pluck('codigo'))->toContain('SB-005');
});

test('orden por defecto es ascendente por stock total', function () {
    $productoAlto = crearProductoDePrueba('SB-006A');
    crearLoteDePrueba($productoAlto, 'L-1', now()->addDays(10)->toDateString(), 20);
    $productoBajo = crearProductoDePrueba('SB-006B');
    crearLoteDePrueba($productoBajo, 'L-1', now()->addDays(10)->toDateString(), 2);
    $productoSinStock = crearProductoDePrueba('SB-006C');

    $response = $this->actingAs($this->admin)->get('/reportes/stock-bajo?umbral=25');
    $orden = collect($response->viewData('productos')->items())->pluck('codigo')->values()->all();

    expect($orden)->toBe(['SB-006C', 'SB-006B', 'SB-006A']);
});

test('filtro por producto_id limita el resultado a ese producto', function () {
    $productoA = crearProductoDePrueba('SB-007A');
    crearLoteDePrueba($productoA, 'L-1', now()->addDays(10)->toDateString(), 5);
    $productoB = crearProductoDePrueba('SB-007B');
    crearLoteDePrueba($productoB, 'L-1', now()->addDays(10)->toDateString(), 5);

    $response = $this->actingAs($this->admin)->get("/reportes/stock-bajo?producto_id={$productoA->id}");
    $codigos = collect($response->viewData('productos')->items())->pluck('codigo');

    expect($codigos)->toContain('SB-007A');
    expect($codigos)->not->toContain('SB-007B');
});

test('los filtros se conservan tras la busqueda', function () {
    $producto = crearProductoDePrueba('SB-008');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(10)->toDateString(), 5);

    $response = $this->actingAs($this->admin)->get("/reportes/stock-bajo?umbral=15&producto_id={$producto->id}");

    expect($response->viewData('filtros')['umbral'])->toBe(15);
    expect($response->viewData('filtros')['producto_id'])->toBe($producto->id);
});

test('sin resultados muestra un mensaje amigable en vez de tabla vacia', function () {
    $producto = crearProductoDePrueba('SB-009');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(10)->toDateString(), 1000);

    $response = $this->actingAs($this->admin)->get("/reportes/stock-bajo?producto_id={$producto->id}");

    $response->assertStatus(200);
    $response->assertSee('No hay productos con stock por debajo de');
});

test('pagina fuera de rango no produce error 500', function () {
    $response = $this->actingAs($this->admin)->get('/reportes/stock-bajo?page=999');

    $response->assertStatus(200);
});

test('sin clave stock_bajo_umbral en configuracion usa el default 30', function () {
    $producto = crearProductoDePrueba('SB-010');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(10)->toDateString(), 25);

    $response = $this->actingAs($this->admin)->get('/reportes/stock-bajo');

    expect($response->viewData('umbral'))->toBe(30);
    expect(collect($response->viewData('productos')->items())->pluck('codigo'))->toContain('SB-010');
});

test('cambiar stock_bajo_umbral en configuracion cambia el default del reporte', function () {
    $producto = crearProductoDePrueba('SB-011');
    crearLoteDePrueba($producto, 'L-1', now()->addDays(10)->toDateString(), 25);

    Configuracion::establecer('stock_bajo_umbral', 10);
    $r1 = $this->actingAs($this->admin)->get('/reportes/stock-bajo');
    expect(collect($r1->viewData('productos')->items())->pluck('codigo'))->not->toContain('SB-011');

    Configuracion::establecer('stock_bajo_umbral', 30);
    $r2 = $this->actingAs($this->admin)->get('/reportes/stock-bajo');
    expect(collect($r2->viewData('productos')->items())->pluck('codigo'))->toContain('SB-011');
});

test('el indice de reportes lista los tres reportes', function () {
    $response = $this->actingAs($this->admin)->get('/reportes');

    $response->assertStatus(200);
    $response->assertSee(route('reportes.stockBajo'), false);
});

test('acceso sin permiso reportes.ver es bloqueado con 403', function () {
    $response = $this->actingAs($this->vendedor)->get('/reportes/stock-bajo');

    $response->assertStatus(403);
});

test('acceso con permiso reportes.ver responde 200', function () {
    $response = $this->actingAs($this->admin)->get('/reportes/stock-bajo');

    $response->assertStatus(200);
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/reportes/stock-bajo');

    $response->assertRedirect('/login');
});
