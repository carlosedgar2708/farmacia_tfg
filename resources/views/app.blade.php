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
      <a href="{{ url('/') }}"></a>
      <a href="{{ route('rols.index') }}">ROLES</a>
      <a href="#">Compras</a>
      <a href="#">Ventas</a>
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
