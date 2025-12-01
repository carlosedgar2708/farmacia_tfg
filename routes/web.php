<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InicioController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\CompraController;


Route::middleware(['auth'])->group(function () {
    Route::get('/ventas',            [VentaController::class, 'index'])->name('ventas.index');
    Route::get('/ventas/create',     [VentaController::class, 'create'])->name('ventas.create');
    Route::post('/ventas',           [VentaController::class, 'store'])->name('ventas.store');

    // Aux (AJAX) – lotes con stock por producto
    Route::get('/api/productos/{producto}/lotes', [VentaController::class, 'lotesPorProducto'])
         ->name('api.productos.lotes');
});

/*
|--------------------------------------------------------------------------
| PÚBLICA (Landing)
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'welcome'])->name('welcome');


/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (auth)
|--------------------------------------------------------------------------
*/
Route::get('/', [InicioController::class, 'index'])
    ->name('inicio')
    ->middleware('auth');

Route::middleware('auth')->group(function () {
Route::resource('lotes', LoteController::class);
    // === DASHBOARD ===
    Route::get('/inicio', [InicioController::class, 'index'])->name('inicio');


    // === PRODUCTOS ===
    Route::prefix('productos')->name('productos.')->group(function () {
        Route::middleware('permiso:productos.ver')
            ->get('/', [ProductoController::class, 'index'])->name('index');

        Route::middleware('permiso:productos.crear')
            ->post('/', [ProductoController::class, 'store'])->name('store');

        Route::middleware('permiso:productos.editar')
            ->put('/{producto}', [ProductoController::class, 'update'])->name('update');

        Route::middleware('permiso:productos.eliminar')
            ->delete('/{producto}', [ProductoController::class, 'destroy'])->name('destroy');

        // === LOTES (stock) ===
        Route::middleware('permiso:productos.stock')
            ->post('/{producto}/lotes/bulk', [LoteController::class, 'bulkUpdate'])
            ->name('lotes.bulk');

        // Si también manejas los lotes individuales:
        Route::prefix('{producto}/lotes')->name('lotes.')->group(function () {
            Route::middleware('permiso:productos.stock')->get('/', [LoteController::class, 'index'])->name('index');
            Route::middleware('permiso:productos.stock')->post('/', [LoteController::class, 'store'])->name('store');
            Route::middleware('permiso:productos.stock')->put('/{lote}', [LoteController::class, 'update'])->name('update');
            Route::middleware('permiso:productos.stock')->delete('/{lote}', [LoteController::class, 'destroy'])->name('destroy');
        });
    });
    Route::middleware(['auth'])->group(function () {

        Route::get('/compras',  [CompraController::class, 'index'])
            ->name('compras.index')
            ->middleware('permiso:compras.ver');

        Route::get('/compras/create', [CompraController::class, 'create'])
            ->name('compras.create')
            ->middleware('permiso:compras.crear');

        Route::post('/compras', [CompraController::class, 'store'])
            ->name('compras.store')
            ->middleware('permiso:compras.crear');
    });


    // === ROLES ===
    Route::resource('rols', RolController::class)->only(['index','store','update','destroy']);

    // === USUARIOS ===
    Route::prefix('users')->name('users.')->group(function () {
        Route::middleware('permiso:usuarios.ver')->get('/', [UserController::class, 'index'])->name('index');
        Route::middleware('permiso:usuarios.crear')->post('/', [UserController::class, 'store'])->name('store');
        Route::middleware('permiso:usuarios.editar')->put('/{user}', [UserController::class, 'update'])->name('update');
        Route::middleware('permiso:usuarios.eliminar')->delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // === CLIENTES ===
    Route::prefix('clientes')->name('clientes.')->group(function () {
        Route::middleware('permiso:clientes.ver')->get('/', [ClienteController::class, 'index'])->name('index');
        Route::middleware('permiso:clientes.crear')->post('/', [ClienteController::class, 'store'])->name('store');
        Route::middleware('permiso:clientes.editar')->put('/{cliente}', [ClienteController::class, 'update'])->name('update');
        Route::middleware('permiso:clientes.eliminar')->delete('/{cliente}', [ClienteController::class, 'destroy'])->name('destroy');
    });

    // === PROVEEDORES ===
    Route::prefix('proveedors')->name('proveedors.')->group(function () {
        Route::middleware('permiso:proveedors.ver')->get('/', [ProveedorController::class, 'index'])->name('index');
        Route::middleware('permiso:proveedors.crear')->post('/', [ProveedorController::class, 'store'])->name('store');
        Route::middleware('permiso:proveedors.editar')->put('/{proveedor}', [ProveedorController::class, 'update'])->name('update');
        Route::middleware('permiso:proveedors.eliminar')->delete('/{proveedor}', [ProveedorController::class, 'destroy'])->name('destroy');
    });
});


/*
|--------------------------------------------------------------------------
| PERMISOS (público o interno)
|--------------------------------------------------------------------------
*/
Route::prefix('permiso')->group(function () {
    Route::get('/', [PermisoController::class, 'index'])->name('mostrar.permiso');
    Route::post('/', [PermisoController::class, 'store'])->name('crear.permiso');
    Route::patch('/', [PermisoController::class, 'update'])->name('editar.permiso');
    Route::delete('/', [PermisoController::class, 'destroy'])->name('eliminar.permiso');
});
