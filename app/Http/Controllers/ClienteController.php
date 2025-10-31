<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));

        $clientes = Cliente::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = "%{$q}%";
                $query->where(function ($sub) use ($like) {
                    $sub->where('nombre', 'like', $like)
                        ->orWhere('documento', 'like', $like)
                        ->orWhere('telefono', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('clientes.index', compact('clientes', 'q'));
    }

    public function create() {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:255'],
            'telefono'  => ['nullable', 'string', 'max:255'],
        ]);

        // <-- EL CAMBIO: usar $data, no $validated
        $cliente = Cliente::create($data);

        // Si viene desde fetch/axios con Accept: application/json
        if ($request->expectsJson()) {
            return response()->json([
                'id'     => $cliente->id,
                'nombre' => $cliente->nombre,
            ], 201);
        }

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente creado');
    }

    public function show(Cliente $cliente) {}

    public function edit(Cliente $cliente) {}

    public function update(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:255'],
            'telefono'  => ['nullable', 'string', 'max:255'],
        ]);

        $cliente->update($data);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}
