@extends('app')
@section('title','Compras')

@section('content')
<div class="page">
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h2>Compras</h2>
      <a href="{{ route('compras.create') }}" class="btn primary">
        + Nueva compra
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
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
              <td colspan="5" style="text-align:center;color:#888">No hay compras registradas</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div style="margin-top:10px">
      {{ $compras->links() }}
    </div>
  </div>
</div>
@endsection
