<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Editar Rol</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
  <header class="wrap header">
    <div class="logo">WEBSITE LOGO</div>
  </header>

  <main class="wrap" style="padding-bottom:40px">
    <div class="panel" style="background:#1157c2;color:#fff">
      <h1><span class="h-top" style="font-size:34px;color:#fff">Editar Rol</span></h1>

      <form method="POST" action="{{ route('rols.update', $rol) }}" style="margin-top:20px">
        @csrf
        @method('PUT')

        <label>Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre', $rol->nombre) }}" class="input" style="width:100%;padding:10px;border-radius:10px;margin-bottom:10px">
        @error('nombre')<div style="color:#fee2e2">{{ $message }}</div>@enderror

        <label>Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $rol->slug) }}" class="input" style="width:100%;padding:10px;border-radius:10px;margin-bottom:10px">
        @error('slug')<div style="color:#fee2e2">{{ $message }}</div>@enderror

        <label>Descripción</label>
        <textarea name="descripcion" rows="3" class="input" style="width:100%;padding:10px;border-radius:10px;margin-bottom:10px">{{ old('descripcion', $rol->descripcion) }}</textarea>

        <div style="display:flex;gap:10px;margin-top:10px">
          <button class="btn" type="submit">Actualizar</button>
          <a href="{{ route('rols.index') }}" class="btn-outline">Cancelar</a>
        </div>
      </form>
    </div>
  </main>
</body>
</html>
