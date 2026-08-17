/**
 * Panel administrativo básico (Fase 6): pedidos e inventario. La seguridad
 * real está en la API (JWT + manage-orders/manage-inventory); aquí solo se
 * oculta el contenido si la petición falla con 401/403, para una UX clara.
 */
const ALL_STATUSES = [
  'PENDIENTE', 'CONFIRMADO', 'PAGO_PENDIENTE', 'PAGO_CONFIRMADO',
  'PREPARANDO', 'EN_CAMINO', 'ENTREGADO', 'CANCELADO', 'DEVUELTO',
];
const GOOD_FINAL = ['ENTREGADO'];
const BAD_FINAL = ['CANCELADO', 'DEVUELTO'];

function statusBadgeClass(status) {
  if (GOOD_FINAL.includes(status)) return 'is-final-good';
  if (BAD_FINAL.includes(status)) return 'is-final-bad';
  return '';
}

function wireTabs() {
  document.querySelectorAll('.admin-tab').forEach((tab) => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.admin-tab').forEach((t) => t.classList.remove('is-active'));
      tab.classList.add('is-active');
      document.getElementById('admin-tab-orders').hidden = tab.dataset.tab !== 'orders';
      document.getElementById('admin-tab-inventory').hidden = tab.dataset.tab !== 'inventory';
      document.getElementById('admin-tab-services').hidden = tab.dataset.tab !== 'services';
    });
  });
}

async function loadOrders() {
  const body = document.getElementById('orders-table-body');
  const errorBox = document.getElementById('orders-error');
  errorBox.textContent = '';

  const status = document.getElementById('orders-status-filter').value || undefined;

  try {
    const result = await adminService.orders({ status, per_page: 30 });

    if (result.data.length === 0) {
      body.innerHTML = '<tr><td colspan="7">No hay pedidos con este filtro.</td></tr>';
      return;
    }

    body.innerHTML = result.data.map((order) => `
      <tr data-order-number="${order.order_number}">
        <td>${helpers.escapeHtml(order.order_number)}</td>
        <td>${helpers.escapeHtml(order.customer_name)} ${helpers.escapeHtml(order.customer_last_name)}<br><span style="color:var(--gris-texto);">${helpers.escapeHtml(order.customer_email)}</span></td>
        <td>${helpers.formatCurrency(order.total)}</td>
        <td><span class="status-badge ${statusBadgeClass(order.status)}">${order.status}</span></td>
        <td>${new Date(order.created_at).toLocaleDateString('es-CO')}</td>
        <td>
          <select data-role="next-status">
            ${ALL_STATUSES.filter((s) => s !== order.status).map((s) => `<option value="${s}">${s}</option>`).join('')}
          </select>
        </td>
        <td><button class="btn btn-primary" data-action="update-status">Cambiar</button></td>
      </tr>
    `).join('');

    body.querySelectorAll('[data-action="update-status"]').forEach((button) => {
      button.addEventListener('click', async () => {
        const row = button.closest('tr');
        const orderNumber = row.dataset.orderNumber;
        const newStatus = row.querySelector('[data-role="next-status"]').value;

        try {
          await adminService.updateOrderStatus(orderNumber, newStatus, null);
          helpers.toast(`Pedido ${orderNumber} actualizado a ${newStatus}.`, 'success');
          loadOrders();
        } catch (error) {
          helpers.toast(helpers.flattenErrors(error.fields) || error.message, 'error');
        }
      });
    });
  } catch (error) {
    handleAdminError(error, errorBox);
  }
}

async function loadInventory() {
  const body = document.getElementById('inventory-table-body');
  const errorBox = document.getElementById('inventory-error');
  errorBox.textContent = '';

  const filters = {
    search: document.getElementById('inventory-search').value || undefined,
    low_stock: document.getElementById('inventory-low-stock').checked ? 1 : undefined,
    per_page: 50,
  };

  try {
    const result = await adminService.inventory(filters);

    if (result.data.length === 0) {
      body.innerHTML = '<tr><td colspan="8">No hay productos con este filtro.</td></tr>';
      return;
    }

    body.innerHTML = result.data.map((item) => `
      <tr data-product-id="${item.product_id}">
        <td>${helpers.escapeHtml(item.name)}</td>
        <td>${helpers.escapeHtml(item.sku)}</td>
        <td>${helpers.escapeHtml(item.category_name || '—')}</td>
        <td>${item.stock_current}</td>
        <td>${item.stock_reserved}</td>
        <td style="color:${item.stock_available <= item.min_stock ? 'var(--error)' : 'var(--exito)'};font-weight:700;">${item.stock_available}</td>
        <td>${item.min_stock}</td>
        <td>
          <div class="flex gap-8" style="flex-wrap:wrap;">
            <select data-role="adjust-type">
              <option value="in">Entrada</option>
              <option value="out">Salida</option>
              <option value="adjustment">Ajuste ±</option>
            </select>
            <input type="number" data-role="adjust-quantity" placeholder="Cant." style="width:70px;">
            <input type="text" data-role="adjust-reason" placeholder="Motivo" style="width:120px;">
            <button class="btn btn-secondary" data-action="adjust">Aplicar</button>
          </div>
        </td>
      </tr>
    `).join('');

    body.querySelectorAll('[data-action="adjust"]').forEach((button) => {
      button.addEventListener('click', async () => {
        const row = button.closest('tr');
        const productId = row.dataset.productId;
        const type = row.querySelector('[data-role="adjust-type"]').value;
        const quantity = Number(row.querySelector('[data-role="adjust-quantity"]').value);
        const reason = row.querySelector('[data-role="adjust-reason"]').value;

        try {
          await adminService.adjustInventory(productId, { type, quantity, reason });
          helpers.toast('Inventario actualizado.', 'success');
          loadInventory();
        } catch (error) {
          helpers.toast(helpers.flattenErrors(error.fields) || error.message, 'error');
        }
      });
    });
  } catch (error) {
    handleAdminError(error, errorBox);
  }
}

