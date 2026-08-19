/**
 * Reserva de "Lavado de Motos y Cascos" — wizard paso a paso sobre la MISMA
 * base de reservas de servicios que ya existe (sección 12: scheduled_at,
 * carrito, checkout, panel admin de Reservas). No se reinventa nada nuevo
 * del lado del backend: "Lavado de Moto" y "Lavado de Casco" son servicios
 * reales del catálogo (creados en /admin → Servicios, con su propio precio
 * editable ahí — "los valores pueden variar" se resuelve solo con eso, sin
 * hardcodear ningún precio acá).
 */
const WASH_SLUGS = { moto: 'lavado-de-moto', casco: 'lavado-de-casco' };

let washState = {
  step: 1,
  type: null, // 'moto' | 'casco'
  service: null, // el servicio real cargado (precio, id, etc.)
  date: '',
  time: '',
  name: '',
  phone: '',
  itemNote: '', // modelo de moto / marca del casco — texto libre, opcional
  primaryAddress: null, // se carga una vez al inicio (ver loadPrimaryAddress) — respaldo de teléfono si el perfil no lo tiene
};

/**
 * El teléfono puede estar cargado en el PERFIL (Mi perfil) o en una
 * DIRECCIÓN guardada (cada una tiene el suyo, sección 9) — son datos
 * distintos que antes el wizard solo miraba por separado. Se trae la
 * dirección principal una sola vez al abrir el wizard para poder usarla
 * como respaldo del teléfono (y a futuro, de la dirección de entrega) sin
 * obligar a cargar todo de nuevo si ya existe en algún lado.
 */
async function loadPrimaryAddress() {
  if (!authService.isAuthenticated()) return;
  try {
    const addresses = await cartService.addresses();
    washState.primaryAddress = addresses.find((a) => a.is_primary) || addresses[0] || null;
  } catch (error) {
    // Sin direcciones todavía, o falló la carga: el wizard sigue con los
    // datos del perfil nomás, no es bloqueante.
  }
}

function setStep(step) {
  washState.step = step;
  document.querySelectorAll('.wash-wizard__step').forEach((el) => {
    el.classList.toggle('is-active', Number(el.dataset.step) === step);
    el.classList.toggle('is-done', Number(el.dataset.step) < step);
  });
  renderStep();
}

