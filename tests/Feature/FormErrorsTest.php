<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('la pagina de registro responde 200 (antes fallaba por layouts.app inexistente)', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
    $response->assertDontSee('<x-', false);
});

test('registro con datos invalidos muestra el banner una sola vez, sin lista tecnica', function () {
    $response = $this->from('/register')->followingRedirects()->post('/register', [
        'name' => '',
        'email' => 'no-es-un-email',
        'password' => '123',
        'password_confirmation' => 'no-coincide',
    ]);

    $response->assertStatus(200);
    $response->assertSee('No se pudo guardar la información.');
    $response->assertSee('Corrige los campos marcados e inténtalo nuevamente.');

    // El banner debe aparecer una sola vez (antes se duplicaba: layout + bloque local).
    $cantidad = substr_count($response->getContent(), 'No se pudo guardar la información.');
    expect($cantidad)->toBe(1);

    // Ya no debe quedar rastro del patrón anterior (lista técnica en inglés/genérica).
    $response->assertDontSee('Revisa los campos:');
});

test('login con credenciales invalidas muestra el banner una sola vez', function () {
    $response = $this->from('/login')->followingRedirects()->post('/login', [
        'email' => 'no-existe@example.com',
        'password' => 'lo-que-sea',
    ]);

    $response->assertStatus(200);
    $cantidad = substr_count($response->getContent(), 'No se pudo guardar la información.');
    expect($cantidad)->toBe(1);
});

test('un formulario autenticado (crear rol) muestra el banner global una sola vez ante datos invalidos', function () {
    // Desde BUG-12, rols.store exige rols.crear, y el redirect tras el error
    // vuelve a /rols (referer), que exige rols.ver.
    $rol = \App\Models\Rol::create(['nombre' => 'Administrador', 'slug' => 'admin']);
    $permisoVer = \App\Models\Permiso::create(['slug' => 'rols.ver', 'nombre' => 'Ver roles']);
    $permisoCrear = \App\Models\Permiso::create(['slug' => 'rols.crear', 'nombre' => 'Crear rol']);
    $rol->permisos()->attach([$permisoVer->id, $permisoCrear->id]);

    $user = crearUsuarioDePrueba();
    $user->rols()->attach($rol->id);

    $response = $this->actingAs($user)
        ->from('/rols')
        ->followingRedirects()
        ->post('/rols', ['nombre' => '', 'slug' => '']);

    $response->assertStatus(200);
    $cantidad = substr_count($response->getContent(), 'No se pudo guardar la información.');
    expect($cantidad)->toBe(1);
    $response->assertDontSee('Revisa los campos:');
});