/**
 * Gestión de servicios (permiso manage-services). El backend ya tenía el CRUD
 * completo desde la Fase 3 (ServiceController) — esto es la primera interfaz
 * que lo usa; antes solo se podía cargar contenido por seeder.
 */
let serviceCategoriesFlat = [];

function statusLabelEs(status) {
  return { draft: 'Borrador', active: 'Activo', inactive: 'Inactivo' }[status] || status;
}

async function populateServiceCategorySelect() {
  const select = document.getElementById('service-category');
  try {
    const categories = await catalogService.categories();
    serviceCategoriesFlat = helpers.flattenCategories(categories);
    select.innerHTML = '<option value="">Sin categoría</option>' +
      serviceCategoriesFlat.map((cat) => `<option value="${cat.id}">${helpers.escapeHtml(cat.name)}</option>`).join('');
  } catch (error) {
    // El formulario sigue siendo usable sin categorías precargadas.
  }
}

async function loadServices() {
  const body = document.getElementById('services-table-body');
  const errorBox = document.getElementById('services-error');
  errorBox.textContent = '';

  try {
    const result = await catalogService.services({ per_page: 50, sort: 'newest' });

    if (result.data.length === 0) {
      body.innerHTML = '<tr><td colspan="6">Todavía no hay servicios creados.</td></tr>';
      return;
    }

    body.innerHTML = result.data.map((service) => `
      <tr data-service-id="${service.id}" data-service-slug="${service.slug}">
        <td>${helpers.escapeHtml(service.name)}</td>
        <td>${helpers.escapeHtml(service.category_name || '—')}</td>
        <td>${helpers.formatCurrency(service.price)}</td>
        <td>${service.location ? helpers.escapeHtml(service.location) : '—'}</td>
        <td><span class="status-badge ${service.status === 'active' ? 'is-final-good' : ''}">${statusLabelEs(service.status)}</span></td>
        <td>
          <div class="flex gap-8">
            <button class="btn btn-secondary" data-action="edit-service">Editar</button>
            <button class="btn btn-secondary" data-action="delete-service">Eliminar</button>
          </div>
        </td>
      </tr>
    `).join('');

    body.querySelectorAll('[data-action="edit-service"]').forEach((button) => {
      button.addEventListener('click', () => {
        const row = button.closest('tr');
        openServiceForm(row.dataset.serviceSlug);
      });
    });

    body.querySelectorAll('[data-action="delete-service"]').forEach((button) => {
      button.addEventListener('click', async () => {
        const row = button.closest('tr');
        if (!window.confirm('¿Eliminar este servicio? Esta acción no se puede deshacer.')) return;

        try {
          await catalogService.deleteService(row.dataset.serviceId);
          helpers.toast('Servicio eliminado.', 'success');
          loadServices();
        } catch (error) {
          helpers.toast(error.message, 'error');
        }
      });
    });
  } catch (error) {
    handleAdminError(error, errorBox);
  }
}

function renderServiceImageThumb(serviceId, image) {
  const list = document.getElementById('service-images-list');
  const item = document.createElement('div');
  item.className = 'admin-image-list__item';
  item.dataset.imageId = image.id;
  item.innerHTML = `
    <img src="${helpers.mediaUrl('services', image.url)}" alt="Foto del servicio">
    <button type="button" class="admin-image-list__remove" aria-label="Eliminar foto">✕</button>
  `;

  item.querySelector('.admin-image-list__remove').addEventListener('click', async () => {
    try {
      await catalogService.deleteServiceImage(serviceId, image.id);
      item.remove();
    } catch (error) {
      helpers.toast(error.message, 'error');
    }
  });

  list.appendChild(item);
}

function resetServiceForm() {
  document.getElementById('service-form').reset();
  document.getElementById('service-id').value = '';
  document.getElementById('service-slug').value = '';
  document.getElementById('service-images-list').innerHTML = '';
  document.getElementById('service-images-section').hidden = true;
  document.getElementById('service-modal-title').textContent = 'Nuevo servicio';
  document.getElementById('service-submit-btn').textContent = 'Crear servicio';
  document.getElementById('service-form-error').textContent = '';
}

