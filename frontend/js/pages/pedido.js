/**
 * Confirmación de pedido (sección 19, paso 6: "Pedido creado").
 */

/**
 * Enlace de WhatsApp con el resumen del pedido ya escrito (sección 17: compartir).
 * Se arma con datos reales del pedido — nunca un texto genérico inventado — y
 * el botón solo se muestra si hay un número de contacto configurado
 * (CONTACT_WHATSAPP_NUMBER en backend/.env, ver settingsService.js).
 */
function orderWhatsappLink(order, whatsappNumber) {
  if (!whatsappNumber) return null;

  const itemsText = order.items.map((item) =>
    `- ${item.quantity}× ${item.name_snapshot}${item.scheduled_at ? ` (${helpers.formatDateTime(item.scheduled_at)})` : ''}`
  ).join('\n');
  const message = `Hola CASTAMOTO! Quiero coordinar mi pedido *${order.order_number}*:\n${itemsText}\nTotal: ${helpers.formatCurrency(order.total)}`;

  return `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`;
}

async function initOrderConfirmationPage() {
  const orderNumber = helpers.routeParam('number', 'pedido');
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
    const [order, settings] = await Promise.all([cartService.order(orderNumber), settingsService.get()]);
    const whatsappLink = orderWhatsappLink(order, settings.contact_whatsapp_number);

    mount.innerHTML = `
      <div class="confirmation-box">
        <div style="font-size:3rem;">✅</div>
        <p>¡Gracias por tu compra!</p>
        <div class="order-number">${helpers.escapeHtml(order.order_number)}</div>
        <span class="badge badge-disponible">${helpers.orderStatusLabel(order.status)}</span>
      </div>

      <div class="summary-box mt-16">
        ${order.items.map((item) => `
          <div class="summary-row">
            <span>${item.quantity}× ${helpers.escapeHtml(item.name_snapshot)}${item.scheduled_at ? ` <br><small style="color:var(--gris-texto);">📅 ${helpers.formatDateTime(item.scheduled_at)}</small>` : ''}</span>
            <span>${helpers.formatCurrency(item.subtotal)}</span>
          </div>
        `).join('')}
        <div class="summary-row"><span>Subtotal</span><span>${helpers.formatCurrency(order.subtotal)}</span></div>
        <div class="summary-row"><span>Descuento</span><span>-${helpers.formatCurrency(order.discount_total)}</span></div>
        <div class="summary-row"><span>Impuestos</span><span>${helpers.formatCurrency(order.tax_total)}</span></div>
        <div class="summary-row"><span>Envío</span><span>${helpers.formatCurrency(order.shipping_total)}</span></div>
        <div class="summary-row total"><span>Total</span><span>${helpers.formatCurrency(order.total)}</span></div>
      </div>

      <div class="mt-16 flex gap-8" style="flex-wrap:wrap;">
        ${whatsappLink ? `<a class="btn btn-whatsapp" href="${whatsappLink}" target="_blank" rel="noopener">📲 Enviar pedido por WhatsApp</a>` : ''}
        <a class="btn btn-primary" href="productos">Seguir comprando</a>
      </div>
    `;
  } catch (error) {
    mount.innerHTML = `<p class="error-state">${helpers.escapeHtml(error.message)}</p>`;
  }
}

document.addEventListener('DOMContentLoaded', initOrderConfirmationPage);
