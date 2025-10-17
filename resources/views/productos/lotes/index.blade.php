@extends('app')
@section('title','Lotes')
@section('content')
  <section class="panel">
    <h1 class="h-top" style="color:white;">Stock de {{ $producto->nombre ?? $producto->codigo }}</h1>
    <p style="color:white;">Listado de lotes.</p>

    @if($lotes->count())
      <table class="table">
        <thead><tr>
          <th>ID</th><th>N° Lote</th><th>Vence</th><th>Costo</th><th>Stock</th>
        </tr></thead>
        <tbody>
          @foreach($lotes as $l)
          <tr>
            <td>{{ $l->id }}</td>
            <td>{{ $l->nro_lote }}</td>
            <td>{{ $l->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ number_format($l->costo_unitario,2) }}</td>
            <td>{{ $l->stock }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      {{ $lotes->links() }}
    @else
      <div class="empty">No hay lotes.</div>
    @endif
  </section>
@endsection
