@extends('app')

@section('title','Productos')

@section('content')
<section class="card">
  <div class="toolbar" style="margin-bottom:4px">
    <div>
      <h1 class="page-title" style="margin:0">Gestión de Productos</h1>
      <div style="color:#64748b;font-weight:600;margin-top:2px">Administra el catálogo base de productos.</div>
    </div>
    <div>
      <a href="#" class="btn" id="btn-open-create"><i class="ri-add-circle-line"></i> Nuevo producto</a>
    </div>
  </div>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  {{-- Buscador con mismo estilo y SUGERENCIAS --}}
  <form id="form-buscar" method="GET" action="{{ route('productos.index') }}" style="margin:0">
    <div class="search-wrap" style="margin-bottom:12px; position:relative">
      <i class="ri-search-line"></i>
      <input id="q" type="text" name="q" placeholder="Buscar por código, nombre o descripción…" value="{{ $q }}">
      <button type="submit" class="btn-outline" style="white-space:nowrap"><i class="ri-filter-2-line"></i> Buscar</button>
      <div id="sugg" class="sugg hidden"></div>
    </div>
  </form>

  @if($productos->count())
    <div class="table-wrap">
      <table class="table table-soft">
        <thead>
          <tr>
            <th>ID</th>
            <th>Código</th>
            <th>Nombre</th>
            <th>Inyectable</th>
            <th>Descripción</th>
            <th style="text-align:center">Stock total</th> {{-- centrado --}}
            <th style="width:320px">Acciones</th>
          </tr>
        </thead>
        <tbody>
        @foreach($productos as $p)
          <tr>
            <td>{{ $p->id }}</td>
            <td style="font-weight:700">{{ $p->codigo }}</td>
            <td>{{ $p->nombre }}</td>
            <td>
              @if($p->es_inyectable)
                <span class="chip chip-ok">Sí</span>
              @else
                <span class="chip chip-neutral">No</span>
              @endif
            </td>
            <td>{{ \Illuminate\Support\Str::limit($p->description, 80) ?? '—' }}</td>
            <td style="text-align:center;font-weight:800">{{ $p->stock_total ?? 0 }}</td>
            <td>
              <div class="actions">
                {{-- EDITAR --}}
                <a href="#"
                   class="action edit"
                   title="Editar"
                   data-id="{{ $p->id }}"
                   data-codigo="{{ $p->codigo }}"
                   data-nombre="{{ $p->nombre }}"
                   data-es_inyectable="{{ $p->es_inyectable ? 1 : 0 }}"
                   data-description="{{ $p->description }}">
                   <i class="ri-pencil-line"></i> Editar
                </a>

                {{-- STOCK (lotes) --}}
                @php
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
                   class="action view stock"
                   title="Editar stock por lotes"
                   data-id="{{ $p->id }}"
                   data-nombre="{{ $p->nombre }}"
                   data-action="{{ route('productos.lotes.bulk', $p) }}"
                   data-lotes='@json($lotesPayload)'>
                   <i class="ri-archive-stack-line"></i> Editar stock
                </a>

                {{-- ELIMINAR --}}
                <form method="POST" action="{{ route('productos.destroy', $p) }}" onsubmit="return confirm('¿Eliminar producto #{{ $p->id }}?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="action delete" title="Eliminar">
                    <i class="ri-delete-bin-6-line"></i> Eliminar
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    <div class="pagination" style="margin-top:14px">
      {{ $productos->onEachSide(1)->links() }}
    </div>
  @else
    <div class="empty">No hay productos registrados.</div>
  @endif
</section>

{{-- ================== Modal Crear/Editar producto ================== --}}
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

      <label style="display:flex;align-items:center;gap:8px;margin-top:8px">
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

{{-- ================== Modal STOCK por producto (lotes) ================== --}}
<div id="modal-stock" class="modal">
  <div class="modal-content" style="max-width:980px">
    <button class="close" id="btn-close-stock">&times;</button>
    <h3 class="modal-title" id="stock-title">Stock del producto</h3>

    <form id="stock-form" method="POST" action="#">
      @csrf
      <div style="display:flex;justify-content:flex-end;margin:8px 0">
        <button type="button" id="btn-add-lote" class="btn-outline"><i class="ri-add-line"></i> Añadir lote</button>
      </div>

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>ID Lote</th>
              <th>N° Lote</th>
              <th>Fecha venc.</th>
              <th>Costo unit.</th>
              <th style="width:130px">Stock</th>
            </tr>
          </thead>
          <tbody id="stock-rows"></tbody>
        </table>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-outline" id="btn-cancel-stock">Cancelar</button>
        <button type="submit" class="btn">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>
@php
  // Fuente para sugerencias: si el controlador envió $sugerencias, úsalo;
  // si no, toma los productos de la página actual.
  $suggData = isset($sugerencias)
    ? $sugerencias
    : $productos->map(function($p){
        return [
          'id'          => $p->id,
          'codigo'      => $p->codigo,
          'nombre'      => $p->nombre,
          'stock_total' => $p->stock_total,
        ];
      })->values();
@endphp

