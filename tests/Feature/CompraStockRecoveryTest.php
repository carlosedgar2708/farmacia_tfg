<?php

use App\Models\Permiso;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function crearUsuarioConPermisosCompra(array $slugs): \App\Models\User
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

test('lote vencido ya no produce una pagina de error generica', function () {
    $user = crearUsuarioConPermisosCompra(['compras.crear']);
    $proveedor = Proveedor::create(['nombre' => 'Proveedor X']);
    $producto = crearProductoDePrueba('CSR-001');

    $response = $this->actingAs($user)->from('/compras/create')->post('/compras', [
        'proveedor_id' => $proveedor->id,
        'items' => [
            ['producto_id' => $producto->id, 'nro_lote' => 'L-1', 'fecha_vencimiento' => now()->subDay()->toDateString(), 'costo_unitario' => 5, 'cantidad' => 2],
        ],
    ]);

    // antes: 422 con una pagina generica de excepcion. ahora: redirect normal.
    $response->assertStatus(302);
    $response->assertSessionHasErrors(['items.0.fecha_vencimiento']);
    expect(session('errors')->first('items.0.fecha_vencimiento'))->toBe('El lote L-1 está vencido. No puedes ingresarlo.');
});

test('lote vencido: la vista reconstruye la fila con el error y old() al volver', function () {
    $user = crearUsuarioConPermisosCompra(['compras.crear']);
    $proveedor = Proveedor::create(['nombre' => 'Proveedor X']);
    $producto = crearProductoDePrueba('CSR-002');

    $response = $this->actingAs($user)->from('/compras/create')->followingRedirects()->post('/compras', [
        'proveedor_id' => $proveedor->id,
        'items' => [
            ['producto_id' => $producto->id, 'nro_lote' => 'L-2', 'fecha_vencimiento' => now()->subDay()->toDateString(), 'costo_unitario' => 5, 'cantidad' => 2],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertSee('El lote L-2 está vencido', false);
    // el producto seleccionado se resuelve por nombre, no solo por id
    $response->assertSee($producto->nombre, false);
    // no se guardó ninguna compra (la transacción hizo rollback)
    expect(\App\Models\Compra::count())->toBe(0);
});

test('compra exitosa no deja restos de OLD_ITEMS en el formulario', function () {
    $user = crearUsuarioConPermisosCompra(['compras.crear', 'compras.ver']);
    $proveedor = Proveedor::create(['nombre' => 'Proveedor Y']);
    $producto = crearProductoDePrueba('CSR-003');

    $this->actingAs($user)->post('/compras', [
        'proveedor_id' => $proveedor->id,
        'items' => [
            ['producto_id' => $producto->id, 'nro_lote' => 'L-3', 'fecha_vencimiento' => null, 'costo_unitario' => 5, 'cantidad' => 2],
        ],
    ])->assertRedirect(route('compras.index'));

    expect(\App\Models\Compra::count())->toBe(1);

    $response = $this->actingAs($user)->get('/compras/create');
    $response->assertSee('const OLD_ITEMS    = [];', false);
});
