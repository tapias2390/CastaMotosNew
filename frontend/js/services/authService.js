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
