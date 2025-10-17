<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // <-- IMPORTANTE

class ProveedorController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'   => [
                'required','string','max:255',
                Rule::unique('proveedors', 'nombre')
                    ->where(fn($q) => $q->whereNull('deleted_at')),
            ],
            'contacto' => ['nullable','string','max:255'],
            'telefono' => ['nullable','string','max:255'],
        ]);

        Proveedor::create($data);

        return redirect()->route('proveedors.index')->with('success','Proveedor creado correctamente.');
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $data = $request->validate([
            'nombre'   => [
                'required','string','max:255',
                Rule::unique('proveedors', 'nombre')
                    ->ignore($proveedor->id)                       // <-- permite su mismo nombre
                    ->where(fn($q) => $q->whereNull('deleted_at')), // <-- ignora soft-deleted
            ],
            'contacto' => ['nullable','string','max:255'],
            'telefono' => ['nullable','string','max:255'],
        ]);

        $proveedor->update($data);

        return redirect()->route('proveedors.index')->with('success','Proveedor actualizado correctamente.');
    }

    public function index(Request $request)
    {
        $q = trim($request->get('q',''));

        $proveedors = Proveedor::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = "%{$q}%";
                $query->where(function ($sub) use ($like) {
                    $sub->where('nombre','like',$like)
                        ->orWhere('contacto','like',$like)
                        ->orWhere('telefono','like',$like);
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        // ⬇️ Cambiamos el path de la vista al folder "proveedors"
        return view('proveedors.index', ['proveedores' => $proveedors, 'q' => $q]);
    }

    public function destroy(Proveedor $proveedor)
    {
        $proveedor->delete();
        return redirect()->route('proveedors.index')->with('success','Proveedor eliminado correctamente.');
    }
}