async function renderStep() {
  const body = document.getElementById('wash-wizard-body');

  if (washState.step === 1) {
    body.innerHTML = `
      <p class="mt-16" style="color:var(--gris-texto);">¿Qué querés lavar?</p>
      <div class="wash-options" id="wash-options">
        <p class="loading-state">Cargando precios…</p>
      </div>
    `;

    try {
      const [moto, casco] = await Promise.all([
        catalogService.service(WASH_SLUGS.moto),
        catalogService.service(WASH_SLUGS.casco),
      ]);

      document.getElementById('wash-options').innerHTML = `
        <button type="button" class="wash-option" data-type="moto">
          <span class="wash-option__icon">🏍️</span>
          <span class="wash-option__name">Lavado de Moto</span>
          <span class="wash-option__price">${helpers.formatCurrency(moto.price)}</span>
        </button>
        <button type="button" class="wash-option" data-type="casco">
          <span class="wash-option__icon">🪖</span>
          <span class="wash-option__name">Lavado de Casco</span>
          <span class="wash-option__price">${helpers.formatCurrency(casco.price)}</span>
        </button>
      `;

      document.querySelectorAll('.wash-option').forEach((btn) => {
        btn.addEventListener('click', () => {
          washState.type = btn.dataset.type;
          washState.service = btn.dataset.type === 'moto' ? moto : casco;
          setStep(2);
        });
      });
    } catch (error) {
      document.getElementById('wash-options').innerHTML = `<p class="error-state">No fue posible cargar los servicios de lavado — todavía no están creados en el catálogo.</p>`;
    }
    return;
  }

  if (washState.step === 2) {
    body.innerHTML = `
      <p class="mt-16" style="color:var(--gris-texto);">Elegí fecha y hora para tu ${washState.type === 'moto' ? 'moto' : 'casco'}:</p>
      <div class="form-group mt-16">
        <label for="wash-date">Fecha</label>
        <input type="date" class="form-control" id="wash-date" value="${washState.date}" style="max-width:220px;">
      </div>
      <div class="form-group">
        <label>Hora</label>
        <div id="wash-time-slots" class="wash-time-slots">
          <p class="loading-state">Elegí una fecha para ver los horarios disponibles.</p>
        </div>
      </div>
      <div class="wash-wizard__nav">
        <button class="btn btn-secondary" id="wash-back-btn">Atrás</button>
        <button class="btn btn-primary" id="wash-next-btn" disabled>Siguiente</button>
      </div>
    `;
    document.getElementById('wash-date').min = new Date().toISOString().slice(0, 10);
    document.getElementById('wash-back-btn').addEventListener('click', () => setStep(1));
    document.getElementById('wash-next-btn').addEventListener('click', () => {
      if (!washState.date || !washState.time) {
        helpers.toast('Elegí una hora disponible para continuar.', 'error');
        return;
      }
      setStep(3);
    });

    document.getElementById('wash-date').addEventListener('change', (event) => {
      washState.date = event.target.value;
      washState.time = ''; // cambiar de fecha invalida la hora ya elegida (los horarios ocupados son por día)
      document.getElementById('wash-next-btn').disabled = true;
      loadWashTimeSlots();
    });

    if (washState.date) loadWashTimeSlots();
    return;
  }

  if (washState.step === 3) {
    const user = authService.currentUser();
    const noteLabel = washState.type === 'moto' ? 'Modelo de tu moto (opcional)' : 'Marca de tu casco (opcional)';

    body.innerHTML = `
      <p class="mt-16" style="color:var(--gris-texto);">Tus datos:</p>
      <div class="form-row mt-16">
        <div class="form-group"><label for="wash-name">Nombre</label><input class="form-control" id="wash-name" value="${helpers.escapeHtml(washState.name || (user ? user.name + ' ' + user.last_name : ''))}"></div>
        <div class="form-group"><label for="wash-phone">Teléfono</label><input class="form-control" id="wash-phone" value="${helpers.escapeHtml(washState.phone || user?.phone || washState.primaryAddress?.phone || '')}"></div>
      </div>
      <div class="form-group">
        <label for="wash-note">${noteLabel}</label>
        <input class="form-control" id="wash-note" value="${helpers.escapeHtml(washState.itemNote)}">
      </div>
      <div class="wash-wizard__nav">
        <button class="btn btn-secondary" id="wash-back-btn">Atrás</button>
        <button class="btn btn-primary" id="wash-next-btn">Siguiente</button>
      </div>
    `;
    document.getElementById('wash-back-btn').addEventListener('click', () => setStep(2));
    document.getElementById('wash-next-btn').addEventListener('click', () => {
      const name = document.getElementById('wash-name').value.trim();
      const phone = document.getElementById('wash-phone').value.trim();
      if (!name || !phone) {
        helpers.toast('Completá tu nombre y teléfono para continuar.', 'error');
        return;
      }
      washState.name = name;
      washState.phone = phone;
      washState.itemNote = document.getElementById('wash-note').value.trim();

      // Si el perfil todavía no tenía teléfono, se completa con este —
      // así la próxima vez (acá o en cualquier otro lado del sitio) ya
      // está guardado, sin pedirlo de nuevo. Nunca pisa un teléfono que
      // ya existía ahí (podría ser distinto a propósito).
      if (user && !user.phone) {
        authService.updateProfile({ name: user.name, last_name: user.last_name, phone }).catch(() => {});
      }

      setStep(4);
    });
    return;
  }

  if (washState.step === 4) {
    const scheduledLabel = helpers.formatDateTime(`${washState.date} ${washState.time}:00`);

    body.innerHTML = `
      <p class="mt-16" style="color:var(--gris-texto);">Revisá y confirmá tu reserva:</p>
      <div class="summary-box mt-16">
        <div class="summary-row"><span>${washState.service.name}</span><span>${helpers.formatCurrency(washState.service.price)}</span></div>
        <div class="summary-row"><span>Fecha</span><span>${scheduledLabel}</span></div>
        <div class="summary-row"><span>Nombre</span><span>${helpers.escapeHtml(washState.name)}</span></div>
        <div class="summary-row"><span>Teléfono</span><span>${helpers.escapeHtml(washState.phone)}</span></div>
        ${washState.itemNote ? `<div class="summary-row"><span>${washState.type === 'moto' ? 'Modelo' : 'Marca del casco'}</span><span>${helpers.escapeHtml(washState.itemNote)}</span></div>` : ''}
        <div class="summary-row total"><span>Total</span><span>${helpers.formatCurrency(washState.service.price)}</span></div>
      </div>
      <div class="form-error" id="wash-error"></div>
      <div class="wash-wizard__nav">
        <button class="btn btn-secondary" id="wash-back-btn">Atrás</button>
        <button class="btn btn-primary" id="wash-confirm-btn">Reservar</button>
      </div>
    `;
    document.getElementById('wash-back-btn').addEventListener('click', () => setStep(3));
    document.getElementById('wash-confirm-btn').addEventListener('click', confirmReservation);
    return;
  }
}

