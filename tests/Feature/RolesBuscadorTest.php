<?php

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Alcance acotado a UI-06 (estandarización de buscadores). El resto del
// módulo de Roles todavía no tuvo su turno de UI-05 y no se toca acá.
// Desde BUG-12, rols.index exige el permiso rols.ver: el usuario de estas
// pruebas necesita un rol con ese permiso para no recibir 403.

function crearUsuarioConPermisoRolsVer(): \App\Models\User
{
    $rol = Rol::create(['nombre' => 'Administrador', 'slug' => 'admin']);
    $permiso = Permiso::create(['slug' => 'rols.ver', 'nombre' => 'Ver roles']);
    $rol->permisos()->attach($permiso->id);

    $user = crearUsuarioDePrueba();
    $user->rols()->attach($rol->id);

    return $user;
}

test('el buscador de roles sigue el patron unificado de UI-06', function () {
    $user = crearUsuarioConPermisoRolsVer();

    $response = $this->actingAs($user)->get('/rols');

    $response->assertStatus(200);
    $response->assertSee('id="form-buscar"', false);
    $response->assertSee('class="search-wrap"', false);
    $response->assertSee('ri-search-line', false);
    $response->assertSee('id="sugg"', false);
});

test('el buscador de roles sigue funcionando por GET tradicional', function () {
    $user = crearUsuarioConPermisoRolsVer();
    \App\Models\Rol::create(['nombre' => 'Rol Buscable Unico', 'slug' => 'rol-buscable-unico']);

    $response = $this->actingAs($user)->get('/rols?q=Buscable');

    $response->assertStatus(200);
    $response->assertSee('Rol Buscable Unico');
});

test('la vista no deja ningun componente Blade sin resolver', function () {
    $user = crearUsuarioConPermisoRolsVer();

    $response = $this->actingAs($user)->get('/rols');

    $response->assertDontSee('<x-', false);
});

test('acceso sin permiso rols.ver es bloqueado con 403', function () {
    $user = crearUsuarioDePrueba();

    $response = $this->actingAs($user)->get('/rols');

    $response->assertStatus(403);
});
