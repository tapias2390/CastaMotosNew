/**
 * Home (sección 41): buscador grande, categorías, productos y servicios
 * destacados, sección de confianza.
 */
async function loadHomeCategories() {
  const mount = document.getElementById('home-categories');
  if (!mount) return;

  try {
    const categories = await catalogService.categories();
    if (categories.length === 0) {
      mount.innerHTML = '<p class="empty-state">Todavía no hay categorías publicadas.</p>';
      return;
    }

    mount.innerHTML = `<div class="grid">${categories.map((cat) => `
      <a class="card" href="categoria/${encodeURIComponent(cat.slug)}">
        <div class="card__body text-center">
          <span class="card__name">${helpers.escapeHtml(cat.name)}</span>
        </div>
      </a>
    `).join('')}</div>`;
  } catch (error) {
    mount.innerHTML = `<p class="error-state">No fue posible cargar las categorías.</p>`;
  }
}

async function loadFeaturedProducts() {
  const mount = document.getElementById('home-products');
  if (!mount) return;

  try {
    const result = await catalogService.products({ sort: 'newest', per_page: 8 });
    if (result.data.length === 0) {
      mount.innerHTML = '<p class="empty-state">Todavía no hay productos publicados.</p>';
      return;
    }

    mount.innerHTML = `<div class="grid">${result.data.map(productCardMarkup).join('')}</div>`;
    wireCardEvents(mount);
  } catch (error) {
    mount.innerHTML = `<p class="error-state">No fue posible cargar los productos.</p>`;
  }
}

async function loadFeaturedServices() {
  const mount = document.getElementById('home-services');
  if (!mount) return;

  try {
    const result = await catalogService.services({ sort: 'newest', per_page: 4 });
    if (result.data.length === 0) {
      mount.innerHTML = '<p class="empty-state">Todavía no hay servicios publicados.</p>';
      return;
    }

    mount.innerHTML = `<div class="grid">${result.data.map(serviceCardMarkup).join('')}</div>`;
    wireCardEvents(mount);
  } catch (error) {
    mount.innerHTML = `<p class="error-state">No fue posible cargar los servicios.</p>`;
  }
}

function wireHomeSearch() {
  const form = document.getElementById('hero-search-form');
  if (!form) return;

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const term = document.getElementById('hero-search-input').value.trim();
    if (term) window.location.href = `productos?search=${encodeURIComponent(term)}`;
  });
}

document.addEventListener('DOMContentLoaded', () => {
  wireHomeSearch();
  loadHomeCategories();
  loadFeaturedProducts();
  loadFeaturedServices();
});
