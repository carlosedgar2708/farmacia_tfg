<?php

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Permiso;
use App\Models\Proveedor;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->rolAdmin = Rol::create(['nombre' => 'Administrador', 'slug' => 'admin']);
    $this->rolVendedor = Rol::create(['nombre' => 'Vendedor', 'slug' => 'vendedor']);

    $permisoCompras = Permiso::create(['slug' => 'compras.ver', 'nombre' => 'Ver compras']);
    $this->rolAdmin->permisos()->attach($permisoCompras->id);
    // El vendedor NO tiene compras.ver, para probar el bloqueo por permiso.

    $this->admin = crearUsuarioDePrueba();
    $this->admin->rols()->attach($this->rolAdmin->id);

    $this->vendedor = crearUsuarioDePrueba();
    $this->vendedor->rols()->attach($this->rolVendedor->id);
});

function crearCompraDePruebaIndex(): Compra
{
    $proveedor = Proveedor::create(['nombre' => 'Proveedor ' . uniqid()]);
    $producto = crearProductoDePrueba('CI-' . uniqid());
    $lote = crearLoteDePrueba($producto, 'L-' . uniqid(), null, 100);

    $compra = Compra::create([
        'fecha' => now(),
        'proveedor_id' => $proveedor->id,
        'user_id' => crearUsuarioDePrueba()->id,
    ]);

    DetalleCompra::create([
        'compra_id' => $compra->id,
        'lote_id' => $lote->id,
        'cantidad' => 7,
        'costo_unitario' => 5,
    ]);

    return $compra;
}

test('sin compras se muestra el estado vacio del Design System', function () {
    $response = $this->actingAs($this->admin)->get('/compras');

    $response->assertStatus(200);
    $response->assertSee('No hay compras registradas.');
});

test('con compras la tabla muestra proveedor y total de items', function () {
    crearCompraDePruebaIndex();

    $response = $this->actingAs($this->admin)->get('/compras');

    $response->assertSee('Proveedor', false);
    $response->assertSee('>7<', false);
});

test('mensaje de exito en sesion se muestra con x-alert variant success', function () {
    $response = $this->actingAs($this->admin)
        ->withSession(['success' => 'Compra registrada correctamente.'])
        ->get('/compras');

    $response->assertSee('class="alert alert-success"', false);
    $response->assertSee('Compra registrada correctamente.');
});

test('la vista no deja ningun componente Blade sin resolver', function () {
    crearCompraDePruebaIndex();

    $response = $this->actingAs($this->admin)->get('/compras');

    $response->assertDontSee('<x-', false);
});

test('acceso sin permiso compras.ver es bloqueado con 403', function () {
    $response = $this->actingAs($this->vendedor)->get('/compras');

    $response->assertStatus(403);
});

test('acceso con permiso compras.ver responde 200', function () {
    $response = $this->actingAs($this->admin)->get('/compras');

    $response->assertStatus(200);
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/compras');

    $response->assertRedirect('/login');
});
