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
            // OJO: ya no pedimos lote_id aquí
            // 'items.*.lote_id'   => ['required','integer', Rule::exists('lotes','id')],
            'items.*.cantidad'     => ['required','integer','min:1'],
            'items.*.precio'       => ['required','numeric','min:0'],
        ],[
            'items.required' => 'Agrega al menos un renglón de venta.',
        ]);


        DB::transaction(function () use ($data) {
            // Cabecera
            $venta = Venta::create([
                'cliente_id'  => $data['cliente_id'] ?? null,
                'user_id'     => auth()->id(),
                'fecha_venta' => Carbon::now(),
                'observacion' => $data['observacion'] ?? null,
                'estado'      => 'confirmada',
            ]);

            $total = 0;

            foreach ($data['items'] as $it) {
                $productoId = (int) $it['producto_id'];
                $cantidadSolicitada = (int) $it['cantidad'];
                $precioUnitario = (float) $it['precio'];

                // Traer TODOS los lotes del producto con stock > 0
                // ordenados por fecha de vencimiento (primero los que vencen antes)
                $lotes = Lote::where('producto_id', $productoId)
                    ->where('stock', '>', 0)
                    ->orderByRaw('fecha_vencimiento IS NULL, fecha_vencimiento ASC') // los NULL al final
                    ->lockForUpdate() // bloqueamos para evitar carreras
                    ->get();

                // Verificar stock total suficiente
                $stockTotal = $lotes->sum('stock');
                if ($stockTotal < $cantidadSolicitada) {
                    abort(422, "Stock insuficiente para el producto ID {$productoId}. Disponible total: {$stockTotal}");
                }

                $cantidadRestante = $cantidadSolicitada;

                foreach ($lotes as $lote) {
                    if ($cantidadRestante <= 0) {
                        break;
                    }

                    $disponibleEnLote = (int) $lote->stock;
                    if ($disponibleEnLote <= 0) {
                        continue;
                    }

                    // Cuánto tomamos de este lote
                    $tomar = min($cantidadRestante, $disponibleEnLote);

                    // Creamos detalle de venta para ESTE lote
                    DetalleVenta::create([
                        'venta_id'        => $venta->id,
                        'producto_id'     => $productoId,
                        'lote_id'         => $lote->id,
                        'cantidad'        => $tomar,
                        'precio_unitario' => $precioUnitario, // mismo precio para todos los lotes del mismo producto
                    ]);

                    // Descontamos stock
                    $lote->decrement('stock', $tomar);

                    // Movimiento de stock
                    MovimientoStock::create([
                        'lote_id'    => $lote->id,
                        'fecha'      => Carbon::now(),
                        'tipo'       => 'Salida',
                        'motivo'     => 'Venta',
                        'cantidad'   => $tomar,
                        'referencia' => 'Venta #'.$venta->id,
                    ]);

                    $total += $tomar * $precioUnitario;
                    $cantidadRestante -= $tomar;
                }

                // Por seguridad, si al final queda algo (no debería pasar por el check de arriba)
                if ($cantidadRestante > 0) {
                    abort(422, "Ocurrió un problema al descontar stock por lotes. Faltan {$cantidadRestante} unidades.");
                }
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
