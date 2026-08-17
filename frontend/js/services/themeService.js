/**
 * Modo claro/oscuro (sección 41). El tema oscuro (negro/amarillo) sigue
 * siendo el de por defecto — es la identidad de marca que pide el prompt
 * maestro — pero el usuario puede preferir el claro y esa elección se
 * recuerda entre visitas. Ver theme-init.js para cómo se aplica ANTES del
 * primer pintado (evita parpadeo).
 */
const themeService = {
  KEY: 'castamoto_theme',

  current() {
    return localStorage.getItem(this.KEY) === 'light' ? 'light' : 'dark';
  },

  apply(theme) {
    if (theme === 'light') {
      document.documentElement.setAttribute('data-theme', 'light');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
  },

  toggle() {
    const next = this.current() === 'light' ? 'dark' : 'light';
    localStorage.setItem(this.KEY, next);
    this.apply(next);
    return next;
  },
};
