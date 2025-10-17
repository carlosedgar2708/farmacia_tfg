<?php

use App\Http\Controllers\RolController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::middleware(['auth', 'permiso:usuarios.ver'])
    ->get('/users', [UserController::class, 'index'])
    ->name('users.index');

Route::middleware(['auth', 'permiso:usuarios.crear'])
    ->post('/users', [UserController::class, 'store'])
    ->name('users.store');

Route::middleware(['auth', 'permiso:usuarios.editar'])
    ->put('/users/{user}', [UserController::class, 'update'])
    ->name('users.update');

Route::middleware(['auth', 'permiso:usuarios.eliminar'])
    ->delete('/users/{user}', [UserController::class, 'destroy'])
    ->name('users.destroy');

Route::get("/", [AuthController::class, 'welcome'])->name('welcome');


Route::middleware('auth')->group(function () {
    Route::resource('rols', RolController::class)->only(['index','store','update','destroy']);
    // aquí irán proveedores, productos, etc.
});
Route::prefix('/permiso')->group(function () {
    Route::get('/', [PermisoController::class, 'index'])->name('mostrar.permiso');
    Route::post('/', [PermisoController::class, 'store'])->name('crear.permiso');
    Route::patch('/', [PermisoController::class, 'update'])->name('editar.permiso');
    Route::delete('/', [PermisoController::class, 'destroy'])->name('eliminar.permiso');
});

