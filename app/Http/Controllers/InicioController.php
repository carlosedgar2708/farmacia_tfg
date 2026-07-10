<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Rol;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Configuracion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InicioController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();

        // ====== STATS ARRIBA ======
        $stats = [
            'usuarios'    => User::count(),
            'roles'       => Rol::count(),
            'proveedors'  => Proveedor::count(),
            'productos'   => Producto::count(),
            'ventasHoy'   => Venta::whereDate('fecha_venta', $hoy)->count(),
        ];

        // ====== PRODUCTOS CON MENOS STOCK (sumatoria lotes) ======
        $productosMenosStock = Producto::query()
            ->leftJoin('lotes', function($j){
                $j->on('lotes.producto_id','=','productos.id')
                  ->whereNull('lotes.deleted_at');
            })
            ->select(
                'productos.id',
                'productos.nombre',
                DB::raw('COALESCE(SUM(lotes.stock),0) as stock_total')
            )
            ->whereNull('productos.deleted_at')
            ->groupBy('productos.id','productos.nombre')
            ->orderBy('stock_total','asc')
            ->limit(5)
            ->get();

        // ====== PRODUCTOS MÁS VENDIDOS (MES ACTUAL) ======
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes    = Carbon::now()->endOfMonth();

        $productosMasVendidos = DetalleVenta::query()
            ->join('ventas','ventas.id','=','detalles_venta.venta_id')
            ->join('productos','productos.id','=','detalles_venta.producto_id')
            ->whereNull('detalles_venta.deleted_at')
            ->whereBetween('ventas.fecha_venta', [$inicioMes, $finMes])
            ->select(
                'productos.id',
                'productos.nombre',
                DB::raw('SUM(detalles_venta.cantidad) as cantidad_vendida')
            )
            ->groupBy('productos.id','productos.nombre')
            ->orderByDesc('cantidad_vendida')
            ->limit(5)
            ->get();

        // ====== PRÓXIMOS A VENCER (umbral configurable, ver Configuracion) ======
        // Estado por PRODUCTO (no por lote), con prioridad: vencido > próximo a vencer > ok.
        // La comparación de fechas vive únicamente en los scopes de Lote (vencidos/proximosAVencer);
        // aquí solo se compone el resultado por producto.
        $diasAlertaVencimiento = Configuracion::obtener('dias_alerta_vencimiento', 90);

        $proximosVencer = Producto::whereNull('deleted_at')
            ->conAlertaVencimiento($diasAlertaVencimiento)
            ->withExists(['lotes as tiene_vencido' => fn ($q) => $q->where('stock', '>', 0)->vencidos()])
            ->orderByDesc('tiene_vencido')
            ->limit(5)
            ->get()
            ->map(function ($p) use ($diasAlertaVencimiento) {
                if ($p->tiene_vencido) {
                    $p->estado_vencimiento = 'vencido';
                    $p->lote_relevante = $p->loteVencidoRelevante();
                } else {
                    $p->estado_vencimiento = 'proximo';
                    $p->lote_relevante = $p->loteProximoRelevante($diasAlertaVencimiento);
                }
                return $p;
            });

        return view('inicio', compact(
            'stats',
            'productosMenosStock',
            'productosMasVendidos',
            'proximosVencer'
        ));
    }
}
