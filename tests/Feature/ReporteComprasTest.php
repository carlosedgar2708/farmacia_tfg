<?php

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Lote;
use App\Models\Permiso;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function crearProveedorDePrueba(string $nombre): Proveedor
{
    return Proveedor::create(['nombre' => $nombre]);
}

function crearCompraDePrueba(Proveedor $proveedor, $usuario, string $fecha): Compra
{
    return Compra::create([
        'fecha' => $fecha,
        'proveedor_id' => $proveedor->id,
        'user_id' => $usuario->id,
    ]);
}

function agregarDetalleCompraDePrueba(Compra $compra, Producto $producto, int $cantidad, float $costoUnitario): DetalleCompra
{
    $lote = Lote::create([
        'producto_id' => $producto->id,
        'nro_lote' => 'L-' . uniqid(),
        'fecha_vencimiento' => null,
        'costo_unitario' => $costoUnitario,
        'stock' => $cantidad,
    ]);

    return DetalleCompra::create([
        'compra_id' => $compra->id,
        'lote_id' => $lote->id,
        'cantidad' => $cantidad,
        'costo_unitario' => $costoUnitario,
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

test('compra dentro del rango solicitado aparece', function () {
    $proveedor = crearProveedorDePrueba('Proveedor A');
    $producto = crearProductoDePrueba('CP-001');
    $compra = crearCompraDePrueba($proveedor, $this->admin, now()->subDays(5)->toDateString());
    agregarDetalleCompraDePrueba($compra, $producto, 10, 2);

    $desde = now()->subDays(10)->toDateString();
    $hasta = now()->toDateString();

    $response = $this->actingAs($this->admin)->get("/reportes/compras?desde={$desde}&hasta={$hasta}");
    $ids = collect($response->viewData('compras')->items())->pluck('id');

    expect($ids)->toContain($compra->id);
});

test('compra fuera del rango solicitado no aparece', function () {
    $proveedor = crearProveedorDePrueba('Proveedor B');
    $producto = crearProductoDePrueba('CP-002');
    $compra = crearCompraDePrueba($proveedor, $this->admin, now()->subDays(60)->toDateString());
    agregarDetalleCompraDePrueba($compra, $producto, 10, 2);

    $desde = now()->subDays(10)->toDateString();
    $hasta = now()->toDateString();

    $response = $this->actingAs($this->admin)->get("/reportes/compras?desde={$desde}&hasta={$hasta}");
    $ids = collect($response->viewData('compras')->items())->pluck('id');

    expect($ids)->not->toContain($compra->id);
});

test('sin desde ni hasta aparecen todas las compras', function () {
    $proveedor = crearProveedorDePrueba('Proveedor C');
    $producto = crearProductoDePrueba('CP-003');
    $compraVieja = crearCompraDePrueba($proveedor, $this->admin, now()->subYears(2)->toDateString());
    agregarDetalleCompraDePrueba($compraVieja, $producto, 5, 3);
    $compraReciente = crearCompraDePrueba($proveedor, $this->admin, now()->toDateString());
    agregarDetalleCompraDePrueba($compraReciente, $producto, 5, 3);

    $response = $this->actingAs($this->admin)->get('/reportes/compras');
    $ids = collect($response->viewData('compras')->items())->pluck('id');

    expect($ids)->toContain($compraVieja->id);
    expect($ids)->toContain($compraReciente->id);
});

test('filtro por proveedor limita el resultado a ese proveedor', function () {
    $proveedorA = crearProveedorDePrueba('Proveedor D');
    $proveedorB = crearProveedorDePrueba('Proveedor E');
    $producto = crearProductoDePrueba('CP-004');

    $compraA = crearCompraDePrueba($proveedorA, $this->admin, now()->toDateString());
    agregarDetalleCompraDePrueba($compraA, $producto, 5, 3);
    $compraB = crearCompraDePrueba($proveedorB, $this->admin, now()->toDateString());
    agregarDetalleCompraDePrueba($compraB, $producto, 5, 3);

    $response = $this->actingAs($this->admin)->get("/reportes/compras?proveedor_id={$proveedorA->id}");
    $ids = collect($response->viewData('compras')->items())->pluck('id');

    expect($ids)->toContain($compraA->id);
    expect($ids)->not->toContain($compraB->id);
});

test('el total por compra es la suma de sus detalles', function () {
    $proveedor = crearProveedorDePrueba('Proveedor F');
    $producto = crearProductoDePrueba('CP-005');
    $compra = crearCompraDePrueba($proveedor, $this->admin, now()->toDateString());
    agregarDetalleCompraDePrueba($compra, $producto, 10, 2); // 20
    agregarDetalleCompraDePrueba($compra, $producto, 4, 5);  // 20

    $response = $this->actingAs($this->admin)->get('/reportes/compras');
    $fila = collect($response->viewData('compras')->items())->firstWhere('id', $compra->id);

    expect((float) $fila->total_compra)->toBe(40.0);
    expect((int) $fila->items_compra)->toBe(2);
});

test('el total general suma todas las compras filtradas, no solo la pagina actual', function () {
    $proveedor = crearProveedorDePrueba('Proveedor G');
    $producto = crearProductoDePrueba('CP-006');

    for ($i = 0; $i < 20; $i++) {
        $compra = crearCompraDePrueba($proveedor, $this->admin, now()->toDateString());
        agregarDetalleCompraDePrueba($compra, $producto, 1, 10); // 10 cada una
    }

    $response = $this->actingAs($this->admin)->get('/reportes/compras');

    expect((float) $response->viewData('total'))->toBe(200.0);
    expect($response->viewData('compras')->count())->toBeLessThan(20); // paginado
});

test('fecha invalida en desde u hasta se ignora sin error 500', function () {
    $proveedor = crearProveedorDePrueba('Proveedor H');
    $producto = crearProductoDePrueba('CP-007');
    $compra = crearCompraDePrueba($proveedor, $this->admin, now()->toDateString());
    agregarDetalleCompraDePrueba($compra, $producto, 1, 1);

    $response = $this->actingAs($this->admin)->get('/reportes/compras?desde=no-es-una-fecha&hasta=2026-08-01');

    $response->assertStatus(200);
    expect($response->viewData('filtros')['desde'])->toBeNull();
});

test('rango de fechas invertido se ignora sin error 500', function () {
    $desde = now()->addDays(10)->toDateString();
    $hasta = now()->toDateString();

    $response = $this->actingAs($this->admin)->get("/reportes/compras?desde={$desde}&hasta={$hasta}");

    $response->assertStatus(200);
    expect($response->viewData('filtros')['desde'])->toBeNull();
});

test('los filtros se conservan tras la busqueda', function () {
    $proveedor = crearProveedorDePrueba('Proveedor I');

    $response = $this->actingAs($this->admin)->get("/reportes/compras?proveedor_id={$proveedor->id}");

    expect($response->viewData('filtros')['proveedor_id'])->toBe($proveedor->id);
});

test('sin resultados muestra un mensaje amigable en vez de tabla vacia', function () {
    $proveedor = crearProveedorDePrueba('Proveedor J');

    $response = $this->actingAs($this->admin)->get("/reportes/compras?proveedor_id={$proveedor->id}");

    $response->assertStatus(200);
    $response->assertSee('No hay compras que coincidan con estos filtros');
});

test('pagina fuera de rango no produce error 500', function () {
    $response = $this->actingAs($this->admin)->get('/reportes/compras?page=999');

    $response->assertStatus(200);
});

test('orden por defecto es por fecha descendente', function () {
    $proveedor = crearProveedorDePrueba('Proveedor K');
    $producto = crearProductoDePrueba('CP-008');

    $compraVieja = crearCompraDePrueba($proveedor, $this->admin, now()->subDays(5)->toDateString());
    agregarDetalleCompraDePrueba($compraVieja, $producto, 1, 1);
    $compraReciente = crearCompraDePrueba($proveedor, $this->admin, now()->toDateString());
    agregarDetalleCompraDePrueba($compraReciente, $producto, 1, 1);

    $response = $this->actingAs($this->admin)->get('/reportes/compras');
    $orden = collect($response->viewData('compras')->items())->pluck('id')->values()->all();

    expect($orden)->toBe([$compraReciente->id, $compraVieja->id]);
});

test('el indice de reportes lista los cuatro reportes', function () {
    $response = $this->actingAs($this->admin)->get('/reportes');

    $response->assertStatus(200);
    $response->assertSee(route('reportes.compras'), false);
});

test('acceso sin permiso reportes.ver es bloqueado con 403', function () {
    $response = $this->actingAs($this->vendedor)->get('/reportes/compras');

    $response->assertStatus(403);
});

test('acceso con permiso reportes.ver responde 200', function () {
    $response = $this->actingAs($this->admin)->get('/reportes/compras');

    $response->assertStatus(200);
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/reportes/compras');

    $response->assertRedirect('/login');
});
