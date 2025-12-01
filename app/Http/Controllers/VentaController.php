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

        // Traigo el precio del produto
        $productosForJs = Producto::with([
                'lotes' => fn($q) => $q->where('stock','>',0)->orderBy('fecha_vencimiento'),
            ])
            ->orderBy('nombre')
            ->get(['id','nombre','precio_venta'])
            ->map(function ($p) {
                return [
                    'id'           => $p->id,
                    'nombre'       => $p->nombre,
                    'precio_venta' => (float) $p->precio_venta,
                    'lotes'        => $p->lotes->map(fn($l) => [
                        'id'          => $l->id,
                        'label'       => 'Lote #'.$l->id.(
                                            $l->fecha_vencimiento
                                                ? (' - Vence: '.$l->fecha_vencimiento)
                                                : ''
                                         ),
                        'stock'       => (int) $l->stock,
                        'producto_id' => (int) $l->producto_id,
                    ])->values(),
                ];
            })->values();

        // no tocar, ni mirar esta bien
            $esAdmin = auth()->user()->esAdmin();

        return view('ventas.create', [
            'clientes'       => $clientes,
            'productosForJs' => $productosForJs,
            'esAdmin'        => $esAdmin,
        ]);
    }

    public function store(Request $request)
    {
        // voce sabe que descuento no vai
        $data = $request->validate([
            'cliente_id'           => ['nullable','integer', Rule::exists('clientes','id')],
            'observacion'          => ['nullable','string','max:500'],
            'items'                => ['required','array','min:1'],
            'items.*.producto_id'  => ['required','integer', Rule::exists('productos','id')],
            'items.*.cantidad'     => ['required','integer','min:1'],
            'items.*.precio'       => ['nullable','numeric','min:0'],
            'items.*.descuento'    => ['nullable','numeric','min:0'],
        ],[
            'items.required' => 'Agrega al menos un renglón de venta.',
        ]);

        DB::transaction(function () use ($data) {

            $venta = Venta::create([
                'cliente_id'  => $data['cliente_id'] ?? null,
                'user_id'     => auth()->id(),
                'fecha_venta' => Carbon::now(),
                'observacion' => $data['observacion'] ?? null,
                'estado'      => 'confirmada',
            ]);

            $total = 0;
            $esAdmin = auth()->user()->esAdmin();

            foreach ($data['items'] as $it) {

                $productoId         = (int) $it['producto_id'];
                $cantidadSolicitada = (int) $it['cantidad'];

                // es para traer el precio del producto ;v
                $producto = Producto::findOrFail($productoId);
                $precioUnitario = (float) $producto->precio_venta;

                $descuento = $esAdmin ? (float)($it['descuento'] ?? 0) : 0;

                // con esto tragio mis productos por orden de vecimiento
                $lotes = Lote::where('producto_id', $productoId)
                    ->where('stock', '>', 0)
                    ->orderByRaw('fecha_vencimiento IS NULL, fecha_vencimiento ASC')
                    ->lockForUpdate()
                    ->get();

                $stockTotal = $lotes->sum('stock');
                if ($stockTotal < $cantidadSolicitada) {
                    abort(422, "Stock insuficiente para el producto ID {$productoId}. Disponible total: {$stockTotal}");
                }

                // total del item
                $importeItem = ($cantidadSolicitada * $precioUnitario) - $descuento;
                $total += $importeItem;

                $cantidadRestante = $cantidadSolicitada;

                foreach ($lotes as $lote) {
                    if ($cantidadRestante <= 0) break;

                    $disponibleEnLote = (int) $lote->stock;
                    if ($disponibleEnLote <= 0) continue;

                    $tomar = min($cantidadRestante, $disponibleEnLote);

                    DetalleVenta::create([
                        'venta_id'        => $venta->id,
                        'producto_id'     => $productoId,
                        'lote_id'         => $lote->id,
                        'cantidad'        => $tomar,
                        'precio_unitario' => $precioUnitario,
                    ]);

                    $lote->decrement('stock', $tomar);

                    MovimientoStock::create([
                        'lote_id'    => $lote->id,
                        'fecha'      => Carbon::now(),
                        'tipo'       => 'Salida',
                        'motivo'     => 'Venta',
                        'cantidad'   => $tomar,
                        'referencia' => 'Venta #'.$venta->id,
                    ]);

                    $cantidadRestante -= $tomar;
                }

                if ($cantidadRestante > 0) {
                    abort(422, "Ocurrió un problema al descontar stock por lotes. Faltan {$cantidadRestante} unidades.");
                }
            }

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
