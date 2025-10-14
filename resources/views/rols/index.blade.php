<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Roles</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
  <header class="wrap header">
    <div class="logo">WEBSITE LOGO</div>
    <nav class="nav">
      <a href="{{ url('/') }}">HOME</a>
      <a href="{{ route('rols.index') }}" class="hover:text-sky-600">ROLES</a>
    </nav>
  </header>

  <main class="wrap" style="padding-bottom:40px">
    <section class="grid" style="grid-template-columns:1fr">
      <div class="hero">
        <div class="panel" style="background:#1157c2; color:#fff">
          <h1><span class="h-top" style="color:#fff;font-size:38px">LISTA DE ROLES</span></h1>

          @if (session('success'))
            <div class="alert">{{ session('success') }}</div>
          @endif

          <div class="toolbar">
            <form class="search" method="GET" action="{{ route('rols.index') }}">
              <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar rol...">
              <button type="submit">Buscar</button>
            </form>

            <a class="btn" href="{{ route('rols.create') }}">+ Nuevo rol</a>
          </div>

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
                  <td>{{ $rol->created_at->format('Y-m-d') }}</td>
                  <td>
                    <div class="actions">
                      <a class="action view" href="{{ route('rols.show', $rol) }}">Ver</a>
                      <a class="action edit" href="{{ route('rols.edit', $rol) }}">Editar</a>
                      <form action="{{ route('rols.destroy', $rol) }}" method="POST" onsubmit="return confirm('¿Eliminar el rol {{ $rol->nombre }}?');">
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
            <div class="empty">No hay roles registrados.</div>
          @endif
        </div>
        <div class="shadow"></div>
      </div>
    </section>
  </main>

  <footer>© {{ date('Y') }} Pharmacy. All rights reserved.</footer>
</body>
</html>
