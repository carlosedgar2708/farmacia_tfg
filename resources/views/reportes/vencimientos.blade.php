@extends('app')

@section('title', 'Reportes')

@section('content')
<section class="card">
  <div class="toolbar" style="margin-bottom:4px">
    <div>
      <h1 class="page-title" style="margin:0">Lotes próximos a vencer</h1>
      <div style="color:#64748b;font-weight:600;margin-top:2px">
        Lotes con stock vencidos o próximos a vencer. Por defecto: vencidos + los que vencen dentro de {{ $diasAlerta }} días.
      </div>
    </div>
  </div>

  <form method="GET" action="{{ route('reportes.vencimientos') }}" class="card" style="margin:14px 0;padding:14px">
    <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:end">
      <div>
        <label for="estado" style="display:block;font-weight:700;margin-bottom:4px">Estado</label>
        <select id="estado" name="estado">
          <option value="todos"    {{ $filtros['estado'] === 'todos'    ? 'selected' : '' }}>Todos</option>
          <option value="vencidos" {{ $filtros['estado'] === 'vencidos' ? 'selected' : '' }}>Solo vencidos</option>
          <option value="proximos" {{ $filtros['estado'] === 'proximos' ? 'selected' : '' }}>Solo próximos a vencer</option>
        </select>
      </div>

      <div>
        <label for="desde" style="display:block;font-weight:700;margin-bottom:4px">Vence desde</label>
        <input type="date" id="desde" name="desde" value="{{ $filtros['desde'] }}">
      </div>

      <div>
        <label for="hasta" style="display:block;font-weight:700;margin-bottom:4px">Vence hasta</label>
        <input type="date" id="hasta" name="hasta" value="{{ $filtros['hasta'] }}">
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

      <div style="display:flex;gap:8px">
        <button type="submit" class="btn-outline"><i class="ri-filter-2-line"></i> Filtrar</button>
        <a href="{{ route('reportes.vencimientos') }}" class="btn-outline">Limpiar</a>
      </div>
    </div>
    <p style="color:#64748b;font-size:13px;margin:10px 0 0">
      "Vence desde/hasta" solo afecta a los lotes próximos a vencer. Los lotes ya vencidos con stock se muestran siempre, sin importar hace cuánto vencieron.
    </p>
  </form>

  @if($lotes->count())
    <div class="table-wrap">
      <table class="table table-soft">
        <thead>
          <tr>
            <th>Producto</th>
            <th>N.º de lote</th>
            <th>Fecha de vencimiento</th>
            <th style="text-align:center">Stock</th>
            <th style="text-align:right">Costo unitario</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
        @foreach($lotes as $lote)
          <tr>
            <td>{{ $lote->producto->nombre ?? '—' }}</td>
            <td>{{ $lote->nro_lote }}</td>
            <td>{{ optional($lote->fecha_vencimiento)->format('Y-m-d') }}</td>
            <td style="text-align:center">{{ $lote->stock }}</td>
            <td style="text-align:right">{{ number_format((float) $lote->costo_unitario, 2) }}</td>
            <td>
              @if($lote->estado_vencimiento === 'vencido')
                <span class="badge danger">VENCIDO</span>
              @else
                <span class="badge warn">PRÓXIMO A VENCER</span>
              @endif
            </td>
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
      No hay lotes que coincidan con estos filtros.
    </div>
  @endif
</section>

<style>
.empty-box{
  background:#f4f6f8;
  padding:20px;
  border-radius:8px;
  text-align:center;
  color:#999;
}
</style>
@endsection
