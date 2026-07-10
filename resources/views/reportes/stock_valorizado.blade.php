@extends('app')

@section('title', 'Reportes')

@section('content')
<section class="card">
  <div class="toolbar" style="margin-bottom:4px">
    <div>
      <h1 class="page-title" style="margin:0">Stock valorizado</h1>
      <div style="color:#64748b;font-weight:600;margin-top:2px">
        Valor del inventario actual (stock × costo unitario) por lote. Ordenado por valor, de mayor a menor.
      </div>
    </div>
  </div>

  <form method="GET" action="{{ route('reportes.stockValorizado') }}" class="card" style="margin:14px 0;padding:14px">
    <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:end">
      <div>
        <label for="producto_id" style="display:block;font-weight:700;margin-bottom:4px">Producto</label>
        <select id="producto_id" name="producto_id">
          <option value="">Todos</option>
          @foreach($productos as $p)
            <option value="{{ $p->id }}" {{ (int) $filtros['producto_id'] === $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div style="display:flex;gap:8px">
        <button type="submit" class="btn-outline"><i class="ri-filter-2-line"></i> Filtrar</button>
        <a href="{{ route('reportes.stockValorizado') }}" class="btn-outline">Limpiar</a>
      </div>
    </div>
  </form>

  <div style="margin:0 0 16px;font-weight:800">
    Valor total {{ $filtros['producto_id'] ? 'del producto filtrado' : 'del inventario' }}: {{ number_format($total, 2) }}
  </div>

  @if($lotes->count())
    <div class="table-wrap">
      <table class="table table-soft">
        <thead>
          <tr>
            <th>Producto</th>
            <th>N.º de lote</th>
            <th style="text-align:center">Stock</th>
            <th style="text-align:right">Costo unitario</th>
            <th style="text-align:right">Valor</th>
          </tr>
        </thead>
        <tbody>
        @foreach($lotes as $lote)
          <tr>
            <td>{{ $lote->producto->nombre ?? '—' }}</td>
            <td>{{ $lote->nro_lote }}</td>
            <td style="text-align:center">{{ $lote->stock }}</td>
            <td style="text-align:right">{{ number_format((float) $lote->costo_unitario, 2) }}</td>
            <td style="text-align:right;font-weight:700">{{ number_format($lote->valor, 2) }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    <div style="margin-top:10px">
      {{ $lotes->links() }}
    </div>
  @else
    <div class="empty-box">
      No hay lotes con stock que coincidan con este filtro.
    </div>
  @endif
</section>
@endsection
