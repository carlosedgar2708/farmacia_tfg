@extends('app')

@section('title', 'Recibo N° ' . $recibo->id)

@section('content')
{{-- Vista normal en pantalla. Se oculta al imprimir (.recibo-normal, ver @media print más abajo);
     la impresión usa el bloque .ticket-print con formato de ticket térmico. --}}
<x-card class="recibo-normal" title="Recibo N° {{ $recibo->id }}" icon="ri-receipt-line">
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

{{-- Ticket térmico: oculto en pantalla (.ticket-print { display:none }), visible solo al
     imprimir. Mismos datos que la vista normal de arriba, sin información nueva. --}}
<div class="ticket-print">
    <div class="t-center t-title">Farmacia Katy</div>
    <div class="t-center">Recibo N° {{ $recibo->id }}</div>
    <div class="t-center">{{ $recibo->venta->fecha_venta?->format('Y-m-d H:i') }}</div>
    <div class="t-sep"></div>
    <div>Cliente: {{ $recibo->venta->cliente?->nombre ?? '—' }}</div>
    <div>Vendedor: {{ $recibo->venta->user?->name }}</div>
    <div class="t-sep"></div>
    @foreach ($recibo->venta->detalles as $detalle)
    <div class="t-item">
        <div class="t-item-name">{{ $detalle->producto?->nombre ?? '—' }}</div>
        <div class="t-row">
            <span>{{ $detalle->cantidad }} x Bs {{ number_format($detalle->precio_unitario, 2) }}</span>
            <span>Bs {{ number_format($detalle->subtotal, 2) }}</span>
        </div>
    </div>
    @endforeach
    <div class="t-sep"></div>
    <div class="t-row t-total">
        <span>TOTAL</span>
        <span>Bs {{ number_format($recibo->monto, 2) }}</span>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Ancho de contenido del ticket térmico. Rollo habitual en farmacias: 80mm (default).
   Para cambiar a una impresora de 58mm: usar --ticket-width: 48mm y cambiar también
   el valor literal de @page más abajo a "58mm auto" (var() dentro de @page no tiene
   soporte confiable entre navegadores/impresoras, así que ambos valores se mantienen
   sincronizados a mano). */
:root {
    --ticket-width: 72mm; /* 58mm -> usar 48mm aquí */
}

.ticket-print { display: none; }

/* Impresión del recibo (RF10 + mejora de impresión térmica): oculta todo lo que no
   sea el ticket, sin cambiar colores ni tipografía de la vista normal en pantalla. */
@media print {
    .sidebar, .main-top, .flash, footer, .print-actions, .recibo-normal {
        display: none !important;
    }
    .main {
        margin: 0 !important;
        padding: 0 !important;
    }

    @page {
        size: 80mm auto; /* 58mm -> usar "58mm auto", junto con --ticket-width: 48mm arriba */
        margin: 0;
    }
    body { margin: 0; }

    .ticket-print {
        /* max-width + margin:0 auto: si el navegador o la impresora ignoran @page
           (formato Letter por defecto), el ticket igual queda angosto y centrado
           en vez de estirarse a toda la hoja. */
        display: block;
        width: var(--ticket-width);
        max-width: 100%;
        margin: 0 auto;
        padding: 2mm;
        font-family: 'Courier New', Courier, monospace;
        font-size: 11px;
        line-height: 1.4;
        color: #000;
    }
    .ticket-print .t-center { text-align: center; }
    .ticket-print .t-title { font-size: 13px; font-weight: 700; }
    .ticket-print .t-sep { border-top: 1px dashed #000; margin: 4px 0; }
    .ticket-print .t-row { display: flex; justify-content: space-between; gap: 6px; }
    .ticket-print .t-item { margin-bottom: 2px; }
    .ticket-print .t-item-name { font-weight: 700; }
    .ticket-print .t-total { font-weight: 700; font-size: 13px; margin-top: 4px; }
}
</style>
@endpush
