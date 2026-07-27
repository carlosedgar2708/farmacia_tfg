<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Lote;
use App\Models\MovimientoStock;
use App\Support\FormRecovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        // Reconstrucción tras un error de validación (PEND-08): si venimos de
        // un submit fallido, old('items') trae los datos crudos (solo ids);
        // se enriquecen acá con el nombre del producto para no depender de
        // que el array PRODUCTOS del cliente esté completo/actualizado.
        $oldItems = FormRecovery::items('items', ['producto_id' => Producto::class]);
        $oldProveedorNombre = FormRecovery::label('proveedor_id', Proveedor::class);
        $fieldErrors = FormRecovery::fieldErrors();

        return view('compras.create', compact(
            'proveedores','productos',
            'productosForJs','proveedoresForJs',
            'oldItems','oldProveedorNombre','fieldErrors'
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

            foreach ($data['items'] as $i => $it) {

                $productoId = (int)$it['producto_id'];
                $nroLote    = trim($it['nro_lote']);
                $cantidad   = (int)$it['cantidad'];
                $costo      = (float)$it['costo_unitario'];
                $vence      = $it['fecha_vencimiento'] ?? null;

                // si el lote ya tiene fecha de vencimiento se tiene que bloquear.
                // Es un error corregible por el usuario (PEND-08): se trata como
                // fallo de validación (conserva old()/errors), no como abort().
                if ($vence && Carbon::parse($vence)->isPast()) {
                    throw ValidationException::withMessages([
                        "items.$i.fecha_vencimiento" => "El lote {$nroLote} está vencido. No puedes ingresarlo.",
                    ]);
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
