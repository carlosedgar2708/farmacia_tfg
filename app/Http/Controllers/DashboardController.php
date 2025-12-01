<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Rol;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Lote;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $umbralLowStock = 30;

        // Mini stats
        $stats = [
            'usuarios'    => User::count(),
            'roles'       => Rol::count(),
            'proveedors'  => Proveedor::count(),
            'productos'   => Producto::count(),
            'ventas_hoy'  => Venta::whereDate('fecha_venta', Carbon::today())
                                ->where('estado','confirmada')
                                ->count(),
        ];

        // 1) Productos con menos stock (sumando stock de lotes)
        $lowStock = Producto::query()
            ->select(
                'productos.id',
                'productos.nombre',
                DB::raw('COALESCE(SUM(lotes.stock),0) as stock_total')
            )
            ->leftJoin('lotes', function($j){
                $j->on('lotes.producto_id','=','productos.id')
                  ->whereNull('lotes.deleted_at');
            })
            ->whereNull('productos.deleted_at')
            ->groupBy('productos.id','productos.nombre')
            ->orderBy('stock_total','asc')
            ->limit(10)
            ->get();

        // 2) Más vendidos — Mes actual
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes    = Carbon::now()->endOfMonth();

        $topVendidos = DetalleVenta::query()
            ->select(
                'productos.id',
                'productos.nombre',
                DB::raw('SUM(detalles_venta.cantidad) as cantidad')
            )
            ->join('ventas','ventas.id','=','detalles_venta.venta_id')
            ->join('productos','productos.id','=','detalles_venta.producto_id')
            ->whereNull('detalles_venta.deleted_at')
            ->whereNull('productos.deleted_at')
            ->where('ventas.estado','confirmada')
            ->whereBetween('ventas.fecha_venta', [$inicioMes, $finMes])
            ->groupBy('productos.id','productos.nombre')
            ->orderByDesc('cantidad')
            ->limit(5)
            ->get();

        // 3) Próximos a vencer (4 meses)
        $hasta = Carbon::now()->addMonths(4);

        $porVencer = Lote::query()
            ->with('producto:id,nombre')
            ->whereNull('deleted_at')
            ->where('stock','>',0)
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento','<=', $hasta)
            ->orderBy('fecha_vencimiento','asc')
            ->limit(8)
            ->get()
            ->map(function($l){
                return (object)[
                    'producto_nombre' => $l->producto->nombre ?? 'Producto',
                    'nro_lote' => $l->nro_lote,
                    'fecha_vencimiento' => $l->fecha_vencimiento,
                    'stock' => $l->stock,
                ];
            });

        // Label del mes
        $mesLabel = Carbon::now()->translatedFormat('F');

        // USAMOS TU VISTA inicio.blade.php
        return view('inicio', compact(
            'stats',
            'lowStock',
            'topVendidos',
            'porVencer',
            'umbralLowStock',
            'mesLabel'
        ));
    }
}
