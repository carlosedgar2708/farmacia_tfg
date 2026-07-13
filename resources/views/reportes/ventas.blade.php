@extends('app')

@section('title', 'Reportes')

@section('content')
<section class="card">
  <div class="toolbar" style="margin-bottom:4px">
    <div>
      <h1 class="page-title" style="margin:0">Ventas por período</h1>
      <div style="color:#64748b;font-weight:600;margin-top:2px">
        Sin fechas, se muestran todas las ventas registradas.
      </div>
    </div>
  </div>

  <form method="GET" action="{{ route('reportes.ventas') }}" class="card" style="margin:14px 0;padding:14px">
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
        <label for="user_id" style="display:block;font-weight:700;margin-bottom:4px">Usuario</label>
        <select id="user_id" name="user_id">
          <option value="">Todos</option>
          @foreach($usuarios as $u)
            <option value="{{ $u->id }}" {{ (int) $filtros['user_id'] === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label for="cliente_id" style="display:block;font-weight:700;margin-bottom:4px">Cliente</label>
        <select id="cliente_id" name="cliente_id">
          <option value="">Todos</option>
          @foreach($clientes as $c)
            <option value="{{ $c->id }}" {{ (int) $filtros['cliente_id'] === $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div style="display:flex;gap:8px">
        <button type="submit" class="btn-outline"><i class="ri-filter-2-line"></i> Filtrar</button>
        <a href="{{ route('reportes.ventas') }}" class="btn-outline">Limpiar</a>
      </div>
    </div>
  </form>

  <p style="color:#64748b;font-size:13px;margin:0 0 10px">
    El total mostrado es <strong>bruto</strong> (cantidad × precio unitario), reconstruido desde el detalle de cada venta.
    El sistema no persiste el descuento aplicado al momento de la venta, así que no refleja el monto neto realmente cobrado
    en ventas con descuento.
  </p>

  <div style="margin:0 0 16px;font-weight:800">
    Total bruto {{ ($filtros['desde'] || $filtros['hasta'] || $filtros['user_id'] || $filtros['cliente_id']) ? 'del filtro aplicado' : 'de todas las ventas' }}: {{ number_format($total, 2) }}
  </div>

  @if($ventas->count())
    <div class="table-wrap">
      <table class="table table-soft">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Registró</th>
            <th>Estado</th>
            <th style="text-align:center">Unidades vendidas</th>
            <th style="text-align:right">Total (bruto)</th>
          </tr>
        </thead>
        <tbody>
        @foreach($ventas as $v)
          <tr>
            <td>{{ $v->fecha_venta->format('Y-m-d H:i') }}</td>
            <td>{{ $v->cliente->nombre ?? '—' }}</td>
            <td>{{ $v->user->name ?? '—' }}</td>
            <td>{{ ucfirst($v->estado) }}</td>
            <td style="text-align:center">{{ (int) $v->unidades_venta }}</td>
            <td style="text-align:right;font-weight:700">{{ number_format((float) $v->total_venta, 2) }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    <div style="margin-top:10px">
      {{ $ventas->links() }}
    </div>
  @else
    <div class="empty-box">
      No hay ventas que coincidan con estos filtros.
    </div>
  @endif
</section>
@endsection
