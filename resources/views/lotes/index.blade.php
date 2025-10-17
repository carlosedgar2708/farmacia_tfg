@extends('app')

@section('title', 'Stock de ' . $producto->nombre)

@section('content')
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<section class="panel">
  <h1 class="h-top" style="color:white;">Stock de {{ $producto->nombre }}</h1>
  <p style="color:white;">Gestiona los lotes del producto.</p>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  <div class="toolbar">
    <form method="GET" action="{{ route('productos.lotes.index', $producto) }}" class="search">
      <input type="text" name="q" placeholder="Buscar por N° de lote..." value="{{ $q }}">
      <button type="submit">Buscar</button>
    </form>

    <div>
      <a href="#" class="btn" id="btn-open-create">Nuevo lote</a>
      <a href="{{ route('productos.index') }}" class="btn-outline">Volver a productos</a>
    </div>
  </div>

  @if($lotes->count())
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>N° Lote</th>
          <th>Fecha venc.</th>
          <th>Costo unitario</th>
          <th>Stock</th>
          <th style="width:200px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($lotes as $l)
        <tr>
          <td>{{ $l->id }}</td>
          <td>{{ $l->nro_lote }}</td>
          <td>{{ $l->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
          <td>₡{{ number_format($l->costo_unitario, 2) }}</td>
          <td>{{ $l->stock }}</td>
          <td>
            <div class="actions">
              <a href="#"
                 class="action edit"
                 data-id="{{ $l->id }}"
                 data-nro_lote="{{ $l->nro_lote }}"
                 data-fecha_vencimiento="{{ optional($l->fecha_vencimiento)->format('Y-m-d') }}"
                 data-costo_unitario="{{ $l->costo_unitario }}"
                 data-stock="{{ $l->stock }}">Editar</a>

              <form method="POST" action="{{ route('productos.lotes.destroy', [$producto, $l]) }}" onsubmit="return confirm('¿Eliminar lote #{{ $l->id }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="action delete">Eliminar</button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="pagination" style="margin-top:15px;">
      {{ $lotes->onEachSide(1)->links() }}
    </div>
  @else
    <div class="empty">No hay lotes registrados para este producto.</div>
  @endif
</section>

{{-- Modal crear/editar --}}
<div id="modal" class="modal">
  <div class="modal-content">
    <button class="close" id="btn-close-modal">&times;</button>
    <h3 class="modal-title" id="modal-title">Nuevo lote</h3>

    <form id="modal-form" method="POST" action="{{ route('productos.lotes.store', $producto) }}">
      @csrf
      <input type="hidden" name="_method" id="form-method" value="POST">

      <label>N° de Lote *</label>
      <input type="text" name="nro_lote" id="f-nro" required maxlength="100">

      <label>Fecha de vencimiento</label>
      <input type="date" name="fecha_vencimiento" id="f-venc">

      <label>Costo unitario *</label>
      <input type="number" step="0.01" min="0" name="costo_unitario" id="f-costo" required>

      <label>Stock *</label>
      <input type="number" min="0" name="stock" id="f-stock" required>

      <div class="modal-actions">
        <button type="button" class="btn-outline" id="btn-cancel">Cancelar</button>
        <button type="submit" class="btn">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
(function() {
  const modal = document.getElementById('modal');
  const openCreate = document.getElementById('btn-open-create');
  const closeBtn = document.getElementById('btn-close-modal');
  const cancelBtn = document.getElementById('btn-cancel');
  const form = document.getElementById('modal-form');
  const method = document.getElementById('form-method');
  const title = document.getElementById('modal-title');
  const f_nro   = document.getElementById('f-nro');
  const f_venc  = document.getElementById('f-venc');
  const f_costo = document.getElementById('f-costo');
  const f_stock = document.getElementById('f-stock');

  function openModal(){ modal.style.display='block'; }
  function closeModal(){ modal.style.display='none'; form.reset(); }

  openCreate?.addEventListener('click',(e)=>{
    e.preventDefault();
    title.textContent='Nuevo lote';
    form.action = "{{ route('productos.lotes.store', $producto) }}";
    method.value='POST';
    openModal();
  });

  document.querySelectorAll('.action.edit').forEach(btn=>{
    btn.addEventListener('click',(e)=>{
      e.preventDefault();
      const id = btn.dataset.id;
      title.textContent = 'Editar lote #'+id;
      form.action = "{{ route('productos.lotes.index', $producto) }}/"+id;
      method.value='PUT';

      f_nro.value   = btn.dataset.nro_lote ?? '';
      f_venc.value  = btn.dataset.fecha_vencimiento ?? '';
      f_costo.value = btn.dataset.costo_unitario ?? '';
      f_stock.value = btn.dataset.stock ?? 0;

      openModal();
    });
  });

  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);
  window.addEventListener('click', (e)=>{ if(e.target===modal) closeModal(); });
})();
</script>
@endsection
