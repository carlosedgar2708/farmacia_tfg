@extends('app')
@section('title', 'Crear cuenta')
@section('content')
<section class="grid" style="grid-template-columns:1fr">
  <div class="hero">
    <div class="panel" style="background:#1157c2;color:#fff">
      <h1><span class="h-top" style="color:#7dd3fc">Crear cuenta</span></h1>

      <form method="POST" action="{{ route('register') }}" style="max-width:420px">
        @csrf
        <label>Nombre</label>
        <input type="text" name="name" required value="{{ old('name') }}">
        @error('name') <small class="field-error">{{ $message }}</small> @enderror

        <label>Correo</label>
        <input type="email" name="email" required value="{{ old('email') }}">
        @error('email') <small class="field-error">{{ $message }}</small> @enderror

        <label>Contraseña</label>
        <input type="password" name="password" required>
        @error('password') <small class="field-error">{{ $message }}</small> @enderror

        <label>Confirmar contraseña</label>
        <input type="password" name="password_confirmation" required>
        @error('password_confirmation') <small class="field-error">{{ $message }}</small> @enderror

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

@push('styles')
<style>
/* Oculta el sidebar solo en esta pantalla (mismo mecanismo que auth/login.blade.php) */
body.auth .sidebar{ display:none !important; }
body.auth .layout.eres{ grid-template-columns:1fr !important; }
</style>
@endpush

@push('scripts')
<script>
document.body.classList.add('auth');
window.addEventListener('pagehide', ()=>document.body.classList.remove('auth'));
</script>
@endpush
