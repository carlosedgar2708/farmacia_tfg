@extends('layouts.app')
@section('title','Recuperar contraseña')
@section('content')
<section class="grid" style="grid-template-columns:1fr">
  <div class="hero">
    <div class="panel" style="background:#1157c2;color:#fff">
      <h1><span class="h-top" style="color:#7dd3fc">Recuperar contraseña</span></h1>

      @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif

      <form method="POST" action="{{ route('password.email') }}" style="max-width:420px">
        @csrf
        <label>Correo</label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        <div class="modal-actions" style="justify-content:flex-start">
          <button class="btn" type="submit">Enviar enlace</button>
          <a class="btn-outline" href="{{ route('login') }}">Volver</a>
        </div>
      </form>
    </div><div class="shadow"></div>
  </div>
</section>
@endsection
