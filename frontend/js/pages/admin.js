/**
 * Panel administrativo básico (Fase 6): pedidos e inventario. La seguridad
 * real está en la API (JWT + manage-orders/manage-inventory); aquí solo se
 * oculta el contenido si la petición falla con 401/403, para una UX clara.
 */
const GOOD_FINAL = ['ENTREGADO'];
const BAD_FINAL = ['CANCELADO', 'DEVUELTO'];
const MAX_CATALOG_IMAGES = 6; // debe coincidir con app.uploads.max_images_per_catalog_item (backend/config/app.php)

function statusBadgeClass(status) {
  if (GOOD_FINAL.includes(status)) return 'is-final-good';
  if (BAD_FINAL.includes(status)) return 'is-final-bad';
  return '';
}

/**
 * En vez de un selector con TODOS los estados posibles (confuso: ¿cuál sigue?),
 * un botón por cada estado al que la máquina de estados (sección 22,
 * OrderStatusTransitions) permite avanzar desde el actual — el backend ya
 * calcula esa lista (`order.next_statuses`) para no duplicar el grafo aquí.
 * Cancelar/devolver quedan en rojo y piden confirmación por ser irreversibles.
 */
function nextStatusActionsMarkup(nextStatuses) {
  if (nextStatuses.length === 0) {
    return '<span style="color:var(--gris-texto-tenue);font-size:0.8rem;">Sin más acciones</span>';
  }

  return `<div class="flex gap-8" style="flex-wrap:wrap;">${nextStatuses.map((status, index) => {
    const isDanger = BAD_FINAL.includes(status);
    const btnClass = isDanger ? 'btn-danger' : (index === 0 ? 'btn-primary' : 'btn-secondary');
    const confirmAttr = isDanger
      ? ` data-confirm="¿${helpers.orderActionLabel(status)}? Esta acción no se puede deshacer."`
      : '';

    return `<button class="btn ${btnClass}" data-action="advance-status" data-status="${status}"${confirmAttr}>${helpers.orderActionLabel(status)}</button>`;
  }).join('')}</div>`;
}

const ADMIN_SECTIONS = {
  dashboard: { title: 'Resumen', hint: 'Cómo va el negocio: ventas, pedidos y lo que necesita tu atención.' },
  orders: { title: 'Pedidos', hint: 'Cambia el estado de cada pedido con la acción que corresponde según en qué paso está.' },
  reservations: { title: 'Reservas', hint: 'Servicios agendados por los clientes, ordenados por fecha y hora.' },
  customers: { title: 'Clientes', hint: 'Cuentas de clientes registrados, con su historial de compras.' },
  inventory: { title: 'Inventario', hint: 'Stock disponible por producto y ajustes manuales con trazabilidad.' },
  services: { title: 'Servicios', hint: 'Crea, edita y elimina los servicios publicados en el catálogo.' },
  products: { title: 'Productos', hint: 'Crea, edita y elimina los productos publicados en el catálogo.' },
  brands: { title: 'Marcas', hint: 'Fabricantes/marcas de los productos (ej. AKT, Bajaj) — lo más parecido a "proveedores" en un marketplace, donde no se le compra inventario a terceros para revender.' },
};

/** Barra lateral (fija en escritorio, cajón deslizable en pantallas angostas). */
function wireSidebar() {
  const sidebar = document.getElementById('admin-sidebar');
  const backdrop = document.getElementById('admin-drawer-backdrop');

  function openDrawer() {
    sidebar.classList.add('is-open');
    backdrop.classList.add('is-open');
  }
  function closeDrawer() {
    sidebar.classList.remove('is-open');
    backdrop.classList.remove('is-open');
  }

  document.getElementById('admin-drawer-toggle').addEventListener('click', openDrawer);
  document.getElementById('admin-sidebar-close').addEventListener('click', closeDrawer);
  backdrop.addEventListener('click', closeDrawer);

  document.querySelectorAll('.admin-nav-link').forEach((link) => {
    link.addEventListener('click', () => {
      document.querySelectorAll('.admin-nav-link').forEach((l) => l.classList.remove('is-active'));
      link.classList.add('is-active');

      const tab = link.dataset.tab;
      ['dashboard', 'orders', 'reservations', 'customers', 'inventory', 'services', 'products', 'brands'].forEach((name) => {
        const section = document.getElementById(`admin-tab-${name}`);
        section.hidden = name !== tab;
        if (name === tab) {
          // Se reinicia la animación de entrada quitando y volviendo a poner la clase.
          section.classList.remove('admin-panel-enter');
          void section.offsetWidth; // fuerza reflow para que el navegador note el cambio
          section.classList.add('admin-panel-enter');
        }
      });

      document.getElementById('admin-section-title').textContent = ADMIN_SECTIONS[tab].title;
      document.getElementById('admin-section-hint').textContent = ADMIN_SECTIONS[tab].hint;

      closeDrawer();
    });
  });
}

