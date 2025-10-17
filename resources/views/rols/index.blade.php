@extends('app')

@section('title', 'Lista de Roles')

@section('content')
<section class="grid" style="grid-template-columns:1fr">
  <div class="hero">
    <div class="panel" style="background:#1157c2;color:#fff">
      <h1><span class="h-top" style="color:#fff;font-size:38px">LISTA DE ROLES</span></h1>

      {{-- Flash success --}}
        @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}
        </div>
        @endif
        <div class="toolbar">
        {{-- Buscador --}}
        <form class="search" method="GET" action="{{ route('rols.index') }}">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar rol...">
            <button type="submit">Buscar</button>
        </form>

        {{-- Botón de nuevo rol (abre modal) --}}
        <button type="button" class="btn" onclick="openCreateModal()">+ Nuevo rol</button>
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
        <div class="empty">No hay roles registrados.</div>
      @endif
    </div>
    <div class="shadow"></div>

    {{-- Burbujas deco (opcionales) --}}
    <span class="bubble b1"></span><span class="bubble b2"></span>
    <span class="bubble b3"></span><span class="bubble b4"></span><span class="bubble b5"></span>
  </div>
</section>
@endsection

@push('modals')
  <!-- Modal de Crear / Editar Rol -->
  <div id="rolModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <button class="close" type="button" aria-label="Cerrar" onclick="closeModal()">&times;</button>
      <h2 id="modalTitle">Nuevo Rol</h2>

      <form id="rolForm" method="POST" action="{{ route('rols.store') }}">
        @csrf
        <input type="hidden" id="methodField" name="_method" value="POST">
        <input type="hidden" id="modalMode" value="create"><!-- create|edit para reabrir en errores -->

        <label for="nombre">Nombre</label>
        <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" required>

        <label for="slug">Slug</label>
        <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required>

        <label for="descripcion">Descripción</label>
        <textarea name="descripcion" id="descripcion" rows="3">{{ old('descripcion') }}</textarea>

        <label>Permisos</label>
            <div class="perm-list">
            @foreach ($permisos as $perm)
                <label class="perm-item">
                <input type="checkbox" name="permisos[]" value="{{ $perm->id }}" class="perm-check">
                <span>{{ $perm->nombre }}</span>
                </label>
            @endforeach
            </div>
        {{-- Errores inline --}}
        @if ($errors->any())
          <div class="alert" style="background:#fee2e2;border-color:#fecaca;color:#7f1d1d">
            <ul style="margin:0;padding-left:18px">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <div style="margin-top:15px;display:flex;gap:10px">
          <button type="submit" class="btn" id="submitBtn">Guardar</button>
          <button type="button" class="btn-outline" onclick="closeModal()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
@endpush

@push('scripts')
  {{-- Exponemos la ruta store para usarla en el JS externo --}}
  <script>
    window.routesRolsStore = "{{ route('rols.store') }}";
  </script>
  <script src="{{ asset('js/rols.js') }}"></script>
@endpush
