@extends('app')

@section('title', 'Inicio')

@section('content')
<section class="panel" style="background:none; box-shadow:none;padding:0;">

    <h1 class="h-top">Inicio</h1>
    <p>Resumen general del sistema.</p>

    {{-- ====== TARJETAS SUPERIORES ====== --}}
    <div class="dashboard-cards">
        <div class="dash-card">
            <span class="label">USUARIOS</span>
            <span class="value">{{ $stats['usuarios'] }}</span>
        </div>

        <div class="dash-card">
            <span class="label">ROLES</span>
            <span class="value">{{ $stats['roles'] }}</span>
        </div>

        <div class="dash-card">
            <span class="label">PROVEEDORES</span>
            <span class="value">{{ $stats['proveedors'] }}</span>
        </div>

        <div class="dash-card">
            <span class="label">PRODUCTOS</span>
            <span class="value">{{ $stats['productos'] }}</span>
        </div>

        <div class="dash-card">
            <span class="label">VENTAS HOY</span>
            <span class="value">{{ $stats['ventasHoy'] }}</span>
        </div>
    </div>

    {{-- ====== CONTENIDO PRINCIPAL ====== --}}
    <div class="dashboard-grid">

        {{-- ======= 1. PRODUCTOS CON MENOS STOCK ======= --}}
        <div class="info-box">
        <h3>📉 Productos con menos stock</h3>
        <p class="small-note">Los que estén con <b>menos de 30</b> deben mostrarse en rojo.</p>

        @if(($productosMenosStock ?? collect())->isEmpty())
            <div class="empty-box">Sin datos aún.</div>
        @else
            <div class="info-list">
            @foreach($productosMenosStock as $p)
                @php $low = (int)$p->stock_total < 30; @endphp
                <div class="info-item">
                <div class="left">
                    <div class="title {{ $low ? 'text-danger' : '' }}">
                    {{ $p->nombre }}
                    </div>
                    <div class="sub">Stock total</div>
                </div>
                <span class="badge {{ $low ? 'danger' : 'ok' }}">
                    {{ $p->stock_total }}
                </span>
                </div>
            @endforeach
            </div>
        @endif

        <a href="{{ route('productos.index') }}" class="btn add mt-12">Ver más →</a>
        </div>



        {{-- ======= 2. GRAFICA (A futuro) ======= --}}
        <div class="info-box big">
            <h3>📊 Rendimiento del personal</h3>
            <div class="chart-placeholder">Aquí va tu gráfico</div>
        </div>


        {{-- ======= 3. PRODUCTOS MÁS VENDIDOS ======= --}}
        <div class="info-box">
        <h3>🔥 Productos más vendidos</h3>
        <p class="small-note">Top del mes actual.</p>

        @if(($productosMasVendidos ?? collect())->isEmpty())
            <div class="empty-box">Sin datos aún.</div>
        @else
            <div class="info-list">
            @foreach($productosMasVendidos as $p)
                <div class="info-item">
                <div class="left">
                    <div class="title">{{ $p->nombre }}</div>
                    <div class="sub">Unidades vendidas</div>
                </div>
                <span class="badge">
                    {{ $p->cantidad_vendida }} u
                </span>
                </div>
            @endforeach
            </div>
        @endif

        <a href="{{ route('ventas.index') }}" class="btn add mt-12">Ver más →</a>
        </div>


        {{-- ======= 4. PRÓXIMOS A VENCER ======= --}}
        <div class="info-box">
        <h3>⏳ Próximos a vencer</h3>
        <p class="small-note">Los que estén en 4 meses o menos deben estar en rojo.</p>

        @if(($proximosVencer ?? collect())->isEmpty())
            <div class="empty-box">Sin datos aún.</div>
        @else
            <div class="info-list">
            @foreach($proximosVencer as $l)
                @php $soon = (bool)$l->vence_pronto; @endphp
                <div class="info-item">
                <div class="left">
                    <div class="title {{ $soon ? 'text-danger' : '' }}">
                    {{ $l->producto->nombre ?? 'Producto' }}
                    </div>
                    <div class="sub">
                    Lote {{ $l->nro_lote }} · Vence {{ $l->fecha_vencimiento }}
                    </div>
                </div>
                <span class="badge {{ $soon ? 'danger' : 'warn' }}">
                    {{ $soon ? 'Vence pronto' : 'OK' }}
                </span>
                </div>
            @endforeach
            </div>
        @endif

        <a href="{{ route('lotes.index') }}" class="btn add mt-12">Ver más →</a>
        </div>


    </div>

</section>


{{-- ====== ESTILOS ESPECIALES PARA ESTE DASHBOARD ====== --}}
<style>
.dashboard-cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
    gap:15px;
    margin-bottom:20px;
}
.dash-card{
    background:white;
    border-radius:12px;
    padding:15px;
    box-shadow:0 2px 4px #0001;
}
.dash-card .label{ font-size:13px; color:#888; }
.dash-card .value{
    display:block; font-size:28px; font-weight:700; margin-top:5px;
}

.dashboard-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:22px;
}

/* Box general */
.info-box{
    background:white;
    border-radius:15px;
    padding:20px;
    box-shadow:0 4px 10px #0001;
}
.info-box.big{ min-height:260px; }
.small-note{
    font-size:13px;
    margin-top:-5px;
    color:#666;
}
.chart-placeholder{
    height:200px;
    background:#f2f6f9;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#999;
    font-size:14px;
    margin-top:10px;
}

/* Listas */
.info-list{
    list-style:none;
    padding:0;
    margin:10px 0;
}
.info-list li{
    display:flex;
    justify-content:space-between;
    padding:8px 0;
    border-bottom:1px solid #eee;
}
.item-name{
    font-weight:600;
}
.sub{
    font-size:12px;
    color:#888;
}

/* Badges */
.badge{
    padding:3px 7px;
    border-radius:6px;
    font-size:13px;
}
.badge.danger{
    background:#ffe2e2;
    color:#b70000;
}
.badge.normal{
    background:#e4f3ff;
    color:#0062a3;
}

/* Botón */
.btn.more{
    margin-top:10px;
    padding:6px 12px;
    background:#ff8c7b;
    color:white;
    border-radius:8px;
    font-size:14px;
    display:inline-block;
}
.empty-box{
    background:#f4f6f8;
    padding:12px;
    border-radius:8px;
    text-align:center;
    color:#999;
}
</style>

@endsection
