<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CASTAMOTO — Todo para tu moto</title>
<meta name="description" content="Marketplace de motocicletas: repuestos, accesorios, cascos y servicios especializados.">
<!-- El sitio corre bajo un subdirectorio (ej. /proyectos/castamotos/), no en la raíz
     del dominio: <base> hace que TODAS las rutas relativas (CSS, JS, fetch, enlaces)
     se resuelvan contra él en vez de contra la raíz real del servidor. Único lugar a
     cambiar si el despliegue cambia de subcarpeta (ver APP_URL en backend/.env). -->
<base href="/proyectos/castamotos/">
<link rel="stylesheet" href="frontend/css/main.css">
<script src="frontend/js/theme-init.js"></script>
</head>
<body>

<div id="app-header"></div>

<section class="hero">
  <div class="container">
    <h1>Todo para tu moto, en un solo lugar</h1>
    <p>Repuestos, accesorios, cascos y servicios especializados para tu motocicleta.</p>
    <form id="hero-search-form" style="max-width:520px;margin:20px auto 0;display:flex;border-radius:10px;overflow:hidden;border:1px solid var(--gris-borde);">
      <label class="sr-only" for="hero-search-input">Buscar</label>
      <input class="form-control" id="hero-search-input" type="search" placeholder="¿Qué estás buscando?" style="border:none;border-radius:0;">
      <button class="btn btn-primary" type="submit">Buscar</button>
    </form>
  </div>
</section>

<main class="container">
  <div class="promo-banner">
    <div class="promo-banner__text">
      <h2>Todo para tu moto, con envío a todo el país</h2>
      <p>Repuestos, accesorios y servicios especializados en un solo lugar.</p>
    </div>
    <a class="btn btn-primary" href="productos">Explorar productos</a>
  </div>

  <section class="section">
    <h2 class="section__title">Categorías</h2>
    <div id="home-categories"><p class="loading-state">Cargando categorías…</p></div>
  </section>

  <section class="section" id="home-deals-section" hidden>
    <h2 class="section__title">Ofertas</h2>
    <div id="home-deals"></div>
  </section>

  <section class="section">
    <h2 class="section__title">Productos destacados</h2>
    <div id="home-products"><p class="loading-state">Cargando productos…</p></div>
  </section>

  <section class="section">
    <h2 class="section__title">Servicios destacados</h2>
    <div id="home-services"><p class="loading-state">Cargando servicios…</p></div>
  </section>

  <section class="section">
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
      <div class="card"><div class="card__body text-center"><strong style="color:var(--amarillo);">🔒 Compra segura</strong><p class="card__meta">Datos protegidos en cada pedido.</p></div></div>
      <div class="card"><div class="card__body text-center"><strong style="color:var(--amarillo);">🚚 Envíos a todo el país</strong><p class="card__meta">Entrega a domicilio o recogida en tienda.</p></div></div>
      <div class="card"><div class="card__body text-center"><strong style="color:var(--amarillo);">💬 Soporte especializado</strong><p class="card__meta">Servicios realizados por profesionales.</p></div></div>
    </div>
  </section>
</main>

<div id="app-footer"></div>

<script src="frontend/js/utils/helpers.js"></script>
<script src="frontend/js/services/apiService.js"></script>
<script src="frontend/js/services/authService.js"></script>
<script src="frontend/js/services/catalogService.js"></script>
<script src="frontend/js/services/cartService.js"></script>
<script src="frontend/js/services/themeService.js"></script>
<script src="frontend/js/components/layout.js"></script>
<script src="frontend/js/components/cards.js"></script>
<script src="frontend/js/pages/home.js"></script>
</body>
</html>
