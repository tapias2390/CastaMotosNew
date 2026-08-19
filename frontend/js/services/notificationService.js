/**
 * Campanita de notificaciones (header, todo el sitio). Cada usuario logueado
 * ve solo las suyas — el fan-out (una fila por usuario) ya lo hace el
 * backend al crear el producto/servicio/pedido.
 */
const notificationService = {
  list: () => apiService.get('/notifications'),
  markRead: (id) => apiService.put(`/notifications/${id}/read`, {}),
  markAllRead: () => apiService.put('/notifications/read-all', {}),
};
