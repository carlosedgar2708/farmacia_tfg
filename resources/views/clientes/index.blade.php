@extends('app')

@section('title', 'Clientes')

@section('content')
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<section class="panel">
  {{-- Título y descripción con color blanco --}}
  <h1 class="h-top">Lista de Clientes</h1>


  {{-- Alertas de éxito o error --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @elseif(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Barra superior con búsqueda y botón nuevo --}}
  <div class="toolbar">
    <form method="GET" action="{{ route('clientes.index') }}" class="search">
      <input type="text" name="q" placeholder="Buscar por nombre, documento o teléfono..." value="{{ $q }}">
      <button type="submit">Buscar</button>
    </form>

    <div>
      <a href="#" class="btn" id="btn-open-create">Nuevo cliente</a>
    </div>
  </div>

  {{-- Tabla --}}
  @if($clientes->count())
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Documento</th>
          <th>Teléfono</th>
          <th style="width:200px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($clientes as $c)
        <tr>
          <td>{{ $c->id }}</td>
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
                 data-telefono="{{ $c->telefono }}"
              >Editar</a>

              <form method="POST" action="{{ route('clientes.destroy', $c) }}" onsubmit="return confirm('¿Eliminar cliente #{{ $c->id }}?')">
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
      {{ $clientes->onEachSide(1)->links() }}
    </div>
  @else
    <div class="empty">No hay clientes registrados.</div>
  @endif
</section>

{{-- Modal de creación/edición --}}
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
    nombre:    document.getElementById('f-nombre'),
    documento: document.getElementById('f-documento'),
    telefono:  document.getElementById('f-telefono'),
  };

  function openModal() { modal.style.display = 'block'; }
  function closeModal() { modal.style.display = 'none'; form.reset(); }

  openCreate?.addEventListener('click', (e) => {
    e.preventDefault();
    title.textContent = 'Nuevo cliente';
    form.action = "{{ route('clientes.store') }}";
    method.value = 'POST';
    openModal();
  });

  document.querySelectorAll('.action.edit').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const id = btn.dataset.id;
      title.textContent = 'Editar cliente #' + id;
      form.action = "{{ url('clientes') }}/" + id;
      method.value = 'PUT';

      f.nombre.value    = btn.dataset.nombre ?? '';
      f.documento.value = btn.dataset.documento ?? '';
      f.telefono.value  = btn.dataset.telefono ?? '';

      openModal();
    });
  });

  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);
  window.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
})();
</script>
@endsection