/**
 * Resumen del negocio (sección 28): tarjetas de números + dos gráficas
 * (ingresos por día, pedidos por estado) + top de productos vendidos. Todo
 * viene calculado del backend con datos reales (DashboardController) —
 * aquí solo se pinta lo que ya llega listo.
 */
async function loadDashboard() {
  const errorBox = document.getElementById('dashboard-error');
  errorBox.textContent = '';

  try {
    const summary = await adminService.dashboardSummary();

    document.getElementById('dashboard-stat-cards').innerHTML = [
      statCardMarkup('💰', 'Ingresos (30 días)', helpers.formatCurrency(summary.revenue.last_30_days)),
      statCardMarkup('🧾', 'Pedidos totales', summary.revenue.orders_count),
      statCardMarkup('🎯', 'Ticket promedio', helpers.formatCurrency(summary.revenue.average_ticket)),
      statCardMarkup('📅', 'Reservas próximas', summary.upcoming_reservations_count),
      statCardMarkup('⚠️', 'Productos con stock bajo', summary.low_stock_count),
      statCardMarkup('🧑‍🤝‍🧑', 'Usuarios nuevos (30 días)', summary.new_users_last_30_days),
    ].join('');

    document.getElementById('dashboard-revenue-chart').innerHTML = barChartMarkup(
      summary.revenue_by_day,
      {
        valueKey: 'revenue',
        labelKey: 'date',
        formatValue: (v) => helpers.formatCurrency(v),
      }
    );
    // Las fechas completas solo se ven en el tooltip (<title>); en el eje X
    // se muestra únicamente día/mes para que no se amontonen las etiquetas.
    document.querySelectorAll('#dashboard-revenue-chart .chart-axis-label').forEach((label, i) => {
      const iso = summary.revenue_by_day[i * (summary.revenue_by_day.length > 10 ? 2 : 1)]?.date;
      if (iso) label.textContent = iso.slice(5).replace('-', '/');
    });

    const statusRows = Object.entries(summary.orders_by_status).map(([status, count]) => ({
      label: helpers.orderStatusLabel(status),
      value: count,
    }));
    document.getElementById('dashboard-status-chart').innerHTML = horizontalBarsMarkup(statusRows);

    const topProductsList = document.getElementById('dashboard-top-products');
    if (summary.top_products.length === 0) {
      topProductsList.innerHTML = '<p class="empty-state">Todavía no hay ventas registradas.</p>';
    } else {
      topProductsList.innerHTML = summary.top_products.map((product, index) => `
        <li>
          <span><span class="rank">#${index + 1}</span>${helpers.escapeHtml(product.name)}</span>
          <span>${product.units_sold} und. — ${helpers.formatCurrency(product.revenue)}</span>
        </li>
      `).join('');
    }
  } catch (error) {
    handleAdminError(error, errorBox);
  }
}

