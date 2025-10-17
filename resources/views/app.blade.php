<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title', 'Pharmacy')</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">

  {{-- Ajustes mínimos de estilo para mejorar estética sin romper tu CSS --}}
  <style>
    .site-header { backdrop-filter: blur(6px); }
    .brand { font-weight: 800; letter-spacing: .5px; }
    .nav a { margin-right: 14px; text-decoration: none; }
    .nav a.active { border-bottom: 2px solid currentColor; padding-bottom: 2px; }
    .nav-actions { display: flex; align-items: center; gap: 12px; }
    .flash { margin: 16px auto; max-width: 1100px; }
    .flash > .alert { margin-bottom: 0; }
    .burger { display: none; background: transparent; border: 0; font-size: 22px; cursor: pointer; }
    @media (max-width: 900px) {
      .nav { display: none; flex-direction: column; gap: 10px; padding-top: 8px; }
      .nav.open { display: flex; }
      .burger { display: inline-block; }
      .nav-actions form { display: inline; }
    }
  </style>
</head>

<body>
  {{-- Header / Navbar global --}}
  <header class="wrap header site-header" style="display:flex;align-items:center;justify-content:space-between;">
    <div style="display:flex;align-items:center;gap:14px;">
      <div class="logo brand">Farmacia</div>
      <button id="burger" class="burger" aria-label="Abrir menú">☰</button>
    </div>

    <nav id="main-nav" class="nav" style="display:flex;align-items:center;">
      {{-- <a href="{{ url('/') }}"
         class="{{ request()->is('/') ? 'active' : '' }}">HOME</a>--}}

      @auth
        @if (Route::has('productos.index'))
        <a href="{{ route('productos.index') }}" class="{{ request()->routeIs('productos.*') ? 'active' : '' }}">
            Productos
        </a>
        @endif

        {{-- Proveedors --}}
        @if (Route::has('proveedors.index'))
          <a href="{{ route('proveedors.index') }}"
             class="{{ request()->routeIs('proveedors.*') ? 'active' : '' }}">
             Proveedores
          </a>
        @endif

        {{-- Roles --}}
        @if (Route::has('rols.index'))
          <a href="{{ route('rols.index') }}"
             class="{{ request()->routeIs('rols.*') ? 'active' : '' }}">
             Roles
          </a>
        @endif

        {{-- Usuarios --}}
        @if (Route::has('users.index'))
          <a href="{{ route('users.index') }}"
             class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
             Usuarios
          </a>
        @endif
      @endauth
    </nav>

    <div class="nav-actions">
      @auth
        <span style="opacity:.85;">{{ auth()->user()->name ?? 'Usuario' }}</span>
        <form action="{{ route('logout') }}" method="POST">
          @csrf
          <button type="submit" class="btn-outline">Salir</button>
        </form>
      @else
        @if (Route::has('login'))
          <a class="btn-outline" href="{{ route('login') }}">Ingresar</a>
        @endif
      @endauth
    </div>
  </header>

  {{-- Flash messages globales --}}
  <div class="flash wrap">
    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger">
        <strong>Revisa los campos:</strong>
        <ul style="margin:6px 0 0 16px;">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif
  </div>

  {{-- Contenido variable --}}
  <main class="wrap" style="padding-bottom:40px">
    @yield('content')
  </main>

  <footer>© {{ date('Y') }} Pharmacy. All rights reserved.</footer>

  @stack('modals')
  @stack('scripts')

  <script>
    // Toggle menú móvil
    (function() {
      const burger = document.getElementById('burger');
      const nav = document.getElementById('main-nav');
      burger?.addEventListener('click', () => {
        nav.classList.toggle('open');
      });
    })();
  </script>
</body>
</html>
