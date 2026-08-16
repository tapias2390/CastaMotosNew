/**
 * Header + navegación de categorías + modal de login/registro + footer.
 * Se monta en TODAS las páginas sobre <div id="app-header"> / <div id="app-footer">
 * (sección 40: componentes reutilizables — Header, Navbar, Buscador, Modal).
 */
/**
 * Solo controla si se MUESTRA el link "Admin" (mejor UX) — no es la
 * seguridad real, que vive en el backend (AuthMiddleware + RequirePermissionMiddleware).
 */
function isAdminUser(user) {
  return !!user && Array.isArray(user.roles) && user.roles.some((role) => ['administrador', 'superadministrador'].includes(role));
}

function renderHeaderShell() {
  const mount = document.getElementById('app-header');
  if (!mount) return;

  const user = authService.currentUser();

  mount.innerHTML = `
    <header class="site-header">
      <div class="container site-header__bar">
        <a href="." class="site-header__logo">
          <img src="frontend/assets/img/logo.png" alt="CASTAMOTO">
          <span>CASTAMOTO</span>
        </a>
        <div class="site-header__search">
          <form id="header-search-form" role="search">
            <label class="sr-only" for="header-search-input">Buscar productos y servicios</label>
            <input id="header-search-input" type="search" placeholder="Buscar cascos, repuestos, servicios..." autocomplete="off">
            <button type="submit" aria-label="Buscar">🔍</button>
          </form>
        </div>
        <nav class="site-header__actions">
          <a class="icon-btn" href="carrito" aria-label="Carrito">
            🛒<span class="badge-count" id="cart-badge" hidden>0</span>
          </a>
          ${isAdminUser(user) ? '<a class="icon-btn" href="admin">Admin</a>' : ''}
          ${user
            ? `<span style="font-size:0.85rem;color:var(--gris-texto);">Hola, <strong style="color:var(--blanco)">${helpers.escapeHtml(user.name)}</strong></span>
               <button class="icon-btn" id="logout-btn">Salir</button>`
            : `<button class="icon-btn" id="open-login-btn">Iniciar sesión</button>`}
        </nav>
      </div>
      <div class="category-nav">
        <div class="container" id="category-nav-list">
          <a href="productos">Todos los productos</a>
          <a href="servicios">Servicios</a>
        </div>
      </div>
    </header>
    ${authModalMarkup()}
  `;

  document.getElementById('header-search-form').addEventListener('submit', (event) => {
    event.preventDefault();
    const term = document.getElementById('header-search-input').value.trim();
    if (term) window.location.href = `productos?search=${encodeURIComponent(term)}`;
  });

  if (user) {
    document.getElementById('logout-btn').addEventListener('click', () => {
      authService.logout();
      helpers.toast('Sesión cerrada.', 'success');
      window.location.href = '.';
    });
  } else {
    document.getElementById('open-login-btn').addEventListener('click', () => openAuthModal('login'));
  }

  loadCategoryNav();
  refreshCartBadge();
}

async function loadCategoryNav() {
  const list = document.getElementById('category-nav-list');
  if (!list) return;

  try {
    const categories = await catalogService.categories();
    const topLevel = categories.slice(0, 8);
    list.innerHTML += topLevel.map((cat) => `<a href="categoria/${encodeURIComponent(cat.slug)}">${helpers.escapeHtml(cat.name)}</a>`).join('');
  } catch (error) {
    // La navegación de categorías es un "nice to have": si falla, el resto de la página sigue funcionando.
    console.error('No fue posible cargar las categorías del menú.', error);
  }
}

async function refreshCartBadge() {
  const badge = document.getElementById('cart-badge');
  if (!badge) return;

  try {
    const cart = await cartService.get();
    const count = cart.items.reduce((sum, item) => sum + item.quantity, 0);
    badge.textContent = String(count);
    badge.hidden = count === 0;
  } catch (error) {
    badge.hidden = true;
  }
}

