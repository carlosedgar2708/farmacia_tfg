@extends('app')

@section('title', 'Iniciar sesión')

@section('content')
<section class="grid" style="grid-template-columns:1fr">
  <div class="hero">
    <div class="panel" style="background:#1157c2;color:#fff">
      <h1><span class="h-top" style="color:#7dd3fc">Inicia sesión</span></h1>

      @if ($errors->any())
        <div class="alert" style="background:#fee2e2;border-color:#fecaca;color:#7f1d1d">
          <ul style="margin:0;padding-left:18px">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

        <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <div class="field">
            <label for="email">Correo</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="field">
            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password" required>
        </div>

        <label class="remember">
            <input type="checkbox" name="remember">
            <span>Recordarme</span>
        </label>

        <div class="auth-actions">
            <button class="btn" type="submit">Entrar</button>

            @if (Route::has('register'))
            <a class="btn-outline" href="{{ route('register') }}">Crear cuenta</a>
            @endif
        </div>
        </form>

    </div>
    <div class="shadow"></div>
  </div>
</section>
@endsection
