@extends('app')
@section('title','Usuarios')

@section('content')
<section class="hero">
  <div class="panel">
    <h1 class="h-top" style="color:#7dd3fc">Lista de usuarios</h1>

    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="toolbar">
      <form class="search" method="GET" action="{{ route('users.index') }}">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar usuario...">
        <button type="submit">Buscar</button>
      </form>

      @if(auth()->user()->tienePermiso('usuarios.crear'))
        <button class="btn" type="button" onclick="openCreateUserModal()">+ Nuevo usuario</button>
      @endif
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>ID</th><th>Nombre</th><th>Email</th><th>Roles</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($users as $u)
        <tr>
          <td>{{ $u->id }}</td>
          <td>{{ $u->name }}</td>
          <td>{{ $u->email }}</td>
          <td>
            @forelse ($u->rols as $r)
              <span class="badge">{{ $r->nombre }}</span>
            @empty
              <span style="color:#94a3b8">sin rol</span>
            @endforelse
          </td>

          <td class="actions">
            {{-- Ver --}}
            <button class="action view"
                    onclick="openViewUser(this)"
                    data-id="{{ $u->id }}"
                    data-username="{{ e($u->username) }}"
                    data-name="{{ e($u->name) }}"
                    data-apellido="{{ e($u->apellido) }}"
                    data-email="{{ e($u->email) }}"
                    data-telefono="{{ e($u->telefono) }}"
                    data-activo="{{ (int)$u->activo }}"
                    data-rols='@json($u->rols->pluck("nombre"))'>
              Ver
            </button>

            {{-- Editar --}}
            @if(auth()->user()->tienePermiso('usuarios.editar'))
            <button class="action edit"
                    onclick="openEditUserModal(this)"
                    data-id="{{ $u->id }}"
                    data-username="{{ e($u->username) }}"
                    data-name="{{ e($u->name) }}"
                    data-apellido="{{ e($u->apellido) }}"
                    data-email="{{ e($u->email) }}"
                    data-telefono="{{ e($u->telefono) }}"
                    data-activo="{{ (int)$u->activo }}"
                    data-roles="{{ $u->rols->pluck('id')->implode(',') }}">
              Editar
            </button>
            @endif

            {{-- Eliminar --}}
            @if(auth()->user()->tienePermiso('usuarios.eliminar') && auth()->id() !== $u->id)
            <form action="{{ route('users.destroy', $u) }}" method="POST" style="display:inline"
                  onsubmit="return confirm('¿Eliminar este usuario?');">
              @csrf
              @method('DELETE')
              <button class="action delete" type="submit">Eliminar</button>
            </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty">Sin registros.</td></tr>
      @endforelse
      </tbody>
    </table>

    <div style="margin-top:10px">{{ $users->withQueryString()->links() }}</div>
  </div>
  <div class="shadow"></div>
</section>
@endsection

@push('modals')
{{-- Modal CREAR/EDITAR (mismo modal) --}}
<div id="userModal" class="modal" aria-hidden="true">
  <div class="modal-content modal-wide">
    <button class="close" type="button" onclick="closeUserModal()">&times;</button>
    <h2 id="userModalTitle" class="modal-title">Nuevo usuario</h2>

    <form id="userForm" method="POST" action="{{ route('users.store') }}">
      @csrf
      <input type="hidden" name="_method" id="f_method" value="POST">

      <div class="modal-grid">
        <div class="modal-col">
          <label>Usuario</label>
          <input type="text" name="username" id="f_username" placeholder="(se genera si lo dejas vacío)">

          <label>Nombre</label>
          <input type="text" name="name" id="f_name" required>

          <label>Apellido</label>
          <input type="text" name="apellido" id="f_apellido">

          <label>Email</label>
          <input type="email" name="email" id="f_email" required>

          <label>Teléfono</label>
          <input type="text" name="telefono" id="f_telefono">

          <label>Contraseña <small style="color:#64748b">(obligatoria al crear / opcional al editar)</small></label>
          <input type="password" name="password" id="f_password">

          <label style="display:flex;align-items:center;gap:8px;margin-top:8px">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" id="f_activo" value="1" checked>
            Activo
          </label>
        </div>

        <div class="modal-col">
          <label>Roles</label>
          <div class="perm-box">
            <div class="perm-list">
              @foreach($rols as $role)
                <label class="perm-item">
                  <input type="checkbox" class="role-check" name="roles[]" value="{{ $role->id }}">
                  <span>{{ $role->nombre }}</span>
                </label>
              @endforeach
            </div>
          </div>

          <div class="modal-actions">
            <button class="btn" id="userSubmit" type="submit">Guardar</button>
            <button class="btn btn-outline" type="button" onclick="closeUserModal()">Cancelar</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Modal VER --}}
