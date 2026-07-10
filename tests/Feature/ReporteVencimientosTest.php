<?php

use App\Models\Configuracion;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Configuracion::establecer('dias_alerta_vencimiento', 90);

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

test('lote vigente dentro del rango por defecto aparece como proximo a vencer', function () {
    $producto = crearProductoDePrueba('P-001');
    crearLoteDePrueba($producto, 'L-CERCANO', now()->addDays(10)->toDateString(), 10);

    $response = $this->actingAs($this->admin)->get('/reportes/vencimientos');
    $lotes = $response->viewData('lotes');

    expect(collect($lotes->items())->pluck('nro_lote'))->toContain('L-CERCANO');
    $fila = collect($lotes->items())->firstWhere('nro_lote', 'L-CERCANO');
    expect($fila->estado_vencimiento)->toBe('proximo');
});

test('lote vigente fuera del rango por defecto no aparece', function () {
    $producto = crearProductoDePrueba('P-002');
    crearLoteDePrueba($producto, 'L-LEJANO', now()->addDays(200)->toDateString(), 10);

    $response = $this->actingAs($this->admin)->get('/reportes/vencimientos');
    $lotes = $response->viewData('lotes');

    expect(collect($lotes->items())->pluck('nro_lote'))->not->toContain('L-LEJANO');
});

test('lote vencido con stock aparece como vencido sin importar la antiguedad', function () {
    $producto = crearProductoDePrueba('P-003');
    crearLoteDePrueba($producto, 'L-VENCIDO-VIEJO', now()->subYears(2)->toDateString(), 5);

    $response = $this->actingAs($this->admin)->get('/reportes/vencimientos');
    $lotes = $response->viewData('lotes');

    $fila = collect($lotes->items())->firstWhere('nro_lote', 'L-VENCIDO-VIEJO');
    expect($fila)->not->toBeNull();
    expect($fila->estado_vencimiento)->toBe('vencido');
});

test('filtro estado=vencidos excluye los proximos', function () {
    $producto = crearProductoDePrueba('P-004');
    crearLoteDePrueba($producto, 'L-VENCIDO', now()->subDays(5)->toDateString(), 5);
    crearLoteDePrueba($producto, 'L-PROXIMO', now()->addDays(10)->toDateString(), 5);

    $response = $this->actingAs($this->admin)->get('/reportes/vencimientos?estado=vencidos');
    $nros = collect($response->viewData('lotes')->items())->pluck('nro_lote');

    expect($nros)->toContain('L-VENCIDO');
    expect($nros)->not->toContain('L-PROXIMO');
});

test('filtro estado=proximos excluye los vencidos', function () {
    $producto = crearProductoDePrueba('P-005');
    crearLoteDePrueba($producto, 'L-VENCIDO', now()->subDays(5)->toDateString(), 5);
    crearLoteDePrueba($producto, 'L-PROXIMO', now()->addDays(10)->toDateString(), 5);

    $response = $this->actingAs($this->admin)->get('/reportes/vencimientos?estado=proximos');
    $nros = collect($response->viewData('lotes')->items())->pluck('nro_lote');

    expect($nros)->toContain('L-PROXIMO');
    expect($nros)->not->toContain('L-VENCIDO');
});

test('filtro de rango de fechas explicito reemplaza el default', function () {
    $producto = crearProductoDePrueba('P-006');
    crearLoteDePrueba($producto, 'L-EN-RANGO', now()->addDays(120)->toDateString(), 5);
    crearLoteDePrueba($producto, 'L-FUERA-DE-RANGO', now()->addDays(10)->toDateString(), 5);

    $desde = now()->addDays(100)->toDateString();
    $hasta = now()->addDays(150)->toDateString();

    $response = $this->actingAs($this->admin)->get("/reportes/vencimientos?desde={$desde}&hasta={$hasta}");
    $nros = collect($response->viewData('lotes')->items())->pluck('nro_lote');

    expect($nros)->toContain('L-EN-RANGO');
    expect($nros)->not->toContain('L-FUERA-DE-RANGO');
});

test('filtro por producto_id limita los resultados a ese producto', function () {
    $productoA = crearProductoDePrueba('P-007A');
    $productoB = crearProductoDePrueba('P-007B');
    crearLoteDePrueba($productoA, 'L-A', now()->addDays(10)->toDateString(), 5);
    crearLoteDePrueba($productoB, 'L-B', now()->addDays(10)->toDateString(), 5);

    $response = $this->actingAs($this->admin)->get("/reportes/vencimientos?producto_id={$productoA->id}");
    $nros = collect($response->viewData('lotes')->items())->pluck('nro_lote');

    expect($nros)->toContain('L-A');
    expect($nros)->not->toContain('L-B');
});

