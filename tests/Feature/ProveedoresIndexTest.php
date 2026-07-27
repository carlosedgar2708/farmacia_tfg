<?php

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->rolAdmin = Rol::create(['nombre' => 'Administrador', 'slug' => 'admin']);
    $this->rolVendedor = Rol::create(['nombre' => 'Vendedor', 'slug' => 'vendedor']);

    $permisoProveedores = Permiso::create(['slug' => 'proveedors.ver', 'nombre' => 'Ver proveedores']);
    $this->rolAdmin->permisos()->attach($permisoProveedores->id);
    // El vendedor NO tiene proveedors.ver, para probar el bloqueo por permiso.

    $this->admin = crearUsuarioDePrueba();
    $this->admin->rols()->attach($this->rolAdmin->id);

    $this->vendedor = crearUsuarioDePrueba();
    $this->vendedor->rols()->attach($this->rolVendedor->id);
});

test('sin proveedores se muestra el estado vacio del Design System con el texto corregido', function () {
    $response = $this->actingAs($this->admin)->get('/proveedors');

    $response->assertStatus(200);
    $response->assertSee('No hay proveedores registrados.');
    $response->assertDontSee('No hay proveedors registrados.');
});

test('con proveedores la tabla muestra nombre y contacto', function () {
    \App\Models\Proveedor::create(['nombre' => 'Proveedor de Prueba', 'contacto' => 'Juan Pérez']);

    $response = $this->actingAs($this->admin)->get('/proveedors');

    $response->assertSee('Proveedor de Prueba');
    $response->assertSee('Juan Pérez');
});

test('el encabezado ya no usa texto blanco invisible', function () {
    $response = $this->actingAs($this->admin)->get('/proveedors');

    $response->assertDontSee('color:white', false);
});

test('mensaje de exito en sesion se muestra una sola vez, via el banner global', function () {
    $response = $this->actingAs($this->admin)
        ->withSession(['success' => 'Proveedor creado.'])
        ->get('/proveedors');

    $cantidad = substr_count($response->getContent(), 'Proveedor creado.');
    expect($cantidad)->toBe(1);
});

test('la vista no deja ningun componente Blade sin resolver', function () {
    $response = $this->actingAs($this->admin)->get('/proveedors');

    $response->assertDontSee('<x-', false);
});

test('la vista ya no carga style.css por duplicado', function () {
    $response = $this->actingAs($this->admin)->get('/proveedors');

    $cantidad = substr_count($response->getContent(), 'css/style.css');
    expect($cantidad)->toBe(1);
});

test('los botones clave conservan sus ids para el JavaScript existente', function () {
    $response = $this->actingAs($this->admin)->get('/proveedors');

    foreach (['btn-open-create', 'btn-cancel', 'btn-submit'] as $id) {
        $response->assertSee('id="' . $id . '"', false);
    }
});

test('el buscador sigue el patron unificado de UI-06: icono, dropdown e id de formulario', function () {
    \App\Models\Proveedor::create(['nombre' => 'Proveedor Buscable']);

    $response = $this->actingAs($this->admin)->get('/proveedors');

    $response->assertSee('id="form-buscar"', false);
    $response->assertSee('class="search-wrap"', false);
    $response->assertSee('ri-search-line', false);
    $response->assertSee('id="sugg"', false);
    $response->assertSee('Proveedor Buscable', false);
});

test('acceso sin permiso proveedors.ver es bloqueado con 403', function () {
    $response = $this->actingAs($this->vendedor)->get('/proveedors');

    $response->assertStatus(403);
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/proveedors');

    $response->assertRedirect('/login');
});
