<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Lote;
use App\Models\MovimientoStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CompraController extends Controller
{
    public function index()
    {
        $compras = Compra::with(['proveedor','user','detalles.lote.producto'])
            ->latest('fecha')
            ->paginate(12);

        return view('compras.index', compact('compras'));
    }

    public function create()
    {
        $proveedores = Proveedor::orderBy('nombre')->get(['id','nombre']);
        $productos   = Producto::orderBy('nombre')->get(['id','codigo','nombre']);

        // para JS igual a ventas
        $productosForJs = $productos->map(fn($p)=>[
            'id'=>$p->id,
            'codigo'=>$p->codigo,
            'nombre'=>$p->nombre,
        ])->values();

        $proveedoresForJs = $proveedores->map(fn($pr)=>[
            'id'=>$pr->id,
            'nombre'=>$pr->nombre,
        ])->values();

        return view('compras.create', compact(
            'proveedores','productos',
            'productosForJs','proveedoresForJs'
        ));
    }



    public function store(Request $request)
    {
        $data = $request->validate([
            'proveedor_id'          => ['required','integer', Rule::exists('proveedors','id')],
            'observacion'           => ['nullable','string','max:500'], // si quieres agregar esto a compras
            'items'                 => ['required','array','min:1'],
            'items.*.producto_id'   => ['required','integer', Rule::exists('productos','id')],
            'items.*.nro_lote'      => ['required','string','max:100'],
            'items.*.fecha_vencimiento' => ['nullable','date'],
            'items.*.costo_unitario'=> ['required','numeric','min:0'],
            'items.*.cantidad'      => ['required','integer','min:1'],
        ],[
            'items.required' => 'Agrega al menos un renglón de compra.',
        ]);

        DB::transaction(function () use ($data) {

            $compra = Compra::create([
                'fecha'        => Carbon::now(),
                'proveedor_id' => $data['proveedor_id'],
                'user_id'      => auth()->id(),
                // 'observacion' => $data['observacion'] ?? null,
            ]);

            foreach ($data['items'] as $it) {

                $productoId = (int)$it['producto_id'];
                $nroLote    = trim($it['nro_lote']);
                $cantidad   = (int)$it['cantidad'];
                $costo      = (float)$it['costo_unitario'];
                $vence      = $it['fecha_vencimiento'] ?? null;

                // si el lote ya tiene fecha de vencimiento se tiene que bloquear
                if ($vence && Carbon::parse($vence)->isPast()) {
                    abort(422, "El lote {$nroLote} está vencido. No puedes ingresarlo.");
                }

                // buscamos si ya existe ese nro_lote para ese producto
                $lote = Lote::where('producto_id', $productoId)
                    ->where('nro_lote', $nroLote)
                    ->lockForUpdate()
                    ->first();

                if (!$lote) {
                    // crear lote nuevo
                    $lote = Lote::create([
                        'producto_id'       => $productoId,
                        'nro_lote'          => $nroLote,
                        'fecha_vencimiento' => $vence,
                        'costo_unitario'    => $costo,
                        'stock'             => 0,
                    ]);
                } else {
                    // si ya existe, opcional: actualizar datos del lote
                    // (si no quieres tocar costo/vencimiento, comenta estas líneas)
                    if ($vence) {
                        $lote->fecha_vencimiento = $vence;
                    }
                    $lote->costo_unitario = $costo; // último costo registrado
                    $lote->save();
                }

                // detalle compra (por lote)
                DetalleCompra::create([
                    'compra_id'     => $compra->id,
                    'lote_id'       => $lote->id,
                    'cantidad'      => $cantidad,
                    'costo_unitario'=> $costo,
                ]);

                // sumar stock al lote
                $lote->increment('stock', $cantidad);

                // movimiento stock
                MovimientoStock::create([
                    'lote_id'    => $lote->id,
                    'fecha'      => Carbon::now(),
                    'tipo'       => 'Entrada',
                    'motivo'     => 'Compra',
                    'cantidad'   => $cantidad,
                    'referencia' => 'Compra #'.$compra->id,
                ]);
            }
        });

        return redirect()
            ->route('compras.index')
            ->with('success','Compra registrada correctamente.');
    }
}
