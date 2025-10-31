@extends('app')
@section('title','Ventas')

@section('content')
<div class="panel">
  <div class="toolbar">
    <h1>Ventas</h1>
    <a class="btn" href="{{ route('ventas.create') }}">+ Nueva venta</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <table class="table">
    <thead>
      <tr>
        <th>#</th>
        <th>Fecha</th>
        <th>Cliente</th>
        <th>Registró</th>
        <th>Estado</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($ventas as $v)
        <tr>
          <td>{{ $v->id }}</td>
          <td>{{ $v->fecha_venta?->format('Y-m-d H:i') }}</td>
          <td>{{ $v->cliente?->nombre }}</td>
          <td>{{ $v->user?->name }}</td>
          <td>{{ ucfirst($v->estado) }}</td>
          <td>Bs. {{ number_format($v->total,2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div style="margin-top:10px">{{ $ventas->links() }}</div>
</div>
@endsection
