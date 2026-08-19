/**
 * "Mis pedidos" (sección 23): historial completo del usuario — estado
 * actual de cada uno (incluye CANCELADO/DEVUELTO tal cual pasaron, nunca se
 * ocultan) — cada fila lleva al detalle en /pedido/{numero}, que ya trae la
 * línea de tiempo completa de cambios de estado (ver pedido.js).
 */
function orderListItemMarkup(order) {
  const image = helpers.mediaUrl(order.thumbnail_type, order.thumbnail);

  return `
    <a class="order-list-item" href="pedido/${encodeURIComponent(order.order_number)}">
      <div class="order-list-item__thumb">
        ${image ? `<img src="${image}" alt="">` : '<span class="order-list-item__thumb-placeholder">🏍️</span>'}
      </div>
      <div class="order-list-item__info">
        <div class="order-list-item__number">${helpers.escapeHtml(order.order_number)}</div>
        <div class="order-list-item__meta">${helpers.formatDateTime(order.created_at)} · ${order.delivery_method === 'recogida_tienda' ? 'Recogida en tienda' : 'Entrega a domicilio'}</div>
      </div>
      <div style="text-align:right;">
        <div style="font-weight:700;color:var(--amarillo);">${helpers.formatCurrency(order.total)}</div>
        <span class="badge ${helpers.orderStatusBadgeClass(order.status)}">${helpers.orderStatusLabel(order.status)}</span>
      </div>
    </a>
  `;
}

async function initOrdersPage() {
  const mount = document.getElementById('orders-mount');

  if (!authService.isAuthenticated()) {
    mount.innerHTML = `
      <div class="empty-state">
        <p>Inicia sesión para ver tu historial de pedidos.</p>
        <button class="btn btn-primary" id="orders-login-btn">Iniciar sesión</button>
      </div>
    `;
    document.getElementById('orders-login-btn').addEventListener('click', () => openAuthModal('login'));
    return;
  }

  try {
    const orders = await cartService.myOrders();

    if (orders.length === 0) {
      mount.innerHTML = `
        <div class="empty-state">
          <p>Todavía no tienes pedidos.</p>
          <a class="btn btn-primary" href="productos">Ver productos</a>
        </div>
      `;
      return;
    }

    mount.innerHTML = orders.map(orderListItemMarkup).join('');
  } catch (error) {
    mount.innerHTML = `<p class="error-state">${helpers.escapeHtml(error.message)}</p>`;
  }
}

document.addEventListener('DOMContentLoaded', initOrdersPage);
