<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoteController extends Controller
{
    /**
     * Lista los lotes de un producto con búsqueda y paginación.
     */
    public function index(Request $request, Producto $producto)
    {
        $q = trim($request->get('q', ''));

        $lotes = $producto->lotes()
            ->when($q !== '', function ($query) use ($q) {
                $like = "%{$q}%";
                $query->where('nro_lote', 'like', $like);
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('productos.lotes.index', compact('producto', 'lotes', 'q'));
    }

    /**
     * Crea un lote nuevo para el producto.
     */
    public function store(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'nro_lote' => [
                'required', 'string', 'max:100',
                Rule::unique('lotes', 'nro_lote')
                    ->where(fn($q) => $q->where('producto_id', $producto->id)
                                        ->whereNull('deleted_at')),
            ],
            'fecha_vencimiento' => ['nullable','date'],
            'costo_unitario'    => ['required','numeric','min:0'],
            'stock'             => ['required','integer','min:0'],
        ]);

        $data['producto_id'] = $producto->id;

        Lote::create($data);

        return redirect()
            ->route('productos.lotes.index', $producto)
            ->with('success', 'Lote creado correctamente.');
    }

    /**
     * Actualiza un lote existente.
     */
    public function update(Request $request, Producto $producto, Lote $lote)
    {
        $data = $request->validate([
            'nro_lote' => [
                'required', 'string', 'max:100',
                Rule::unique('lotes', 'nro_lote')
                    ->ignore($lote->id)
                    ->where(fn($q) => $q->where('producto_id', $producto->id)
                                        ->whereNull('deleted_at')),
            ],
            'fecha_vencimiento' => ['nullable','date'],
            'costo_unitario'    => ['required','numeric','min:0'],
            'stock'             => ['required','integer','min:0'],
        ]);

        $lote->update($data);

        return redirect()
            ->route('productos.lotes.index', $producto)
            ->with('success', 'Lote actualizado correctamente.');
    }

    /**
     * Elimina (soft delete) un lote.
     */
    public function destroy(Producto $producto, Lote $lote)
    {
        $lote->delete();

        return redirect()
            ->route('productos.lotes.index', $producto)
            ->with('success', 'Lote eliminado correctamente.');
    }
    public function bulkUpdate(Request $request, \App\Models\Producto $producto)
    {
        $data = $request->validate([
            'lotes'                              => ['required','array'],
            'lotes.*.id'                         => ['nullable','integer','exists:lotes,id'],
            'lotes.*.nro_lote'                   => ['required_without:lotes.*.id','nullable','string','max:100'],
            'lotes.*.fecha_vencimiento'          => ['nullable','date'],
            'lotes.*.costo_unitario'             => ['required','numeric','min:0'],
            'lotes.*.stock'                      => ['required','integer','min:0'],
        ]);

        foreach ($data['lotes'] as $row) {
            // UPDATE
            if (!empty($row['id'])) {
                $lote = \App\Models\Lote::where('id', $row['id'])
                    ->where('producto_id', $producto->id)
                    ->firstOrFail();

                $lote->update([
                    'nro_lote'          => $row['nro_lote'] ?? $lote->nro_lote,
                    'fecha_vencimiento' => $row['fecha_vencimiento'] ?? $lote->fecha_vencimiento,
                    'costo_unitario'    => $row['costo_unitario'],
                    'stock'             => $row['stock'],
                ]);
                continue;
            }

            // CREATE (cuando id viene vacío)
            if (empty($row['nro_lote'])) {
                return back()->with('error', 'El N° de lote es obligatorio para nuevos lotes.');
            }

            // validar unicidad por producto (nro_lote)
            $exists = \App\Models\Lote::where('producto_id', $producto->id)
                ->where('nro_lote', $row['nro_lote'])
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                return back()->with('error', "El N° de lote {$row['nro_lote']} ya existe para este producto.");
            }

            \App\Models\Lote::create([
                'producto_id'        => $producto->id,
                'nro_lote'           => $row['nro_lote'],
                'fecha_vencimiento'  => $row['fecha_vencimiento'] ?? null,
                'costo_unitario'     => $row['costo_unitario'],
                'stock'              => $row['stock'],
            ]);
        }

        return back()->with('success', 'Cambios de stock guardados correctamente.');
    }

}
