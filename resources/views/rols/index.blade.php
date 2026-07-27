@extends('app')

@section('title', 'Lista de Roles')

@section('content')
@php
  // Fuente para sugerencias: la página actual (mismo patrón de fallback que productos/index.blade.php)
  $suggData = $rols->map(function($r){
    return [
      'id'     => $r->id,
      'nombre' => $r->nombre,
      'slug'   => $r->slug,
    ];
  })->values();
@endphp

<x-card>
  <h1><span class="h-top" style="font-size:38px">LISTA DE ROLES</span></h1>

  <div class="toolbar">
    {{-- Buscador --}}
    <form id="form-buscar" method="GET" action="{{ route('rols.index') }}" style="margin:0">
      <div class="search-wrap" style="margin-bottom:12px; position:relative">
        <i class="ri-search-line"></i>
        <input id="q" type="text" name="q" value="{{ request('q') }}" placeholder="Buscar rol…">
        <x-button type="submit" variant="secondary" icon="ri-filter-2-line">Buscar</x-button>
        <div id="sugg" class="sugg hidden"></div>
      </div>
    </form>

    {{-- Botón de nuevo rol (abre modal) --}}
    <x-button type="button" variant="primary" icon="ri-add-line" onclick="openCreateModal()">Nuevo rol</x-button>
  </div>

  {{-- Toolbar --}}
    <script>
    function slugify(str){
    return (str||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'')
        .toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)+/g,'');
    }

    function setFormDisabled(disabled){
    ['nombre','slug','descripcion'].forEach(id=>{
        const el=document.getElementById(id);
        el.disabled = disabled;
        el.readOnly = disabled && id!=='descripcion'; // textarea admite disabled ya
    });
    }

function openCreateModal(){
  const modal = document.getElementById('rolModal');
  modal.style.display='block';

  document.getElementById('modalTitle').innerText='Nuevo Rol';
  document.getElementById('rolForm').action='{{ route('rols.store') }}';
  document.getElementById('methodField').value='POST';
  document.getElementById('modalMode').value='create';

  document.getElementById('nombre').value='';
  document.getElementById('slug').value='';
  document.getElementById('descripcion').value='';

  setFormDisabled(false);
  document.getElementById('submitBtn').style.display='';
  document.getElementById('submitBtn').innerText='Guardar';
  document.getElementById('cancelBtn').innerText='Cancelar';
}

function openEditModal(btn){
  const id = btn.dataset.id;
  const nombre = btn.dataset.nombre || '';
  const slug = btn.dataset.slug || '';
  const descripcion = btn.dataset.descripcion || '';

  const modal = document.getElementById('rolModal');
  modal.style.display='block';

  document.getElementById('modalTitle').innerText='Editar Rol';
  document.getElementById('rolForm').action='/rols/'+id;
  document.getElementById('methodField').value='PUT';
  document.getElementById('modalMode').value='edit';

  document.getElementById('nombre').value=nombre;
  document.getElementById('slug').value=slug;
  document.getElementById('descripcion').value=descripcion;

  setFormDisabled(false);
  document.getElementById('submitBtn').style.display='';
  document.getElementById('submitBtn').innerText='Actualizar';
  document.getElementById('cancelBtn').innerText='Cancelar';
}

function openViewModal(btn){
  const nombre = btn.dataset.nombre || '';
  const slug = btn.dataset.slug || '';
  const descripcion = btn.dataset.descripcion || '';

  const modal = document.getElementById('rolModal');
  modal.style.display='block';

  document.getElementById('modalTitle').innerText='Detalle del Rol';
  document.getElementById('rolForm').action='#';             // no envía
  document.getElementById('methodField').value='GET';        // solo informativo
  document.getElementById('modalMode').value='view';

  document.getElementById('nombre').value=nombre;
  document.getElementById('slug').value=slug;
  document.getElementById('descripcion').value=descripcion;

  // deshabilitamos campos y ocultamos submit
  setFormDisabled(true);
  document.getElementById('submitBtn').style.display='none';
  document.getElementById('cancelBtn').innerText='Cerrar';
}

function closeModal(){ document.getElementById('rolModal').style.display='none'; }

// cerrar al hacer click fuera
window.addEventListener('click', e=>{
  const modal=document.getElementById('rolModal');
  if(e.target===modal) closeModal();
});

// autogenerar slug en modo create si el usuario no tocó el slug
(function autoSlugWireup(){
  const nombreEl=document.getElementById('nombre');
  const slugEl=document.getElementById('slug');
  let touched=false;
  slugEl.addEventListener('input',()=>{touched = slugEl.value.trim().length>0;});
  nombreEl.addEventListener('input',()=>{
    if(document.getElementById('modalMode').value==='create' && !touched){
      slugEl.value=slugify(nombreEl.value);
    }
  });
})();

