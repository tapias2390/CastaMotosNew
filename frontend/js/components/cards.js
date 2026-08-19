/**
 * Tarjetas de producto/servicio (sección 40: ProductCard, ServiceCard,
 * FavoriteButton). Cada función devuelve el HTML como string; wireCardEvents()
 * delega los clicks de "favorito" de todas las tarjetas ya insertadas.
 */
function productCardMarkup(product) {
  const image = helpers.mediaUrl('products', product.primary_image || product.image);
  const hasDiscount = product.previous_price && Number(product.previous_price) > Number(product.price);
  const discountPercent = Number(product.discount_percentage) || 0;
  const stockStatus = product.stock_status || 'disponible';
  const stockLabel = {
    disponible: i18nService.t('card.stock.available'),
    ultimas_unidades: i18nService.t('card.stock.lastUnits'),
    agotado: i18nService.t('card.stock.soldOut'),
  }[stockStatus];
  const stars = helpers.renderStars(product.rating_avg, product.rating_count);
  const name = helpers.localized(product, 'name');

  return `
    <a class="card" href="producto/${encodeURIComponent(product.slug)}" data-card-type="product" data-card-id="${product.id}">
      <div class="card__image">
        ${image ? `<img src="${image}" alt="${helpers.escapeHtml(name)}" loading="lazy">` : i18nService.t('card.noImage')}
        ${discountPercent > 0 ? `<span class="card__discount-badge">-${Math.round(discountPercent)}%</span>` : ''}
        <button class="card__favorite ${product.is_favorite ? 'is-active' : ''}" data-favorite-type="product" data-favorite-id="${product.id}" aria-label="${i18nService.t('card.favorite')}" onclick="event.preventDefault();">♥</button>
      </div>
      <div class="card__body">
        <span class="card__name">${helpers.escapeHtml(name)}</span>
        ${stars ? `<span class="card__rating">${stars}</span>` : ''}
        <div class="card__price-row">
          <span class="card__price">${helpers.formatCurrency(product.price)}</span>
          ${hasDiscount ? `<span class="card__price-old">${helpers.formatCurrency(product.previous_price)}</span>` : ''}
        </div>
        <span class="badge badge-${stockStatus}">${stockLabel}</span>
        ${product.brand_name ? `<span class="card__meta">${helpers.escapeHtml(product.brand_name)}</span>` : ''}
      </div>
    </a>
  `;
}

function serviceCardMarkup(service) {
  const image = helpers.mediaUrl('services', service.primary_image || service.image);
  const name = helpers.localized(service, 'name');

  return `
    <a class="card" href="servicio/${encodeURIComponent(service.slug)}" data-card-type="service" data-card-id="${service.id}">
      <div class="card__image">
        ${image ? `<img src="${image}" alt="${helpers.escapeHtml(name)}" loading="lazy">` : i18nService.t('card.noImage')}
        <button class="card__favorite ${service.is_favorite ? 'is-active' : ''}" data-favorite-type="service" data-favorite-id="${service.id}" aria-label="${i18nService.t('card.favorite')}" onclick="event.preventDefault();">♥</button>
      </div>
      <div class="card__body">
        <span class="card__name">${helpers.escapeHtml(name)}</span>
        <div class="card__price-row"><span class="card__price">${helpers.formatCurrency(service.price)}</span></div>
        ${service.location ? `<span class="card__meta">📍 ${helpers.escapeHtml(service.location)}</span>` : ''}
      </div>
    </a>
  `;
}

function wireCardEvents(container) {
  container.querySelectorAll('.card__favorite').forEach((button) => {
    button.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      if (!authService.isAuthenticated()) {
        helpers.toast('Inicia sesión para guardar favoritos.', 'error');
        return;
      }

      const type = button.dataset.favoriteType;
      const id = button.dataset.favoriteId;
      const isActive = button.classList.contains('is-active');

      try {
        if (isActive) {
          await catalogService.removeFavorite(type, id);
          button.classList.remove('is-active');
        } else {
          await catalogService.addFavorite(type, Number(id));
          button.classList.add('is-active');
        }
      } catch (error) {
        helpers.toast(error.message, 'error');
      }
    });
  });
}
