<?php
declare(strict_types=1);
/**
 * Placeholder de la raíz del proyecto durante la Fase 1 (arquitectura/config/BD).
 * El Home real (sección 41 del prompt maestro) se construye en fases posteriores.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CASTAMOTO — En construcción</title>
<style>
  :root {
    --negro: #0d0d0d;
    --amarillo: #f4c430;
    --gris: #1a1a1a;
    --blanco: #ffffff;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: var(--negro);
    color: var(--blanco);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    text-align: center;
    padding: 24px;
  }
  img {
    max-width: 220px;
    width: 60%;
    margin-bottom: 24px;
  }
  h1 {
    color: var(--amarillo);
    font-size: clamp(1.5rem, 5vw, 2.5rem);
    margin: 0 0 8px;
    letter-spacing: 1px;
  }
  p {
    color: #cfcfcf;
    max-width: 480px;
    line-height: 1.5;
  }
  .badge {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 20px;
    background: var(--gris);
    border: 1px solid var(--amarillo);
    border-radius: 8px;
    color: var(--amarillo);
    text-decoration: none;
    font-weight: 600;
    transition: transform .15s ease;
  }
  .badge:hover { transform: translateY(-2px); }
</style>
</head>
<body>
  <img src="frontend/assets/img/logo.png" alt="Logo CASTAMOTO">
  <h1>CASTAMOTO</h1>
  <p>Plataforma en construcción. La Fase 1 (arquitectura, configuración y base de datos) ya está lista.</p>
  <a class="badge" href="api/health">Ver estado de la API →</a>
</body>
</html>
