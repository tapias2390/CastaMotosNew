/**
 * Mi perfil: datos personales + foto + cambio de contraseña. Todo esto ya
 * existía en el backend (GET/PUT /api/profile, POST /api/profile/avatar,
 * POST /api/auth/change-password) desde la Fase 2 pero no tenía página —
 * solo se podían llamar "a mano" contra la API.
 */
function renderAvatar(user) {
  const img = document.getElementById('profile-avatar-preview');
  const placeholder = document.getElementById('profile-avatar-placeholder');
  const url = helpers.mediaUrl('avatars', user.avatar);

  if (url) {
    img.src = url;
    img.hidden = false;
    placeholder.hidden = true;
  } else {
    img.hidden = true;
    placeholder.hidden = false;
  }
}

function fillProfileForm(user) {
  document.getElementById('profile-email').value = user.email;
  document.getElementById('profile-name').value = user.name;
  document.getElementById('profile-last-name').value = user.last_name;
  document.getElementById('profile-phone').value = user.phone || '';
  renderAvatar(user);
}

function wireAvatarUpload() {
  document.getElementById('profile-avatar-input').addEventListener('change', async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const errorBox = document.getElementById('profile-avatar-error');
    errorBox.textContent = '';

    try {
      await authService.uploadAvatar(file);
      renderAvatar(authService.currentUser());
      helpers.toast('Foto de perfil actualizada.', 'success');
    } catch (error) {
      errorBox.textContent = helpers.flattenErrors(error.fields) || error.message;
    }
  });
}

function wireProfileForm() {
  document.getElementById('profile-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const errorBox = document.getElementById('profile-form-error');
    errorBox.textContent = '';

    try {
      await authService.updateProfile({
        name: document.getElementById('profile-name').value,
        last_name: document.getElementById('profile-last-name').value,
        phone: document.getElementById('profile-phone').value,
      });
      helpers.toast('Perfil actualizado.', 'success');
    } catch (error) {
      errorBox.textContent = helpers.flattenErrors(error.fields) || error.message;
    }
  });
}

function wireChangePasswordForm() {
  document.getElementById('change-password-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const errorBox = document.getElementById('password-form-error');
    errorBox.textContent = '';

    const form = event.target;

    try {
      await authService.changePassword(
        document.getElementById('current-password').value,
        document.getElementById('new-password').value,
        document.getElementById('new-password-confirmation').value
      );
      helpers.toast('Contraseña actualizada.', 'success');
      form.reset();
    } catch (error) {
      errorBox.textContent = helpers.flattenErrors(error.fields) || error.message;
    }
  });
}

async function initProfilePage() {
  if (!authService.isAuthenticated()) {
    helpers.toast('Inicia sesión para ver tu perfil.', 'error');
    window.location.href = '.';
    return;
  }

  // refreshUser() (no currentUser()) para partir de los datos reales del
  // servidor, no de una copia de localStorage que pudo quedar desactualizada.
  const user = await authService.refreshUser();
  if (!user) return; // refreshUser() ya redirige/limpia la sesión si el token venció

  fillProfileForm(user);
  wireAvatarUpload();
  wireProfileForm();
  wireChangePasswordForm();
  helpers.initPasswordToggles(document.getElementById('change-password-form'));
}

document.addEventListener('DOMContentLoaded', initProfilePage);
