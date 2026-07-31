@extends('app')
@section('title','Iniciar sesión')

@section('content')
<div class="login-shell">
  <x-card class="login-card">
    <div class="form-head">
      <h2>Inicio de sesión</h2>
      <p>Ingresa tus credenciales para continuar.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="login-form">
      @csrf

      <label class="field">
        <span class="fi"><i class="ri-mail-line"></i></span>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Correo electrónico">
      </label>
      @error('email') <small class="field-error">{{ $message }}</small> @enderror

      <label class="field">
        <span class="fi"><i class="ri-lock-2-line"></i></span>
        <input id="password" type="password" name="password" required placeholder="Contraseña">
      </label>
      @error('password') <small class="field-error">{{ $message }}</small> @enderror

      <label class="remember">
        <input type="checkbox" name="remember">
        <span>Recordarme</span>
      </label>

      <x-button type="submit" variant="primary" style="width:100%;height:46px">Iniciar sesión</x-button>
    </form>
  </x-card>
</div>
@endsection

@push('styles')
<style>
/* Ocultar sidebar solo en login */
body.auth .sidebar{ display:none !important; }
body.auth .layout.eres{ grid-template-columns:1fr !important; }

.form-head h2{ margin:0; color:var(--primary); font-weight:700; font-size:28px; }
.form-head p{ margin:6px 0 18px; color:var(--muted); }

.login-form{ display:flex; flex-direction:column; gap:14px; }

.field{
  display:grid; grid-template-columns: 28px 1fr; gap:10px; align-items:center;
  padding:4px 2px 6px;
  border-bottom:2px solid var(--line);
}
.field:focus-within{ border-color: var(--primary); }
.field .fi{ color:#94a3b8; font-size:18px; display:grid; place-items:center; }
.field input{
  border:0; outline:0; background:transparent;
  padding:8px 2px; font:inherit; color:var(--ink);
}

.remember{ display:flex; align-items:center; gap:8px; margin-top:4px; color:var(--muted); }
.remember input{ transform:scale(1.05); }
</style>
@endpush

@push('scripts')
<script>
// marca el body para ocultar la sidebar solo aquí
document.body.classList.add('auth');
window.addEventListener('pagehide', ()=>document.body.classList.remove('auth'));
</script>
@endpush
