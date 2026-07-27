<?php

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Alcance acotado a UI-06 (estandarización de buscadores). El resto del
// módulo de Usuarios todavía no tuvo su turno de UI-05 y no se toca acá.

beforeEach(function () {
    $this->rolAdmin = Rol::create(['nombre' => 'Administrador', 'slug' => 'admin']);
    $permisoUsuarios = Permiso::create(['slug' => 'usuarios.ver', 'nombre' => 'Ver usuarios']);
    $this->rolAdmin->permisos()->attach($permisoUsuarios->id);

    $this->admin = crearUsuarioDePrueba();
    $this->admin->rols()->attach($this->rolAdmin->id);
});

test('el buscador de usuarios sigue el patron unificado de UI-06', function () {
    $response = $this->actingAs($this->admin)->get('/users');

    $response->assertStatus(200);
    $response->assertSee('id="form-buscar"', false);
    $response->assertSee('class="search-wrap"', false);
    $response->assertSee('ri-search-line', false);
    $response->assertSee('id="sugg"', false);
});

test('el buscador de usuarios sigue funcionando por GET tradicional', function () {
    $otro = crearUsuarioDePrueba();
    $otro->update(['name' => 'Nombre Buscable Unico']);

    $response = $this->actingAs($this->admin)->get('/users?q=Buscable');

    $response->assertStatus(200);
    $response->assertSee('Nombre Buscable Unico');
});

test('la vista no deja ningun componente Blade sin resolver', function () {
    $response = $this->actingAs($this->admin)->get('/users');

    $response->assertDontSee('<x-', false);
});
