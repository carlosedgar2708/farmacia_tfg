@extends('app')

@section('title', 'Reportes')

@section('content')
<section class="card">
  <div class="toolbar" style="margin-bottom:4px">
    <div>
      <h1 class="page-title" style="margin:0">Historial de movimientos de stock</h1>
      <div style="color:#64748b;font-weight:600;margin-top:2px">
        Sin filtros, se muestran todos los movimientos registrados.
      </div>
    </div>
  </div>

  <form method="GET" action="{{ route('reportes.movimientos') }}" class="card" style="margin:14px 0;padding:14px">
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
        <label for="tipo" style="display:block;font-weight:700;margin-bottom:4px">Tipo</label>
        <select id="tipo" name="tipo">
          <option value="">Todos</option>
          <option value="Entrada" {{ $filtros['tipo'] === 'Entrada' ? 'selected' : '' }}>Entrada</option>
          <option value="Salida" {{ $filtros['tipo'] === 'Salida' ? 'selected' : '' }}>Salida</option>
        </select>
      </div>

      <div>
        <label for="producto_id" style="display:block;font-weight:700;margin-bottom:4px">Producto</label>
        <select id="producto_id" name="producto_id">
          <option value="">Todos</option>
          @foreach($productos as $p)
            <option value="{{ $p->id }}" {{ (int) $filtros['producto_id'] === $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label for="lote_id" style="display:block;font-weight:700;margin-bottom:4px">Lote</label>
        <select id="lote_id" name="lote_id">
          <option value="">Todos</option>
          @foreach($lotes as $l)
            <option value="{{ $l->id }}" {{ (int) $filtros['lote_id'] === $l->id ? 'selected' : '' }}>{{ ($l->producto->nombre ?? '—') . ' - ' . $l->nro_lote }}</option>
          @endforeach
        </select>
      </div>

      <div style="display:flex;gap:8px">
        <button type="submit" class="btn-outline"><i class="ri-filter-2-line"></i> Filtrar</button>
        <a href="{{ route('reportes.movimientos') }}" class="btn-outline">Limpiar</a>
      </div>
    </div>
  </form>

  @if($movimientos->count())
    <div class="table-wrap">
      <table class="table table-soft">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Producto</th>
            <th>Lote</th>
            <th>Tipo</th>
            <th>Motivo</th>
            <th style="text-align:center">Cantidad</th>
            <th>Referencia</th>
          </tr>
        </thead>
        <tbody>
        @foreach($movimientos as $m)
          <tr>
            <td>{{ $m->fecha->format('Y-m-d H:i') }}</td>
            <td>{{ $m->lote->producto->nombre ?? '—' }}</td>
            <td>{{ $m->lote->nro_lote ?? '—' }}</td>
            <td>
              @if($m->tipo === 'Entrada')
                <span class="badge ok">ENTRADA</span>
              @else
                <span class="badge warn">SALIDA</span>
              @endif
            </td>
            <td>{{ $m->motivo }}</td>
            <td style="text-align:center">{{ (int) $m->cantidad }}</td>
            <td>{{ $m->referencia ?? '—' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    <div style="margin-top:10px">
      {{ $movimientos->links() }}
    </div>
  @else
    <div class="empty-box">
      No hay movimientos que coincidan con estos filtros.
    </div>
  @endif
</section>
@endsection
