/**
 * Carrito + checkout (Fase 5) y direcciones (Fase 2, se usan dentro del
 * checkout). Funciona para invitados: apiService ya adjunta X-Cart-Token
 * automáticamente cuando existe uno guardado.
 */
const cartService = {
  get: () => apiService.get('/cart'),
  addItem: (payload) => apiService.post('/cart/items', payload),
  updateItem: (itemId, quantity) => apiService.put(`/cart/items/${itemId}`, { quantity }),
  removeItem: (itemId) => apiService.del(`/cart/items/${itemId}`),
  clear: () => apiService.del('/cart'),
  applyCoupon: (code) => apiService.post('/cart/coupon', { code }),
  removeCoupon: () => apiService.del('/cart/coupon'),

  addresses: () => apiService.get('/addresses'),
  createAddress: (payload) => apiService.post('/addresses', payload),

  checkout: (payload) => apiService.post('/checkout', payload),
  order: (orderNumber) => apiService.get(`/orders/${encodeURIComponent(orderNumber)}`),
  myOrders: () => apiService.get('/orders'),
  paymentMethods: () => apiService.get('/payment-methods'),
};
