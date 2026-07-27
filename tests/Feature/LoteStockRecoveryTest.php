<?php

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function crearUsuarioConPermisosLote(array $slugs): \App\Models\User
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

test('nro_lote duplicado ya no produce una pagina de error generica', function () {
    $user = crearUsuarioConPermisosLote(['productos.stock', 'productos.ver']);
    $producto = crearProductoDePrueba('LSR-001');
    crearLoteDePrueba($producto, 'L-EXISTENTE', null, 5);

    $response = $this->actingAs($user)->from('/productos')->post("/productos/{$producto->id}/lotes/bulk", [
        'lotes' => [
            ['id' => '', 'nro_lote' => 'L-EXISTENTE', 'fecha_vencimiento' => null, 'costo_unitario' => 5, 'stock' => 10],
        ],
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['lotes.0.nro_lote']);
    expect(session('errors')->first('lotes.0.nro_lote'))->toBe('El N° de lote L-EXISTENTE ya existe para este producto.');
    // redirige explícitamente al índice con el producto marcado, no a back() generico
    $response->assertRedirect(route('productos.index', ['stock_error' => $producto->id]));
});

test('bulkUpdate no deja cambios parciales si una fila posterior falla (transaccion)', function () {
    $user = crearUsuarioConPermisosLote(['productos.stock', 'productos.ver']);
    $producto = crearProductoDePrueba('LSR-002');
    crearLoteDePrueba($producto, 'L-DUP', null, 5);

    // fila 0 válida (crearía un lote nuevo), fila 1 duplica L-DUP y falla
    $this->actingAs($user)->post("/productos/{$producto->id}/lotes/bulk", [
        'lotes' => [
            ['id' => '', 'nro_lote' => 'L-NUEVO', 'fecha_vencimiento' => null, 'costo_unitario' => 3, 'stock' => 7],
            ['id' => '', 'nro_lote' => 'L-DUP', 'fecha_vencimiento' => null, 'costo_unitario' => 3, 'stock' => 7],
        ],
    ]);

    // la fila 0 NO debe haber quedado guardada — todo o nada
    expect(\App\Models\Lote::where('nro_lote', 'L-NUEVO')->exists())->toBeFalse();
});

test('la vista reabre el modal de stock del producto correcto con el error y old()', function () {
    $user = crearUsuarioConPermisosLote(['productos.stock', 'productos.ver']);
    $producto = crearProductoDePrueba('LSR-003');
    crearLoteDePrueba($producto, 'L-DUP2', null, 5);

    $response = $this->actingAs($user)->followingRedirects()->post("/productos/{$producto->id}/lotes/bulk", [
        'lotes' => [
            ['id' => '', 'nro_lote' => 'L-DUP2', 'fecha_vencimiento' => null, 'costo_unitario' => 3, 'stock' => 7],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertSee('ya existe para este producto', false);
    $response->assertSee($producto->nombre, false);
});

test('sin stock_error en la query el modal de stock no se reabre', function () {
    $user = crearUsuarioConPermisosLote(['productos.ver']);

    $response = $this->actingAs($user)->get('/productos');

    $response->assertSee('const STOCK_ERROR   = null;', false);
});
