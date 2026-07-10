<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Lote;
use App\Models\Producto;
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
