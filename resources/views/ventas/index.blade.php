@extends('app')

@section('title', 'Ventas')

@section('content')
<div class="page ventas-page">
  <x-card>
    <div class="toolbar">
      <h1 class="title">Ventas</h1>
      <x-button variant="primary" icon="ri-add-line" href="{{ route('ventas.create') }}">Nueva venta</x-button>
    </div>

    @if(session('recibo_id'))
      <div class="mt-12">
        <x-button icon="ri-receipt-line" href="{{ route('recibos.show', session('recibo_id')) }}">Ver recibo</x-button>
        <x-button variant="secondary" icon="ri-add-line" href="{{ route('ventas.create') }}">Nueva venta</x-button>
      </div>
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
              <td colspan="6">
                <x-empty-state message="No hay ventas registradas." />
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="pagination mt-12">
      {{ $ventas->links() }}
    </div>
  </x-card>
</div>
@endsection
