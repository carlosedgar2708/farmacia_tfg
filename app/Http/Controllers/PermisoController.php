<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use Illuminate\Http\Request;

class PermisoController extends Controller
{

    public function index()
    {
        // Listar todos los permisos
    }



    public function store(Request $request)
    {
        // Crear un nuevo permiso
    }

    public function show(Permiso $permiso)
    {
       // Mostrar un permiso específico
    }
    public function update(Request $request, Permiso $permiso)
    {
        // Actualizar un permiso existente
    }


    public function destroy(Permiso $permiso)
    {
            // Eliminar un permiso
    }
}
