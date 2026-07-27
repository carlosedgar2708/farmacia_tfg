<?php

use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = crearUsuarioDePrueba();
});

test('sin roles se muestra el estado vacio del Design System', function () {
    Rol::query()->delete();

    $response = $this->actingAs($this->admin)->get('/rols');

    $response->assertStatus(200);
    $response->assertSee('No hay roles registrados.');
});

test('con roles la tabla muestra nombre y slug', function () {
    Rol::create(['nombre' => 'Rol de Prueba', 'slug' => 'rol-de-prueba']);

    $response = $this->actingAs($this->admin)->get('/rols');

    $response->assertSee('Rol de Prueba');
    $response->assertSee('rol-de-prueba');
});

test('la vista no deja ningun componente Blade sin resolver', function () {
    $response = $this->actingAs($this->admin)->get('/rols');

    $response->assertDontSee('<x-', false);
});

test('la vista ya no referencia el archivo rols.js inexistente', function () {
    $response = $this->actingAs($this->admin)->get('/rols');

    $response->assertDontSee('js/rols.js', false);
    $response->assertDontSee('routesRolsStore', false);
});

test('la fila de acciones no contiene un td anidado invalido', function () {
    Rol::create(['nombre' => 'Rol de Prueba', 'slug' => 'rol-de-prueba']);

    $response = $this->actingAs($this->admin)->get('/rols');

    $response->assertDontSee('<td><td>', false);
});

test('el buscador sigue el patron unificado de UI-06', function () {
    Rol::create(['nombre' => 'Rol Buscable', 'slug' => 'rol-buscable']);

    $response = $this->actingAs($this->admin)->get('/rols');

    $response->assertSee('id="form-buscar"', false);
    $response->assertSee('class="search-wrap"', false);
    $response->assertSee('ri-search-line', false);
    $response->assertSee('id="sugg"', false);
});

test('los botones clave del modal conservan sus ids para el JavaScript existente', function () {
    $response = $this->actingAs($this->admin)->get('/rols');

    foreach (['submitBtn', 'cancelBtn'] as $id) {
        $response->assertSee('id="' . $id . '"', false);
    }
});

test('peticion sin sesion redirige a login', function () {
    $response = $this->get('/rols');

    $response->assertRedirect('/login');
});
