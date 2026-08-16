/**
 * Listado de servicios (sección 12) con filtro de categoría/ubicación/búsqueda.
 */
let servicesPage = 1;

function flattenCategoriesList(tree) {
  return tree.reduce((flat, node) => flat.concat([node], flattenCategoriesList(node.children || [])), []);
}

async function populateServiceFilters() {
  const select = document.getElementById('filter-service-category');
  try {
    const categories = await catalogService.categories();
    const flat = flattenCategoriesList(categories);
    select.innerHTML = '<option value="">Todas las categorías</option>' +
      flat.map((cat) => `<option value="${cat.id}">${helpers.escapeHtml(cat.name)}</option>`).join('');

    const categorySlug = helpers.queryParam('category');
    if (categorySlug) {
      const match = flat.find((cat) => cat.slug === categorySlug);
      if (match) select.value = String(match.id);
    }
  } catch (error) {
    console.error('No fue posible cargar las categorías.', error);
  }
}

async function loadServices() {
  const resultsMount = document.getElementById('services-results');

  const filters = {
    category_id: document.getElementById('filter-service-category').value || undefined,
    location: document.getElementById('filter-service-location').value || undefined,
    search: document.getElementById('filter-service-search').value || undefined,
    page: servicesPage,
    per_page: 12,
  };

  resultsMount.innerHTML = '<p class="loading-state">Cargando servicios…</p>';

  try {
    const result = await catalogService.services(filters);

    if (result.data.length === 0) {
      resultsMount.innerHTML = '<p class="empty-state">No se encontraron servicios con estos filtros.</p>';
      return;
    }

    resultsMount.innerHTML = `<div class="grid">${result.data.map(serviceCardMarkup).join('')}</div>`;
    wireCardEvents(resultsMount);
  } catch (error) {
    resultsMount.innerHTML = `<p class="error-state">${helpers.escapeHtml(error.message)}</p>`;
  }
}

function wireServiceFilterEvents() {
  document.getElementById('filter-service-category').addEventListener('change', () => { servicesPage = 1; loadServices(); });
  document.getElementById('service-filter-form').addEventListener('submit', (event) => {
    event.preventDefault();
    servicesPage = 1;
    loadServices();
  });
}

document.addEventListener('DOMContentLoaded', async () => {
  await populateServiceFilters();
  wireServiceFilterEvents();
  loadServices();
});
