@extends('app')

@section('title', 'Reportes')

@section('content')
<section class="card">
  <div class="toolbar" style="margin-bottom:4px">
    <div>
      <h1 class="page-title" style="margin:0">Compras por período</h1>
      <div style="color:#64748b;font-weight:600;margin-top:2px">
        Sin fechas, se muestran todas las compras registradas.
      </div>
    </div>
  </div>

  <form method="GET" action="{{ route('reportes.compras') }}" class="card" style="margin:14px 0;padding:14px">
    <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:end">
      <div>
        <label for="desde" style="display:block;font-weight:700;margin-bottom:4px">Desde</label>
        <input type="date" id="desde" name="desde" value="{{ $filtros['desde'] }}">
      </div>

      <div>
        <label for="hasta" style="display:block;font-weight:700;margin-bottom:4px">Hasta</label>
        <input type="date" id="hasta" name="hasta" value="{{ $filtros['hasta'] }}">
      </div>

      <div>
        <label for="proveedor_id" style="display:block;font-weight:700;margin-bottom:4px">Proveedor</label>
        <select id="proveedor_id" name="proveedor_id">
          <option value="">Todos</option>
          @foreach($proveedores as $p)
            <option value="{{ $p->id }}" {{ (int) $filtros['proveedor_id'] === $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div style="display:flex;gap:8px">
        <button type="submit" class="btn-outline"><i class="ri-filter-2-line"></i> Filtrar</button>
        <a href="{{ route('reportes.compras') }}" class="btn-outline">Limpiar</a>
      </div>
    </div>
  </form>

  <div style="margin:0 0 16px;font-weight:800">
    Total {{ ($filtros['desde'] || $filtros['hasta'] || $filtros['proveedor_id']) ? 'del filtro aplicado' : 'de todas las compras' }}: {{ number_format($total, 2) }}
  </div>

  @if($compras->count())
    <div class="table-wrap">
      <table class="table table-soft">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Proveedor</th>
            <th>Registró</th>
            <th style="text-align:center">Ítems</th>
            <th style="text-align:right">Total</th>
          </tr>
        </thead>
        <tbody>
        @foreach($compras as $c)
          <tr>
            <td>{{ $c->fecha->format('Y-m-d') }}</td>
            <td>{{ $c->proveedor->nombre ?? '—' }}</td>
            <td>{{ $c->user->name ?? '—' }}</td>
            <td style="text-align:center">{{ (int) $c->items_compra }}</td>
            <td style="text-align:right;font-weight:700">{{ number_format((float) $c->total_compra, 2) }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    <div style="margin-top:10px">
      {{ $compras->links() }}
    </div>
  @else
    <div class="empty-box">
      No hay compras que coincidan con estos filtros.
    </div>
  @endif
</section>
@endsection
