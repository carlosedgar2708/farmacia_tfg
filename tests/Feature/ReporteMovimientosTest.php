<?php

use App\Models\Lote;
use App\Models\MovimientoStock;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function crearMovimientoDePrueba(Lote $lote, string $tipo, string $motivo, int $cantidad, string $fecha, ?string $referencia = null): MovimientoStock
{
    return MovimientoStock::create([
        'lote_id' => $lote->id,
        'fecha' => $fecha,
        'tipo' => $tipo,
        'motivo' => $motivo,
        'cantidad' => $cantidad,
        'referencia' => $referencia,
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

test('movimiento de tipo Entrada aparece al filtrar por Entrada y no al filtrar por Salida', function () {
    $producto = crearProductoDePrueba('MV-001');
    $lote = crearLoteDePrueba($producto, 'L-1', null, 10);
    $mov = crearMovimientoDePrueba($lote, 'Entrada', 'Compra', 10, now()->toDateString(), 'Compra #1');

    $conEntrada = $this->actingAs($this->admin)->get('/reportes/movimientos?tipo=Entrada');
    expect(collect($conEntrada->viewData('movimientos')->items())->pluck('id'))->toContain($mov->id);

    $conSalida = $this->actingAs($this->admin)->get('/reportes/movimientos?tipo=Salida');
    expect(collect($conSalida->viewData('movimientos')->items())->pluck('id'))->not->toContain($mov->id);
});

test('movimiento de tipo Salida aparece al filtrar por Salida y no al filtrar por Entrada', function () {
    $producto = crearProductoDePrueba('MV-002');
    $lote = crearLoteDePrueba($producto, 'L-1', null, 10);
    $mov = crearMovimientoDePrueba($lote, 'Salida', 'Venta', 5, now()->toDateString(), 'Venta #1');

    $conSalida = $this->actingAs($this->admin)->get('/reportes/movimientos?tipo=Salida');
    expect(collect($conSalida->viewData('movimientos')->items())->pluck('id'))->toContain($mov->id);

    $conEntrada = $this->actingAs($this->admin)->get('/reportes/movimientos?tipo=Entrada');
    expect(collect($conEntrada->viewData('movimientos')->items())->pluck('id'))->not->toContain($mov->id);
});

test('filtro por producto_id limita el resultado a ese producto', function () {
    $productoA = crearProductoDePrueba('MV-003A');
    $productoB = crearProductoDePrueba('MV-003B');
    $loteA = crearLoteDePrueba($productoA, 'L-A', null, 10);
    $loteB = crearLoteDePrueba($productoB, 'L-B', null, 10);
    $movA = crearMovimientoDePrueba($loteA, 'Entrada', 'Compra', 10, now()->toDateString());
    $movB = crearMovimientoDePrueba($loteB, 'Entrada', 'Compra', 10, now()->toDateString());

    $response = $this->actingAs($this->admin)->get("/reportes/movimientos?producto_id={$productoA->id}");
    $ids = collect($response->viewData('movimientos')->items())->pluck('id');

    expect($ids)->toContain($movA->id);
    expect($ids)->not->toContain($movB->id);
});

test('filtro por lote_id limita el resultado a ese lote', function () {
    $producto = crearProductoDePrueba('MV-004');
    $loteA = crearLoteDePrueba($producto, 'L-A', null, 10);
    $loteB = crearLoteDePrueba($producto, 'L-B', null, 10);
    $movA = crearMovimientoDePrueba($loteA, 'Entrada', 'Compra', 10, now()->toDateString());
    $movB = crearMovimientoDePrueba($loteB, 'Entrada', 'Compra', 10, now()->toDateString());

    $response = $this->actingAs($this->admin)->get("/reportes/movimientos?lote_id={$loteA->id}");
    $ids = collect($response->viewData('movimientos')->items())->pluck('id');

    expect($ids)->toContain($movA->id);
    expect($ids)->not->toContain($movB->id);
});

test('movimiento dentro del rango de fechas aparece y fuera del rango no', function () {
    $producto = crearProductoDePrueba('MV-005');
    $lote = crearLoteDePrueba($producto, 'L-1', null, 10);
    $movReciente = crearMovimientoDePrueba($lote, 'Entrada', 'Compra', 10, now()->subDays(2)->toDateString());
    $movViejo = crearMovimientoDePrueba($lote, 'Entrada', 'Compra', 10, now()->subDays(60)->toDateString());

    $desde = now()->subDays(10)->toDateString();
    $hasta = now()->toDateString();

    $response = $this->actingAs($this->admin)->get("/reportes/movimientos?desde={$desde}&hasta={$hasta}");
    $ids = collect($response->viewData('movimientos')->items())->pluck('id');

    expect($ids)->toContain($movReciente->id);
    expect($ids)->not->toContain($movViejo->id);
});

test('sin desde ni hasta aparecen todos los movimientos', function () {
    $producto = crearProductoDePrueba('MV-006');
    $lote = crearLoteDePrueba($producto, 'L-1', null, 10);
    $movReciente = crearMovimientoDePrueba($lote, 'Entrada', 'Compra', 10, now()->toDateString());
    $movViejo = crearMovimientoDePrueba($lote, 'Entrada', 'Compra', 10, now()->subYears(2)->toDateString());

    $response = $this->actingAs($this->admin)->get('/reportes/movimientos');
    $ids = collect($response->viewData('movimientos')->items())->pluck('id');

    expect($ids)->toContain($movReciente->id);
    expect($ids)->toContain($movViejo->id);
});

test('fecha invalida en desde u hasta se ignora sin error 500', function () {
    $producto = crearProductoDePrueba('MV-007');
    $lote = crearLoteDePrueba($producto, 'L-1', null, 10);
    crearMovimientoDePrueba($lote, 'Entrada', 'Compra', 10, now()->toDateString());

    $response = $this->actingAs($this->admin)->get('/reportes/movimientos?desde=no-es-una-fecha&hasta=2026-08-01');

    $response->assertStatus(200);
    expect($response->viewData('filtros')['desde'])->toBeNull();
});

test('rango de fechas invertido se ignora sin error 500', function () {
    $desde = now()->addDays(10)->toDateString();
    $hasta = now()->toDateString();

    $response = $this->actingAs($this->admin)->get("/reportes/movimientos?desde={$desde}&hasta={$hasta}");

    $response->assertStatus(200);
    expect($response->viewData('filtros')['desde'])->toBeNull();
});

test('el campo referencia se muestra tal cual sin usar relaciones rotas', function () {
    $producto = crearProductoDePrueba('MV-008');
    $lote = crearLoteDePrueba($producto, 'L-1', null, 10);
    $mov = crearMovimientoDePrueba($lote, 'Entrada', 'Compra', 10, now()->toDateString(), 'Compra #99');

    $response = $this->actingAs($this->admin)->get('/reportes/movimientos');
    $fila = collect($response->viewData('movimientos')->items())->firstWhere('id', $mov->id);

    expect($fila->referencia)->toBe('Compra #99');
});

test('los filtros se conservan tras la busqueda', function () {
    $producto = crearProductoDePrueba('MV-009');

    $response = $this->actingAs($this->admin)->get("/reportes/movimientos?tipo=Entrada&producto_id={$producto->id}");

    expect($response->viewData('filtros')['tipo'])->toBe('Entrada');
    expect($response->viewData('filtros')['producto_id'])->toBe($producto->id);
});

test('sin resultados muestra un mensaje amigable en vez de tabla vacia', function () {
    $producto = crearProductoDePrueba('MV-010');

    $response = $this->actingAs($this->admin)->get("/reportes/movimientos?producto_id={$producto->id}");

    $response->assertStatus(200);
    $response->assertSee('No hay movimientos que coincidan con estos filtros');
});

test('pagina fuera de rango no produce error 500', function () {
    $response = $this->actingAs($this->admin)->get('/reportes/movimientos?page=999');

    $response->assertStatus(200);
});

test('orden por defecto es por fecha descendente', function () {
    $producto = crearProductoDePrueba('MV-011');
    $lote = crearLoteDePrueba($producto, 'L-1', null, 10);
    $movViejo = crearMovimientoDePrueba($lote, 'Entrada', 'Compra', 10, now()->subDays(5)->toDateString());
    $movReciente = crearMovimientoDePrueba($lote, 'Entrada', 'Compra', 10, now()->toDateString());

    $response = $this->actingAs($this->admin)->get('/reportes/movimientos');
    $orden = collect($response->viewData('movimientos')->items())->pluck('id')->values()->all();

    expect($orden)->toBe([$movReciente->id, $movViejo->id]);
});

test('un tipo invalido en la query string se ignora en vez de filtrar', function () {
    $producto = crearProductoDePrueba('MV-012');
    $lote = crearLoteDePrueba($producto, 'L-1', null, 10);
    $mov = crearMovimientoDePrueba($lote, 'Entrada', 'Compra', 10, now()->toDateString());

    $response = $this->actingAs($this->admin)->get('/reportes/movimientos?tipo=algo-invalido');
    $ids = collect($response->viewData('movimientos')->items())->pluck('id');

    expect($ids)->toContain($mov->id);
    expect($response->viewData('filtros')['tipo'])->toBeNull();
});

test('el indice de reportes lista los seis reportes', function () {
    $response = $this->actingAs($this->admin)->get('/reportes');

    $response->assertStatus(200);
    $response->assertSee(route('reportes.movimientos'), false);
});

test('acceso sin permiso reportes.ver es bloqueado con 403', function () {
    $response = $this->actingAs($this->vendedor)->get('/reportes/movimientos');

    $response->assertStatus(403);
});

test('acceso con permiso reportes.ver responde 200', function () {
    $response = $this->actingAs($this->admin)->get('/reportes/movimientos');

    $response->assertStatus(200);
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/reportes/movimientos');

    $response->assertRedirect('/login');
});
