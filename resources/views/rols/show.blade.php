<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ver Rol</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
  <header class="wrap header">
    <div class="logo">WEBSITE LOGO</div>
  </header>

  <main class="wrap" style="padding-bottom:40px">
    <div class="panel" style="background:#1157c2;color:#fff">
      <h1><span class="h-top" style="font-size:34px;color:#fff">Detalles del Rol</span></h1>

      <p><strong>ID:</strong> {{ $rol->id }}</p>
      <p><strong>Nombre:</strong> {{ $rol->nombre }}</p>
      <p><strong>Slug:</strong> {{ $rol->slug }}</p>
      <p><strong>Descripción:</strong> {{ $rol->descripcion ?? '-' }}</p>
      <p><strong>Creado el:</strong> {{ $rol->created_at->format('Y-m-d H:i') }}</p>

      <div style="margin-top:20px">
        <a href="{{ route('rols.edit', $rol) }}" class="btn">Editar</a>
        <a href="{{ route('rols.index') }}" class="btn-outline">Volver</a>
      </div>
    </div>
  </main>
</body>
</html>
