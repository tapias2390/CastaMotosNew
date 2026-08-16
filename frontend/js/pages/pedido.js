/**
 * Confirmación de pedido (sección 19, paso 6: "Pedido creado").
 */
function statusLabel(status) {
  const labels = {
    PENDIENTE: 'Pendiente', CONFIRMADO: 'Confirmado', PAGO_PENDIENTE: 'Pago pendiente',
    PAGO_CONFIRMADO: 'Pago confirmado', PREPARANDO: 'Preparando', EN_CAMINO: 'En camino',
    ENTREGADO: 'Entregado', CANCELADO: 'Cancelado', DEVUELTO: 'Devuelto',
  };
  return labels[status] || status;
}

async function initOrderConfirmationPage() {
  const orderNumber = helpers.queryParam('number');
  const mount = document.getElementById('order-mount');

  if (!authService.isAuthenticated()) {
    mount.innerHTML = '<p class="error-state">Inicia sesión para ver este pedido.</p>';
    return;
  }

  if (!orderNumber) {
    mount.innerHTML = '<p class="error-state">Pedido no especificado.</p>';
    return;
  }

  try {
    const order = await cartService.order(orderNumber);

    mount.innerHTML = `
      <div class="confirmation-box">
        <div style="font-size:3rem;">✅</div>
        <p>¡Gracias por tu compra!</p>
        <div class="order-number">${helpers.escapeHtml(order.order_number)}</div>
        <span class="badge badge-disponible">${statusLabel(order.status)}</span>
      </div>

      <div class="summary-box mt-16">
        ${order.items.map((item) => `
          <div class="summary-row"><span>${item.quantity}× ${helpers.escapeHtml(item.name_snapshot)}</span><span>${helpers.formatCurrency(item.subtotal)}</span></div>
        `).join('')}
        <div class="summary-row"><span>Subtotal</span><span>${helpers.formatCurrency(order.subtotal)}</span></div>
        <div class="summary-row"><span>Descuento</span><span>-${helpers.formatCurrency(order.discount_total)}</span></div>
        <div class="summary-row"><span>Impuestos</span><span>${helpers.formatCurrency(order.tax_total)}</span></div>
        <div class="summary-row"><span>Envío</span><span>${helpers.formatCurrency(order.shipping_total)}</span></div>
        <div class="summary-row total"><span>Total</span><span>${helpers.formatCurrency(order.total)}</span></div>
      </div>

      <div class="mt-16">
        <a class="btn btn-primary" href="productos">Seguir comprando</a>
      </div>
    `;
  } catch (error) {
    mount.innerHTML = `<p class="error-state">${helpers.escapeHtml(error.message)}</p>`;
  }
}

document.addEventListener('DOMContentLoaded', initOrderConfirmationPage);
