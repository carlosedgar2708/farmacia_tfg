<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');

        $rols = Rol::query()
            ->when($q, fn($qr) => $qr->where('nombre','like',"%{$q}%")
                                    ->orWhere('slug','like',"%{$q}%"))
            ->with('permisos:id')          // si ya definiste la relación permisos() en Rol
            ->orderBy('id','desc')
            ->paginate(10);

        // 👇 ESTA LÍNEA ES LA QUE FALTABA
        $permisos = Permiso::orderBy('nombre')->get(['id','nombre']);

        return view('rols.index', compact('rols','permisos'));
    }

    public function create()
    {
        return view('rols.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:rols,nombre',
            'slug' => 'required|string|max:255|unique:rols,slug',
            'descripcion' => 'nullable|string|max:255',
        ]);

        Rol::create($validated);
        return redirect()->route('rols.index')->with('success', 'Rol creado correctamente.');
    }

    public function show(Rol $rol)
    {
        return view('rols.show', compact('rol'));
    }

    public function edit(Rol $rol)
    {
        return view('rols.edit', compact('rol'));
    }

    public function update(Request $request, Rol $rol)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:rols,nombre,' . $rol->id,
            'slug' => 'required|string|max:255|unique:rols,slug,' . $rol->id,
            'descripcion' => 'nullable|string|max:255',
        ]);

        $rol->update($validated);
        return redirect()->route('rols.index')->with('success', 'Rol actualizado correctamente.');
    }
    public function destroy(Rol $rol)
    {
        $rol->delete();

        return redirect()->route('rols.index')->with('success', 'Rol eliminado correctamente.');
    }
}