/** @param {string|null} slug - null para crear, slug del servicio para editar. */
async function openServiceForm(slug) {
  resetServiceForm();
  document.getElementById('service-modal-overlay').classList.add('is-open');

  if (!slug) return;

  try {
    const service = await catalogService.service(slug);

    document.getElementById('service-id').value = service.id;
    document.getElementById('service-slug').value = service.slug;
    document.getElementById('service-name').value = service.name;
    document.getElementById('service-category').value = service.category_id || '';
    document.getElementById('service-price').value = service.price;
    document.getElementById('service-duration').value = service.duration_minutes || '';
    document.getElementById('service-location').value = service.location || '';
    document.getElementById('service-description').value = service.description || '';
    document.getElementById('service-cancellation').value = service.cancellation_policy || '';
    document.getElementById('service-status').value = service.status;

    document.getElementById('service-modal-title').textContent = 'Editar servicio';
    document.getElementById('service-submit-btn').textContent = 'Guardar cambios';

    const imagesSection = document.getElementById('service-images-section');
    imagesSection.hidden = false;
    (service.images || []).forEach((image) => renderServiceImageThumb(service.id, image));
  } catch (error) {
    helpers.toast(error.message, 'error');
    closeServiceForm();
  }
}

function closeServiceForm() {
  document.getElementById('service-modal-overlay').classList.remove('is-open');
}

function serviceFormPayload() {
  return {
    name: document.getElementById('service-name').value.trim(),
    category_id: document.getElementById('service-category').value || undefined,
    price: document.getElementById('service-price').value,
    duration_minutes: document.getElementById('service-duration').value || undefined,
    location: document.getElementById('service-location').value.trim() || undefined,
    description: document.getElementById('service-description').value.trim() || undefined,
    cancellation_policy: document.getElementById('service-cancellation').value.trim() || undefined,
    status: document.getElementById('service-status').value,
  };
}

function wireServiceManagement() {
  document.getElementById('new-service-btn').addEventListener('click', () => openServiceForm(null));
  document.getElementById('service-modal-close').addEventListener('click', closeServiceForm);
  document.getElementById('service-modal-overlay').addEventListener('click', (event) => {
    if (event.target === document.getElementById('service-modal-overlay')) closeServiceForm();
  });

  document.getElementById('service-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const errorBox = document.getElementById('service-form-error');
    errorBox.textContent = '';

    const id = document.getElementById('service-id').value;
    const payload = serviceFormPayload();

    try {
      let service;
      if (id) {
        service = await catalogService.updateService(id, payload);
        helpers.toast('Servicio actualizado.', 'success');
      } else {
        service = await catalogService.createService(payload);
        helpers.toast('Servicio creado. Ahora puedes agregarle fotos.', 'success');
      }

      // Tras crear, el formulario pasa a modo "edición" del servicio recién creado
      // (sin cerrar el modal) para que se puedan subir fotos de inmediato — las
      // imágenes solo se pueden asociar a un servicio que ya existe.
      document.getElementById('service-id').value = service.id;
      document.getElementById('service-slug').value = service.slug;
      document.getElementById('service-modal-title').textContent = 'Editar servicio';
      document.getElementById('service-submit-btn').textContent = 'Guardar cambios';
      document.getElementById('service-images-section').hidden = false;

      loadServices();
    } catch (error) {
      errorBox.textContent = helpers.flattenErrors(error.fields) || error.message;
    }
  });

  document.getElementById('service-image-input').addEventListener('change', async (event) => {
    const file = event.target.files[0];
    const serviceId = document.getElementById('service-id').value;
    if (!file || !serviceId) return;

    try {
      const image = await catalogService.uploadServiceImage(serviceId, file);
      renderServiceImageThumb(serviceId, image);
    } catch (error) {
      helpers.toast(helpers.flattenErrors(error.fields) || error.message, 'error');
    } finally {
      event.target.value = '';
    }
  });
}

function handleAdminError(error, errorBox) {
  if (error.status === 401 || error.status === 403) {
    errorBox.textContent = 'No tienes permisos para ver esta sección.';
    return;
  }
  errorBox.textContent = error.message;
}

async function initAdminPage() {
  if (!authService.isAuthenticated()) {
    document.querySelector('main').innerHTML = '<p class="error-state mt-16">Inicia sesión con una cuenta con permisos administrativos.</p>';
    return;
  }

  wireTabs();
  wireServiceManagement();
  document.getElementById('orders-status-filter').addEventListener('change', loadOrders);
  document.getElementById('inventory-filter-form').addEventListener('submit', (event) => {
    event.preventDefault();
    loadInventory();
  });

  loadOrders();
  loadInventory();
  populateServiceCategorySelect();
  loadServices();
}

document.addEventListener('DOMContentLoaded', initAdminPage);