/* Buscador con sugerencias (mismo patrón que productos/index.blade.php) */
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
    $sugg.innerHTML = list.map((r,i)=>`
      <div class="sugg-item${i===idx?' active':''}" data-text="${r.nombre}">
        <div class="sugg-icon"><i class="ri-lock-2-line"></i></div>
        <div>
          <div class="sugg-title">${r.nombre}</div>
          <div class="sugg-sub">${r.slug || '—'}</div>
        </div>
      </div>
    `).join('');
    $sugg.classList.remove('hidden');
  }

  const doSearch = ()=>{
    const q = norm($q.value.trim());
    if(!q){ close(); return; }
    const results = SUGG.filter(r=> norm(r.nombre+' '+r.slug).includes(q)).slice(0,12);
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


      {{-- Tabla --}}
      @if($rols->count())
      <div style="overflow-x:auto;background:transparent;padding-top:6px">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Slug</th>
              <th>Descripción</th>
              <th>Creado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($rols as $rol)
            <tr>
              <td>{{ $rol->id }}</td>
              <td><span class="badge">{{ $rol->nombre }}</span></td>
              <td>{{ $rol->slug }}</td>
              <td>{{ $rol->descripcion ?? '-' }}</td>
              <td>{{ optional($rol->created_at)->format('Y-m-d') }}</td>
              <td>
                <div class="actions">
                    {{-- VER en modal (no navega) --}}
                    <button
                    type="button"
                    class="action view"
                    data-id="{{ $rol->id }}"
                    data-nombre="{{ e($rol->nombre) }}"
                    data-slug="{{ e($rol->slug) }}"
                    data-descripcion="{{ e($rol->descripcion) }}"
                    data-permisos="{{ $rol->permisos->pluck('id')->implode(',') }}"
                    onclick="openViewModal(this)"
                    >
                    Ver
                    </button>

                    {{-- EDITAR en modal (no navega) --}}
                    <button
                    type="button"
                    class="action edit"
                    data-id="{{ $rol->id }}"
                    data-nombre="{{ e($rol->nombre) }}"
                    data-slug="{{ e($rol->slug) }}"
                    data-descripcion="{{ e($rol->descripcion) }}"
                    data-permisos="{{ $rol->permisos->pluck('id')->implode(',') }}"
                    onclick="openEditModal(this)"
                    >
                    Editar
                    </button>

                    {{-- ELIMINAR (igual que antes, con confirm) --}}
                    <form action="{{ route('rols.destroy', $rol) }}" method="POST"
                        onsubmit="return confirm('¿Eliminar el rol {{ $rol->nombre }}?');" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="action delete" style="border:0;cursor:pointer">Eliminar</button>
                    </form>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div style="margin-top:16px">
        {{ $rols->links() }}
      </div>

      @else
        <x-empty-state message="No hay roles registrados." />
      @endif
</x-card>
@endsection

@push('modals')
<div id="rolModal" class="modal" aria-hidden="true">
  <div class="modal-content modal-wide">
    <button class="close" type="button" aria-label="Cerrar" onclick="closeModal()">&times;</button>
    <h2 id="modalTitle" class="modal-title">Nuevo Rol</h2>

    <form id="rolForm" method="POST" action="{{ route('rols.store') }}">
      @csrf
      <input type="hidden" id="methodField" name="_method" value="POST">
      <input type="hidden" id="modalMode" value="create">

      <!-- GRID 2 COLUMNAS -->
      <div class="modal-grid">
        <!-- Columna izquierda: campos -->
        <div class="modal-col">
          <label for="nombre">Nombre</label>
          <input type="text" name="nombre" id="nombre" required>
          @error('nombre') <small class="field-error">{{ $message }}</small> @enderror

          <label for="slug">Slug</label>
          <input type="text" name="slug" id="slug" required>
          @error('slug') <small class="field-error">{{ $message }}</small> @enderror

          <label for="descripcion">Descripción</label>
          <textarea name="descripcion" id="descripcion" rows="7"></textarea>
          @error('descripcion') <small class="field-error">{{ $message }}</small> @enderror
        </div>

        <!-- Columna derecha: permisos + acciones -->
        <div class="modal-col">
          <label>Permisos</label>
          <div class="perm-box">
            <div class="perm-list">
              @forelse(($permisos ?? collect()) as $perm)
                <label class="perm-item">
                  <input type="checkbox" name="permisos[]" value="{{ $perm->id }}" class="perm-check">
                  <span>{{ $perm->nombre }}</span>
                </label>
              @empty
                <span style="color:#64748b">No hay permisos disponibles.</span>
              @endforelse
            </div>
          </div>

          <div class="modal-actions">
            <x-button type="submit" variant="primary" id="submitBtn">Guardar</x-button>
            <x-button type="button" variant="secondary" id="cancelBtn" onclick="closeModal()">Cancelar</x-button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endpush
