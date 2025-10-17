@extends('app')

@section('title', 'Productos')

@section('content')
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<section class="panel">
  <h1 class="h-top" style="color:white;">Gestión de Productos</h1>
  <p style="color:white;">Administra el catálogo base de productos.</p>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  <div class="toolbar">
    <form method="GET" action="{{ route('productos.index') }}" class="search">
      <input type="text" name="q" placeholder="Buscar por código, nombre o descripción..." value="{{ $q }}">
      <button type="submit">Buscar</button>
    </form>

    <div>
      <a href="#" class="btn" id="btn-open-create">Nuevo producto</a>
    </div>
  </div>

  @if($productos->count())
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Código</th>
          <th>Nombre</th>
          <th>Inyectable</th>
          <th>Descripción</th>
          <th>Stock total</th>
          <th style="width:280px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($productos as $p)
        <tr>
          <td>{{ $p->id }}</td>
          <td>{{ $p->codigo }}</td>
          <td>{{ $p->nombre }}</td>
          <td>{{ $p->es_inyectable ? 'Sí' : 'No' }}</td>
          <td>{{ \Illuminate\Support\Str::limit($p->description, 60) ?? '—' }}</td>
          <td>{{ $p->stock_total ?? 0 }}</td>
          <td>
            <div class="actions">
              <a href="#"
                 class="action edit"
                 data-id="{{ $p->id }}"
                 data-codigo="{{ $p->codigo }}"
                 data-nombre="{{ $p->nombre }}"
                 data-es_inyectable="{{ $p->es_inyectable ? 1 : 0 }}"
                 data-description="{{ $p->description }}">Editar</a>

              @php
                // Preparamos el payload de lotes SIN arrow functions para evitar problemas de Blade/compilado
                $lotesPayload = $p->lotes->map(function($l) {
                  return [
                    'id' => $l->id,
                    'nro_lote' => $l->nro_lote,
                    'fecha_vencimiento' => optional($l->fecha_vencimiento)->format('Y-m-d'),
                    'costo_unitario' => $l->costo_unitario,
                    'stock' => $l->stock,
                  ];
                })->values();
              @endphp

              <a href="#"
                 class="action stock"
                 data-id="{{ $p->id }}"
                 data-nombre="{{ $p->nombre }}"
                 data-action="{{ route('productos.lotes.bulk', $p) }}"
                 data-lotes='@json($lotesPayload)'>
                 Stock
              </a>

              <form method="POST" action="{{ route('productos.destroy', $p) }}" onsubmit="return confirm('¿Eliminar producto #{{ $p->id }}?')">
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
      {{ $productos->onEachSide(1)->links() }}
    </div>
  @else
    <div class="empty">No hay productos registrados.</div>
  @endif
</section>

{{-- Modal crear/editar --}}
<div id="modal" class="modal">
  <div class="modal-content">
    <button class="close" id="btn-close-modal">&times;</button>
    <h3 class="modal-title" id="modal-title">Nuevo producto</h3>

    <form id="modal-form" method="POST" action="{{ route('productos.store') }}">
      @csrf
      <input type="hidden" name="_method" id="form-method" value="POST">

      <label>Código *</label>
      <input type="text" name="codigo" id="f-codigo" required maxlength="50">

      <label>Nombre *</label>
      <input type="text" name="nombre" id="f-nombre" required maxlength="255">

      <label style="display:flex;align-items:center;gap:8px;margin-top:8px;">
        <input type="checkbox" name="es_inyectable" id="f-iny"> Es inyectable
      </label>

      <label>Descripción</label>
      <textarea name="description" id="f-description" rows="3"></textarea>

      <div class="modal-actions">
        <button type="button" class="btn-outline" id="btn-cancel">Cancelar</button>
        <button type="submit" class="btn">Guardar</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal de STOCK por producto --}}
<div id="modal-stock" class="modal">
  <div class="modal-content" style="max-width:900px;">
    <button class="close" id="btn-close-stock">&times;</button>
    <h3 class="modal-title" id="stock-title">Stock del producto</h3>

    <form id="stock-form" method="POST" action="#">
      @csrf
      <div class="table-wrapper" style="overflow-x:auto;margin-top:10px;">
        <div style="display:flex;justify-content:flex-end;margin-bottom:8px;">
        <button type="button" id="btn-add-lote" class="btn-outline">+ Añadir lote</button>
        </div>

        <table class="table">
          <thead>
            <tr>
              <th>ID Lote</th>
              <th>N° Lote</th>
              <th>Fecha venc.</th>
              <th>Costo unit.</th>
              <th style="width:130px;">Stock</th>
            </tr>
          </thead>
          <tbody id="stock-rows">
            {{-- filas dinámicas desde JS --}}
          </tbody>
        </table>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-outline" id="btn-cancel-stock">Cancelar</button>
        <button type="submit" class="btn">Guardar cambios</button>
      </div>
      <p style="opacity:.8;margin-top:8px;">
        Nota: solo los usuarios con permiso <code>productos.stock</code> pueden guardar cambios.
      </p>
    </form>
  </div>
