<?php

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->rolAdmin = Rol::create(['nombre' => 'Administrador', 'slug' => 'admin']);
    $this->rolVendedor = Rol::create(['nombre' => 'Vendedor', 'slug' => 'vendedor']);

    $permisoProductos = Permiso::create(['slug' => 'productos.ver', 'nombre' => 'Ver productos']);
    $this->rolAdmin->permisos()->attach($permisoProductos->id);
    // El vendedor NO tiene productos.ver, para probar el bloqueo por permiso.

    $this->admin = crearUsuarioDePrueba();
    $this->admin->rols()->attach($this->rolAdmin->id);

    $this->vendedor = crearUsuarioDePrueba();
    $this->vendedor->rols()->attach($this->rolVendedor->id);
});

test('sin productos se muestra el estado vacio del Design System', function () {
    $response = $this->actingAs($this->admin)->get('/productos');

    $response->assertStatus(200);
    $response->assertSee('No hay productos registrados.');
});

test('con productos la tabla muestra codigo y nombre', function () {
    crearProductoDePrueba('PR-001');

    $response = $this->actingAs($this->admin)->get('/productos');

    $response->assertSee('PR-001');
    $response->assertSee('Producto PR-001');
});

test('el stock total de un producto se calcula sumando sus lotes', function () {
    $producto = crearProductoDePrueba('PR-002');
    crearLoteDePrueba($producto, 'L-1', null, 10);
    crearLoteDePrueba($producto, 'L-2', null, 5);

    $response = $this->actingAs($this->admin)->get('/productos');
    $fila = $response->viewData('productos')->firstWhere('codigo', 'PR-002');

    expect((int) $fila->stock_total)->toBe(15);
});

test('mensaje de exito en sesion se muestra con x-alert variant success', function () {
    $response = $this->actingAs($this->admin)
        ->withSession(['success' => 'Producto creado correctamente.'])
        ->get('/productos');

    $response->assertSee('class="alert alert-success"', false);
    $response->assertSee('Producto creado correctamente.');
});

test('mensaje de error en sesion se muestra con x-alert variant danger', function () {
    $response = $this->actingAs($this->admin)
        ->withSession(['error' => 'No se pudo eliminar el producto.'])
        ->get('/productos');

    $response->assertSee('class="alert alert-danger"', false);
    $response->assertSee('No se pudo eliminar el producto.');
});

test('la vista no deja ningun componente Blade sin resolver', function () {
    crearProductoDePrueba('PR-003');

    $response = $this->actingAs($this->admin)->get('/productos');

    $response->assertDontSee('<x-', false);
});

test('los botones clave conservan sus ids para el JavaScript existente', function () {
    $response = $this->actingAs($this->admin)->get('/productos');

    foreach (['btn-open-create', 'btn-cancel', 'btn-add-lote', 'btn-cancel-stock'] as $id) {
        $response->assertSee('id="' . $id . '"', false);
    }
});

test('acceso sin permiso productos.ver es bloqueado con 403', function () {
    $response = $this->actingAs($this->vendedor)->get('/productos');

    $response->assertStatus(403);
});

test('acceso con permiso productos.ver responde 200', function () {
    $response = $this->actingAs($this->admin)->get('/productos');

    $response->assertStatus(200);
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/productos');

    $response->assertRedirect('/login');
});