function authModalMarkup() {
  return `
    <div class="modal-overlay" id="auth-modal-overlay">
      <div class="modal">
        <div class="modal__header">
          <h2 class="modal__title" id="auth-modal-title">Iniciar sesión</h2>
          <button class="modal__close" id="auth-modal-close" aria-label="Cerrar">✕</button>
        </div>
        <div class="modal__tabs">
          <button class="modal__tab is-active" data-tab="login">Iniciar sesión</button>
          <button class="modal__tab" data-tab="register">Crear cuenta</button>
        </div>

        <form id="login-form">
          <div class="form-group">
            <label for="login-email">Correo</label>
            <input class="form-control" type="email" id="login-email" required>
          </div>
          <div class="form-group">
            <label for="login-password">Contraseña</label>
            <input class="form-control" type="password" id="login-password" required>
          </div>
          <div class="form-error" id="login-error"></div>
          <button class="btn btn-primary btn-block" type="submit">Entrar</button>
        </form>

        <form id="register-form" hidden>
          <div class="form-row">
            <div class="form-group">
              <label for="register-name">Nombre</label>
              <input class="form-control" id="register-name" required>
            </div>
            <div class="form-group">
              <label for="register-last-name">Apellido</label>
              <input class="form-control" id="register-last-name" required>
            </div>
          </div>
          <div class="form-group">
            <label for="register-email">Correo</label>
            <input class="form-control" type="email" id="register-email" required>
          </div>
          <div class="form-group">
            <label for="register-password">Contraseña</label>
            <input class="form-control" type="password" id="register-password" minlength="8" required>
          </div>
          <div class="form-group">
            <label for="register-password-confirmation">Confirmar contraseña</label>
            <input class="form-control" type="password" id="register-password-confirmation" minlength="8" required>
          </div>
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;font-size:0.85rem;">
              <input type="checkbox" id="register-terms" required style="width:auto;">
              Acepto los términos y condiciones
            </label>
          </div>
          <div class="form-error" id="register-error"></div>
          <button class="btn btn-primary btn-block" type="submit">Crear cuenta</button>
        </form>
      </div>
    </div>
  `;
}

function openAuthModal(tab = 'login') {
  const overlay = document.getElementById('auth-modal-overlay');
  overlay.classList.add('is-open');
  switchAuthTab(tab);
}

function closeAuthModal() {
  document.getElementById('auth-modal-overlay').classList.remove('is-open');
}

function switchAuthTab(tab) {
  document.querySelectorAll('.modal__tab').forEach((btn) => btn.classList.toggle('is-active', btn.dataset.tab === tab));
  document.getElementById('login-form').hidden = tab !== 'login';
  document.getElementById('register-form').hidden = tab !== 'register';
  document.getElementById('auth-modal-title').textContent = tab === 'login' ? 'Iniciar sesión' : 'Crear cuenta';
}

function initAuthModalEvents() {
  const overlay = document.getElementById('auth-modal-overlay');
  if (!overlay) return;

  document.getElementById('auth-modal-close').addEventListener('click', closeAuthModal);
  overlay.addEventListener('click', (event) => { if (event.target === overlay) closeAuthModal(); });

  document.querySelectorAll('.modal__tab').forEach((btn) => {
    btn.addEventListener('click', () => switchAuthTab(btn.dataset.tab));
  });

  document.getElementById('login-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const errorBox = document.getElementById('login-error');
    errorBox.textContent = '';

    try {
      await authService.login(
        document.getElementById('login-email').value,
        document.getElementById('login-password').value
      );
      window.location.reload();
    } catch (error) {
      errorBox.textContent = error.message;
    }
  });

  document.getElementById('register-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const errorBox = document.getElementById('register-error');
    errorBox.textContent = '';

    try {
      await authService.register({
        name: document.getElementById('register-name').value,
        last_name: document.getElementById('register-last-name').value,
        email: document.getElementById('register-email').value,
        password: document.getElementById('register-password').value,
        password_confirmation: document.getElementById('register-password-confirmation').value,
        terms_accepted: document.getElementById('register-terms').checked,
      });
      window.location.reload();
    } catch (error) {
      errorBox.textContent = helpers.flattenErrors(error.fields) || error.message;
    }
  });
}

function renderFooter() {
  const mount = document.getElementById('app-footer');
  if (!mount) return;

  mount.innerHTML = `
    <footer class="site-footer">
      <div class="container">
        <div class="site-footer__grid">
          <div class="site-footer__col">
            <h3>CASTAMOTO</h3>
            <p style="color:var(--gris-texto);font-size:0.85rem;">Todo para tu moto: repuestos, accesorios y servicios especializados.</p>
          </div>
          <div class="site-footer__col">
            <h3>Comprar</h3>
            <a href="productos">Todos los productos</a>
            <a href="servicios">Servicios</a>
            <a href="productos?on_sale=1">Ofertas</a>
          </div>
          <div class="site-footer__col">
            <h3>Mi cuenta</h3>
            <a href="carrito">Carrito</a>
          </div>
        </div>
        <div class="site-footer__bottom">
          Plataforma en construcción progresiva. © ${new Date().getFullYear()} CASTAMOTO
        </div>
      </div>
    </footer>
  `;
}

function initLayout() {
  renderHeaderShell();
  initAuthModalEvents();
  renderFooter();
}

document.addEventListener('DOMContentLoaded', initLayout);
