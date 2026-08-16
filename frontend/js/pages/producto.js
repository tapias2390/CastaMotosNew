/**
 * Detalle de producto (sección 11): galería con zoom simple, variantes,
 * atributos, disponibilidad, agregar al carrito, favorito y compartir
 * (sección 17: copiar enlace, WhatsApp, Facebook, X, Telegram, Web Share API).
 */
let currentProduct = null;

function shareLinks(product) {
  const url = product.canonical_url || window.location.href;
  const text = encodeURIComponent(`Mira "${product.name}" en CASTAMOTO`);
  const encodedUrl = encodeURIComponent(url);

  return {
    whatsapp: `https://wa.me/?text=${text}%20${encodedUrl}`,
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
    x: `https://twitter.com/intent/tweet?text=${text}&url=${encodedUrl}`,
    telegram: `https://t.me/share/url?url=${encodedUrl}&text=${text}`,
    url,
  };
}

function galleryMarkup(product) {
  const images = product.images && product.images.length > 0
    ? product.images.map((img) => helpers.mediaUrl('products', img.url))
    : [null];

  const main = images[0]
    ? `<img id="gallery-main-img" src="${images[0]}" alt="${helpers.escapeHtml(product.name)}">`
    : `<span id="gallery-main-img">Sin imagen disponible</span>`;

  const thumbs = images.length > 1
    ? `<div class="detail-gallery__thumbs">${images.map((src, index) => src ? `<img src="${src}" class="${index === 0 ? 'is-active' : ''}" data-full="${src}">` : '').join('')}</div>`
    : '';

  return `<div class="detail-gallery"><div class="detail-gallery__main">${main}</div>${thumbs}</div>`;
}

function variantOptionsMarkup(product) {
  if (!product.variants || product.variants.length === 0) return '';

  const options = product.variants.map((variant) =>
    `<option value="${variant.price_modifier}">${helpers.escapeHtml(variant.name)}${Number(variant.price_modifier) > 0 ? ` (+${helpers.formatCurrency(variant.price_modifier)})` : ''}</option>`
  ).join('');

  return `
    <div class="form-group">
      <label for="variant-select">Variante</label>
      <select class="form-control" id="variant-select">${options}</select>
    </div>
  `;
}

function attributesMarkup(product) {
  if (!product.attributes || product.attributes.length === 0) return '';

  return `
    <table style="width:100%;font-size:0.85rem;color:var(--gris-texto);margin-top:12px;">
      ${product.attributes.map((attr) => `<tr><td style="padding:4px 0;font-weight:600;color:var(--blanco);">${helpers.escapeHtml(attr.name)}</td><td>${helpers.escapeHtml(attr.value)}</td></tr>`).join('')}
    </table>
  `;
}

function renderProductDetail(product) {
  const stockStatus = product.stock_status || 'disponible';
  const stockLabel = { disponible: 'Disponible', ultimas_unidades: `Últimas unidades (${product.stock} disp.)`, agotado: 'Agotado' }[stockStatus];
  const links = shareLinks(product);
  const hasDiscount = product.previous_price && Number(product.previous_price) > Number(product.price);

  const breadcrumbCategory = product.category_slug
    ? `<a href="categoria/${encodeURIComponent(product.category_slug)}">${helpers.escapeHtml(product.category_name)}</a>`
    : helpers.escapeHtml(product.category_name || '');
  const stars = helpers.renderStars(product.rating_avg, product.rating_count);

  document.getElementById('product-detail-mount').innerHTML = `
    <nav class="breadcrumbs" aria-label="Ruta de navegación">
      <a href=".">Inicio</a> › <a href="productos">Productos</a>
      ${breadcrumbCategory ? ` › ${breadcrumbCategory}` : ''} › <span aria-current="page">${helpers.escapeHtml(product.name)}</span>
    </nav>
    <div class="detail-grid mt-16">
      ${galleryMarkup(product)}
      <div>
        <h1>${helpers.escapeHtml(product.name)}</h1>
        <p style="color:var(--gris-texto);">${product.brand_name ? helpers.escapeHtml(product.brand_name) + ' · ' : ''}SKU ${helpers.escapeHtml(product.sku)}</p>
        ${stars ? `<p class="rating-summary">${stars}</p>` : ''}

        <div class="purchase-box mt-16">
          <span class="badge badge-${stockStatus}">${stockLabel}</span>
          <p style="font-size:1.8rem;font-weight:800;color:var(--amarillo);margin:12px 0 4px;">
            ${helpers.formatCurrency(product.price)}
            ${hasDiscount ? `<span class="card__price-old" style="font-size:1rem;">${helpers.formatCurrency(product.previous_price)}</span>` : ''}
          </p>
          ${product.short_description ? `<p style="color:var(--gris-texto);">${helpers.escapeHtml(product.short_description)}</p>` : ''}

          ${variantOptionsMarkup(product)}

          <div class="form-row" style="align-items:flex-end;">
            <div class="form-group" style="max-width:120px;">
              <label for="add-quantity">Cantidad</label>
              <input class="form-control" type="number" id="add-quantity" value="1" min="1" max="${product.stock}">
            </div>
            <div class="form-group" style="flex:2;">
              <button class="btn btn-primary btn-block" id="add-to-cart-btn" ${stockStatus === 'agotado' ? 'disabled' : ''}>
                ${stockStatus === 'agotado' ? 'Agotado' : 'Agregar al carrito'}
              </button>
            </div>
          </div>

          <button class="btn btn-secondary btn-block" id="favorite-btn">
            ${product.is_favorite ? '♥ En favoritos' : '♡ Agregar a favoritos'}
          </button>
        </div>

        <div class="seller-box">
          <span class="seller-box__icon">🏍️</span>
          <div>
            <div class="seller-box__name">Vendido por ${helpers.escapeHtml(product.store_name || 'CASTAMOTO')}</div>
            <div class="seller-box__meta">${product.store_name ? 'Tienda del marketplace' : 'Vendido y despachado directamente por CASTAMOTO'}</div>
          </div>
        </div>

        <div class="share-row">
          <a class="share-btn" href="${links.whatsapp}" target="_blank" rel="noopener">WhatsApp</a>
          <a class="share-btn" href="${links.facebook}" target="_blank" rel="noopener">Facebook</a>
          <a class="share-btn" href="${links.x}" target="_blank" rel="noopener">X</a>
          <a class="share-btn" href="${links.telegram}" target="_blank" rel="noopener">Telegram</a>
          <button class="share-btn" id="copy-link-btn">Copiar enlace</button>
          <button class="share-btn" id="native-share-btn" hidden>Compartir</button>
        </div>

        ${attributesMarkup(product)}
        ${product.description ? `<div class="mt-16" style="color:var(--gris-texto);">${helpers.escapeHtml(product.description)}</div>` : ''}
      </div>
    </div>

    <div class="section">
      <h2 class="section__title">Productos relacionados</h2>
      <div class="carousel" id="related-products"></div>
    </div>
  `;

  wireProductDetailEvents(product);
}

