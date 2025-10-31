@extends('app')

@section('title', 'Clientes')

@section('content')
{{-- Usa tu style.css global que ya trae .search-wrap, .sugg, .actions, .action, etc. --}}
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

@php
  // Datos para sugerencias del buscador (si el controlador no envió una lista aparte, usamos la página actual)
  $suggData = $clientes->map(function($c){
    return [
      'id'        => $c->id,
      'nombre'    => $c->nombre ?? '',
      'documento' => $c->documento ?? '',
      'telefono'  => $c->telefono ?? '',
    ];
  })->values();
@endphp

<section class="panel">
  <h1 class="h-top">Lista de Clientes</h1>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @elseif(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Barra superior: buscador + nuevo cliente --}}
  <div class="toolbar" style="gap:14px;align-items:flex-start">
    {{-- Buscador con sugerencias (se mantiene formulario GET para enter/submit) --}}
    <form id="form-buscar" method="GET" action="{{ route('clientes.index') }}" style="flex:1;">
      <div class="search-wrap xl" id="search-clients">
        <i class="ri-search-line"></i>
        <input id="q" name="q" type="text"
               placeholder="Buscar por nombre, documento o teléfono…"
               value="{{ $q }}">
        <div id="sugg" class="sugg hidden"></div>
      </div>
    </form>

    <a href="#" class="btn" id="btn-open-create">Nuevo cliente</a>
  </div>

  {{-- Tabla --}}
  @if($clientes->count())
    <table class="table">
      <thead>
        <tr>
          <th style="width:80px">ID</th>
          <th>Nombre</th>
          <th>Documento</th>
          <th>Teléfono</th>
          <th style="width:240px;">Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody-clientes">
        @foreach($clientes as $c)
        <tr data-id="{{ $c->id }}"
            data-nombre="{{ strtolower(\Illuminate\Support\Str::ascii($c->nombre ?? '')) }}"
            data-documento="{{ strtolower(\Illuminate\Support\Str::ascii($c->documento ?? '')) }}"
            data-telefono="{{ strtolower(\Illuminate\Support\Str::ascii($c->telefono ?? '')) }}">
          <td class="ta-center">{{ $c->id }}</td>
          <td>{{ $c->nombre ?? '—' }}</td>
          <td>{{ $c->documento ?? '—' }}</td>
          <td>{{ $c->telefono ?? '—' }}</td>
          <td>
            <div class="actions">
              <a href="#"
                 class="action edit"
                 data-id="{{ $c->id }}"
                 data-nombre="{{ $c->nombre }}"
                 data-documento="{{ $c->documento }}"
                 data-telefono="{{ $c->telefono }}">
                 <i class="ri-edit-2-line"></i> Editar
              </a>

              <form method="POST" action="{{ route('clientes.destroy', $c) }}" onsubmit="return confirm('¿Eliminar cliente #{{ $c->id }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="action delete">
                  <i class="ri-delete-bin-line"></i> Eliminar
                </button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="pagination" style="margin-top:15px;">
      {{ $clientes->onEachSide(1)->links() }}
    </div>
  @else
    <div class="empty">No hay clientes registrados.</div>
  @endif
</section>

{{-- Modal crear/editar --}}
<div id="modal" class="modal">
  <div class="modal-content">
    <button class="close" id="btn-close-modal">&times;</button>
    <h3 class="modal-title" id="modal-title">Nuevo cliente</h3>

    <form id="modal-form" method="POST" action="{{ route('clientes.store') }}">
      @csrf
      <input type="hidden" name="_method" id="form-method" value="POST">

      <label>Nombre *</label>
      <input type="text" name="nombre" id="f-nombre" required maxlength="255">

      <label>Documento</label>
      <input type="text" name="documento" id="f-documento" maxlength="255">

      <label>Teléfono</label>
      <input type="text" name="telefono" id="f-telefono" maxlength="255">

      <div class="modal-actions">
        <button type="button" class="btn-outline" id="btn-cancel">Cancelar</button>
        <button type="submit" class="btn" id="btn-submit">Guardar</button>
      </div>
    </form>
  </div>
</div>

