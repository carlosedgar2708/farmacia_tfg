@extends('app')

@section('title', 'Proveedors')

@section('content')
@php
  // Fuente para sugerencias: la página actual (mismo patrón de fallback que productos/index.blade.php)
  $suggData = $proveedores->map(function($p){
    return [
      'id'       => $p->id,
      'nombre'   => $p->nombre,
      'contacto' => $p->contacto,
      'telefono' => $p->telefono,
    ];
  })->values();
@endphp

<x-card>
  {{-- Título y descripción --}}
  <h1 class="h-top">Proveedores</h1>
  <p>Administra los proveedores de productos.</p>

  {{-- Barra superior --}}
  <div class="toolbar">
    <form id="form-buscar" method="GET" action="{{ route('proveedors.index') }}" style="margin:0">
      <div class="search-wrap" style="margin-bottom:12px; position:relative">
        <i class="ri-search-line"></i>
        <input id="q" type="text" name="q" placeholder="Buscar por nombre, contacto o teléfono…" value="{{ $q }}">
        <x-button type="submit" variant="secondary" icon="ri-filter-2-line">Buscar</x-button>
        <div id="sugg" class="sugg hidden"></div>
      </div>
    </form>

    <div>
      <x-button variant="primary" href="#" id="btn-open-create">Nuevo proveedor</x-button>
    </div>
  </div>

  {{-- Tabla --}}
  @if($proveedores->count())
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Contacto</th>
          <th>Teléfono</th>
          <th style="width:200px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($proveedores as $p)
        <tr>
          <td>{{ $p->id }}</td>
          <td>{{ $p->nombre }}</td>
          <td>{{ $p->contacto ?? '—' }}</td>
          <td>{{ $p->telefono ?? '—' }}</td>
          <td>
            <div class="actions">
              <a href="#"
                 class="action edit"
                 data-id="{{ $p->id }}"
                 data-nombre="{{ $p->nombre }}"
                 data-contacto="{{ $p->contacto }}"
                 data-telefono="{{ $p->telefono }}"
              >Editar</a>

              <form method="POST" action="{{ route('proveedors.destroy', $p) }}" onsubmit="return confirm('¿Eliminar proveedor #{{ $p->id }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="action delete">Eliminar</button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="pagination" style="margin-top:15px;">
      {{ $proveedores->onEachSide(1)->links() }}
    </div>
  @else
    <x-empty-state message="No hay proveedores registrados." />
  @endif
</x-card>

{{-- Modal crear/editar --}}
<div id="modal" class="modal">
  <div class="modal-content">
    <button class="close" id="btn-close-modal">&times;</button>
    <h3 class="modal-title" id="modal-title">Nuevo proveedor</h3>

    <form id="modal-form" method="POST" action="{{ route('proveedors.store') }}">
      @csrf
      <input type="hidden" name="_method" id="form-method" value="POST">

      <label>Nombre *</label>
      <input type="text" name="nombre" id="f-nombre" required maxlength="255">
      @error('nombre') <small class="field-error">{{ $message }}</small> @enderror

      <label>Contacto</label>
      <input type="text" name="contacto" id="f-contacto" maxlength="255">
      @error('contacto') <small class="field-error">{{ $message }}</small> @enderror

      <label>Teléfono</label>
      <input type="text" name="telefono" id="f-telefono" maxlength="255">
      @error('telefono') <small class="field-error">{{ $message }}</small> @enderror

      <div class="modal-actions">
        <x-button type="button" variant="secondary" id="btn-cancel">Cancelar</x-button>
        <x-button type="submit" variant="primary" id="btn-submit">Guardar</x-button>
      </div>
    </form>
  </div>
</div>

{{-- Script del modal --}}
<script>
(function() {
  const modal = document.getElementById('modal');
  const openCreate = document.getElementById('btn-open-create');
  const closeBtn = document.getElementById('btn-close-modal');
  const cancelBtn = document.getElementById('btn-cancel');
  const form = document.getElementById('modal-form');
  const method = document.getElementById('form-method');
  const title = document.getElementById('modal-title');

  const f = {
    nombre:   document.getElementById('f-nombre'),
    contacto: document.getElementById('f-contacto'),
    telefono: document.getElementById('f-telefono'),
  };

  function openModal() { modal.style.display = 'block'; }
  function closeModal() { modal.style.display = 'none'; form.reset(); }

  openCreate?.addEventListener('click', (e) => {
    e.preventDefault();
    title.textContent = 'Nuevo proveedor';
    form.action = "{{ route('proveedors.store') }}";
    method.value = 'POST';
    openModal();
  });

  document.querySelectorAll('.action.edit').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const id = btn.dataset.id;
      title.textContent = 'Editar proveedor #' + id;
      form.action = "{{ url('proveedors') }}/" + id;
      method.value = 'PUT';

      f.nombre.value   = btn.dataset.nombre ?? '';
      f.contacto.value = btn.dataset.contacto ?? '';
      f.telefono.value = btn.dataset.telefono ?? '';

      openModal();
    });
  });

  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);
  window.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
})();
</script>

{{-- Script del buscador con sugerencias (mismo patrón que productos/index.blade.php) --}}
<script>
(function(){
  const $q    = document.getElementById('q');
  const $sugg = document.getElementById('sugg');
  const $form = document.getElementById('form-buscar');

  const SUGG = @json($suggData);

  const norm = s => (s||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();

  let items=[], idx=-1;

  function close(){ $sugg.classList.add('hidden'); $sugg.innerHTML=''; items=[]; idx=-1; }

  function render(list){
    if(!list.length){ close(); return; }
    items=list;
    $sugg.innerHTML = list.map((p,i)=>`
      <div class="sugg-item${i===idx?' active':''}" data-text="${p.nombre}">
        <div class="sugg-icon"><i class="ri-truck-line"></i></div>
        <div>
          <div class="sugg-title">${p.nombre}</div>
          <div class="sugg-sub">${p.contacto || '—'} &middot; ${p.telefono || '—'}</div>
        </div>
      </div>
    `).join('');
    $sugg.classList.remove('hidden');
  }

  const doSearch = ()=>{
    const q = norm($q.value.trim());
    if(!q){ close(); return; }
    const results = SUGG.filter(p=> norm(p.nombre+' '+(p.contacto||'')+' '+(p.telefono||'')).includes(q)).slice(0,12);
    render(results);
  };

  $q.addEventListener('input', doSearch);

  $q.addEventListener('keydown', (e)=>{
    if($sugg.classList.contains('hidden')) return;
    const max = items.length-1;
    if(e.key==='ArrowDown'){ e.preventDefault(); idx=Math.min(max,idx+1); render(items); }
    else if(e.key==='ArrowUp'){ e.preventDefault(); idx=Math.max(0,idx-1); render(items); }
    else if(e.key==='Enter'){
      if(idx>=0){ e.preventDefault(); $q.value = items[idx].nombre; }
      close(); $form.submit();
    }else if(e.key==='Escape'){ close(); }
  });

  $sugg.addEventListener('click',(e)=>{
    const it = e.target.closest('.sugg-item'); if(!it) return;
    $q.value = it.dataset.text; close(); $form.submit();
  });

  document.addEventListener('click',(e)=>{ if(!e.target.closest('.search-wrap')) close(); });
})();
</script>
@endsection
