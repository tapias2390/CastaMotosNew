/**
 * Catálogo (Fase 3) y búsqueda (Fase 4). Delgado a propósito: solo arma la
 * query string y delega en apiService.
 */
function toQueryString(params = {}) {
  const usable = Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== '');
  if (usable.length === 0) return '';
  return '?' + usable.map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`).join('&');
}

const catalogService = {
  categories: () => apiService.get('/categories'),
  brands: () => apiService.get('/brands'),

  products: (filters = {}) => apiService.get('/products' + toQueryString(filters)),
  product: (slug) => apiService.get(`/products/${encodeURIComponent(slug)}`),

  services: (filters = {}) => apiService.get('/services' + toQueryString(filters)),
  service: (slug) => apiService.get(`/services/${encodeURIComponent(slug)}`),

  search: (q) => apiService.get('/search' + toQueryString({ q })),
  suggestions: (q) => apiService.get('/search/suggestions' + toQueryString({ q })),

  // Favoritos (Fase 4) — requieren sesión iniciada.
  favorites: () => apiService.get('/favorites'),
  addFavorite: (type, id) => apiService.post('/favorites', { type, id }),
  removeFavorite: (type, id) => apiService.del(`/favorites/${type}/${id}`),

  toQueryString,
};
