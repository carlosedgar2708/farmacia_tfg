<?php

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->rolAdmin = Rol::create(['nombre' => 'Administrador', 'slug' => 'admin']);
    $this->rolVendedor = Rol::create(['nombre' => 'Vendedor', 'slug' => 'vendedor']);

    $permisoClientes = Permiso::create(['slug' => 'clientes.ver', 'nombre' => 'Ver clientes']);
    $this->rolAdmin->permisos()->attach($permisoClientes->id);
    // El vendedor NO tiene clientes.ver, para probar el bloqueo por permiso.

    $this->admin = crearUsuarioDePrueba();
    $this->admin->rols()->attach($this->rolAdmin->id);

    $this->vendedor = crearUsuarioDePrueba();
    $this->vendedor->rols()->attach($this->rolVendedor->id);
});

test('sin clientes se muestra el estado vacio del Design System', function () {
    $response = $this->actingAs($this->admin)->get('/clientes');

    $response->assertStatus(200);
    $response->assertSee('No hay clientes registrados.');
});

test('con clientes la tabla muestra nombre y documento', function () {
    \App\Models\Cliente::create(['nombre' => 'Cliente de Prueba', 'documento' => '12345678']);

    $response = $this->actingAs($this->admin)->get('/clientes');

    $response->assertSee('Cliente de Prueba');
    $response->assertSee('12345678');
});

test('mensaje de exito en sesion se muestra una sola vez, via el banner global', function () {
    $response = $this->actingAs($this->admin)
        ->withSession(['success' => 'Cliente creado'])
        ->get('/clientes');

    $cantidad = substr_count($response->getContent(), 'Cliente creado');
    expect($cantidad)->toBe(1);
});

test('la vista no deja ningun componente Blade sin resolver', function () {
    $response = $this->actingAs($this->admin)->get('/clientes');

    $response->assertDontSee('<x-', false);
});

test('la vista ya no carga style.css por duplicado', function () {
    $response = $this->actingAs($this->admin)->get('/clientes');

    $cantidad = substr_count($response->getContent(), 'css/style.css');
    expect($cantidad)->toBe(1);
});

test('los botones clave conservan sus ids para el JavaScript existente', function () {
    $response = $this->actingAs($this->admin)->get('/clientes');

    foreach (['btn-open-create', 'btn-cancel', 'btn-submit'] as $id) {
        $response->assertSee('id="' . $id . '"', false);
    }
});

test('el buscador sigue el patron unificado de UI-06: sin xl, con boton Buscar', function () {
    $response = $this->actingAs($this->admin)->get('/clientes');

    $response->assertSee('class="search-wrap"', false);
    $response->assertDontSee('search-wrap xl', false);
    $response->assertSee('ri-filter-2-line', false);
});

test('el filtrado de tabla en vivo sigue funcionando (excepcion propia de esta vista)', function () {
    \App\Models\Cliente::create(['nombre' => 'Cliente de Prueba']);

    $response = $this->actingAs($this->admin)->get('/clientes');

    $response->assertSee('id="tbody-clientes"', false);
    $response->assertSee('filterRows', false);
});

test('acceso sin permiso clientes.ver es bloqueado con 403', function () {
    $response = $this->actingAs($this->vendedor)->get('/clientes');

    $response->assertStatus(403);
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/clientes');

    $response->assertRedirect('/login');
});