</div>

<script>
(function() {
  // ------- Modal Crear/Editar producto -------
  const modal = document.getElementById('modal');
  const openCreate = document.getElementById('btn-open-create');
  const closeBtn = document.getElementById('btn-close-modal');
  const cancelBtn = document.getElementById('btn-cancel');
  const form = document.getElementById('modal-form');
  const method = document.getElementById('form-method');
  const title = document.getElementById('modal-title');

  const f = {
    codigo: document.getElementById('f-codigo'),
    nombre: document.getElementById('f-nombre'),
    iny:    document.getElementById('f-iny'),
    description: document.getElementById('f-description'),
  };

  function openModal(){ modal.style.display='block'; }
  function closeModal(){ modal.style.display='none'; form.reset(); f.iny.checked=false; }

  openCreate?.addEventListener('click', (e)=>{
    e.preventDefault();
    title.textContent='Nuevo producto';
    form.action = "{{ route('productos.store') }}";
    method.value='POST';
    openModal();
  });

  document.querySelectorAll('.action.edit').forEach(btn=>{
    btn.addEventListener('click',(e)=>{
      e.preventDefault();
      const id = btn.dataset.id;
      title.textContent = 'Editar producto #'+id;
      form.action = "{{ url('productos') }}/"+id;
      method.value='PUT';

      f.codigo.value = btn.dataset.codigo ?? '';
      f.nombre.value = btn.dataset.nombre ?? '';
      f.iny.checked  = (btn.dataset.es_inyectable === '1');
      f.description.value = btn.dataset.description ?? '';

      openModal();
    });
  });

  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);
  window.addEventListener('click', (e)=>{ if(e.target===modal) closeModal(); });

  // ------- Modal de STOCK -------
  const stockModal = document.getElementById('modal-stock');
  const stockTitle = document.getElementById('stock-title');
  const stockRows  = document.getElementById('stock-rows');
  const stockForm  = document.getElementById('stock-form');
  const btnCloseStock  = document.getElementById('btn-close-stock');
  const btnCancelStock = document.getElementById('btn-cancel-stock');
  const btnAddLote = document.getElementById('btn-add-lote');

    function addEmptyRow(idx) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>nuevo<input type="hidden" name="lotes[${idx}][id]" value=""></td>
        <td><input type="text" name="lotes[${idx}][nro_lote]" required maxlength="100"></td>
        <td><input type="date" name="lotes[${idx}][fecha_vencimiento]"></td>
        <td><input type="number" step="0.01" min="0" name="lotes[${idx}][costo_unitario]" required></td>
        <td><input type="number" min="0" name="lotes[${idx}][stock]" required></td>
    `;
    stockRows.appendChild(tr);
    }

btnAddLote?.addEventListener('click', (e)=>{
  e.preventDefault();
  // índice siguiente
  const idx = stockRows.querySelectorAll('tr').length;
  // si solo hay la fila “No hay lotes.”, bórrala
  if (stockRows.children.length === 1 && stockRows.children[0].querySelector('td[colspan]')) {
    stockRows.innerHTML = '';
  }
  addEmptyRow(idx);
});


  function openStock(){ stockModal.style.display='block'; }
  function closeStock(){ stockModal.style.display='none'; stockRows.innerHTML=''; stockForm.reset(); }

  // click en botón "Stock"
  document.querySelectorAll('.action.stock').forEach(btn=>{
    btn.addEventListener('click', (e)=>{
      e.preventDefault();

      const nombre = btn.dataset.nombre;
      const action = btn.dataset.action;
      const lotes  = JSON.parse(btn.dataset.lotes || '[]');

      stockTitle.textContent = 'Stock de ' + (nombre || 'producto');
      stockForm.action = action;

      // construir filas
      stockRows.innerHTML = '';
      if (!lotes.length) {
        stockRows.innerHTML = `<tr><td colspan="5" style="text-align:center;">No hay lotes.</td></tr>`;
      } else {
        lotes.forEach((l, idx) => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${l.id}<input type="hidden" name="lotes[${idx}][id]" value="${l.id}"></td>
            <td>${l.nro_lote ?? ''}</td>
            <td>
              <input type="date" name="lotes[${idx}][fecha_vencimiento]" value="${l.fecha_vencimiento ?? ''}">
            </td>
            <td>
              <input type="number" step="0.01" min="0" name="lotes[${idx}][costo_unitario]" value="${l.costo_unitario ?? 0}">
            </td>
            <td>
              <input type="number" min="0" name="lotes[${idx}][stock]" value="${l.stock ?? 0}">
            </td>
          `;
          stockRows.appendChild(tr);
        });
      }

      openStock();
    });
  });

  btnCloseStock?.addEventListener('click', closeStock);
  btnCancelStock?.addEventListener('click', closeStock);
  window.addEventListener('click', (e)=>{ if(e.target===stockModal) closeStock(); });

})();
</script>
@endsection
