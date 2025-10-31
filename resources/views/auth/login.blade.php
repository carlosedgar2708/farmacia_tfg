@extends('app')
@section('title','Iniciar sesión')

@section('content')
<div class="login-shell">
  <div class="login-card">
    {{-- Lado ilustración (izquierda) --}}
    <div class="login-left">
      <div class="blob">
        <img src="{{ asset('images/pharmacy-hero.png') }}" alt="Pharmacy" class="hero-img">
      </div>
      <div class="brand-chip">
        <i class="ri-shield-cross-line"></i> Farmacia Katy
      </div>
    </div>

    {{-- Lado formulario (derecha) --}}
    <div class="login-right">
      <div class="form-head">
        <h2>Inicio de sesión</h2>
        <p>Ingresa tus credenciales para continuar.</p>
      </div>

      @if ($errors->any())
        <div class="alert alert-danger login-alert">
          <ul>
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('login') }}" class="login-form">
        @csrf

        <label class="field">
          <span class="fi"><i class="ri-mail-line"></i></span>
          <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Correo electrónico">
        </label>

        <label class="field">
          <span class="fi"><i class="ri-lock-2-line"></i></span>
          <input id="password" type="password" name="password" required placeholder="Contraseña">
        </label>

        <label class="remember">
          <input type="checkbox" name="remember">
          <span>Recordarme</span>
        </label>

        <button type="submit" class="btn-login">Iniciar sesión</button>
      </form>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
/* ===== Paleta base (usa la tuya) ===== */
:root{
  --primary:#357c90;   /* azul petróleo */
  --accent:#f27d72;    /* coral */
  --success:#c4e6b0;   /* verde claro */
  --ink:#1f2937;
  --muted:#64748b;
  --bg:#f6f8fb;
  --card:#ffffff;
  --line:#e5e7eb;
}

/* ===== Ocultar sidebar SOLO en login ===== */
body.auth .sidebar{ display:none !important; }
body.auth .layout.eres{ grid-template-columns:1fr !important; }

/* ===== Contenedor general ===== */
.login-shell{
  min-height: calc(100vh - 44px);
  display:grid; place-items:center;
}
.login-card{
  width:min(1140px, 92vw);
  background:#fff;
  border:1px solid var(--line);
  border-radius:28px;
  box-shadow:0 30px 80px rgba(0,0,0,.10);
  display:grid;
  grid-template-columns: 1.1fr .9fr;
  overflow:hidden;
}
@media (max-width: 980px){
  .login-card{ grid-template-columns:1fr; }
}

/* ===== Izquierda (ilustración) ===== */
.login-left{
  position:relative;
  background: radial-gradient(1100px 480px at -15% 0%, rgba(255,255,255,.35), transparent 60%),
              radial-gradient(900px 360px at 120% 100%, rgba(255,255,255,.25), transparent 60%),
              linear-gradient(135deg, var(--primary) 0%, var(--success) 100%);
  padding:28px;
  display:grid; place-items:center;
}
.blob{
  position:relative;
  width:100%; max-width:520px;
  aspect-ratio: 4/3;
  background:linear-gradient(160deg, rgba(255,255,255,.22), rgba(255,255,255,.08));
  border-radius:36px;
  box-shadow: inset 0 0 0 1px rgba(255,255,255,.25);
  display:grid; place-items:center;
}
.hero-img{
  width:min(360px, 70%);
  height:auto; display:block; filter:drop-shadow(0 10px 28px rgba(0,0,0,.18));
}
.brand-chip{
  position:absolute; top:16px; left:16px;
  background:rgba(255,255,255,.9);
  border:1px solid rgba(255,255,255,.8);
  color:var(--primary);
  padding:6px 10px; border-radius:999px; font-weight:800; font-size:12px;
  display:inline-flex; align-items:center; gap:6px;
}

/* ===== Derecha (formulario) ===== */
.login-right{
  padding:30px 32px 34px;
  display:flex; flex-direction:column; justify-content:center;
}
.form-head h2{
  margin:0; color:var(--primary); font-weight:900; font-size:28px;
}
.form-head p{ margin:6px 0 18px; color:var(--muted); }

.login-alert{
  margin:0 0 12px;
  padding:10px 12px; border-radius:12px;
}

.login-form{ display:flex; flex-direction:column; gap:14px; }

/* Campo con icono + estilo subrayado (como el diseño) */
.field{
  display:grid; grid-template-columns: 28px 1fr; gap:10px; align-items:center;
  padding:4px 2px 6px;
  border-bottom:2px solid #e2e8f0;
}
.field:focus-within{ border-color: var(--primary); }
.field .fi{ color:#94a3b8; font-size:18px; display:grid; place-items:center; }
.field input{
  border:0; outline:0; background:transparent;
  padding:8px 2px; font:inherit; color:var(--ink);
}

/* Recordarme + botón */
.remember{ display:flex; align-items:center; gap:8px; margin-top:4px; color:var(--muted); }
.remember input{ transform:scale(1.05); }

.btn-login{
  height:46px; border-radius:999px; border:0; cursor:pointer;
  color:#fff; font-weight:900; letter-spacing:.2px;
  background: linear-gradient(135deg, #2c6e7e 0%, #3e9ab0 100%);
  box-shadow:0 12px 28px rgba(53,124,144,.25);
}
.btn-login:hover{ filter:brightness(.98); }

/* Ajustes responsivos */
@media (max-width: 520px){
  .form-head h2{ font-size:22px; }
  .btn-login{ height:44px; }
}
</style>
@endpush

@push('scripts')
<script>
// marca el body para ocultar la sidebar solo aquí
document.body.classList.add('auth');
window.addEventListener('pagehide', ()=>document.body.classList.remove('auth'));
</script>
@endpush
