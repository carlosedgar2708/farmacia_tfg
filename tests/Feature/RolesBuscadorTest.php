<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Alcance acotado a UI-06 (estandarización de buscadores). El resto del
// módulo de Roles todavía no tuvo su turno de UI-05 y no se toca acá.
// rols.index no tiene middleware de permiso propio, basta con estar autenticado.

test('el buscador de roles sigue el patron unificado de UI-06', function () {
    $user = crearUsuarioDePrueba();

    $response = $this->actingAs($user)->get('/rols');

    $response->assertStatus(200);
    $response->assertSee('id="form-buscar"', false);
    $response->assertSee('class="search-wrap"', false);
    $response->assertSee('ri-search-line', false);
    $response->assertSee('id="sugg"', false);
});

test('el buscador de roles sigue funcionando por GET tradicional', function () {
    $user = crearUsuarioDePrueba();
    \App\Models\Rol::create(['nombre' => 'Rol Buscable Unico', 'slug' => 'rol-buscable-unico']);

    $response = $this->actingAs($user)->get('/rols?q=Buscable');

    $response->assertStatus(200);
    $response->assertSee('Rol Buscable Unico');
});

test('la vista no deja ningun componente Blade sin resolver', function () {
    $user = crearUsuarioDePrueba();

    $response = $this->actingAs($user)->get('/rols');

    $response->assertDontSee('<x-', false);
});
