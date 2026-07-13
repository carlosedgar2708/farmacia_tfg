<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Configuracion;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
use App\Models\Lote;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ReporteController extends Controller
{
    /**
     * Landing del módulo de Reportes: enlaces a los reportes disponibles.
     */
    public function index()
    {
        return view('reportes.index');
    }

    /**
     * Reporte "Lotes próximos a vencer": lotes con stock que están vencidos o
     * próximos a vencer, filtrables por estado, rango de fechas y producto.
     *
     * Toda la lógica de fechas se delega en los scopes de Lote (vigentes/vencidos/
     * proximosAVencer/venceEntre) y en Configuracion::obtener('dias_alerta_vencimiento').
     * Aquí solo se compone el resultado, sin comparar fechas directamente.
     */
    public function vencimientos(Request $request)
    {
        $estado = $request->get('estado', 'todos');
        if (!in_array($estado, ['todos', 'vencidos', 'proximos'], true)) {
            $estado = 'todos';
        }

        $desdeInput = $this->fechaValida($request->get('desde'));
        $hastaInput = $this->fechaValida($request->get('hasta'));
        $productoId = $request->filled('producto_id') ? (int) $request->get('producto_id') : null;

        // Rango inválido (desde > hasta): se ignora en vez de romper la consulta.
        if ($desdeInput && $hastaInput && Carbon::parse($desdeInput)->gt(Carbon::parse($hastaInput))) {
            $desdeInput = null;
            $hastaInput = null;
        }

        $diasAlerta = (int) Configuracion::obtener('dias_alerta_vencimiento', 90);

        $vencidos = collect();
        if ($estado !== 'proximos') {
            $vencidos = Lote::query()
                ->with('producto')
                ->where('stock', '>', 0)
                ->when($productoId, fn ($q) => $q->where('producto_id', $productoId))
                ->vencidos()
                ->orderBy('fecha_vencimiento')
                ->get()
                ->each(fn ($lote) => $lote->estado_vencimiento = 'vencido');
        }

        $proximos = collect();
        if ($estado !== 'vencidos') {
            $query = Lote::query()
                ->with('producto')
                ->where('stock', '>', 0)
                ->when($productoId, fn ($q) => $q->where('producto_id', $productoId))
                ->vigentes();

            $query = ($desdeInput || $hastaInput)
                ? $query->venceEntre($desdeInput ?? today(), $hastaInput ?? today()->copy()->addDays($diasAlerta))
                : $query->proximosAVencer($diasAlerta);

            $proximos = $query->orderBy('fecha_vencimiento')
                ->get()
                ->each(fn ($lote) => $lote->estado_vencimiento = 'proximo');
        }

        $todos = $vencidos->concat($proximos);

        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $lotes = new LengthAwarePaginator(
            $todos->forPage($page, $perPage)->values(),
            $todos->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $productos = Producto::orderBy('nombre')->get(['id', 'nombre']);

        return view('reportes.vencimientos', [
            'lotes'       => $lotes,
            'productos'   => $productos,
            'diasAlerta'  => $diasAlerta,
            'filtros'     => [
                'estado'       => $estado,
                'desde'        => $desdeInput,
                'hasta'        => $hastaInput,
                'producto_id'  => $productoId,
            ],
        ]);
    }

    /**
     * Reporte "Stock valorizado": valor del stock actual (stock x costo_unitario)
     * por lote, con el total general de lo filtrado. Orden por defecto: valor
     * descendente (los lotes que representan mayor inversión primero).
     */
    public function stockValorizado(Request $request)
    {
        $productoId = $request->filled('producto_id') ? (int) $request->get('producto_id') : null;

        $base = Lote::query()
            ->where('stock', '>', 0)
            ->when($productoId, fn ($q) => $q->where('producto_id', $productoId));

        $total = (float) (clone $base)->selectRaw('COALESCE(SUM(stock * costo_unitario), 0) as total')->value('total');

        $lotes = (clone $base)
            ->with('producto')
            ->orderByRaw('(stock * costo_unitario) DESC')
            ->paginate(15)
            ->withQueryString();

        $productos = Producto::orderBy('nombre')->get(['id', 'nombre']);

        return view('reportes.stock_valorizado', [
            'lotes'     => $lotes,
            'total'     => $total,
            'productos' => $productos,
            'filtros'   => [
                'producto_id' => $productoId,
            ],
        ]);
    }

    /**
     * Reporte "Productos con stock bajo": productos cuyo stock total (suma de
     * todos sus lotes) está por debajo de un umbral. Incluye productos con
     * stock 0, incluso sin ningún lote registrado — es el caso más crítico.
     *
     * El stock total reutiliza el mismo patrón withSum() ya usado en
     * ProductoController::index() para la columna "Stock total" del catálogo,
     * en vez de reimplementar el join/groupBy manual que usa InicioController.
     */
    public function stockBajo(Request $request)
    {
        $productoId = $request->filled('producto_id') ? (int) $request->get('producto_id') : null;

        $umbralDefault = (int) Configuracion::obtener('stock_bajo_umbral', 30);
        $umbral = $request->filled('umbral') ? (int) $request->get('umbral') : $umbralDefault;

        // withSum() deja stock_total en NULL (no 0) para productos sin ningún lote,
        // y filtrar por su alias vía HAVING/GROUP BY resultó no ser portable entre
        // MySQL y SQLite (el motor de los tests). Se usa la misma suma correlacionada
        // que genera withSum(), pero explícita, para el WHERE/ORDER BY con COALESCE.
        $sumaStock = 'COALESCE((select sum(l.stock) from lotes l '
            . 'where l.producto_id = productos.id and l.deleted_at is null), 0)';

        $productos = Producto::query()
            ->withSum('lotes as stock_total', 'stock')
            ->when($productoId, fn ($q) => $q->where('id', $productoId))
            ->whereRaw("{$sumaStock} < ?", [$umbral])
            ->orderByRaw("{$sumaStock} ASC")
            ->paginate(15)
            ->withQueryString();

        $productos->getCollection()->transform(function ($p) {
            $p->estado_stock = ((int) $p->stock_total) === 0 ? 'sin_stock' : 'bajo';
            return $p;
        });

        $productosFiltro = Producto::orderBy('nombre')->get(['id', 'nombre']);

        return view('reportes.stock_bajo', [
            'productos'        => $productos,
            'umbral'           => $umbral,
            'productosFiltro'  => $productosFiltro,
            'filtros'          => [
                'producto_id' => $productoId,
                'umbral'      => $umbral,
            ],
        ]);
    }

    /**
     * Reporte "Compras por período": una fila por compra, filtrable por rango
     * de fechas (opcional, sin período por defecto) y proveedor. El total por
     * compra y el total general se calculan en SQL, no con
     * Compra::getTotalAttribute() (que suma en PHP sobre detalles cargados —
     * adecuado para una sola compra, no para un listado paginado de muchas).
     */
    public function compras(Request $request)
    {
        $desde = $this->fechaValida($request->get('desde'));
        $hasta = $this->fechaValida($request->get('hasta'));

        // Rango inválido (desde > hasta): se ignora, igual que en "vencimientos".
        if ($desde && $hasta && Carbon::parse($desde)->gt(Carbon::parse($hasta))) {
            $desde = null;
            $hasta = null;
        }

        $proveedorId = $request->filled('proveedor_id') ? (int) $request->get('proveedor_id') : null;

        $base = Compra::query()
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->when($proveedorId, fn ($q) => $q->where('proveedor_id', $proveedorId));

        $total = (float) DetalleCompra::query()
            ->join('compras', 'compras.id', '=', 'detalles_compra.compra_id')
            ->when($desde, fn ($q) => $q->whereDate('compras.fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('compras.fecha', '<=', $hasta))
            ->when($proveedorId, fn ($q) => $q->where('compras.proveedor_id', $proveedorId))
            ->selectRaw('COALESCE(SUM(detalles_compra.cantidad * detalles_compra.costo_unitario), 0) as total')
            ->value('total');

        $totalCompraSql = 'COALESCE((select sum(dc.cantidad * dc.costo_unitario) '
            . 'from detalles_compra dc where dc.compra_id = compras.id), 0)';
        $itemsCompraSql = '(select count(*) from detalles_compra dc where dc.compra_id = compras.id)';

        $compras = (clone $base)
            ->with(['proveedor', 'user'])
            ->selectRaw("compras.*, {$totalCompraSql} as total_compra, {$itemsCompraSql} as items_compra")
            ->orderByDesc('fecha')
            ->paginate(15)
            ->withQueryString();

        $proveedores = Proveedor::orderBy('nombre')->get(['id', 'nombre']);

        return view('reportes.compras', [
            'compras'     => $compras,
            'total'       => $total,
            'proveedores' => $proveedores,
            'filtros'     => [
                'desde'        => $desde,
                'hasta'        => $hasta,
                'proveedor_id' => $proveedorId,
            ],
        ]);
    }

    /**
     * Reporte "Ventas por período": una fila por venta, filtrable por rango de
     * fechas (opcional, sin período por defecto), usuario y cliente. Sin filtro
     * de estado (PEND-03/anulación de ventas no existe todavía) — se muestra
     * solo como columna informativa.
     *
     * Limitación de datos conocida: el descuento aplicado en VentaController::store()
     * nunca se persiste (no hay columna `descuento` en detalles_venta, y el intento
     * de guardar el total con descuento en `recibos.monto` se descarta en silencio
     * porque esa columna no existe). El total de este reporte es, por lo tanto, el
     * bruto reconstruido desde detalles_venta — el mismo cálculo que ya muestra
     * ventas/index.blade.php vía Venta::getTotalAttribute(), no una limitación nueva.
     */
    public function ventas(Request $request)
    {
        $desde = $this->fechaValida($request->get('desde'));
        $hasta = $this->fechaValida($request->get('hasta'));

        // Rango inválido (desde > hasta): se ignora, igual que en los reportes anteriores.
        if ($desde && $hasta && Carbon::parse($desde)->gt(Carbon::parse($hasta))) {
            $desde = null;
            $hasta = null;
        }

        $userId = $request->filled('user_id') ? (int) $request->get('user_id') : null;
        $clienteId = $request->filled('cliente_id') ? (int) $request->get('cliente_id') : null;

        $base = Venta::query()
            ->when($desde, fn ($q) => $q->whereDate('fecha_venta', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_venta', '<=', $hasta))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId));

        $total = (float) DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalles_venta.venta_id')
            ->when($desde, fn ($q) => $q->whereDate('ventas.fecha_venta', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('ventas.fecha_venta', '<=', $hasta))
            ->when($userId, fn ($q) => $q->where('ventas.user_id', $userId))
            ->when($clienteId, fn ($q) => $q->where('ventas.cliente_id', $clienteId))
            ->selectRaw('COALESCE(SUM(detalles_venta.cantidad * detalles_venta.precio_unitario), 0) as total')
            ->value('total');

        // Se usa SUM(cantidad) en vez de COUNT(*) de detalles_venta: un mismo
        // producto puede generar varios DetalleVenta si el FIFO tomó stock de
        // más de un lote, así que COUNT(*) no equivale a unidades vendidas.
        $totalVentaSql = 'COALESCE((select sum(dv.cantidad * dv.precio_unitario) '
            . 'from detalles_venta dv where dv.venta_id = ventas.id), 0)';
        $unidadesVentaSql = 'COALESCE((select sum(dv.cantidad) '
            . 'from detalles_venta dv where dv.venta_id = ventas.id), 0)';

        $ventas = (clone $base)
            ->with(['cliente', 'user'])
            ->selectRaw("ventas.*, {$totalVentaSql} as total_venta, {$unidadesVentaSql} as unidades_venta")
            ->orderByDesc('fecha_venta')
            ->paginate(15)
            ->withQueryString();

        $usuarios = User::orderBy('name')->get(['id', 'name']);
        $clientes = Cliente::orderBy('nombre')->get(['id', 'nombre']);

        return view('reportes.ventas', [
            'ventas'      => $ventas,
            'total'       => $total,
            'usuarios'    => $usuarios,
            'clientes'    => $clientes,
            'filtros'     => [
                'desde'       => $desde,
                'hasta'       => $hasta,
                'user_id'     => $userId,
                'cliente_id'  => $clienteId,
            ],
        ]);
    }

    /**
     * Reporte "Historial de movimientos de stock": una fila por movimiento,
     * filtrable por producto, lote, tipo (Entrada/Salida) y rango de fechas.
     * Resuelve conjuntamente AUS-01 (Reporte 6) y AUS-02 (ver docs/pendientes.md).
     *
     * No usa MovimientoStock::user() ni MovimientoStock::referencia() (ambas
     * relaciones están rotas: referencian columnas que no existen en la
     * migración de movimientos_stock). El campo `referencia` se muestra tal
     * cual, como el texto libre que es. Filtra por la columna `tipo` en vez de
     * los scopes scopeEntradas()/scopeSalidas() (rotos por BUG-06, sin corregir
     * en este sprint). Sin agregación: es el único reporte que no necesita
     * SUM/COUNT, así que no hereda el riesgo de portabilidad MySQL/SQLite de
     * los reportes 3-5.
     */
    public function movimientos(Request $request)
    {
        $desde = $this->fechaValida($request->get('desde'));
        $hasta = $this->fechaValida($request->get('hasta'));

        // Rango inválido (desde > hasta): se ignora, igual que en los reportes anteriores.
        if ($desde && $hasta && Carbon::parse($desde)->gt(Carbon::parse($hasta))) {
            $desde = null;
            $hasta = null;
        }

        $productoId = $request->filled('producto_id') ? (int) $request->get('producto_id') : null;
        $loteId = $request->filled('lote_id') ? (int) $request->get('lote_id') : null;

        $tipo = $request->get('tipo');
        if (!in_array($tipo, ['Entrada', 'Salida'], true)) {
            $tipo = null;
        }

        $movimientos = MovimientoStock::query()
            ->with('lote.producto')
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->when($loteId, fn ($q) => $q->where('lote_id', $loteId))
            ->when($productoId, fn ($q) => $q->whereHas('lote', fn ($l) => $l->where('producto_id', $productoId)))
            ->when($tipo, fn ($q) => $q->where('tipo', $tipo))
            ->orderByDesc('fecha')
            ->paginate(15)
            ->withQueryString();

        $productos = Producto::orderBy('nombre')->get(['id', 'nombre']);
        $lotes = Lote::with('producto')->orderByDesc('id')->get(['id', 'producto_id', 'nro_lote']);

        return view('reportes.movimientos', [
            'movimientos' => $movimientos,
            'productos'   => $productos,
            'lotes'       => $lotes,
            'filtros'     => [
                'desde'       => $desde,
                'hasta'       => $hasta,
                'producto_id' => $productoId,
                'lote_id'     => $loteId,
                'tipo'        => $tipo,
            ],
        ]);
    }

    /**
     * Valida y normaliza una fecha recibida por query string. Si no es una
     * fecha parseable, se ignora en vez de propagar la excepción de Carbon.
     */
    private function fechaValida(?string $valor): ?string
    {
        if (!$valor) {
            return null;
        }

        try {
            return Carbon::parse($valor)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
