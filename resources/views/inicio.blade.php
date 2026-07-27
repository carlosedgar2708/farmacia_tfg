@extends('app')

@section('title', 'Inicio')

@section('content')

    {{-- ======= 1. QUÉ PASÓ HOY ======= --}}
    <x-card title="Qué pasó hoy" icon="ri-calendar-check-line">
        <div class="info-list">
            <div class="info-item">
                <div class="left">
                    <div class="title">Ventas de hoy</div>
                    <div class="sub">{{ $ventasHoy['cantidad'] }} {{ Str::plural('venta', $ventasHoy['cantidad']) }}</div>
                </div>
                <span class="money">Bs. {{ number_format($ventasHoy['monto'], 2) }}</span>
            </div>
            <div class="info-item">
                <div class="left">
                    <div class="title">Compras de hoy</div>
                    <div class="sub">{{ $comprasHoy['cantidad'] }} {{ Str::plural('compra', $comprasHoy['cantidad']) }}</div>
                </div>
                <span class="money">Bs. {{ number_format($comprasHoy['monto'], 2) }}</span>
            </div>
        </div>
    </x-card>

    {{-- ======= 2. ALERTAS CRÍTICAS ======= --}}
    <x-card title="Alertas críticas" icon="ri-alarm-warning-line">

        <div class="sb-section">Vencidos</div>
        @if($vencidos->isEmpty())
            <x-empty-state compact icon="ri-checkbox-circle-line" message="No hay productos vencidos." />
        @else
            <div class="info-list">
                @foreach($vencidos as $p)
                    <div class="info-item">
                        <div class="left">
                            <div class="title">{{ $p->nombre }}</div>
                            <div class="sub">
                                @if($p->lote_relevante)
                                    Lote {{ $p->lote_relevante->nro_lote }} · Venció {{ $p->lote_relevante->fecha_vencimiento }}
                                @endif
                            </div>
                        </div>
                        <x-badge variant="danger">VENCIDO</x-badge>
                    </div>
                @endforeach
            </div>
        @endif
        <x-button variant="ghost" href="{{ route('reportes.vencimientos') }}">Ver reporte completo →</x-button>

        <div class="two mt-12">
            <div>
                <div class="sb-section">Próximos a vencer</div>
                @if($proximosAVencer->isEmpty())
                    <x-empty-state compact icon="ri-checkbox-circle-line" message="No hay productos próximos a vencer." />
                @else
                    <div class="info-list">
                        @foreach($proximosAVencer as $p)
                            <div class="info-item">
                                <div class="left">
                                    <div class="title">{{ $p->nombre }}</div>
                                    <div class="sub">
                                        @if($p->lote_relevante)
                                            Vence {{ $p->lote_relevante->fecha_vencimiento }}
                                        @endif
                                    </div>
                                </div>
                                <x-badge variant="warn">PRÓXIMO</x-badge>
                            </div>
                        @endforeach
                    </div>
                @endif
                <x-button variant="ghost" href="{{ route('reportes.vencimientos') }}">Ver reporte completo →</x-button>
            </div>

            <div>
                <div class="sb-section">Stock bajo</div>
                @if($stockBajo->isEmpty())
                    <x-empty-state compact icon="ri-checkbox-circle-line" message="No hay productos con stock bajo." />
                @else
                    <div class="info-list">
                        @foreach($stockBajo as $p)
                            <div class="info-item">
                                <div class="left">
                                    <div class="title">{{ $p->nombre }}</div>
                                    <div class="sub">{{ (int) $p->stock_total }} unidades</div>
                                </div>
                                <x-badge :variant="$p->estado_stock === 'sin_stock' ? 'danger' : 'warn'">
                                    {{ $p->estado_stock === 'sin_stock' ? 'SIN STOCK' : 'STOCK BAJO' }}
                                </x-badge>
                            </div>
                        @endforeach
                    </div>
                @endif
                <x-button variant="ghost" href="{{ route('reportes.stockBajo') }}">Ver reporte completo →</x-button>
            </div>
        </div>
    </x-card>

    {{-- ======= 3. ÚLTIMOS MOVIMIENTOS ======= --}}
    <x-card title="Últimos movimientos" icon="ri-exchange-line">
        @if($ultimosMovimientos->isEmpty())
            <x-empty-state message="Todavía no hay movimientos registrados." />
        @else
            <div class="info-list">
                @foreach($ultimosMovimientos as $m)
                    <div class="info-item">
                        <div class="left">
                            <div class="title">{{ $m->lote->producto->nombre ?? '—' }}</div>
                            <div class="sub">{{ $m->fecha->format('d/m H:i') }} · Lote {{ $m->lote->nro_lote ?? '—' }} · {{ $m->motivo }}</div>
                        </div>
                        <x-badge :variant="$m->tipo === 'Entrada' ? 'ok' : 'warn'">{{ strtoupper($m->tipo) }}</x-badge>
                    </div>
                @endforeach
            </div>
        @endif
        <x-button variant="ghost" href="{{ route('reportes.movimientos') }}">Ver historial completo →</x-button>
    </x-card>

    {{-- ======= 4. ACCESOS RÁPIDOS ======= --}}
    <x-card title="Accesos rápidos" icon="ri-flashlight-line">
        <div class="actions">
            <x-button variant="primary" icon="ri-shopping-bag-3-line" href="{{ route('ventas.create') }}">Nueva venta</x-button>
            <x-button variant="secondary" icon="ri-truck-line" href="{{ route('compras.create') }}">Registrar compra</x-button>
            <x-button variant="secondary" icon="ri-arrow-go-back-line" :disabled="true">Registrar devolución — Próximamente</x-button>
            <x-button variant="secondary" icon="ri-capsule-line" href="{{ route('productos.index') }}">Productos</x-button>
        </div>
    </x-card>

@endsection
