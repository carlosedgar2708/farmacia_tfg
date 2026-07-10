@extends('app')

@section('title', 'Reportes')

@section('content')
<section class="card">
  <div class="toolbar" style="margin-bottom:4px">
    <div>
      <h1 class="page-title" style="margin:0">Productos con stock bajo</h1>
      <div style="color:#64748b;font-weight:600;margin-top:2px">
        Productos cuyo stock total (suma de todos sus lotes) está por debajo de {{ $umbral }} unidades.
      </div>
    </div>
  </div>

  <form method="GET" action="{{ route('reportes.stockBajo') }}" class="card" style="margin:14px 0;padding:14px">
    <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:end">
      <div>
        <label for="umbral" style="display:block;font-weight:700;margin-bottom:4px">Umbral</label>
        <input type="number" id="umbral" name="umbral" min="0" value="{{ $filtros['umbral'] }}" style="width:100px">
      </div>

      <div>
        <label for="producto_id" style="display:block;font-weight:700;margin-bottom:4px">Producto</label>
        <select id="producto_id" name="producto_id">
          <option value="">Todos</option>
          @foreach($productosFiltro as $p)
            <option value="{{ $p->id }}" {{ (int) $filtros['producto_id'] === $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div style="display:flex;gap:8px">
        <button type="submit" class="btn-outline"><i class="ri-filter-2-line"></i> Filtrar</button>
        <a href="{{ route('reportes.stockBajo') }}" class="btn-outline">Limpiar</a>
      </div>
    </div>
  </form>

  @if($productos->count())
    <div class="table-wrap">
      <table class="table table-soft">
        <thead>
          <tr>
            <th>Código</th>
            <th>Producto</th>
            <th style="text-align:center">Stock total</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
        @foreach($productos as $p)
          <tr>
            <td>{{ $p->codigo }}</td>
            <td>{{ $p->nombre }}</td>
            <td style="text-align:center">{{ (int) $p->stock_total }}</td>
            <td>
              @if($p->estado_stock === 'sin_stock')
                <span class="badge danger">SIN STOCK</span>
              @else
                <span class="badge warn">STOCK BAJO</span>
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    <div style="margin-top:10px">
      {{ $productos->links() }}
    </div>
  @else
    <div class="empty-box">
      No hay productos con stock por debajo de {{ $umbral }} unidades para este filtro.
    </div>
  @endif
</section>
@endsection
