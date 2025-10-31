@extends('app')

@section('title', 'Ventas')

@section('content')
<div class="page ventas-page">
  <div class="card panel">
    <div class="toolbar">
      <h1 class="title">Ventas</h1>
      <a href="{{ route('ventas.create') }}" class="btn">
        <i class="ri-add-line"></i>
        <span>Nueva venta</span>
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-wrap">
      <table class="table table-soft">
        <thead>
          <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Registró</th>
            <th>Estado</th>
            <th class="ta-right">Total</th>
          </tr>
        </thead>
        <tbody>
          @forelse($ventas as $v)
            @php
              $estado = strtolower($v->estado ?? '');
              $map = ['pagada' => 'ok', 'pendiente' => 'warn', 'anulada' => 'bad'];
              $clase = $map[$estado] ?? 'neutral';
            @endphp
            <tr>
              <td>{{ $v->id }}</td>
              <td>{{ $v->fecha_venta?->format('Y-m-d H:i') }}</td>
              <td>{{ $v->cliente?->nombre ?? '—' }}</td>
              <td>{{ $v->user?->name ?? '—' }}</td>
              <td>
                <span class="chip chip-{{ $clase }}">
                  {{ ucfirst($v->estado) }}
                </span>
              </td>
              <td class="ta-right money">Bs. {{ number_format($v->total, 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="ta-center" style="padding:18px; color:#64748b;">
                No hay ventas registradas.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="pagination mt-12">
      {{ $ventas->links() }}
    </div>
  </div>
</div>
@endsection
