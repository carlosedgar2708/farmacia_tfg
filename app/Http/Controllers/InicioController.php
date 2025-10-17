<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Proveedor;
// si tienes roles en un modelo propio:
use App\Models\Rol; // ajusta el namespace si es distinto

class InicioController extends Controller
{
    public function index()
    {
        // Si no tienes alguno de estos modelos, comenta la línea correspondiente
        $stats = [
            'usuarios'    => class_exists(User::class) ? User::count() : 0,
            'roles'       => class_exists(Rol::class) ? Rol::count() : 0,
            'proveedors'  => class_exists(Proveedor::class) ? Proveedor::count() : 0,
        ];

        return view('inicio', compact('stats'));
    }
}