function wireProductDetailEvents(product) {
  document.querySelectorAll('.detail-gallery__thumbs img').forEach((thumb) => {
    thumb.addEventListener('click', () => {
      document.getElementById('gallery-main-img').src = thumb.dataset.full;
      document.querySelectorAll('.detail-gallery__thumbs img').forEach((img) => img.classList.remove('is-active'));
      thumb.classList.add('is-active');
    });
  });

  document.getElementById('add-to-cart-btn')?.addEventListener('click', async () => {
    const quantity = Number(document.getElementById('add-quantity').value) || 1;
    try {
      await cartService.addItem({ product_id: product.id, quantity });
      helpers.toast('Producto agregado al carrito.', 'success');
      refreshCartBadge();
    } catch (error) {
      helpers.toast(helpers.flattenErrors(error.fields) || error.message, 'error');
    }
  });

  document.getElementById('favorite-btn').addEventListener('click', async () => {
    if (!authService.isAuthenticated()) {
      helpers.toast('Inicia sesión para guardar favoritos.', 'error');
      return;
    }
    try {
      if (product.is_favorite) {
        await catalogService.removeFavorite('product', product.id);
        product.is_favorite = false;
      } else {
        await catalogService.addFavorite('product', product.id);
        product.is_favorite = true;
      }
      renderProductDetail(product);
    } catch (error) {
      helpers.toast(error.message, 'error');
    }
  });

  const links = shareLinks(product);
  document.getElementById('copy-link-btn').addEventListener('click', async () => {
    await navigator.clipboard.writeText(links.url);
    helpers.toast('Enlace copiado.', 'success');
  });

  if (navigator.share) {
    const nativeBtn = document.getElementById('native-share-btn');
    nativeBtn.hidden = false;
    nativeBtn.addEventListener('click', () => navigator.share({ title: product.name, url: links.url }));
  }

  const relatedMount = document.getElementById('related-products');
  if (product.related && product.related.length > 0) {
    relatedMount.innerHTML = product.related.map(productCardMarkup).join('');
    wireCardEvents(relatedMount);
  } else {
    relatedMount.innerHTML = '<p class="empty-state">Sin productos relacionados por ahora.</p>';
  }
}

async function initProductDetailPage() {
  const slug = helpers.queryParam('slug');
  const mount = document.getElementById('product-detail-mount');

  if (!slug) {
    mount.innerHTML = '<p class="error-state">Producto no especificado.</p>';
    return;
  }

  try {
    currentProduct = await catalogService.product(slug);
    document.title = `${currentProduct.name} — CASTAMOTO`;
    renderProductDetail(currentProduct);
  } catch (error) {
    mount.innerHTML = `<p class="error-state">${helpers.escapeHtml(error.message)}</p>`;
  }
}

document.addEventListener('DOMContentLoaded', initProductDetailPage);
