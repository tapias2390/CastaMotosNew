/**
 * Términos y condiciones (sección 6): contenido real guardado en la base
 * (site_settings, migración 047), no un texto fijo en el HTML — si algún día
 * se agrega un editor en /admin (README: "falta un editor de configuración
 * general del sitio"), esta página lo refleja solo con volver a cargar.
 */
async function initTermsPage() {
  const mount = document.getElementById('terms-mount');

  try {
    const { content } = await settingsService.terms();
    mount.textContent = content || 'Todavía no se ha publicado el texto de términos y condiciones.';
  } catch (error) {
    mount.innerHTML = `<p class="error-state">No fue posible cargar los términos y condiciones.</p>`;
  }
}

document.addEventListener('DOMContentLoaded', initTermsPage);
