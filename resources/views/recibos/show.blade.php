@extends('app')

@section('title', 'Recibo N° ' . $recibo->id)

@section('content')
<x-card title="Recibo N° {{ $recibo->id }}" icon="ri-receipt-line">
    <div class="info-list">
        <div><strong>Fecha:</strong> {{ $recibo->venta->fecha_venta?->format('Y-m-d H:i') }}</div>
        <div><strong>Cliente:</strong> {{ $recibo->venta->cliente?->nombre ?? '—' }}</div>
        <div><strong>Vendedor:</strong> {{ $recibo->venta->user?->name }}</div>
    </div>

    <div class="table-wrap" style="margin-top:16px">
        <table class="table table-soft">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:right">Cantidad</th>
                    <th style="text-align:right">Precio unitario</th>
                    <th style="text-align:right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recibo->venta->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->producto?->nombre ?? '—' }}</td>
                    <td class="ta-right">{{ $detalle->cantidad }}</td>
                    <td class="ta-right money">Bs. {{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td class="ta-right money">Bs. {{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="text-align:right;margin-top:12px;font-weight:700;font-size:18px">
        Total: <span class="money">Bs. {{ number_format($recibo->monto, 2) }}</span>
    </div>

    <div class="print-actions" style="margin-top:16px">
        <x-button type="button" variant="primary" icon="ri-printer-line" onclick="window.print()">Imprimir</x-button>
    </div>
</x-card>
@endsection

@push('styles')
<style>
/* Impresión del recibo (RF10): oculta todo lo que no sea el comprobante,
   sin cambiar colores ni tipografía — solo qué se ve al imprimir. */
@media print {
    .sidebar, .main-top, .flash, footer, .print-actions {
        display: none !important;
    }
    .main {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>
@endpush
