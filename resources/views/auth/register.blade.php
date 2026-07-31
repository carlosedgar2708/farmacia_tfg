@extends('app')
@section('title', 'Crear cuenta')
@section('content')
<div class="login-shell">
  <x-card class="login-card" title="Crear cuenta" icon="ri-user-add-line">
    <form method="POST" action="{{ route('register') }}">
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
        <x-button type="submit" variant="primary">Registrarme</x-button>
        <x-button variant="secondary" href="{{ route('login') }}">Ya tengo cuenta</x-button>
      </div>
    </form>
  </x-card>
</div>
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
