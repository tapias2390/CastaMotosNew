/**
 * Aplica el tema guardado ANTES de que se pinte la página (sección 41: modo
 * claro/oscuro). Se carga como <script> normal (no "defer"/"async") justo
 * después del <link rel="stylesheet"> en el <head> de cada página, así
 * bloquea el render hasta ejecutarse — evita el parpadeo de "oscuro por un
 * instante, luego cambia a claro" que se vería si esto corriera después del
 * <body>. Es intencionalmente mínimo (no depende de helpers.js/themeService.js,
 * que cargan más tarde) — la lógica completa del botón de tema vive en
 * frontend/js/services/themeService.js.
 */
(function () {
  try {
    if (localStorage.getItem('castamoto_theme') === 'light') {
      document.documentElement.setAttribute('data-theme', 'light');
    }
  } catch (error) {
    // localStorage puede fallar en navegación privada estricta; el sitio
    // simplemente arranca en el tema oscuro por defecto.
  }
})();
