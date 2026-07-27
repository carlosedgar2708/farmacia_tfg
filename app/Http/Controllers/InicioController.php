<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Configuracion;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Venta;

class InicioController extends Controller
{
    /**
     * Dashboard "centro de acción diario" (UI-05). Cada bloque reutiliza
     * exactamente la lógica ya construida en el módulo de Reportes o en
     * scopes ya existentes — aquí solo se acota el alcance (hoy / sin
     * paginación / top 5), sin reimplementar ninguna consulta.
     *
     * Excepción documentada: el bloque de stock bajo repite la misma
     * subquery que ReporteController::stockBajo() en vez de compartir un
     * scope. Es deuda técnica ya aceptada (ver docs/pendientes.md) — la
     * extracción a Producto::scopeConStockBajo() queda fuera de este sprint.
     */
    public function index()
    {
        // ====== 1) QUÉ PASÓ HOY ======
        // Venta::delDia() ya existía (sin usar en ningún lado hasta ahora).
        // Compra::delDia() se agregó en este sprint, calcado del mismo criterio.
        $ventasHoy = [
            'cantidad' => Venta::delDia()->count(),
            'monto'    => (float) DetalleVenta::query()
                ->join('ventas', 'ventas.id', '=', 'detalles_venta.venta_id')
                ->whereDate('ventas.fecha_venta', today())
                ->selectRaw('COALESCE(SUM(detalles_venta.cantidad * detalles_venta.precio_unitario), 0) as total')
                ->value('total'),
        ];

        $comprasHoy = [
            'cantidad' => Compra::delDia()->count(),
            'monto'    => (float) DetalleCompra::query()
                ->join('compras', 'compras.id', '=', 'detalles_compra.compra_id')
                ->whereDate('compras.fecha', today())
                ->selectRaw('COALESCE(SUM(detalles_compra.cantidad * detalles_compra.costo_unitario), 0) as total')
                ->value('total'),
        ];

        // ====== 2) ALERTAS CRÍTICAS ======

        // Vencidos / próximos a vencer: misma composición que ya usaba este
        // controlador (Producto::conAlertaVencimiento + loteVencidoRelevante/
        // loteProximoRelevante + Configuracion::obtener('dias_alerta_vencimiento')),
        // separada en dos grupos en vez de una sola lista mezclada.
        $diasAlertaVencimiento = (int) Configuracion::obtener('dias_alerta_vencimiento', 90);

        $productosConAlerta = Producto::whereNull('deleted_at')
            ->conAlertaVencimiento($diasAlertaVencimiento)
            ->withExists(['lotes as tiene_vencido' => fn ($q) => $q->where('stock', '>', 0)->vencidos()])
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

        $ordenarPorVencimiento = fn ($p) => optional($p->lote_relevante)->fecha_vencimiento ?? '9999-99-99';

        $vencidos = $productosConAlerta->where('estado_vencimiento', 'vencido')
            ->sortBy($ordenarPorVencimiento)->take(5)->values();

        $proximosAVencer = $productosConAlerta->where('estado_vencimiento', 'proximo')
            ->sortBy($ordenarPorVencimiento)->take(5)->values();

        // Stock bajo: misma subquery que ReporteController::stockBajo() —
        // deliberadamente duplicada, ver nota de deuda técnica arriba.
        $umbralStockBajo = (int) Configuracion::obtener('stock_bajo_umbral', 30);
        $sumaStock = 'COALESCE((select sum(l.stock) from lotes l '
            . 'where l.producto_id = productos.id and l.deleted_at is null), 0)';

        $stockBajo = Producto::query()
            ->withSum('lotes as stock_total', 'stock')
            ->whereRaw("{$sumaStock} < ?", [$umbralStockBajo])
            ->orderByRaw("{$sumaStock} ASC")
            ->limit(5)
            ->get()
            ->each(function ($p) {
                $p->estado_stock = ((int) $p->stock_total) === 0 ? 'sin_stock' : 'bajo';
            });

        // ====== 3) ÚLTIMOS MOVIMIENTOS ======
        // Misma base que ReporteController::movimientos(), sin filtros, limit(10) en vez de paginate().
        $ultimosMovimientos = MovimientoStock::query()
            ->with('lote.producto')
            ->orderByDesc('fecha')
            ->limit(10)
            ->get();

        return view('inicio', compact(
            'ventasHoy',
            'comprasHoy',
            'vencidos',
            'proximosAVencer',
            'stockBajo',
            'ultimosMovimientos'
        ));
    }
}