test('los vencidos aparecen antes que los proximos y cada grupo ordenado por fecha', function () {
    $producto = crearProductoDePrueba('P-008');
    crearLoteDePrueba($producto, 'L-VENCIDO-RECIENTE', now()->subDays(1)->toDateString(), 5);
    crearLoteDePrueba($producto, 'L-VENCIDO-ANTIGUO', now()->subDays(10)->toDateString(), 5);
    crearLoteDePrueba($producto, 'L-PROXIMO-LEJANO', now()->addDays(20)->toDateString(), 5);
    crearLoteDePrueba($producto, 'L-PROXIMO-CERCANO', now()->addDays(5)->toDateString(), 5);

    $response = $this->actingAs($this->admin)->get('/reportes/vencimientos');
    $orden = collect($response->viewData('lotes')->items())->pluck('nro_lote')->values()->all();

    expect($orden)->toBe(['L-VENCIDO-ANTIGUO', 'L-VENCIDO-RECIENTE', 'L-PROXIMO-CERCANO', 'L-PROXIMO-LEJANO']);
});

test('lote con stock cero nunca aparece', function () {
    $producto = crearProductoDePrueba('P-009');
    crearLoteDePrueba($producto, 'L-SIN-STOCK', now()->subDays(5)->toDateString(), 0);

    $response = $this->actingAs($this->admin)->get('/reportes/vencimientos?estado=todos');
    $nros = collect($response->viewData('lotes')->items())->pluck('nro_lote');

    expect($nros)->not->toContain('L-SIN-STOCK');
});

test('lote sin fecha de vencimiento nunca aparece', function () {
    $producto = crearProductoDePrueba('P-010');
    crearLoteDePrueba($producto, 'L-SIN-FECHA', null, 5);

    $response = $this->actingAs($this->admin)->get('/reportes/vencimientos');
    $nros = collect($response->viewData('lotes')->items())->pluck('nro_lote');

    expect($nros)->not->toContain('L-SIN-FECHA');
});

test('cambiar dias_alerta_vencimiento cambia el rango por defecto del reporte', function () {
    $producto = crearProductoDePrueba('P-011');
    crearLoteDePrueba($producto, 'L-45D', now()->addDays(45)->toDateString(), 5);

    Configuracion::establecer('dias_alerta_vencimiento', 30);
    $r1 = $this->actingAs($this->admin)->get('/reportes/vencimientos');
    expect(collect($r1->viewData('lotes')->items())->pluck('nro_lote'))->not->toContain('L-45D');

    Configuracion::establecer('dias_alerta_vencimiento', 60);
    $r2 = $this->actingAs($this->admin)->get('/reportes/vencimientos');
    expect(collect($r2->viewData('lotes')->items())->pluck('nro_lote'))->toContain('L-45D');
});

test('fecha invalida en desde u hasta no produce error 500 y se ignora', function () {
    $producto = crearProductoDePrueba('P-014');
    crearLoteDePrueba($producto, 'L-Y', now()->addDays(10)->toDateString(), 5);

    $response = $this->actingAs($this->admin)->get('/reportes/vencimientos?desde=no-es-una-fecha&hasta=2026-08-01');

    $response->assertStatus(200);
    expect($response->viewData('filtros')['desde'])->toBeNull();
});

test('rango de fechas invertido no produce error y se ignora', function () {
    $producto = crearProductoDePrueba('P-012');
    crearLoteDePrueba($producto, 'L-X', now()->addDays(10)->toDateString(), 5);

    $desde = now()->addDays(50)->toDateString();
    $hasta = now()->addDays(10)->toDateString();

    $response = $this->actingAs($this->admin)->get("/reportes/vencimientos?desde={$desde}&hasta={$hasta}");

    $response->assertStatus(200);
});

test('producto_id sin lotes en alerta muestra mensaje amigable sin error', function () {
    $producto = crearProductoDePrueba('P-013');

    $response = $this->actingAs($this->admin)->get("/reportes/vencimientos?producto_id={$producto->id}");

    $response->assertStatus(200);
    $response->assertSee('No hay lotes que coincidan con estos filtros');
});

test('pagina fuera de rango no produce error 500', function () {
    $response = $this->actingAs($this->admin)->get('/reportes/vencimientos?page=999');

    $response->assertStatus(200);
});

test('acceso sin permiso reportes.ver es bloqueado con 403', function () {
    $response = $this->actingAs($this->vendedor)->get('/reportes/vencimientos');

    $response->assertStatus(403);
});

test('acceso con permiso reportes.ver responde 200', function () {
    $response = $this->actingAs($this->admin)->get('/reportes/vencimientos');

    $response->assertStatus(200);
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/reportes/vencimientos');

    $response->assertRedirect('/login');
});
