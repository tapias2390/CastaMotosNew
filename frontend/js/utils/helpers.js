/**
 * Utilidades compartidas por todas las páginas: formato de moneda, lectura
 * de query params y notificaciones tipo "toast" (sección 42: feedback visual).
 */
const helpers = {
  formatCurrency(value) {
    const number = Number(value) || 0;
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(number);
  },

  queryParam(name) {
    return new URLSearchParams(window.location.search).get(name);
  },

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
  },

  mediaUrl(type, filename) {
    if (!filename) return null;
    return `api/media/${type}/${filename}`; // relativo: se resuelve contra <base>, ver apiService.js
  },

  toast(message, variant = 'info') {
    let stack = document.querySelector('.toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      document.body.appendChild(stack);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${variant}`;
    toast.textContent = message;
    stack.appendChild(toast);

    setTimeout(() => toast.remove(), 4000);
  },

  /** Traduce el objeto "errors" del backend (campo -> [mensajes]) a un solo texto. */
  flattenErrors(errors) {
    if (!errors || typeof errors !== 'object') return '';
    return Object.values(errors).flat().join(' ');
  },
};
