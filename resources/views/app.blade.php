<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Pharmacy')</title>

  {{-- Iconos Remix --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css"/>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">

  {{-- 👇 Esto permitirá que tus vistas como login usen su propio CSS interno --}}
  @stack('styles')
</head>

<body>
  {{-- Iconos Remix --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css"/>
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
  <div class="layout eres">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar">
      <div class="sb-top">
        <a href="{{ url('/') }}" class="brand">
          <i class="ri-shield-cross-line"></i>
          <span>Farmacia Katy</span>
        </a>

        <button id="btn-collapse" class="ghost" aria-label="Colapsar">
          <i class="ri-sidebar-fold-line"></i>
        </button>
      </div>

      {{-- (Opcional) buscador dentro de la barra --}}
      <div class="sb-search">
        <i class="ri-search-line"></i>
        <input type="text" placeholder="Buscar…" />
      </div>

      <nav class="sb-nav">
        <div class="sb-section">Navegación</div>
        <a href="{{ url('/inicio') }}"
           class="sb-item {{ request()->is('inicio') ? 'active' : '' }}">
          <i class="ri-dashboard-2-line"></i><span>Dashboard</span>
        </a>

        @auth
          <div class="sb-section">Gestión</div>

          @if (Route::has('productos.index'))
          <a href="{{ route('productos.index') }}" class="sb-item {{ request()->routeIs('productos.*') ? 'active' : '' }}">
            <i class="ri-capsule-line"></i><span>Productos</span>
          </a>
          @endif

          {{-- Lotes (si tienes ruta propia; ajusta nombre si aplica) --}}
          @if (Route::has('lotes.index'))
          <a href="{{ route('lotes.index') }}" class="sb-item {{ request()->routeIs('lotes.*') ? 'active' : '' }}">
            <i class="ri-price-tag-3-line"></i><span>Lotes</span>
          </a>
          @endif

          @if (Route::has('proveedors.index'))
          <a href="{{ route('proveedors.index') }}" class="sb-item {{ request()->routeIs('proveedors.*') ? 'active' : '' }}">
            <i class="ri-truck-line"></i><span>Proveedores</span>
          </a>
          @endif

          @if (Route::has('clientes.index'))
          <a href="{{ route('clientes.index') }}" class="sb-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
            <i class="ri-user-3-line"></i><span>Clientes</span>
          </a>
          @endif

          @if (Route::has('ventas.index'))
          <a href="{{ route('ventas.index') }}" class="sb-item {{ request()->routeIs('ventas.*') ? 'active' : '' }}">
            <i class="ri-shopping-bag-3-line"></i><span>Ventas</span>
          </a>
          @endif

          @if (Route::has('rols.index'))
          <a href="{{ route('rols.index') }}" class="sb-item {{ request()->routeIs('rols.*') ? 'active' : '' }}">
            <i class="ri-lock-2-line"></i><span>Roles</span>
          </a>
          @endif

          @if (Route::has('users.index'))
          <a href="{{ route('users.index') }}" class="sb-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <i class="ri-team-line"></i><span>Usuarios</span>
          </a>
          @endif

          <div class="sb-section">Casos de uso</div>
          {{-- **Mapea tus CU** a vistas reales cuando existan rutas. Por ahora dejo # cuando no haya ruta.
          <a href="{{ Route::has('login') ? route('login') : '#' }}" class="sb-item">
            <i class="ri-login-box-line"></i><span>CU01 Autenticarse</span>
          </a>
          <a href="{{ Route::has('users.index') ? route('users.index') : '#' }}" class="sb-item">
            <i class="ri-user-settings-line"></i><span>CU02 Gestionar usuarios</span>
          </a>
          <a href="{{ Route::has('rols.index') ? route('rols.index') : '#' }}" class="sb-item">
            <i class="ri-key-2-line"></i><span>CU03 Gestionar roles</span>
          </a>
          <a href="{{ Route::has('rols.index') ? route('rols.index') : '#' }}" class="sb-item">
            <i class="ri-checkbox-multiple-line"></i><span>CU04 Asignar permisos</span>
          </a>
          <a href="{{ Route::has('proveedors.index') ? route('proveedors.index') : '#' }}" class="sb-item">
            <i class="ri-store-2-line"></i><span>CU05 Proveedores</span>
          </a>
          <a href="{{ Route::has('clientes.index') ? route('clientes.index') : '#' }}" class="sb-item">
            <i class="ri-user-heart-line"></i><span>CU06 Clientes</span>
          </a>
          <a href="{{ Route::has('productos.index') ? route('productos.index') : '#' }}" class="sb-item">
            <i class="ri-capsule-fill"></i><span>CU07 Productos</span>
          </a>
          <a href="{{ Route::has('lotes.index') ? route('lotes.index') : '#' }}" class="sb-item">
            <i class="ri-stack-line"></i><span>CU08 Controlar stock</span>
          </a>
          <a href="#" class="sb-item">
            <i class="ri-file-list-2-line"></i><span>CU09 Registrar compras</span>
          </a>
          <a href="{{ Route::has('ventas.index') ? route('ventas.index') : '#' }}" class="sb-item">
            <i class="ri-bill-line"></i><span>CU10 Registrar ventas</span>
          </a>
          <a href="#" class="sb-item">
            <i class="ri-receipt-line"></i><span>CU11 Comprobante/recibo</span>
          </a>
          <a href="#" class="sb-item">
            <i class="ri-arrow-go-back-line"></i><span>CU12 Devoluciones</span>
          </a>
          <a href="#" class="sb-item">
            <i class="ri-bar-chart-2-line"></i><span>CU13 Reportes</span>
          </a>
          <a href="{{ Route::has('lotes.index') ? route('lotes.index') : '#' }}" class="sb-item">
            <i class="ri-exchange-dollar-line"></i><span>CU14 Mov. de stock</span>
          </a>
          <a href="{{ Route::has('ventas.index') ? route('ventas.index') : '#' }}" class="sb-item">
            <i class="ri-user-follow-line"></i><span>CU15 Asociar cliente a venta</span>
          </a>--}}
        @endauth
      </nav>

      <div class="sb-bottom">
        <button id="btn-mobile" class="ghost" aria-label="Abrir menú">
          <i class="ri-menu-line"></i>
        </button>

        <div class="sb-user">
          @auth
            <img class="avatar" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'U') }}&background=357c90&color=fff" alt="avatar">
            <div class="u-info">
              <strong>{{ auth()->user()->name ?? 'Usuario' }}</strong>
              <small>En línea</small>
            </div>
            <form action="{{ route('logout') }}" method="POST">
              @csrf
              <button type="submit" class="btn-logout"><i class="ri-logout-circle-r-line"></i></button>
            </form>
          @else
            @if (Route::has('login'))
              <a href="{{ route('login') }}" class="btn-logout"><i class="ri-login-box-line"></i></a>
            @endif
          @endauth
        </div>
      </div>
    </aside>

    <!-- Contenido -->
    <main class="main">
      <div class="main-top">
        <button id="btn-open" class="ghost only-mobile" aria-label="Abrir">
          <i class="ri-menu-2-line"></i>
        </button>
        <h1 class="page-title">@yield('title','Dashboard')</h1>
      </div>

      {{-- flashes --}}
      <div class="flash">
        @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if (session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
        @if ($errors->any())
          <div class="alert alert-danger">
            <strong>Revisa los campos:</strong>
            <ul style="margin:6px 0 0 16px;">
              @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
          </div>
        @endif
      </div>

      <section class="content">
        @yield('content')
      </section>

      <footer>© {{ date('Y') }} Farmacia Katy. Todos los derechos reservados.</footer>
    </main>
  </div>

  @stack('modals')
  @stack('scripts')

  {{-- JS Sidebar --}}
  <script>
    (function () {
      const sidebar   = document.getElementById('sidebar');
      const btnCol    = document.getElementById('btn-collapse');
      const btnOpen   = document.getElementById('btn-open');
      const btnMobile = document.getElementById('btn-mobile');

      // estado persistente (colapsado en desktop)
      const k = 'sb-collapsed';
      if (localStorage.getItem(k) === '1') sidebar.classList.add('collapsed');

      btnCol?.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem(k, sidebar.classList.contains('collapsed') ? '1' : '0');
      });

      // móvil: abrir/cerrar
      const toggleMobile = () => sidebar.classList.toggle('open');
      btnOpen?.addEventListener('click', toggleMobile);
      btnMobile?.addEventListener('click', toggleMobile);

      // cerrar tocando fuera en móvil
      document.addEventListener('click', (e) => {
        if (window.innerWidth <= 900 && sidebar.classList.contains('open')) {
          if (!sidebar.contains(e.target) && !btnOpen.contains(e.target)) {
            sidebar.classList.remove('open');
          }
        }
      });
    })();
  </script>
</body>

