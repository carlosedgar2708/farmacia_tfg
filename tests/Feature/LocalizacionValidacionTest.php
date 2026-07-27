<?php

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function crearUsuarioConPermiso(string $slug): \App\Models\User
{
    $rol = Rol::create(['nombre' => 'Rol ' . uniqid(), 'slug' => 'rol-' . uniqid()]);
    $permiso = Permiso::firstOrCreate(['slug' => $slug], ['nombre' => $slug]);
    $rol->permisos()->attach($permiso->id);

    $user = crearUsuarioDePrueba();
    $user->rols()->attach($rol->id);

    return $user;
}

test('el locale de la aplicacion es español', function () {
    expect(app()->getLocale())->toBe('es');
});

test('registro con nombre vacio muestra el mensaje generico traducido al español', function () {
    $this->from('/register')->post('/register', [
        'name' => '',
        'email' => 'valido@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $errors = session('errors');
    expect($errors->first('name'))->toBe('El campo nombre es obligatorio.');
});

test('proveedor sin nombre muestra el mensaje generico traducido', function () {
    $user = crearUsuarioConPermiso('proveedors.crear');

    $this->actingAs($user)->from('/proveedors')->post('/proveedors', []);

    $errors = session('errors');
    expect($errors->first('nombre'))->toBe('El campo nombre es obligatorio.');
});

test('compra sin proveedor muestra el mensaje personalizado "Seleccione un proveedor."', function () {
    $user = crearUsuarioConPermiso('compras.crear');

    $this->actingAs($user)->from('/compras/create')->post('/compras', [
        'items' => [
            ['producto_id' => '', 'nro_lote' => '', 'costo_unitario' => 1, 'cantidad' => 1],
        ],
    ]);

    $errors = session('errors');
    expect($errors->first('proveedor_id'))->toBe('Seleccione un proveedor.');
    expect($errors->first('items.0.producto_id'))->toBe('Seleccione un producto.');
    expect($errors->first('items.0.nro_lote'))->toBe('Ingrese el número de lote.');
});

test('venta sin items usa el mensaje personalizado ya definido en el controlador', function () {
    // ventas.store no tiene middleware de permiso propio, basta con estar autenticado.
    $user = crearUsuarioDePrueba();

    $this->actingAs($user)->from('/ventas/create')->post('/ventas', []);

    $errors = session('errors');
    expect($errors->first('items'))->toBe('Agrega al menos un renglón de venta.');
});