@push('scripts')
<script>
/* =========================================================
   1) Buscador con sugerencias (cliente) – sin CSS extra
   ========================================================= */
(function(){
  const $q    = document.getElementById('q');
  const $sugg = document.getElementById('sugg');
  const $form = document.getElementById('form-buscar');

  // Fuente para sugerencias (si el controlador no pasa otra, usamos la página actual)
    const SUGG = @json($suggData);

  const norm = s => (s||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();

  let items=[], idx=-1;

  function close(){ $sugg.classList.add('hidden'); $sugg.innerHTML=''; items=[]; idx=-1; }

  function render(list){
    if(!list.length){ close(); return; }
    items=list;
    $sugg.innerHTML = list.map((p,i)=>`
      <div class="sugg-item${i===idx?' active':''}" data-text="${p.codigo} ${p.nombre}">
        <div class="sugg-icon"><i class="ri-archive-2-line"></i></div>
        <div>
          <div class="sugg-title">${p.nombre}</div>
          <div class="sugg-sub">${p.codigo} &middot; Stock: ${p.stock_total ?? 0}</div>
        </div>
      </div>
    `).join('');
    $sugg.classList.remove('hidden');
  }

  const doSearch = ()=>{
    const q = norm($q.value.trim());
    if(!q){ close(); return; }
    const results = SUGG.filter(p=> norm(p.nombre+' '+p.codigo).includes(q)).slice(0,12);
    render(results);
  };

  $q.addEventListener('input', doSearch);

  $q.addEventListener('keydown', (e)=>{
    if($sugg.classList.contains('hidden')) return;
    const max = items.length-1;
    if(e.key==='ArrowDown'){ e.preventDefault(); idx=Math.min(max,idx+1); render(items); }
    else if(e.key==='ArrowUp'){ e.preventDefault(); idx=Math.max(0,idx-1); render(items); }
    else if(e.key==='Enter'){
      if(idx>=0){ e.preventDefault(); $q.value = items[idx].codigo+' '+items[idx].nombre; }
      close(); $form.submit();
    }else if(e.key==='Escape'){ close(); }
  });

  $sugg.addEventListener('click',(e)=>{
    const it = e.target.closest('.sugg-item'); if(!it) return;
    $q.value = it.dataset.text; close(); $form.submit();
  });

  document.addEventListener('click',(e)=>{ if(!e.target.closest('.search-wrap')) close(); });
})();

/* =========================================================
   2) Modales Crear/Editar y Stock (como antes)
   ========================================================= */
(function(){
  // ------- Modal Crear/Editar -------
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

      f.codigo.value = btn.dataset.codigo || '';
      f.nombre.value = btn.dataset.nombre || '';
      f.iny.checked  = (btn.dataset.es_inyectable === '1');
      f.description.value = btn.dataset.description || '';

      openModal();
    });
  });

  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);
  window.addEventListener('click', (e)=>{ if(e.target===modal) closeModal(); });

  // ------- Modal de STOCK (lotes) -------
  const stockModal = document.getElementById('modal-stock');
  const stockTitle = document.getElementById('stock-title');
  const stockRows  = document.getElementById('stock-rows');
  const stockForm  = document.getElementById('stock-form');
  const btnCloseStock  = document.getElementById('btn-close-stock');
  const btnCancelStock = document.getElementById('btn-cancel-stock');
  const btnAddLote     = document.getElementById('btn-add-lote');

  function openStock(){ stockModal.style.display='block'; }
  function closeStock(){ stockModal.style.display='none'; stockRows.innerHTML=''; stockForm.reset(); }

  function addEmptyRow(idx){
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
    const idx = stockRows.querySelectorAll('tr').length;
    if (stockRows.children.length === 1 && stockRows.children[0].querySelector('td[colspan]')) {
      stockRows.innerHTML = '';
    }
    addEmptyRow(idx);
  });

  document.querySelectorAll('.action.stock').forEach(btn=>{
    btn.addEventListener('click', (e)=>{
      e.preventDefault();
      const nombre = btn.dataset.nombre || 'Producto';
      const action = btn.dataset.action;
      const lotes  = JSON.parse(btn.dataset.lotes || '[]');

      stockTitle.textContent = 'Stock de ' + nombre;
      stockForm.action = action;
      stockRows.innerHTML = '';

      if (!lotes.length) {
        stockRows.innerHTML = `<tr><td colspan="5" style="text-align:center">No hay lotes.</td></tr>`;
      } else {
        lotes.forEach((l, idx)=>{
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${l.id}<input type="hidden" name="lotes[${idx}][id]" value="${l.id}"></td>
            <td>${l.nro_lote ?? ''}</td>
            <td><input type="date" name="lotes[${idx}][fecha_vencimiento]" value="${l.fecha_vencimiento ?? ''}"></td>
            <td><input type="number" step="0.01" min="0" name="lotes[${idx}][costo_unitario]" value="${l.costo_unitario ?? 0}"></td>
            <td><input type="number" min="0" name="lotes[${idx}][stock]" value="${l.stock ?? 0}"></td>
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
@endpush
@endsection
