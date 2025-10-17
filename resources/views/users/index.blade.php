@extends('app')
@section('title','Usuarios')

@section('content')
<section class="hero">
  <div class="panel">
    <h1 class="h-top" style="color:#7dd3fc">Lista de usuarios</h1>

    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
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
                            data-name="{{ e($u->name) }}"
                            data-email="{{ e($u->email) }}"
                            data-rols='@json($u->rols->pluck("nombre"))'
                            data-rolids='@json($u->rols->pluck("id"))'>
                    Ver
                    </button>

                {{-- Editar --}}
                @if(auth()->user()->tienePermiso('usuarios.editar'))
                <button class="action edit"
                        onclick="openEditUserModal(this)"
                        data-id="{{ $u->id }}"
                        data-name="{{ $u->name }}"
                        data-email="{{ $u->email }}"
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

    <div style="margin-top:10px">{{ $users->links() }}</div>
  </div>
  <div class="shadow"></div>
</section>
@endsection

@push('modals')
<!-- Modal Usuarios -->
<div id="userModal" class="modal" aria-hidden="true">
  <div class="modal-content modal-wide">
    <button class="close" type="button" onclick="closeUserModal()">&times;</button>
    <h2 id="userModalTitle" class="modal-title">Nuevo usuario</h2>

    <form id="userForm" method="POST" action="{{ route('users.store') }}">
      @csrf
      <input type="hidden" name="_method" id="userMethod" value="POST">

      <div class="modal-grid">
        <div class="modal-col">
          <label>Nombre</label>
          <input type="text" name="name" id="u_name" required>

          <label>Email</label>
          <input type="email" name="email" id="u_email" required>

          <label>Contraseña <small style="color:#64748b">(solo al crear o si deseas cambiar)</small></label>
          <input type="password" name="password" id="u_password">
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
        <label>Nombre</label>
        <input id="v_name" type="text" readonly>
        <label>Email</label>
        <input id="v_email" type="text" readonly>
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

{{-- Modal EDITAR --}}
<div id="modalEditUser" class="modal">
  <div class="modal-content modal-wide">
    <button class="close" data-close>&times;</button>
    <h2 class="modal-title">Editar usuario</h2>

    <form id="formEditUser" method="POST">
      @csrf
      @method('PUT')

      <div class="modal-grid">
        <div>
          <label>Nombre</label>
          <input name="name" id="e_name" type="text" required>
          <label>Email</label>
          <input name="email" id="e_email" type="email" required>
          <label>Nueva contraseña (opcional)</label>
          <input name="password" id="e_password" type="password" placeholder="Deja vacío para no cambiar">
        </div>

        <div>
          <label>Roles</label>
          <div class="perm-list">
            @foreach($rols as $r)
              <label class="perm-item">
                <input type="checkbox" name="roles[]" value="{{ $r->id }}">
                <span>{{ $r->nombre }}</span>
              </label>
            @endforeach
          </div>

          <div class="modal-actions">
            <button type="submit" class="btn">Guardar</button>
            <button type="button" class="btn-outline" data-close>Cancelar</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

@endpush

@push('scripts')
<script>
function openCreateUserModal(){
  const m = document.getElementById('userModal');
  m.style.display = 'block';
  document.getElementById('userModalTitle').innerText = 'Nuevo usuario';
  document.getElementById('userForm').action = "{{ route('users.store') }}";
  document.getElementById('userMethod').value = 'POST';
  document.getElementById('u_name').value = '';
  document.getElementById('u_email').value = '';
  document.getElementById('u_password').value = '';
  document.querySelectorAll('.role-check').forEach(c => c.checked = false);
  document.getElementById('userSubmit').innerText = 'Guardar';
}

function openEditUserModal(btn){
  const id = btn.dataset.id;
  const roles = (btn.dataset.roles || '').split(',').filter(Boolean).map(Number);

  const m = document.getElementById('userModal');
  m.style.display = 'block';
  document.getElementById('userModalTitle').innerText = 'Editar usuario';
  document.getElementById('userForm').action = '/users/' + id;
  document.getElementById('userMethod').value = 'PUT';
  document.getElementById('u_name').value = btn.dataset.name || '';
  document.getElementById('u_email').value = btn.dataset.email || '';
  document.getElementById('u_password').value = ''; // vacío (cambiar sólo si rellena)

  const want = new Set(roles);
  document.querySelectorAll('.role-check').forEach(c => c.checked = want.has(Number(c.value)));

  document.getElementById('userSubmit').innerText = 'Actualizar';
}

function closeUserModal(){
  document.getElementById('userModal').style.display = 'none';
}

window.addEventListener('click', e => {
  const m = document.getElementById('userModal');
  if (e.target === m) closeUserModal();
});
</script>
<script>
// ---- VER ----
function openViewUser(btn){
  try {
    const name   = btn.dataset.name || '';
    const email  = btn.dataset.email || '';
    const rols   = JSON.parse(btn.dataset.rols || '[]');     // nombres
    const modal  = document.getElementById('modalViewUser');

    document.getElementById('v_name').value  = name;
    document.getElementById('v_email').value = email;

    const cont = document.getElementById('v_roles');
    cont.innerHTML = '';
    if (rols.length) {
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

    modal.style.display = 'block';
    modal.querySelectorAll('[data-close]').forEach(x => x.onclick = () => modal.style.display = 'none');
    modal.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };
  } catch (e) {
    console.error('openViewUser error', e);
  }
}

// ---- EDITAR (tu modal combinado de crear/editar) ----
function openEditUserModal(btn){
  const id    = btn.dataset.id;
  const name  = btn.dataset.name || '';
  const email = btn.dataset.email || '';
  const roles = (btn.dataset.roles || '').split(',').filter(Boolean).map(Number);

  const m = document.getElementById('userModal');
  m.style.display = 'block';

  document.getElementById('userModalTitle').innerText = 'Editar usuario';
  document.getElementById('userForm').action = '/users/' + id;
  document.getElementById('userMethod').value = 'PUT';
  document.getElementById('u_name').value = name;
  document.getElementById('u_email').value = email;
  document.getElementById('u_password').value = '';

  const want = new Set(roles);
  document.querySelectorAll('.role-check').forEach(c => c.checked = want.has(Number(c.value)));

  document.getElementById('userSubmit').innerText = 'Actualizar';
}
</script>

@endpush