<div id="modalViewUser" class="modal">
  <div class="modal-content modal-wide">
    <button class="close" data-close>&times;</button>
    <h2 class="modal-title">Detalle de usuario</h2>

    <div class="modal-grid">
      <div>
        <label>Usuario</label>
        <input id="v_username" type="text" readonly>
        <label>Nombre</label>
        <input id="v_name" type="text" readonly>
        <label>Apellido</label>
        <input id="v_apellido" type="text" readonly>
        <label>Email</label>
        <input id="v_email" type="text" readonly>
        <label>Teléfono</label>
        <input id="v_telefono" type="text" readonly>
        <label>Activo</label>
        <input id="v_activo" type="text" readonly>
      </div>

      <div>
        <label>Roles</label>
        <div id="v_roles" class="perm-list"></div>
        <div class="modal-actions">
          <button class="btn-outline" data-close>Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</div>
@endpush

@push('scripts')
<script>
function resetRoles() {
  document.querySelectorAll('.role-check').forEach(c => c.checked = false);
}

function openCreateUserModal(){
  const m = document.getElementById('userModal');
  m.style.display = 'block';
  document.getElementById('userModalTitle').innerText = 'Nuevo usuario';
  document.getElementById('userForm').action = "{{ route('users.store') }}";
  document.getElementById('f_method').value = 'POST';

  // reset
  document.getElementById('f_username').value = '';
  document.getElementById('f_name').value     = '';
  document.getElementById('f_apellido').value = '';
  document.getElementById('f_email').value    = '';
  document.getElementById('f_telefono').value = '';
  document.getElementById('f_password').value = '';
  document.getElementById('f_activo').checked = true;
  resetRoles();

  document.getElementById('userSubmit').innerText = 'Guardar';
  document.getElementById('f_name').focus();
}

function openEditUserModal(btn){
  const id = btn.dataset.id;
  const roles = (btn.dataset.roles || '').split(',').filter(Boolean).map(Number);

  const m = document.getElementById('userModal');
  m.style.display = 'block';
  document.getElementById('userModalTitle').innerText = 'Editar usuario';
  document.getElementById('userForm').action = '/users/' + id;
  document.getElementById('f_method').value = 'PUT';

  document.getElementById('f_username').value = btn.dataset.username || '';
  document.getElementById('f_name').value     = btn.dataset.name || '';
  document.getElementById('f_apellido').value = btn.dataset.apellido || '';
  document.getElementById('f_email').value    = btn.dataset.email || '';
  document.getElementById('f_telefono').value = btn.dataset.telefono || '';
  document.getElementById('f_password').value = '';
  document.getElementById('f_activo').checked = (btn.dataset.activo === '1');

  const want = new Set(roles);
  document.querySelectorAll('.role-check').forEach(c => c.checked = want.has(Number(c.value)));

  document.getElementById('userSubmit').innerText = 'Actualizar';
}

function closeUserModal(){
  document.getElementById('userModal').style.display = 'none';
}

window.addEventListener('click', e => {
  const m1 = document.getElementById('userModal');
  const m2 = document.getElementById('modalViewUser');
  if (e.target === m1) closeUserModal();
  if (e.target === m2) m2.style.display = 'none';
});

// ---- VER ----
function openViewUser(btn){
  try {
    const modal  = document.getElementById('modalViewUser');
    modal.style.display = 'block';

    document.getElementById('v_username').value = btn.dataset.username || '';
    document.getElementById('v_name').value     = btn.dataset.name || '';
    document.getElementById('v_apellido').value = btn.dataset.apellido || '';
    document.getElementById('v_email').value    = btn.dataset.email || '';
    document.getElementById('v_telefono').value = btn.dataset.telefono || '';
    document.getElementById('v_activo').value   = (btn.dataset.activo === '1') ? 'Sí' : 'No';

    const cont = document.getElementById('v_roles');
    cont.innerHTML = '';
    const rols = JSON.parse(btn.dataset.rols || '[]');
    if (rols.length){
      rols.forEach(n => {
        const s = document.createElement('span');
        s.className = 'badge';
        s.textContent = n;
        cont.appendChild(s);
      });
    } else {
      const s = document.createElement('span');
      s.className = 'badge';
      s.textContent = 'sin rol';
      cont.appendChild(s);
    }

    modal.querySelectorAll('[data-close]').forEach(x => x.onclick = () => modal.style.display = 'none');
  } catch(e){
    console.error('openViewUser error', e);
  }
}
</script>
@endpush
