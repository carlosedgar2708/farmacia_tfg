<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Lote;
use App\Models\MovimientoStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class VentaController extends Controller
{
    public function index()
    {
        $ventas = Venta::with(['cliente', 'user', 'detalles'])
            ->latest('fecha_venta')
            ->paginate(12);

        return view('ventas.index', compact('ventas'));
    }

    public function create()
    {
        $clientes = Cliente::orderBy('nombre')->get(['id','nombre']);

        // Prepara productos + lotes (con precio y stock) para JS
        $productosForJs = Producto::with([
            'lotes' => fn($q) => $q->where('stock','>',0)->orderBy('fecha_vencimiento'),
        ])
        ->orderBy('nombre')->get(['id','nombre'])
        ->map(function ($p) {
            return [
                'id'     => $p->id,
                'nombre' => $p->nombre,
                'lotes'  => $p->lotes->map(fn($l) => [
                    'id'     => $l->id,
                    'label'  => 'Lote #'.$l->id.($l->fecha_vencimiento ? (' - Vence: '.$l->fecha_vencimiento) : ''),
                    'precio' => (float) $l->costo_unitario,   // <-- usa aquí tu campo de precio de venta si es distinto
                    'stock'  => (int) $l->stock,
                    'producto_id' => (int) $l->producto_id,
                ])->values(),
            ];
        })->values();

        return view('ventas.create', [
            'clientes'       => $clientes,
            'productosForJs' => $productosForJs,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id'           => ['nullable','integer', Rule::exists('clientes','id')],
            'observacion'          => ['nullable','string','max:500'],
            'items'                => ['required','array','min:1'],
            'items.*.producto_id'  => ['required','integer', Rule::exists('productos','id')],
            'items.*.lote_id'      => ['required','integer', Rule::exists('lotes','id')],
            'items.*.cantidad'     => ['required','integer','min:1'],
            'items.*.precio'       => ['required','numeric','min:0'],
        ],[
            'items.required' => 'Agrega al menos un renglón de venta.',
        ]);

        DB::transaction(function () use ($data) {
            // Cabecera
            $venta = Venta::create([
                'cliente_id'  => $data['cliente_id'] ?? null,   // opcional
                'user_id'     => auth()->id(),
                'fecha_venta' => Carbon::now(),
                'observacion' => $data['observacion'] ?? null,
                'estado'      => 'confirmada',
            ]);

            $total = 0;

            foreach ($data['items'] as $it) {
                // Bloquea fila del lote para evitar carreras
                $lote = Lote::lockForUpdate()->findOrFail($it['lote_id']);

                // Coherencia: que el lote pertenezca al producto elegido
                if ((int)$lote->producto_id !== (int)$it['producto_id']) {
                    abort(422, 'El lote no pertenece al producto seleccionado.');
                }

                // Stock suficiente
                if ((int)$lote->stock < (int)$it['cantidad']) {
                    abort(422, "Stock insuficiente en el lote #{$lote->id} ({$lote->stock} disp.).");
                }

                // Crea detalle (sin producto_id porque tu tabla no lo tiene)
                DetalleVenta::create([
                    'venta_id'        => $venta->id,
                    'producto_id'     => $it['producto_id'],
                    'lote_id'         => $it['lote_id'],
                    'cantidad'        => (int)$it['cantidad'],
                    'precio_unitario' => (float)$it['precio'],
                ]);

                // Descontar stock
                $lote->decrement('stock', (int)$it['cantidad']);

                // Movimiento de stock
                MovimientoStock::create([
                    'lote_id'         => $lote->id,
                    'fecha'           => Carbon::now(),
                    'tipo'            => 'Salida',
                    'motivo'          => 'Venta',
                    'cantidad'        => (int)$it['cantidad'],
                    'referencia'      => 'Venta #'.$venta->id,
                ]);

                $total += ((int)$it['cantidad']) * ((float)$it['precio']);
            }

            // Si manejas recibo asociado
            if (method_exists($venta, 'recibo')) {
                $venta->recibo()->create([
                    'venta_id' => $venta->id,
                    'monto'    => $total,
                ]);
            }
        });

        return redirect()->route('ventas.index')->with('success','Venta registrada correctamente.');
    }
}
