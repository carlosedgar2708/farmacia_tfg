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
    <div class="logo">WEBSITE LOGO</div>
    <nav class="nav">
      <a href="#">HOME</a><a href="#">NEWS</a><a href="#">PROJECT</a><a href="#">ABOUT US</a>
    </nav>
  </header>

  <main class="wrap">
    <section class="grid">
      <div>
        <h1><span class="h-top">PHARMACY</span><br><span class="h-sub">LANDING PAGE</span></h1>
        <p>
          Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut
          laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation.
        </p>
        <a href="#" class="btn">LEARN MORE</a>
      </div>

      <div class="hero">
        <div class="panel">
          <div style="display:flex;gap:16px;align-items:center">
            <div class="imgbox" style="flex:1">
              <img src="{{ asset('images/pharmacy-hero.png') }}" alt="Pharmacist">
            </div>
            <div class="shelf">
              <div class="rack">
                <div class="row"><div class="p1"></div><div class="p2"></div><div class="p3"></div><div class="p2"></div></div>
                <div class="row"><div class="p1"></div><div class="p4"></div><div class="p3"></div></div>
                <div class="row"><div class="p4"></div><div class="p3"></div><div class="p5"></div></div>
              </div>
            </div>
          </div>
          <div class="cross"></div>
        </div>
        <div class="shadow"></div>

        <!-- bubbles -->
        <span class="bubble b1"></span><span class="bubble b2"></span>
        <span class="bubble b3"></span><span class="bubble b4"></span><span class="bubble b5"></span>
      </div>
    </section>
  </main>

  <footer>
    © {{ date('Y') }} Pharmacy. All rights reserved.
  </footer>
</body>
</html>
