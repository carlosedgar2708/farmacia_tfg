@extends('layouts.app')
@section('title', 'Crear cuenta')
@section('content')
<section class="grid" style="grid-template-columns:1fr">
  <div class="hero">
    <div class="panel" style="background:#1157c2;color:#fff">
      <h1><span class="h-top" style="color:#7dd3fc">Crear cuenta</span></h1>

      @if ($errors->any())
        <div class="alert" style="background:#fee2e2;border-color:#fecaca;color:#7f1d1d">
          <ul style="margin:0;padding-left:18px">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('register') }}" style="max-width:420px">
        @csrf
        <label>Nombre</label>
        <input type="text" name="name" required value="{{ old('name') }}">

        <label>Correo</label>
        <input type="email" name="email" required value="{{ old('email') }}">

        <label>Contraseña</label>
        <input type="password" name="password" required>

        <label>Confirmar contraseña</label>
        <input type="password" name="password_confirmation" required>

        <div class="modal-actions" style="justify-content:flex-start">
          <button class="btn" type="submit">Registrarme</button>
          <a class="btn-outline" href="{{ route('login') }}">Ya tengo cuenta</a>
        </div>
      </form>
    </div>
    <div class="shadow"></div>
  </div>
</section>
@endsection
