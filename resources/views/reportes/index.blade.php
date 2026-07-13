@extends('app')

@section('title', 'Reportes')

@section('content')
<section class="card">
  <h1 class="page-title" style="margin:0">Reportes</h1>
  <div style="color:#64748b;font-weight:600;margin:2px 0 16px">Selecciona un reporte para consultar.</div>

  <div style="display:flex;flex-direction:column;gap:10px;max-width:420px">
    <a href="{{ route('reportes.vencimientos') }}" class="btn-outline" style="justify-content:flex-start">
      <i class="ri-alarm-warning-line"></i> Lotes próximos a vencer
    </a>
    <a href="{{ route('reportes.stockValorizado') }}" class="btn-outline" style="justify-content:flex-start">
      <i class="ri-money-dollar-circle-line"></i> Stock valorizado
    </a>
    <a href="{{ route('reportes.stockBajo') }}" class="btn-outline" style="justify-content:flex-start">
      <i class="ri-alert-line"></i> Productos con stock bajo
    </a>
    <a href="{{ route('reportes.compras') }}" class="btn-outline" style="justify-content:flex-start">
      <i class="ri-truck-line"></i> Compras por período
    </a>
    <a href="{{ route('reportes.ventas') }}" class="btn-outline" style="justify-content:flex-start">
      <i class="ri-shopping-bag-3-line"></i> Ventas por período
    </a>
    <a href="{{ route('reportes.movimientos') }}" class="btn-outline" style="justify-content:flex-start">
      <i class="ri-exchange-line"></i> Historial de movimientos de stock
    </a>
  </div>
</section>
@endsection
