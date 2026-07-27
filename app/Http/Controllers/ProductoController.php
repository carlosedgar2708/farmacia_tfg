<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Support\FormRecovery;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));

        $productos = Producto::query()
            ->with(['lotes' => function ($q) {
                $q->select('id','producto_id','nro_lote','fecha_vencimiento','costo_unitario','stock');
            }])
            ->withSum('lotes as stock_total', 'stock')
            ->when($q !== '', function ($query) use ($q) {
                $like = "%{$q}%";
                $query->where(function ($sub) use ($like) {
                    $sub->where('codigo','like',$like)
                        ->orWhere('nombre','like',$like)
                        ->orWhere('description','like',$like);
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        // Reabrir el modal de stock tras un error de validación en bulkUpdate
        // (PEND-08). El modal es compartido por todos los productos de la
        // tabla, así que no basta con old('lotes') — hace falta saber DE QUÉ
        // producto era, y ese producto puede no estar en la página/búsqueda
        // actual. Se resuelve aparte, sin depender de la paginación de arriba.
        $stockError = null;
        if ($request->filled('stock_error')) {
            $productoConError = Producto::find((int) $request->input('stock_error'));
            if ($productoConError) {
                $stockError = [
                    'producto_id' => $productoConError->id,
                    'nombre'      => $productoConError->nombre,
                    'action'      => route('productos.lotes.bulk', $productoConError),
                    'lotes'       => FormRecovery::items('lotes'),
                ];
            }
        }
        $fieldErrors = FormRecovery::fieldErrors();

        return view('productos.index', compact('productos', 'q', 'stockError', 'fieldErrors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo'        => ['required','string','max:50',
                Rule::unique('productos','codigo')->where(fn($q)=>$q->whereNull('deleted_at'))
            ],
            'nombre'        => ['required','string','max:255'],
            'precio_venta'  => ['required','numeric','min:0'],
            'es_inyectable' => ['sometimes','boolean'],
            'description'   => ['nullable','string'],
        ]);

        $data['es_inyectable'] = (bool)($data['es_inyectable'] ?? false);

        $producto = Producto::create($data);

        if ($request->expectsJson()) {
            return response()->json($producto);
        }

        return redirect()->route('productos.index')
            ->with('success','Producto creado correctamente.');
    }


    public function update(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'codigo'        => ['required', 'string', 'max:50',
                Rule::unique('productos', 'codigo')
                    ->ignore($producto->id)
                    ->where(fn($q) => $q->whereNull('deleted_at'))
            ],
            'nombre'        => ['required', 'string', 'max:255'],
            'es_inyectable' => ['sometimes', 'boolean'],
            'description'   => ['nullable', 'string'],
        ]);

        $data['es_inyectable'] = (bool) ($data['es_inyectable'] ?? false);

        $producto->update($data);

        return redirect()->route('productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        $producto->delete();
        return redirect()->route('productos.index')
            ->with('success', 'Producto eliminado correctamente.');
    }
}
