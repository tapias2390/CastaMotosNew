/**
 * Detalle de servicio (sección 12). Más simple que el de producto: sin
 * variantes ni stock, pero con la misma sección de compartir (sección 17).
 */
function serviceShareLinks(service) {
  const url = service.canonical_url || window.location.href;
  const text = encodeURIComponent(`Mira "${service.name}" en CASTAMOTO`);
  const encodedUrl = encodeURIComponent(url);

  return {
    whatsapp: `https://wa.me/?text=${text}%20${encodedUrl}`,
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
    x: `https://twitter.com/intent/tweet?text=${text}&url=${encodedUrl}`,
    telegram: `https://t.me/share/url?url=${encodedUrl}&text=${text}`,
    url,
  };
}

function renderServiceDetail(service) {
  const image = service.images && service.images.length > 0 ? helpers.mediaUrl('services', service.images[0].url) : null;
  const links = serviceShareLinks(service);
  const breadcrumbCategory = service.category_slug
    ? `<a href="categoria/${encodeURIComponent(service.category_slug)}">${helpers.escapeHtml(service.category_name)}</a>`
    : helpers.escapeHtml(service.category_name || '');

  document.getElementById('service-detail-mount').innerHTML = `
    <nav class="breadcrumbs" aria-label="Ruta de navegación">
      <a href=".">Inicio</a> › <a href="servicios">Servicios</a>
      ${breadcrumbCategory ? ` › ${breadcrumbCategory}` : ''} › <span aria-current="page">${helpers.escapeHtml(service.name)}</span>
    </nav>
    <div class="detail-grid mt-16">
      <div class="detail-gallery__main">
        ${image ? `<img src="${image}" alt="${helpers.escapeHtml(service.name)}">` : 'Sin imagen disponible'}
      </div>
      <div>
        <h1>${helpers.escapeHtml(service.name)}</h1>
        ${service.category_name ? `<p style="color:var(--gris-texto);">${helpers.escapeHtml(service.category_name)}</p>` : ''}
        <p style="font-size:1.8rem;font-weight:800;color:var(--amarillo);margin:12px 0 4px;">${helpers.formatCurrency(service.price)}</p>
        ${service.duration_minutes ? `<p style="color:var(--gris-texto);">Duración estimada: ${service.duration_minutes} min</p>` : ''}
        ${service.location ? `<p style="color:var(--gris-texto);">📍 ${helpers.escapeHtml(service.location)}</p>` : ''}

        <button class="btn btn-primary" id="add-service-to-cart-btn">Agregar al carrito</button>
        <button class="btn btn-secondary" id="service-favorite-btn">
          ${service.is_favorite ? '♥ En favoritos' : '♡ Agregar a favoritos'}
        </button>

        <div class="share-row">
          <a class="share-btn" href="${links.whatsapp}" target="_blank" rel="noopener">WhatsApp</a>
          <a class="share-btn" href="${links.facebook}" target="_blank" rel="noopener">Facebook</a>
          <a class="share-btn" href="${links.x}" target="_blank" rel="noopener">X</a>
          <a class="share-btn" href="${links.telegram}" target="_blank" rel="noopener">Telegram</a>
          <button class="share-btn" id="service-copy-link-btn">Copiar enlace</button>
        </div>

        ${service.description ? `<div class="mt-16" style="color:var(--gris-texto);">${helpers.escapeHtml(service.description)}</div>` : ''}
        ${service.cancellation_policy ? `<p class="mt-16" style="font-size:0.8rem;color:var(--gris-texto);"><strong>Política de cancelación:</strong> ${helpers.escapeHtml(service.cancellation_policy)}</p>` : ''}
      </div>
    </div>
  `;

  document.getElementById('add-service-to-cart-btn').addEventListener('click', async () => {
    try {
      await cartService.addItem({ service_id: service.id, quantity: 1 });
      helpers.toast('Servicio agregado al carrito.', 'success');
      refreshCartBadge();
    } catch (error) {
      helpers.toast(helpers.flattenErrors(error.fields) || error.message, 'error');
    }
  });

  document.getElementById('service-favorite-btn').addEventListener('click', async () => {
    if (!authService.isAuthenticated()) {
      helpers.toast('Inicia sesión para guardar favoritos.', 'error');
      return;
    }
    try {
      if (service.is_favorite) {
        await catalogService.removeFavorite('service', service.id);
        service.is_favorite = false;
      } else {
        await catalogService.addFavorite('service', service.id);
        service.is_favorite = true;
      }
      renderServiceDetail(service);
    } catch (error) {
      helpers.toast(error.message, 'error');
    }
  });

  document.getElementById('service-copy-link-btn').addEventListener('click', async () => {
    await navigator.clipboard.writeText(links.url);
    helpers.toast('Enlace copiado.', 'success');
  });
}

async function initServiceDetailPage() {
  const slug = helpers.queryParam('slug');
  const mount = document.getElementById('service-detail-mount');

  if (!slug) {
    mount.innerHTML = '<p class="error-state">Servicio no especificado.</p>';
    return;
  }

  try {
    const service = await catalogService.service(slug);
    document.title = `${service.name} — CASTAMOTO`;
    renderServiceDetail(service);
  } catch (error) {
    mount.innerHTML = `<p class="error-state">${helpers.escapeHtml(error.message)}</p>`;
  }
}

document.addEventListener('DOMContentLoaded', initServiceDetailPage);
