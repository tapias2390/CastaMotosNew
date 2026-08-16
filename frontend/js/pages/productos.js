/**
 * Listado de productos con filtros/orden/paginación (secciones 10 y 14).
 * La URL amigable /categoria/{slug} llega aquí como ?category={slug}
 * (reescritura en el .htaccess raíz); se resuelve a category_id contra la API.
 */
let currentPage = 1;
let categorySlugToId = {};

function flattenCategories(tree) {
  return tree.reduce((flat, node) => flat.concat([node], flattenCategories(node.children || [])), []);
}

async function populateFilterOptions() {
  const categorySelect = document.getElementById('filter-category');
  const brandSelect = document.getElementById('filter-brand');

  try {
    const [categories, brands] = await Promise.all([catalogService.categories(), catalogService.brands()]);
    const flatCategories = flattenCategories(categories);

    flatCategories.forEach((cat) => { categorySlugToId[cat.slug] = cat.id; });

    categorySelect.innerHTML = '<option value="">Todas las categorías</option>' +
      flatCategories.map((cat) => `<option value="${cat.id}">${helpers.escapeHtml(cat.name)}</option>`).join('');

    brandSelect.innerHTML = '<option value="">Todas las marcas</option>' +
      brands.map((brand) => `<option value="${brand.id}">${helpers.escapeHtml(brand.name)}</option>`).join('');
  } catch (error) {
    console.error('No fue posible cargar los filtros.', error);
  }
}

function currentFilters() {
  return {
    category_id: document.getElementById('filter-category').value || undefined,
    brand_id: document.getElementById('filter-brand').value || undefined,
    min_price: document.getElementById('filter-min-price').value || undefined,
    max_price: document.getElementById('filter-max-price').value || undefined,
    availability: document.getElementById('filter-availability').value || undefined,
    sort: document.getElementById('filter-sort').value || undefined,
    search: document.getElementById('filter-search-input').value || undefined,
    page: currentPage,
    per_page: 12,
  };
}

async function loadProducts() {
  const resultsMount = document.getElementById('products-results');
  const countMount = document.getElementById('products-count');
  resultsMount.innerHTML = '<p class="loading-state">Cargando productos…</p>';

  try {
    const result = await catalogService.products(currentFilters());

    if (result.data.length === 0) {
      resultsMount.innerHTML = '<p class="empty-state">No se encontraron productos con estos filtros.</p>';
      countMount.textContent = '';
      renderPagination(0, 1);
      return;
    }

    resultsMount.innerHTML = `<div class="grid">${result.data.map(productCardMarkup).join('')}</div>`;
    wireCardEvents(resultsMount);
    countMount.textContent = `${result.total} resultado(s)`;
    renderPagination(result.total, result.per_page);
  } catch (error) {
    resultsMount.innerHTML = `<p class="error-state">${helpers.escapeHtml(error.message)}</p>`;
  }
}

function renderPagination(total, perPage) {
  const mount = document.getElementById('products-pagination');
  const totalPages = Math.max(1, Math.ceil(total / perPage));

  if (totalPages <= 1) {
    mount.innerHTML = '';
    return;
  }

  mount.innerHTML = `
    <button class="btn btn-secondary" id="page-prev" ${currentPage <= 1 ? 'disabled' : ''}>← Anterior</button>
    <span style="padding:0 12px;">Página ${currentPage} de ${totalPages}</span>
    <button class="btn btn-secondary" id="page-next" ${currentPage >= totalPages ? 'disabled' : ''}>Siguiente →</button>
  `;

  document.getElementById('page-prev')?.addEventListener('click', () => { currentPage--; loadProducts(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
  document.getElementById('page-next')?.addEventListener('click', () => { currentPage++; loadProducts(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
}

function wireFilterEvents() {
  ['filter-category', 'filter-brand', 'filter-min-price', 'filter-max-price', 'filter-availability', 'filter-sort'].forEach((id) => {
    document.getElementById(id).addEventListener('change', () => { currentPage = 1; loadProducts(); });
  });

  document.getElementById('filter-search-form').addEventListener('submit', (event) => {
    event.preventDefault();
    currentPage = 1;
    loadProducts();
  });
}

async function initProductsPage() {
  await populateFilterOptions();

  const searchParam = helpers.queryParam('search');
  if (searchParam) document.getElementById('filter-search-input').value = searchParam;

  const categorySlug = helpers.queryParam('category');
  if (categorySlug && categorySlugToId[categorySlug]) {
    document.getElementById('filter-category').value = String(categorySlugToId[categorySlug]);
  }

  wireFilterEvents();
  loadProducts();
}

document.addEventListener('DOMContentLoaded', initProductsPage);
