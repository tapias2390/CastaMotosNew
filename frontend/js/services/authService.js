/**
 * Sesión del usuario (Fase 2). El JWT y los datos básicos se guardan en
 * localStorage; el propio backend valida el token en cada petición protegida.
 */
const authService = {
  currentUser() {
    const raw = localStorage.getItem('castamoto_user');
    return raw ? JSON.parse(raw) : null;
  },

  isAuthenticated() {
    return !!apiService.authToken();
  },

  /**
   * Vuelve a pedir el usuario actual al backend (GET /auth/me) y actualiza
   * la copia en localStorage. Sin esto, "castamoto_user" solo se escribe en
   * login/registro: si el rol de una cuenta cambia en el servidor, o si el
   * dato quedó desactualizado por una sesión anterior, el header seguiría
   * mostrando (o escondiendo) el link "Admin" con datos viejos para siempre,
   * sin importar cuántas veces se recargue la página. Se llama en cada carga
   * de layout (ver initLayout) cuando hay token guardado.
   */
  async refreshUser() {
    if (!apiService.authToken()) return null;

    try {
      const user = await apiService.get('/auth/me');
      localStorage.setItem('castamoto_user', JSON.stringify(user));
      return user;
    } catch (error) {
      // Token vencido o inválido: se limpia la sesión para no quedar en un
      // estado inconsistente (token guardado pero backend ya no lo reconoce).
      this.logout();
      return null;
    }
  },

  async login(email, password, remember = false) {
    const data = await apiService.post('/auth/login', { email, password, remember });
    this.persistSession(data);
    return data;
  },

  async register(payload) {
    const data = await apiService.post('/auth/register', payload);
    this.persistSession(data);
    return data;
  },

  persistSession(data) {
    localStorage.setItem('castamoto_token', data.token);
    localStorage.setItem('castamoto_user', JSON.stringify(data.user));
    // El backend fusiona el carrito de invitado con el del usuario en el login
    // (Fase 5); una vez fusionado, el token de invitado ya no aplica.
    localStorage.removeItem('castamoto_cart_token');
  },

  logout() {
    localStorage.removeItem('castamoto_token');
    localStorage.removeItem('castamoto_user');
  },
};
