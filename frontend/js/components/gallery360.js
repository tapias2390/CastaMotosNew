/**
 * Galería del detalle de producto/servicio con giro 360° simple (sección 40:
 * componentes reutilizables). No es un modelo 3D real (no existen archivos
 * .glb/.gltf de los productos) — es un giro construido con las FOTOS reales
 * que ya subió el vendedor: arrastrar avanza/retrocede entre ellas, dando
 * sensación de rotación. Con una sola foto no hay nada que rotar, así que el
 * "arrastra para girar" y el modo 360 solo se activan con 2+ imágenes.
 */
function gallery360Markup(images, altText) {
  if (images.length === 0) {
    return `<div class="detail-gallery"><div class="detail-gallery__main"><span id="gallery-main-img">Sin imagen disponible</span></div></div>`;
  }

  const canRotate = images.length > 1;
  const safeAlt = helpers.escapeHtml(altText);

  const main = `<img id="gallery-main-img" src="${images[0]}" alt="${safeAlt}" draggable="false">`;

  const thumbs = canRotate
    ? `<div class="detail-gallery__thumbs">${images.map((src, index) =>
        `<img src="${src}" class="${index === 0 ? 'is-active' : ''}" data-index="${index}" draggable="false" alt="${safeAlt} — foto ${index + 1}">`
      ).join('')}</div>`
    : '';

  return `
    <div class="detail-gallery">
      <div class="detail-gallery__main ${canRotate ? 'is-360' : ''}" id="gallery-360-viewport">
        ${main}
        ${canRotate ? '<span class="gallery-360-hint">🔄 Arrastra para girar</span>' : ''}
      </div>
      ${thumbs}
    </div>
  `;
}

function initGallery360(images) {
  const viewport = document.getElementById('gallery-360-viewport');
  const mainImg = document.getElementById('gallery-main-img');
  if (!viewport || !mainImg || images.length <= 1) return;

  const STEP_PX = 40; // píxeles de arrastre para pasar a la siguiente/anterior foto
  let currentIndex = 0;
  let dragging = false;
  let startX = 0;

  function setIndex(index) {
    currentIndex = ((index % images.length) + images.length) % images.length;
    mainImg.src = images[currentIndex];
    document.querySelectorAll('.detail-gallery__thumbs img').forEach((thumb, i) => {
      thumb.classList.toggle('is-active', i === currentIndex);
    });
  }

  function onDown(clientX) {
    dragging = true;
    startX = clientX;
    viewport.classList.add('is-dragging');
  }
  function onMove(clientX) {
    if (!dragging) return;
    const delta = clientX - startX;
    if (Math.abs(delta) >= STEP_PX) {
      setIndex(currentIndex + (delta < 0 ? 1 : -1));
      startX = clientX;
    }
  }
  function onUp() {
    dragging = false;
    viewport.classList.remove('is-dragging');
  }

  viewport.addEventListener('mousedown', (event) => onDown(event.clientX));
  window.addEventListener('mousemove', (event) => onMove(event.clientX));
  window.addEventListener('mouseup', onUp);

  viewport.addEventListener('touchstart', (event) => onDown(event.touches[0].clientX), { passive: true });
  viewport.addEventListener('touchmove', (event) => onMove(event.touches[0].clientX), { passive: true });
  viewport.addEventListener('touchend', onUp);

  viewport.addEventListener('dragstart', (event) => event.preventDefault());

  document.querySelectorAll('.detail-gallery__thumbs img').forEach((thumb) => {
    thumb.addEventListener('click', () => setIndex(Number(thumb.dataset.index)));
  });
}
