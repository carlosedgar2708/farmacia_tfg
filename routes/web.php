<?php

use App\Http\Controllers\RolController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get("/", [AuthController::class, 'welcome'])->name('welcome');


Route::resource('rol', RolController::class)->names('rols');


Route::prefix('/permiso')->group(function () {
    Route::get('/', [PermisoController::class, 'index'])->name('mostrar.permiso');
    Route::post('/', [PermisoController::class, 'store'])->name('crear.permiso');
    Route::patch('/', [PermisoController::class, 'update'])->name('editar.permiso');
    Route::delete('/', [PermisoController::class, 'destroy'])->name('eliminar.permiso');
});

