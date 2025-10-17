@extends('app')

@section('title', 'Proveedors')

@section('content')
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<section class="panel">
  {{-- Título y descripción --}}
  <h1 class="h-top" style="color:white;">Proveedores</h1>
  <p style="color:white;">Administra los proveedores de productos.</p>

  {{-- Alertas --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @elseif(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Barra superior --}}
  <div class="toolbar">
    <form method="GET" action="{{ route('proveedors.index') }}" class="search">
      <input type="text" name="q" placeholder="Buscar por nombre, contacto o teléfono..." value="{{ $q }}">
      <button type="submit">Buscar</button>
    </form>

    <div>
      <a href="#" class="btn" id="btn-open-create">Nuevo proveedor</a>
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
    <div class="empty">No hay proveedors registrados.</div>
  @endif
</section>

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

      <label>Contacto</label>
      <input type="text" name="contacto" id="f-contacto" maxlength="255">

      <label>Teléfono</label>
      <input type="text" name="telefono" id="f-telefono" maxlength="255">

      <div class="modal-actions">
        <button type="button" class="btn-outline" id="btn-cancel">Cancelar</button>
        <button type="submit" class="btn" id="btn-submit">Guardar</button>
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
@endsection
