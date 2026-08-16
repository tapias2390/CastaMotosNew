/**
 * Página de carrito (sección 18): cantidades, subtotal automático, stock
 * disponible, descuentos/envío/total, y paso a checkout.
 */
async function loadCartPage() {
  const mount = document.getElementById('cart-mount');

  try {
    const cart = await cartService.get();

    if (cart.items.length === 0) {
      mount.innerHTML = `
        <div class="empty-state">
          <p>Tu carrito está vacío.</p>
          <a class="btn btn-primary" href="productos">Ver productos</a>
        </div>
      `;
      return;
    }

    mount.innerHTML = `
      <div class="detail-grid">
        <div id="cart-lines">${cart.items.map(cartLineMarkup).join('')}</div>
        <div>
          <div class="summary-box">
            <div class="summary-row"><span>Subtotal</span><span>${helpers.formatCurrency(cart.subtotal)}</span></div>
            <div class="summary-row"><span>Descuento</span><span>-${helpers.formatCurrency(cart.discount_total)}</span></div>
            <div class="summary-row"><span>Impuestos</span><span>${helpers.formatCurrency(cart.tax_total)}</span></div>
            <div class="summary-row"><span>Envío estimado</span><span>${helpers.formatCurrency(cart.shipping_total)}</span></div>
            <div class="summary-row total"><span>Total</span><span>${helpers.formatCurrency(cart.total)}</span></div>
            <button class="btn btn-primary btn-block mt-16" id="go-to-checkout-btn">Ir a pagar</button>
          </div>
        </div>
      </div>
    `;

    wireCartLineEvents();

    document.getElementById('go-to-checkout-btn').addEventListener('click', () => {
      if (!authService.isAuthenticated()) {
        helpers.toast('Inicia sesión para continuar con la compra.', 'error');
        openAuthModal('login');
        return;
      }
      window.location.href = 'checkout';
    });
  } catch (error) {
    mount.innerHTML = `<p class="error-state">${helpers.escapeHtml(error.message)}</p>`;
  }
}

function cartLineMarkup(item) {
  const image = item.type === 'product' ? helpers.mediaUrl('products', item.image) : helpers.mediaUrl('services', item.image);
  const warning = !item.is_available
    ? '<p class="form-error">Ya no está disponible.</p>'
    : item.quantity_exceeds_stock
      ? `<p class="form-error">Solo quedan ${item.available_stock} disponibles.</p>`
      : '';

  return `
    <div class="cart-line" data-item-id="${item.id}">
      <div class="cart-line__image">${image ? `<img src="${image}" alt="">` : ''}</div>
      <div class="cart-line__info">
        <div>${helpers.escapeHtml(item.name)}</div>
        <div style="color:var(--amarillo);font-weight:700;">${helpers.formatCurrency(item.unit_price)}</div>
        ${warning}
      </div>
      <div class="cart-line__qty">
        <button data-action="decrease" aria-label="Disminuir">−</button>
        <input type="number" min="1" value="${item.quantity}" data-role="quantity">
        <button data-action="increase" aria-label="Aumentar">+</button>
      </div>
      <button class="icon-btn" data-action="remove" aria-label="Eliminar">🗑</button>
    </div>
  `;
}

function wireCartLineEvents() {
  document.querySelectorAll('.cart-line').forEach((line) => {
    const itemId = line.dataset.itemId;
    const input = line.querySelector('[data-role="quantity"]');

    line.querySelector('[data-action="increase"]').addEventListener('click', () => updateCartLine(itemId, Number(input.value) + 1));
    line.querySelector('[data-action="decrease"]').addEventListener('click', () => {
      const next = Number(input.value) - 1;
      if (next >= 1) updateCartLine(itemId, next);
    });
    input.addEventListener('change', () => {
      const next = Number(input.value);
      if (next >= 1) updateCartLine(itemId, next);
    });
    line.querySelector('[data-action="remove"]').addEventListener('click', () => removeCartLine(itemId));
  });
}

async function updateCartLine(itemId, quantity) {
  try {
    await cartService.updateItem(itemId, quantity);
    refreshCartBadge();
    loadCartPage();
  } catch (error) {
    helpers.toast(helpers.flattenErrors(error.fields) || error.message, 'error');
    loadCartPage();
  }
}

async function removeCartLine(itemId) {
  try {
    await cartService.removeItem(itemId);
    helpers.toast('Producto eliminado.', 'success');
    refreshCartBadge();
    loadCartPage();
  } catch (error) {
    helpers.toast(error.message, 'error');
  }
}

document.addEventListener('DOMContentLoaded', loadCartPage);
