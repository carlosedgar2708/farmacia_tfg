<?php

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function crearUsuarioConPermisosFieldErrors(array $slugs): \App\Models\User
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

test('productos: campo codigo vacio muestra el error debajo del input', function () {
    // Necesita productos.ver además de productos.crear: al fallar la
    // validación, Laravel vuelve a /productos (referer), que exige .ver.
    $user = crearUsuarioConPermisosFieldErrors(['productos.crear', 'productos.ver']);

    $response = $this->actingAs($user)->from('/productos')->followingRedirects()->post('/productos', [
        'nombre' => 'Producto válido',
        'precio_venta' => 10,
    ]);

    $response->assertSee('class="field-error"', false);
    $response->assertSee('El campo código es obligatorio.');
});

test('roles: campo nombre vacio muestra el error con la clase field-error', function () {
    // Necesita rols.ver además de rols.crear (BUG-12): al fallar la
    // validación, Laravel vuelve a /rols (referer), que ahora exige .ver.
    $user = crearUsuarioConPermisosFieldErrors(['rols.crear', 'rols.ver']);

    $response = $this->actingAs($user)->from('/rols')->followingRedirects()->post('/rols', [
        'nombre' => '',
        'slug' => 'slug-valido',
    ]);

    $response->assertSee('class="field-error"', false);
    $response->assertSee('El campo nombre es obligatorio.');
});

test('configuracion: valor fuera de rango muestra el error debajo del input', function () {
    $rolAdmin = Rol::create(['nombre' => 'Administrador', 'slug' => 'admin']);
    $user = crearUsuarioDePrueba();
    $user->rols()->attach($rolAdmin->id);

    $response = $this->actingAs($user)->from('/configuracion')->followingRedirects()->put('/configuracion', [
        'dias_alerta_vencimiento' => 0,
    ]);

    $response->assertSee('class="field-error"', false);
});

test('login: credenciales invalidas muestran el error debajo del campo email', function () {
    $response = $this->from('/login')->followingRedirects()->post('/login', [
        'email' => 'no-existe@example.com',
        'password' => 'lo-que-sea',
    ]);

    $response->assertSee('class="field-error"', false);
    $response->assertSee('Esas credenciales no coinciden con nuestros registros.');
});

test('registro: contraseñas que no coinciden muestran el error debajo del campo', function () {
    $response = $this->from('/register')->followingRedirects()->post('/register', [
        'name' => 'Usuario Nuevo',
        'email' => 'nuevo@example.com',
        'password' => 'password123',
        'password_confirmation' => 'otra-cosa',
    ]);

    $response->assertSee('class="field-error"', false);
});

test('proveedores: campo nombre vacio muestra el error debajo del input', function () {
    $user = crearUsuarioConPermisosFieldErrors(['proveedors.crear', 'proveedors.ver']);

    $response = $this->actingAs($user)->from('/proveedors')->followingRedirects()->post('/proveedors', []);

    $response->assertSee('class="field-error"', false);
    $response->assertSee('El campo nombre es obligatorio.');
});

test('clientes: campo nombre vacio muestra el error debajo del input', function () {
    $user = crearUsuarioConPermisosFieldErrors(['clientes.crear', 'clientes.ver']);

    $response = $this->actingAs($user)->from('/clientes')->followingRedirects()->post('/clientes', []);

    $response->assertSee('class="field-error"', false);
    $response->assertSee('El campo nombre es obligatorio.');
});

test('usuarios: campo email invalido muestra el error debajo del input', function () {
    $user = crearUsuarioConPermisosFieldErrors(['usuarios.crear', 'usuarios.ver']);

    $response = $this->actingAs($user)->from('/users')->followingRedirects()->post('/users', [
        'name' => 'Nuevo usuario',
        'email' => 'no-es-un-email',
        'password' => 'password123',
    ]);

    $response->assertSee('class="field-error"', false);
});