async function loadOrders() {
  const body = document.getElementById('orders-table-body');
  const errorBox = document.getElementById('orders-error');
  errorBox.textContent = '';

  const status = document.getElementById('orders-status-filter').value || undefined;

  try {
    const result = await adminService.orders({ status, per_page: 30 });

    if (result.data.length === 0) {
      body.innerHTML = '<tr><td colspan="6">No hay pedidos con este filtro.</td></tr>';
      return;
    }

    body.innerHTML = result.data.map((order) => `
      <tr data-order-number="${order.order_number}">
        <td>${helpers.escapeHtml(order.order_number)}</td>
        <td>${helpers.escapeHtml(order.customer_name)} ${helpers.escapeHtml(order.customer_last_name)}<br><span style="color:var(--gris-texto);">${helpers.escapeHtml(order.customer_email)}</span></td>
        <td>${helpers.formatCurrency(order.total)}</td>
        <td><span class="status-badge ${statusBadgeClass(order.status)}">${helpers.orderStatusLabel(order.status)}</span></td>
        <td>${new Date(order.created_at).toLocaleDateString('es-CO')}</td>
        <td>${nextStatusActionsMarkup(order.next_statuses || [])}</td>
      </tr>
    `).join('');

    body.querySelectorAll('[data-action="advance-status"]').forEach((button) => {
      button.addEventListener('click', async () => {
        const row = button.closest('tr');
        const orderNumber = row.dataset.orderNumber;
        const newStatus = button.dataset.status;

        if (button.dataset.confirm && !window.confirm(button.dataset.confirm)) return;

        try {
          await adminService.updateOrderStatus(orderNumber, newStatus, null);
          helpers.toast(`Pedido ${orderNumber}: ${helpers.orderStatusLabel(newStatus)}.`, 'success');
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

/**
 * Reservas de servicios (sección 12): cada fila ES un pedido con un servicio
 * agendado — el cambio de estado reutiliza el mismo endpoint y los mismos
 * botones "siguiente paso" que la pestaña Pedidos (adminService.updateOrderStatus).
 */
async function loadReservations() {
  const body = document.getElementById('reservations-table-body');
  const errorBox = document.getElementById('reservations-error');
  errorBox.textContent = '';

  const filters = {
    date: document.getElementById('reservations-date-filter').value || undefined,
    upcoming_only: document.getElementById('reservations-upcoming-only').checked ? 1 : undefined,
    per_page: 50,
  };

  try {
    const result = await adminService.reservations(filters);

    if (result.data.length === 0) {
      body.innerHTML = '<tr><td colspan="7">No hay reservas con este filtro.</td></tr>';
      return;
    }

    body.innerHTML = result.data.map((reservation) => `
      <tr data-order-number="${reservation.order_number}">
        <td>${helpers.formatDateTime(reservation.scheduled_at)}</td>
        <td>${helpers.escapeHtml(reservation.service_name)}</td>
        <td>${helpers.escapeHtml(reservation.customer_name)} ${helpers.escapeHtml(reservation.customer_last_name)}<br><span style="color:var(--gris-texto);">${helpers.escapeHtml(reservation.customer_email)}</span></td>
        <td>${reservation.customer_phone ? helpers.escapeHtml(reservation.customer_phone) : '—'}</td>
        <td>${helpers.escapeHtml(reservation.order_number)}</td>
        <td><span class="status-badge ${statusBadgeClass(reservation.status)}">${helpers.orderStatusLabel(reservation.status)}</span></td>
        <td>${nextStatusActionsMarkup(reservation.next_statuses || [])}</td>
      </tr>
    `).join('');

    body.querySelectorAll('[data-action="advance-status"]').forEach((button) => {
      button.addEventListener('click', async () => {
        const row = button.closest('tr');
        const orderNumber = row.dataset.orderNumber;
        const newStatus = button.dataset.status;

        if (button.dataset.confirm && !window.confirm(button.dataset.confirm)) return;

        try {
          await adminService.updateOrderStatus(orderNumber, newStatus, null);
          helpers.toast(`Reserva del pedido ${orderNumber}: ${helpers.orderStatusLabel(newStatus)}.`, 'success');
          loadReservations();
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
 * Clientes registrados (sección 28: "dónde se ven los clientes"). Este
 * marketplace no maneja proveedores externos de inventario — cada producto
 * es propio de CASTAMOTO o de una tienda vendedora del marketplace; esa
 * gestión de tiendas/vendedores es la Fase 10 del prompt maestro, todavía
 * no construida (ver comentario en AdminCustomerController).
 */
async function loadCustomers() {
  const body = document.getElementById('customers-table-body');
  const errorBox = document.getElementById('customers-error');
  errorBox.textContent = '';

  const search = document.getElementById('customers-search').value.trim() || undefined;

  try {
    const result = await adminService.customers({ search, per_page: 50 });

    if (result.data.length === 0) {
      body.innerHTML = '<tr><td colspan="6">No hay clientes registrados todavía.</td></tr>';
      return;
    }

    body.innerHTML = result.data.map((customer) => `
      <tr>
        <td>${helpers.escapeHtml(customer.name)} ${helpers.escapeHtml(customer.last_name)}</td>
        <td>${helpers.escapeHtml(customer.email)}${customer.phone ? `<br><span style="color:var(--gris-texto);">${helpers.escapeHtml(customer.phone)}</span>` : ''}</td>
        <td>${new Date(customer.created_at).toLocaleDateString('es-CO')}</td>
        <td>${customer.email_verified_at ? '<span class="status-badge is-final-good">Sí</span>' : '<span class="status-badge is-final-bad">No</span>'}</td>
        <td>${customer.orders_count}</td>
        <td>${helpers.formatCurrency(customer.total_spent)}</td>
      </tr>
    `).join('');
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

/** Actualiza el contador "(x/6)" y deshabilita el input al llegar al máximo
 * — el tope real lo aplica el backend (ver UploadServiceImageUseCase /
 * UploadProductImageUseCase), esto es solo para que la UI no invite a
 * seguir seleccionando fotos que el servidor va a rechazar. */
function updateImageCounter(prefix) {
  const count = document.getElementById(`${prefix}-images-list`).children.length;
  document.getElementById(`${prefix}-images-count`).textContent = `(${count}/${MAX_CATALOG_IMAGES})`;
  document.getElementById(`${prefix}-image-input`).disabled = count >= MAX_CATALOG_IMAGES;
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
      updateImageCounter('service');
    } catch (error) {
      helpers.toast(error.message, 'error');
    }
  });

  list.appendChild(item);
  updateImageCounter('service');
}

function resetServiceForm() {
  document.getElementById('service-form').reset();
  document.getElementById('service-id').value = '';
  document.getElementById('service-slug').value = '';
  document.getElementById('service-images-list').innerHTML = '';
  document.getElementById('service-images-section').hidden = true;
  document.getElementById('service-image-input').disabled = false;
  document.getElementById('service-images-count').textContent = '(0/6)';
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
    document.getElementById('service-latitude').value = service.latitude ?? '';
    document.getElementById('service-longitude').value = service.longitude ?? '';
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
    latitude: document.getElementById('service-latitude').value || undefined,
    longitude: document.getElementById('service-longitude').value || undefined,
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

  document.getElementById('service-use-location-btn').addEventListener('click', () => {
    if (!navigator.geolocation) {
      helpers.toast('Tu navegador no soporta geolocalización.', 'error');
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (position) => {
        document.getElementById('service-latitude').value = position.coords.latitude.toFixed(7);
        document.getElementById('service-longitude').value = position.coords.longitude.toFixed(7);
        helpers.toast('Ubicación actual cargada.', 'success');
      },
      () => helpers.toast('No fue posible obtener tu ubicación (¿permiso denegado?).', 'error')
    );
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
    const files = Array.from(event.target.files);
    const serviceId = document.getElementById('service-id').value;
    if (files.length === 0 || !serviceId) return;

    const remaining = MAX_CATALOG_IMAGES - document.getElementById('service-images-list').children.length;
    if (files.length > remaining) {
      helpers.toast(`Solo se subirán ${remaining} de las ${files.length} fotos seleccionadas (máximo ${MAX_CATALOG_IMAGES} por servicio).`, 'info');
    }

    // Se suben una por una (el endpoint acepta un archivo por petición) — el
    // orden importa para que el giro 360° siga la secuencia elegida.
    for (const file of files.slice(0, remaining)) {
      try {
        const image = await catalogService.uploadServiceImage(serviceId, file);
        renderServiceImageThumb(serviceId, image);
      } catch (error) {
        helpers.toast(helpers.flattenErrors(error.fields) || error.message, 'error');
        break;
      }
    }

    event.target.value = '';
  });
}

/**
 * Gestión de productos (permiso manage-products) — mismo patrón que la
 * gestión de servicios de arriba. El stock NO se edita aquí a propósito: se
 * ajusta desde la pestaña "Inventario" (`adminService.adjustInventory`), que
 * sí deja trazabilidad en `inventory_movements` (Fase 6) — permitir editarlo
 * también desde este formulario rompería esa única fuente de verdad.
 */
async function populateProductSelects() {
  const categorySelect = document.getElementById('product-category');
  const brandSelect = document.getElementById('product-brand');

  try {
    const [categories, brands] = await Promise.all([catalogService.categories(), catalogService.brands()]);
    const flatCategories = helpers.flattenCategories(categories);

    categorySelect.innerHTML = '<option value="">Selecciona una categoría</option>' +
      flatCategories.map((cat) => `<option value="${cat.id}">${helpers.escapeHtml(cat.name)}</option>`).join('');
    brandSelect.innerHTML = '<option value="">Sin marca</option>' +
      brands.map((brand) => `<option value="${brand.id}">${helpers.escapeHtml(brand.name)}</option>`).join('');
  } catch (error) {
    // El formulario sigue siendo usable sin categorías/marcas precargadas.
  }
}

async function loadProductsAdmin() {
  const body = document.getElementById('products-table-body');
  const errorBox = document.getElementById('products-error');
  errorBox.textContent = '';

  try {
    const result = await catalogService.products({ per_page: 50 });

    if (result.data.length === 0) {
      body.innerHTML = '<tr><td colspan="7">Todavía no hay productos creados.</td></tr>';
      return;
    }

    body.innerHTML = result.data.map((product) => `
      <tr data-product-id="${product.id}" data-product-slug="${product.slug}">
        <td>${helpers.escapeHtml(product.name)}</td>
        <td>${helpers.escapeHtml(product.sku)}</td>
        <td>${helpers.escapeHtml(product.category_name || '—')}</td>
        <td>${helpers.formatCurrency(product.price)}</td>
        <td>${product.stock}</td>
        <td><span class="status-badge ${product.status === 'active' ? 'is-final-good' : ''}">${statusLabelEs(product.status)}</span></td>
        <td>
          <div class="flex gap-8">
            <button class="btn btn-secondary" data-action="edit-product">Editar</button>
            <button class="btn btn-secondary" data-action="delete-product">Eliminar</button>
          </div>
        </td>
      </tr>
    `).join('');

    body.querySelectorAll('[data-action="edit-product"]').forEach((button) => {
      button.addEventListener('click', () => {
        const row = button.closest('tr');
        openProductForm(row.dataset.productSlug);
      });
    });

    body.querySelectorAll('[data-action="delete-product"]').forEach((button) => {
      button.addEventListener('click', async () => {
        const row = button.closest('tr');
        if (!window.confirm('¿Eliminar este producto? Esta acción no se puede deshacer.')) return;

        try {
          await catalogService.deleteProduct(row.dataset.productId);
          helpers.toast('Producto eliminado.', 'success');
          loadProductsAdmin();
        } catch (error) {
          helpers.toast(error.message, 'error');
        }
      });
    });
  } catch (error) {
    handleAdminError(error, errorBox);
  }
}

function renderProductImageThumb(productId, image) {
  const list = document.getElementById('product-images-list');
  const item = document.createElement('div');
  item.className = 'admin-image-list__item';
  item.dataset.imageId = image.id;
  item.title = image.is_primary ? 'Foto principal' : 'Marcar como principal';
  item.innerHTML = `
    <img src="${helpers.mediaUrl('products', image.url)}" alt="Foto del producto" style="${image.is_primary ? 'outline:2px solid var(--amarillo);' : ''}">
    <button type="button" class="admin-image-list__remove" aria-label="Eliminar foto">✕</button>
  `;

  item.querySelector('img').addEventListener('click', async () => {
    try {
      await catalogService.setPrimaryProductImage(productId, image.id);
      document.querySelectorAll('#product-images-list img').forEach((img) => { img.style.outline = ''; });
      item.querySelector('img').style.outline = '2px solid var(--amarillo)';
    } catch (error) {
      helpers.toast(error.message, 'error');
    }
  });

  item.querySelector('.admin-image-list__remove').addEventListener('click', async () => {
    try {
      await catalogService.deleteProductImage(productId, image.id);
      item.remove();
      updateImageCounter('product');
    } catch (error) {
      helpers.toast(error.message, 'error');
    }
  });

  list.appendChild(item);
  updateImageCounter('product');
}

function resetProductForm() {
  document.getElementById('product-form').reset();
  document.getElementById('product-id').value = '';
  document.getElementById('product-slug').value = '';
  document.getElementById('product-images-list').innerHTML = '';
  document.getElementById('product-images-section').hidden = true;
  document.getElementById('product-image-input').disabled = false;
  document.getElementById('product-images-count').textContent = '(0/6)';
  document.getElementById('product-stock').disabled = false;
  document.getElementById('product-stock-hint').hidden = true;
  document.getElementById('product-modal-title').textContent = 'Nuevo producto';
  document.getElementById('product-submit-btn').textContent = 'Crear producto';
  document.getElementById('product-form-error').textContent = '';
}

/** @param {string|null} slug - null para crear, slug del producto para editar. */
async function openProductForm(slug) {
  resetProductForm();
  document.getElementById('product-modal-overlay').classList.add('is-open');

  if (!slug) return;

  try {
    const product = await catalogService.product(slug);

    document.getElementById('product-id').value = product.id;
    document.getElementById('product-slug').value = product.slug;
    document.getElementById('product-name').value = product.name;
    document.getElementById('product-sku').value = product.sku;
    document.getElementById('product-category').value = product.category_id || '';
    document.getElementById('product-brand').value = product.brand_id || '';
    document.getElementById('product-price').value = product.price;
    document.getElementById('product-previous-price').value = product.previous_price || '';
    document.getElementById('product-stock').value = product.stock;
    document.getElementById('product-min-stock').value = product.min_stock || 0;
    document.getElementById('product-short-description').value = product.short_description || '';
    document.getElementById('product-status').value = product.status;

    // El stock ya existe en inventario: se bloquea aquí a propósito (ver comentario arriba).
    document.getElementById('product-stock').disabled = true;
    document.getElementById('product-stock-hint').hidden = false;

    document.getElementById('product-modal-title').textContent = 'Editar producto';
    document.getElementById('product-submit-btn').textContent = 'Guardar cambios';

    const imagesSection = document.getElementById('product-images-section');
    imagesSection.hidden = false;
    (product.images || []).forEach((image) => renderProductImageThumb(product.id, image));
  } catch (error) {
    helpers.toast(error.message, 'error');
    closeProductForm();
  }
}

function closeProductForm() {
  document.getElementById('product-modal-overlay').classList.remove('is-open');
}

function productFormPayload() {
  const isEditing = !!document.getElementById('product-id').value;

  return {
    name: document.getElementById('product-name').value.trim(),
    // Vacío = el backend genera un SKU único automático (sección 10); en
    // edición, vacío conserva el SKU actual (ver UpdateProductUseCase).
    sku: document.getElementById('product-sku').value.trim() || undefined,
    category_id: document.getElementById('product-category').value,
    brand_id: document.getElementById('product-brand').value || undefined,
    price: document.getElementById('product-price').value,
    previous_price: document.getElementById('product-previous-price').value || undefined,
    // Al editar, el input está deshabilitado (readonly) — su .value sigue siendo
    // el stock actual precargado, así que el campo "required" del backend se
    // cumple sin permitir que este formulario lo cambie de verdad.
    stock: document.getElementById('product-stock').value || (isEditing ? '0' : undefined),
    min_stock: document.getElementById('product-min-stock').value || undefined,
    short_description: document.getElementById('product-short-description').value.trim() || undefined,
    status: document.getElementById('product-status').value,
  };
}

function wireProductManagement() {
  document.getElementById('new-product-btn').addEventListener('click', () => openProductForm(null));
  document.getElementById('product-modal-close').addEventListener('click', closeProductForm);
  document.getElementById('product-modal-overlay').addEventListener('click', (event) => {
    if (event.target === document.getElementById('product-modal-overlay')) closeProductForm();
  });

  document.getElementById('product-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const errorBox = document.getElementById('product-form-error');
    errorBox.textContent = '';

    const id = document.getElementById('product-id').value;
    const payload = productFormPayload();

    try {
      let product;
      if (id) {
        product = await catalogService.updateProduct(id, payload);
        helpers.toast('Producto actualizado.', 'success');
      } else {
        product = await catalogService.createProduct(payload);
        helpers.toast('Producto creado. Ahora puedes agregarle fotos.', 'success');
      }

      // Mismo criterio que servicios: tras crear, el formulario pasa a modo
      // "edición" sin cerrarse, para poder subir fotos de inmediato.
      document.getElementById('product-id').value = product.id;
      document.getElementById('product-slug').value = product.slug;
      // Muestra el SKU real ya guardado — si se dejó vacío, este es el que
      // el backend generó automáticamente (sección 10).
      document.getElementById('product-sku').value = product.sku;
      document.getElementById('product-modal-title').textContent = 'Editar producto';
      document.getElementById('product-submit-btn').textContent = 'Guardar cambios';
      document.getElementById('product-stock').disabled = true;
      document.getElementById('product-stock-hint').hidden = false;
      document.getElementById('product-images-section').hidden = false;

      loadProductsAdmin();
    } catch (error) {
      errorBox.textContent = helpers.flattenErrors(error.fields) || error.message;
    }
  });

  document.getElementById('product-image-input').addEventListener('change', async (event) => {
    const files = Array.from(event.target.files);
    const productId = document.getElementById('product-id').value;
    if (files.length === 0 || !productId) return;

    const remaining = MAX_CATALOG_IMAGES - document.getElementById('product-images-list').children.length;
    if (files.length > remaining) {
      helpers.toast(`Solo se subirán ${remaining} de las ${files.length} fotos seleccionadas (máximo ${MAX_CATALOG_IMAGES} por producto).`, 'info');
    }

    for (const file of files.slice(0, remaining)) {
      const isFirstImage = document.getElementById('product-images-list').children.length === 0;

      try {
        const image = await catalogService.uploadProductImage(productId, file, isFirstImage);
        renderProductImageThumb(productId, { ...image, is_primary: isFirstImage });
      } catch (error) {
        helpers.toast(helpers.flattenErrors(error.fields) || error.message, 'error');
        break;
      }
    }

    event.target.value = '';
  });
}

/**
 * Marcas (permiso manage-brands, ya existía desde la Fase 3 en el backend
 * sin ninguna interfaz). Es lo más parecido a "proveedores" que tiene un
 * marketplace como este: no se compra inventario a terceros para revenderlo,
 * cada producto ya viene con su marca/fabricante real (ej. AKT, Bajaj).
 */
async function loadBrands() {
  const body = document.getElementById('brands-table-body');
  const errorBox = document.getElementById('brands-error');
  errorBox.textContent = '';

  try {
    const brands = await catalogService.brands();

    if (brands.length === 0) {
      body.innerHTML = '<tr><td colspan="4">Todavía no hay marcas creadas.</td></tr>';
      return;
    }

    body.innerHTML = brands.map((brand) => `
      <tr data-brand-id="${brand.id}">
        <td>${helpers.escapeHtml(brand.name)}</td>
        <td>${brand.logo ? `<img src="${helpers.escapeHtml(brand.logo)}" alt="" style="height:28px;width:auto;border-radius:4px;">` : '—'}</td>
        <td><span class="status-badge ${brand.status === 'active' ? 'is-final-good' : ''}">${brand.status === 'active' ? 'Activa' : 'Inactiva'}</span></td>
        <td>
          <div class="flex gap-8">
            <button class="btn btn-secondary" data-action="edit-brand">Editar</button>
            <button class="btn btn-secondary" data-action="delete-brand">Eliminar</button>
          </div>
        </td>
      </tr>
    `).join('');

    body.querySelectorAll('[data-action="edit-brand"]').forEach((button) => {
      button.addEventListener('click', () => {
        const brand = brands.find((b) => b.id === Number(button.closest('tr').dataset.brandId));
        openBrandForm(brand);
      });
    });

    body.querySelectorAll('[data-action="delete-brand"]').forEach((button) => {
      button.addEventListener('click', async () => {
        const id = button.closest('tr').dataset.brandId;
        if (!window.confirm('¿Eliminar esta marca? Los productos que la usan quedarán sin marca asignada.')) return;

        try {
          await catalogService.deleteBrand(id);
          helpers.toast('Marca eliminada.', 'success');
          loadBrands();
        } catch (error) {
          helpers.toast(error.message, 'error');
        }
      });
    });
  } catch (error) {
    handleAdminError(error, errorBox);
  }
}

function openBrandForm(brand) {
  document.getElementById('brand-form').reset();
  document.getElementById('brand-form-error').textContent = '';
  document.getElementById('brand-id').value = brand ? brand.id : '';
  document.getElementById('brand-name').value = brand ? brand.name : '';
  document.getElementById('brand-logo').value = brand ? (brand.logo || '') : '';
  document.getElementById('brand-status').value = brand ? brand.status : 'active';
  document.getElementById('brand-modal-title').textContent = brand ? 'Editar marca' : 'Nueva marca';
  document.getElementById('brand-submit-btn').textContent = brand ? 'Guardar cambios' : 'Crear marca';
  document.getElementById('brand-modal-overlay').classList.add('is-open');
}

function wireBrandManagement() {
  document.getElementById('new-brand-btn').addEventListener('click', () => openBrandForm(null));
  document.getElementById('brand-modal-close').addEventListener('click', () => {
    document.getElementById('brand-modal-overlay').classList.remove('is-open');
  });
  document.getElementById('brand-modal-overlay').addEventListener('click', (event) => {
    if (event.target === document.getElementById('brand-modal-overlay')) {
      document.getElementById('brand-modal-overlay').classList.remove('is-open');
    }
  });

  document.getElementById('brand-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const errorBox = document.getElementById('brand-form-error');
    errorBox.textContent = '';

    const id = document.getElementById('brand-id').value;
    const payload = {
      name: document.getElementById('brand-name').value.trim(),
      logo: document.getElementById('brand-logo').value.trim() || undefined,
      status: document.getElementById('brand-status').value,
    };

    try {
      if (id) {
        await catalogService.updateBrand(id, payload);
        helpers.toast('Marca actualizada.', 'success');
      } else {
        await catalogService.createBrand(payload);
        helpers.toast('Marca creada.', 'success');
      }
      document.getElementById('brand-modal-overlay').classList.remove('is-open');
      loadBrands();
    } catch (error) {
      errorBox.textContent = helpers.flattenErrors(error.fields) || error.message;
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

  wireSidebar();
  wireServiceManagement();
  wireProductManagement();
  wireBrandManagement();
  document.getElementById('orders-status-filter').addEventListener('change', loadOrders);
  document.getElementById('inventory-filter-form').addEventListener('submit', (event) => {
    event.preventDefault();
    loadInventory();
  });
  document.getElementById('reservations-date-filter').addEventListener('change', loadReservations);
  document.getElementById('reservations-upcoming-only').addEventListener('change', loadReservations);
  document.getElementById('reservations-clear-filter-btn').addEventListener('click', () => {
    document.getElementById('reservations-date-filter').value = '';
    loadReservations();
  });
  document.getElementById('customers-search-form').addEventListener('submit', (event) => {
    event.preventDefault();
    loadCustomers();
  });

  loadDashboard();
  loadOrders();
  loadReservations();
  loadCustomers();
  loadInventory();
  populateServiceCategorySelect();
  loadServices();
  populateProductSelects();
  loadProductsAdmin();
  loadBrands();
}

document.addEventListener('DOMContentLoaded', initAdminPage);
