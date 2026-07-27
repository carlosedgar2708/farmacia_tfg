@extends('app')
@section('title','Compras')

@section('content')
<div class="page">
  <x-card>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h2>Compras</h2>
      <x-button variant="primary" icon="ri-add-circle-line" href="{{ route('compras.create') }}">Nueva compra</x-button>
    </div>

    @if(session('success'))
      <x-alert variant="success">{{ session('success') }}</x-alert>
    @endif

    <div class="tabla-box soft">
      <table class="table compact">
        <thead>
          <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Proveedor</th>
            <th>Registró</th>
            <th>Total items</th>
          </tr>
        </thead>
        <tbody>
          @forelse($compras as $c)
            <tr>
              <td>{{ $c->id }}</td>
              <td>{{ $c->fecha }}</td>
              <td>{{ $c->proveedor->nombre ?? '-' }}</td>
              <td>{{ $c->user->name ?? '-' }}</td>
              <td>{{ $c->detalles->sum('cantidad') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5">
                <x-empty-state message="No hay compras registradas." />
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div style="margin-top:10px">
      {{ $compras->links() }}
    </div>
  </x-card>
</div>
@endsection
