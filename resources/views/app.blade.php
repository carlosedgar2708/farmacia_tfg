<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title', 'Pharmacy')</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
  <!-- Navbar global -->
  <header class="wrap header">
    <div class="logo">Farmacia TuMama :v</div>
    <nav class="nav">
    <a href="{{ url('/') }}">HOME</a>
    @auth
        <a href="{{ route('rols.index') }}">Roles</a>
        <a href="{{ route('users.index') }}">Usuarios</a>
        <form action="{{ route('logout') }}" method="POST" style="display:inline">
        @csrf
        <button type="submit" class="btn-outline" style="margin-left:12px">Salir</button>
        </form>
    @else
        <a href="{{ route('login') }}">Ingresar</a>
        @if (Route::has('register'))
        @endif
    @endauth
    </nav>

  </header>

  <!-- Contenido variable -->
  <main class="wrap" style="padding-bottom:40px">
    @yield('content')
  </main>

  <footer>© {{ date('Y') }} Pharmacy. All rights reserved.</footer>
  @stack('modals')
@stack('scripts')

</body>

</html>
