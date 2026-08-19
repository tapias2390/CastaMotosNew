/**
 * Página "Recuperar contraseña". Dos pasos en una sola página, según si la
 * URL trae ?token= (sección 6 del prompt maestro — recuperación de cuenta):
 *   1. Sin token: el usuario pide el enlace escribiendo su correo
 *      (POST /api/auth/forgot-password).
 *   2. Con token (llegó por el enlace del correo): define la nueva
 *      contraseña (POST /api/auth/reset-password).
 * El backend responde igual exista o no el correo (evita enumeración de
 * usuarios) — por eso el paso 1 siempre muestra el mismo mensaje de éxito.
 */
function showRecoverSuccess(message) {
  document.getElementById('request-reset-form').hidden = true;
  document.getElementById('do-reset-form').hidden = true;
  document.getElementById('recover-success-message').textContent = message;
  document.getElementById('recover-success').hidden = false;
}

function initRequestResetStep() {
  document.getElementById('request-reset-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const errorBox = document.getElementById('request-reset-error');
    errorBox.textContent = '';

    try {
      await authService.forgotPassword(document.getElementById('request-reset-email').value);
      showRecoverSuccess('Si el correo existe, te enviamos instrucciones para restablecer tu contraseña. Revisa tu bandeja de entrada.');
    } catch (error) {
      errorBox.textContent = helpers.flattenErrors(error.fields) || error.message;
    }
  });
}

function initDoResetStep(token) {
  document.getElementById('recover-title').textContent = 'Restablecer contraseña';
  document.getElementById('request-reset-form').hidden = true;
  document.getElementById('do-reset-form').hidden = false;

  document.getElementById('do-reset-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const errorBox = document.getElementById('do-reset-error');
    errorBox.textContent = '';

    const password = document.getElementById('do-reset-password').value;
    const passwordConfirmation = document.getElementById('do-reset-password-confirmation').value;

    try {
      await authService.resetPassword(token, password, passwordConfirmation);
      showRecoverSuccess('Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión con la nueva.');
    } catch (error) {
      errorBox.textContent = helpers.flattenErrors(error.fields) || error.message;
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const token = helpers.queryParam('token');

  if (token) {
    initDoResetStep(token);
  } else {
    initRequestResetStep();
  }

  helpers.initPasswordToggles(document.getElementById('do-reset-form'));
});
