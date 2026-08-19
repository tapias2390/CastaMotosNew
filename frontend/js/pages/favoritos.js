/**
 * Página "Mis favoritos" (GET /api/favorites 🔒): lista productos y servicios
 * marcados por el usuario, reutilizando las mismas tarjetas de home/productos
 * (cards.js) — la única diferencia es que acá el corazón siempre arranca
 * "activo" (todo lo que llega es, por definición, un favorito) y al
 * quitarlo la tarjeta desaparece de la lista en vez de solo cambiar de color.
 */
function favoriteToCardData(item) {
  // listForUser() (backend) devuelve las columnas crudas de products/services
  // + favorite_id/favorited_at — se completan los campos que productCardMarkup/
  // serviceCardMarkup esperan (is_favorite, etc.) para poder reusar esas mismas
  // funciones tal cual las usa el resto del sitio.
  return { ...item, is_favorite: true };
}

function wireFavoritesPageEvents(container) {
  container.querySelectorAll('.card__favorite').forEach((button) => {
    button.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      const type = button.dataset.favoriteType;
      const id = button.dataset.favoriteId;

      try {
        await catalogService.removeFavorite(type, id);
        const card = button.closest('.card');
        card.remove();

        if (!document.querySelector('#favorites-mount .card')) {
          renderEmptyState();
        }
      } catch (error) {
        helpers.toast(error.message, 'error');
      }
    });
  });
}

function renderEmptyState() {
  document.getElementById('favorites-mount').innerHTML = `
    <div class="empty-state">
      <p>Todavía no tienes favoritos guardados.</p>
      <a class="btn btn-primary" href="productos">Ver productos</a>
    </div>
  `;
}

function renderLoginPrompt() {
  document.getElementById('favorites-mount').innerHTML = `
    <div class="empty-state">
      <p>Inicia sesión para ver tus favoritos guardados.</p>
      <button class="btn btn-primary" id="favorites-login-btn">Iniciar sesión</button>
    </div>
  `;
  document.getElementById('favorites-login-btn').addEventListener('click', () => openAuthModal('login'));
}

async function initFavoritesPage() {
  const mount = document.getElementById('favorites-mount');

  if (!authService.isAuthenticated()) {
    renderLoginPrompt();
    return;
  }

  try {
    const favorites = await catalogService.favorites();

    if (favorites.length === 0) {
      renderEmptyState();
      return;
    }

    mount.innerHTML = `<div class="grid">${favorites.map((item) => {
      const card = favoriteToCardData(item);
      return item.type === 'service' ? serviceCardMarkup(card) : productCardMarkup(card);
    }).join('')}</div>`;

    wireFavoritesPageEvents(mount);
  } catch (error) {
    mount.innerHTML = `<p class="error-state">${helpers.escapeHtml(error.message)}</p>`;
  }
}

document.addEventListener('DOMContentLoaded', initFavoritesPage);
