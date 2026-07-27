<?php

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// UI-06: los 4 selectores (Compras: producto/proveedor, Ventas: producto/cliente)
// solo cambian su icono de emoji a Remix. Nada de su comportamiento de selección
// (llenar un campo oculto, no navegar) se toca.

function crearUsuarioConPermisosIconos(array $slugs): \App\Models\User
{
    $rol = Rol::create(['nombre' => 'Rol ' . uniqid(), 'slug' => 'rol-' . uniqid()]);
    foreach ($slugs as $slug) {
        $permiso = Permiso::firstOrCreate(['slug' => $slug], ['nombre' => $slug]);
        $rol->permisos()->attach($permiso->id);
    }
    $user = crearUsuarioDePrueba();
    $user->rols()->attach($rol->id);

    return $user;
}

test('compras/create ya no usa iconos emoji en los selectores', function () {
    $user = crearUsuarioConPermisosIconos(['compras.crear']);

    $response = $this->actingAs($user)->get('/compras/create');

    $response->assertDontSee('📦', false);
    $response->assertDontSee('🚚', false);
    $response->assertDontSee('➕', false);
    $response->assertSee('ri-archive-2-line', false);
    $response->assertSee('ri-truck-line', false);
});

test('ventas/create ya no usa iconos emoji en los selectores', function () {
    $user = crearUsuarioDePrueba();

    $response = $this->actingAs($user)->get('/ventas/create');

    $response->assertDontSee('📦', false);
    $response->assertDontSee('👤', false);
    $response->assertSee('ri-archive-2-line', false);
    $response->assertSee('ri-user-3-line', false);
});