{{-- JS: modal + buscador con sugerencias en vivo --}}
<script>
(function(){
  /* ====== Modal ====== */
  const modal = document.getElementById('modal');
  const openCreate = document.getElementById('btn-open-create');
  const closeBtn = document.getElementById('btn-close-modal');
  const cancelBtn = document.getElementById('btn-cancel');
  const form = document.getElementById('modal-form');
  const method = document.getElementById('form-method');
  const title = document.getElementById('modal-title');

  const f = {
    nombre:    document.getElementById('f-nombre'),
    documento: document.getElementById('f-documento'),
    telefono:  document.getElementById('f-telefono'),
  };

  function openModal(){ modal.style.display = 'block'; }
  function closeModal(){ modal.style.display = 'none'; form.reset(); }

  openCreate?.addEventListener('click', (e)=>{
    e.preventDefault();
    title.textContent = 'Nuevo cliente';
    form.action = "{{ route('clientes.store') }}";
    method.value = 'POST';
    openModal();
  });

  document.querySelectorAll('.action.edit').forEach(btn=>{
    btn.addEventListener('click', (e)=>{
      e.preventDefault();
      const id = btn.dataset.id;
      title.textContent = 'Editar cliente #'+id;
      form.action = "{{ url('clientes') }}/"+id;
      method.value = 'PUT';
      f.nombre.value    = btn.dataset.nombre ?? '';
      f.documento.value = btn.dataset.documento ?? '';
      f.telefono.value  = btn.dataset.telefono ?? '';
      openModal();
    });
  });

  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);
  window.addEventListener('click', e => { if(e.target === modal) closeModal(); });

  /* ====== Buscador con sugerencias ====== */
  const SUGG = @json($suggData);        // [{id,nombre,documento,telefono}]
  const $form = document.getElementById('form-buscar');
  const $q    = document.getElementById('q');
  const $sugg = document.getElementById('sugg');
  const $tbody= document.getElementById('tbody-clientes');

  const norm = s => (s||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
  const debounce = (fn,ms=140)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms) } };

  let items = [];     // resultados actuales
  let idx   = -1;     // índice activo

  function closeSugg(){
    $sugg.classList.add('hidden');
    $sugg.innerHTML = '';
    items = []; idx = -1;
  }

  function render(list){
    if(!list.length){ closeSugg(); return; }
    items = list;
    $sugg.innerHTML = list.map((c,i)=>`
      <div class="sugg-item${i===idx?' active':''}" data-id="${c.id}">
        <div class="sugg-icon"><i class="ri-user-3-line"></i></div>
        <div>
          <div class="sugg-title">${c.nombre||'—'}</div>
          <div class="sugg-sub">${c.documento||'—'} &middot; ${c.telefono||'—'}</div>
        </div>
        <div></div>
      </div>
    `).join('');
    $sugg.classList.remove('hidden');

    // auto-scroll al activo
    const active = $sugg.querySelector('.sugg-item.active');
    if (active) {
      const cTop = $sugg.scrollTop, cBottom = cTop + $sugg.clientHeight;
      const aTop = active.offsetTop, aBottom = aTop + active.offsetHeight;
      if (aBottom > cBottom) $sugg.scrollTop += (aBottom - cBottom) + 4;
      if (aTop < cTop)       $sugg.scrollTop -= (cTop - aTop) + 4;
    }
  }

  function search(){
    const q = norm($q.value.trim());
    if(q.length===0){ closeSugg(); showAllRows(); return; }
    // fuente local
    const res = SUGG.filter(c => {
      return norm(c.nombre).includes(q) ||
             norm(c.documento).includes(q) ||
             norm(c.telefono).includes(q);
    }).slice(0,20);
    render(res);
    // filtrado en la tabla (client-side) mientras escribe
    filterRows(q);
  }

  const doSearch = debounce(search, 120);

  $q.addEventListener('input', doSearch);

  // Enter/teclas
  $q.addEventListener('keydown', (e)=>{
    if ($sugg.classList.contains('hidden')) return;
    const max = items.length - 1;
    if (e.key === 'ArrowDown'){ e.preventDefault(); idx = Math.min(max, idx+1); render(items); }
    else if (e.key === 'ArrowUp'){ e.preventDefault(); idx = Math.max(0, idx-1); render(items); }
    else if (e.key === 'Enter'){
      e.preventDefault();
      if(idx>=0 && idx<=max){
        $q.value = items[idx].nombre || '';
      }
      $form.submit();        // ejecuta búsqueda de servidor también
    } else if (e.key === 'Escape'){ closeSugg(); }
  });

  // click sugerencia
  $sugg.addEventListener('click', (e)=>{
    const row = e.target.closest('.sugg-item');
    if(!row) return;
    const id = parseInt(row.dataset.id);
    const c  = items.find(x=>x.id===id);
    if(c){ $q.value = c.nombre || ''; }
    $form.submit();
  });

  // cerrar al hacer click fuera
  document.addEventListener('click', (e)=>{
    if(!e.target.closest('#search-clients')) closeSugg();
  });

  // Helpers: filtrado visual de tabla mientras escribe
  function showAllRows(){
    [...$tbody.children].forEach(tr => tr.style.display = '');
  }
  function filterRows(q){
    [...$tbody.children].forEach(tr=>{
      const hay = tr.dataset && (
        tr.dataset.nombre.includes(q) ||
        tr.dataset.documento.includes(q) ||
        tr.dataset.telefono.includes(q)
      );
      tr.style.display = hay ? '' : 'none';
    });
  }
})();
</script>
@endsection
