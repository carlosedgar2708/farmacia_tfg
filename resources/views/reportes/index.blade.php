@extends('app')

@section('title', 'Reportes')

@section('content')
<x-card>
    <h1 class="page-title" style="margin:0">Reportes</h1>
    <div style="color:#64748b;font-weight:600;margin:2px 0 16px">Selecciona un reporte para consultar.</div>

    <div class="info-list">
        <x-button variant="secondary" icon="ri-alarm-warning-line" href="{{ route('reportes.vencimientos') }}">Lotes próximos a vencer</x-button>
        <x-button variant="secondary" icon="ri-money-dollar-circle-line" href="{{ route('reportes.stockValorizado') }}">Stock valorizado</x-button>
        <x-button variant="secondary" icon="ri-alert-line" href="{{ route('reportes.stockBajo') }}">Productos con stock bajo</x-button>
        <x-button variant="secondary" icon="ri-truck-line" href="{{ route('reportes.compras') }}">Compras por período</x-button>
        <x-button variant="secondary" icon="ri-shopping-bag-3-line" href="{{ route('reportes.ventas') }}">Ventas por período</x-button>
        <x-button variant="secondary" icon="ri-exchange-line" href="{{ route('reportes.movimientos') }}">Historial de movimientos de stock</x-button>
    </div>
</x-card>
@endsection