/** Horario de atención asumido para armar la grilla — 08:00 a 17:00, cada hora. */
const WASH_BUSINESS_HOURS = Array.from({ length: 10 }, (_, i) => String(8 + i).padStart(2, '0') + ':00');

/**
 * Trae los horarios YA ocupados de este servicio en la fecha elegida
 * (GET /api/services/{id}/booked-times) y arma la grilla marcando esos como
 * no disponibles — así el usuario ve de entrada qué horas no puede elegir,
 * en vez de enterarse recién al confirmar (que es lo que pasaba antes: el
 * backend igual revalida esto mismo al crear el pedido, sección 35, esto
 * solo evita mostrar una opción que de todas formas iba a fallar).
 */
async function loadWashTimeSlots() {
  const mount = document.getElementById('wash-time-slots');
  mount.innerHTML = '<p class="loading-state">Cargando horarios…</p>';

  try {
    const booked = await catalogService.serviceBookedTimes(washState.service.id, washState.date);
    const isToday = washState.date === new Date().toISOString().slice(0, 10);
    const nowHour = new Date().getHours();

    mount.innerHTML = `
      <div class="wash-time-grid">
        ${WASH_BUSINESS_HOURS.map((time) => {
          const isBooked = booked.includes(time);
          const isPast = isToday && Number(time.slice(0, 2)) <= nowHour;
          const disabled = isBooked || isPast;
          return `
            <button type="button" class="wash-time-slot ${disabled ? 'is-disabled' : ''} ${washState.time === time ? 'is-selected' : ''}"
                    data-time="${time}" ${disabled ? 'disabled' : ''} title="${isBooked ? 'Ya reservado' : isPast ? 'Hora pasada' : ''}">
              ${time}
            </button>
          `;
        }).join('')}
      </div>
      ${booked.length === WASH_BUSINESS_HOURS.length ? '<p class="error-state mt-16">No quedan horarios libres este día — probá con otra fecha.</p>' : ''}
    `;

    mount.querySelectorAll('.wash-time-slot:not(.is-disabled)').forEach((btn) => {
      btn.addEventListener('click', () => {
        washState.time = btn.dataset.time;
        mount.querySelectorAll('.wash-time-slot').forEach((el) => el.classList.remove('is-selected'));
        btn.classList.add('is-selected');
        document.getElementById('wash-next-btn').disabled = false;
      });
    });
  } catch (error) {
    mount.innerHTML = '<p class="error-state">No fue posible cargar los horarios disponibles.</p>';
  }
}

async function confirmReservation() {
  if (!authService.isAuthenticated()) {
    helpers.toast('Inicia sesión para completar tu reserva.', 'error');
    openAuthModal('login');
    return;
  }

  const errorBox = document.getElementById('wash-error');
  errorBox.textContent = '';

  try {
    await cartService.addItem({
      service_id: washState.service.id,
      quantity: 1,
      scheduled_at: `${washState.date} ${washState.time}:00`,
    });

    // El teléfono/modelo van como nota del pedido — checkout.js precarga
    // este texto en el campo de notas (ver ?note= ahí) para que quede
    // registrado en el pedido sin duplicar campos que el sistema de
    // reservas ya cubre (nombre/fecha ya están en la reserva misma).
    const noteParts = [`Tel: ${washState.phone}`];
    if (washState.itemNote) noteParts.push(washState.itemNote);

    window.location.href = 'checkout?note=' + encodeURIComponent(noteParts.join(' — '));
  } catch (error) {
    errorBox.textContent = helpers.flattenErrors(error.fields) || error.message;
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  await loadPrimaryAddress();
  setStep(1);
});
