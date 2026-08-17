/**
 * Panel administrativo básico (Fase 6): pedidos e inventario. Requiere JWT +
 * permiso manage-orders/manage-inventory — la seguridad real la aplica el
 * backend (AuthMiddleware + RequirePermissionMiddleware); esto es solo el
 * cliente HTTP.
 */
const adminService = {
  orders: (filters = {}) => apiService.get('/admin/orders' + catalogService.toQueryString(filters)),
  order: (orderNumber) => apiService.get(`/admin/orders/${encodeURIComponent(orderNumber)}`),
  updateOrderStatus: (orderNumber, status, comment) =>
    apiService.put(`/admin/orders/${encodeURIComponent(orderNumber)}/status`, { status, comment }),

  inventory: (filters = {}) => apiService.get('/admin/inventory' + catalogService.toQueryString(filters)),
  adjustInventory: (productId, payload) => apiService.post(`/admin/inventory/${productId}/adjust`, payload),
  movements: (filters = {}) => apiService.get('/admin/inventory/movements' + catalogService.toQueryString(filters)),

  // Reservas de servicios (sección 12) — cada una ES un pedido con servicio
  // agendado; el cambio de estado reutiliza updateOrderStatus() de arriba.
  reservations: (filters = {}) => apiService.get('/admin/reservations' + catalogService.toQueryString(filters)),

  // Resumen del negocio para la pestaña "Resumen" (sección 28).
  dashboardSummary: () => apiService.get('/admin/dashboard/summary'),

  // Clientes registrados (sección 28) — excluye staff por defecto.
  customers: (filters = {}) => apiService.get('/admin/customers' + catalogService.toQueryString(filters)),
};
