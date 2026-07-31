@extends('app')
@section('title','Restablecer contraseña')
@section('content')
<section class="grid" style="grid-template-columns:1fr">
  <div class="hero">
    <div class="panel" style="background:#1157c2;color:#fff">
      <h1><span class="h-top" style="color:#7dd3fc">Restablecer contraseña</span></h1>

      <form method="POST" action="{{ route('password.update') }}" style="max-width:420px">
        @csrf
        <input type="hidden" name="token" value="{{ request('token') }}">
        <input type="hidden" name="email" value="{{ request('email') }}">

        <label>Nueva contraseña</label>
        <input type="password" name="password" required>

        <label>Confirmar contraseña</label>
        <input type="password" name="password_confirmation" required>

        <div class="modal-actions" style="justify-content:flex-start">
          <button class="btn" type="submit">Guardar</button>
          <a class="btn-outline" href="{{ route('login') }}">Volver al login</a>
        </div>
      </form>
    </div><div class="shadow"></div>
  </div>
</section>
@endsection
