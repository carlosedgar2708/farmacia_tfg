<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pharmacy | Landing</title>

  <!-- Fuente Google -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">

  <!-- Tu CSS externo -->
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
  <header class="wrap header">
    <div class="logo">FARMACIA KATY</div>
    <nav class="nav">

      <a href="https://wa.me/59161531769" target="_blank" rel="noopener noreferrer">AYUDA</a>
    </nav>
  </header>

  <main class="wrap">
    <section class="grid">
      <div>
        <h1>
          <span class="h-top">TU SALUD</span><br>
          <span class="h-sub">NUESTRA MISIÓN</span>
        </h1>
        <p>
         Control total, reportes automáticos y una experiencia diseñada para optimizar cada proceso.
        </p>
        <!-- Redirige al login -->
        <a href="{{ route('login') }}" class="btn">Iniciar Sesion</a>
      </div>

      <div class="hero">
        <div class="panel">
          <div style="display:flex;gap:16px;align-items:center">
            <div class="imgbox" style="flex:1">
              <img src="{{ asset('images/pharmacy-hero.png') }}" alt="Pharmacist">
            </div>
            <div class="shelf">
              <div class="rack">
                <div class="row">
                  <div class="p1"></div><div class="p2"></div><div class="p3"></div><div class="p2"></div>
                </div>
                <div class="row">
                  <div class="p1"></div><div class="p4"></div><div class="p3"></div>
                </div>
                <div class="row">
                  <div class="p4"></div><div class="p3"></div><div class="p5"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="cross"></div>
        </div>
        <div class="shadow"></div>

        <!-- bubbles -->
        <span class="bubble b1"></span>
        <span class="bubble b2"></span>
        <span class="bubble b3"></span>
        <span class="bubble b4"></span>
        <span class="bubble b5"></span>
      </div>
    </section>
  </main>

  <footer>
    © {{ date('Y') }} Farmacia Katy. All rights reserved.
  </footer>
</body>
</html>
