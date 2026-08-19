/**
 * Galería del detalle de producto/servicio (sección 40: componentes
 * reutilizables): giro 360° simple, zoom al pasar el mouse (estilo lupa +
 * panel ampliado, funcionalidad típica de marketplace grande — sección 1:
 * se replica el COMPORTAMIENTO, nunca el diseño/paleta/logo de un tercero) y
 * pantalla completa al hacer click/tap (equivalente táctil, ya que el zoom
 * por hover no existe en pantallas táctiles).
 *
 * - Giro 360°: no es un modelo 3D real (no hay archivos .glb/.gltf) — arrastrar
 *   avanza/retrocede entre las FOTOS reales que subió el vendedor. Con una
 *   sola foto no hay nada que rotar, así que solo se activa con 2+ imágenes.
 * - Zoom: solo en dispositivos con mouse real (hover: hover), donde no compite
 *   con el gesto de arrastrar para girar — mientras se arrastra, el zoom se oculta.
 * - Pantalla completa: funciona siempre (con o sin mouse) y es el reemplazo
 *   del zoom en táctil — dentro del overlay, el usuario puede hacer pinch-zoom
 *   nativo del navegador (el <meta viewport> del sitio no lo bloquea).
 */
const GALLERY_ZOOM_FACTOR = 2.5;
const GALLERY_DRAG_THRESHOLD_PX = 6; // más que esto entre down/up = fue un arrastre, no un click

function gallery360Markup(images, altText, placeholderMarkup) {
  if (images.length === 0) {
    // placeholderMarkup (opcional): reemplazo animado para ítems sin foto
    // real todavía — hoy solo "Lavado de Moto"/"Lavado de Casco", ver
    // cards.js washPlaceholderMarkup(). Cualquier otro caso sigue con el
    // texto genérico de siempre.
    const content = placeholderMarkup || '<span id="gallery-main-img">Sin imagen disponible</span>';
    return `<div class="detail-gallery"><div class="detail-gallery__main">${content}</div></div>`;
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
        <div class="gallery-zoom-lens" id="gallery-zoom-lens"></div>
      </div>
      ${thumbs}
      <div class="gallery-zoom-panel" id="gallery-zoom-panel" aria-hidden="true"></div>
    </div>
  `;
}

/**
 * Inicializa las tres interacciones de la galería sobre el mismo viewport,
 * compartiendo el estado de "se arrastró o fue un click" para que no se
 * disparen a la vez (ej. arrastrar para girar no debe además abrir la
 * pantalla completa al soltar).
 */
function initGalleryInteractions(images) {
  const viewport = document.getElementById('gallery-360-viewport');
  const mainImg = document.getElementById('gallery-main-img');
  if (!viewport || !mainImg) return;

  const canRotate = images.length > 1;
  const STEP_PX = 40; // píxeles de arrastre para pasar a la siguiente/anterior foto
  let currentIndex = 0;
  let dragging = false;
  let startX = 0;
  let startY = 0;
  let moved = false;

  function setIndex(index) {
    currentIndex = ((index % images.length) + images.length) % images.length;
    mainImg.src = images[currentIndex];
    document.querySelectorAll('.detail-gallery__thumbs img').forEach((thumb, i) => {
      thumb.classList.toggle('is-active', i === currentIndex);
    });
  }

  function onDown(clientX, clientY) {
    dragging = true;
    moved = false;
    startX = clientX;
    startY = clientY;
    if (canRotate) viewport.classList.add('is-dragging');
    hideZoom();
  }
  function onMove(clientX, clientY) {
    if (!dragging) return;
    if (Math.abs(clientX - startX) > GALLERY_DRAG_THRESHOLD_PX || Math.abs(clientY - startY) > GALLERY_DRAG_THRESHOLD_PX) {
      moved = true;
    }
    if (canRotate) {
      const delta = clientX - startX;
      if (Math.abs(delta) >= STEP_PX) {
        setIndex(currentIndex + (delta < 0 ? 1 : -1));
        startX = clientX;
      }
    }
  }
  function onUp() {
    dragging = false;
    viewport.classList.remove('is-dragging');
  }

  viewport.addEventListener('mousedown', (event) => onDown(event.clientX, event.clientY));
  window.addEventListener('mousemove', (event) => onMove(event.clientX, event.clientY));
  window.addEventListener('mouseup', onUp);

  viewport.addEventListener('touchstart', (event) => onDown(event.touches[0].clientX, event.touches[0].clientY), { passive: true });
  viewport.addEventListener('touchmove', (event) => onMove(event.touches[0].clientX, event.touches[0].clientY), { passive: true });
  viewport.addEventListener('touchend', onUp);

  viewport.addEventListener('dragstart', (event) => event.preventDefault());

  document.querySelectorAll('.detail-gallery__thumbs img').forEach((thumb) => {
    thumb.addEventListener('click', () => setIndex(Number(thumb.dataset.index)));
  });

  // --- Zoom al pasar el mouse (solo con puntero real: hover + precisión fina) ---
  const panel = document.getElementById('gallery-zoom-panel');
  const lens = document.getElementById('gallery-zoom-lens');
  const canHoverZoom = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  function hideZoom() {
    if (panel) panel.classList.remove('is-visible');
    if (lens) lens.classList.remove('is-visible');
  }

  if (canHoverZoom && panel && lens) {
    viewport.classList.add('is-zoomable');

    viewport.addEventListener('mousemove', (event) => {
      if (dragging) { hideZoom(); return; }

      const rect = viewport.getBoundingClientRect();
      const x = event.clientX - rect.left;
      const y = event.clientY - rect.top;

      const xPercent = (x / rect.width) * 100;
      const yPercent = (y / rect.height) * 100;

      panel.style.backgroundImage = `url("${mainImg.src}")`;
      panel.style.backgroundSize = `${rect.width * GALLERY_ZOOM_FACTOR}px ${rect.height * GALLERY_ZOOM_FACTOR}px`;
      panel.style.backgroundPosition = `${xPercent}% ${yPercent}%`;
      panel.classList.add('is-visible');

      const lensSize = rect.width / GALLERY_ZOOM_FACTOR;
      lens.style.width = `${lensSize}px`;
      lens.style.height = `${lensSize}px`;
      lens.style.left = `${Math.min(Math.max(x - lensSize / 2, 0), rect.width - lensSize)}px`;
      lens.style.top = `${Math.min(Math.max(y - lensSize / 2, 0), rect.height - lensSize)}px`;
      lens.classList.add('is-visible');
    });
    viewport.addEventListener('mouseleave', hideZoom);
  }

  // --- Pantalla completa al hacer click/tap (equivalente táctil del zoom) ---
  viewport.addEventListener('click', () => {
    if (moved) return; // fue un arrastre para girar, no un click real
    hideZoom();
    openGalleryLightbox(mainImg.src, mainImg.alt);
  });
}

function openGalleryLightbox(src, alt) {
  const overlay = document.createElement('div');
  overlay.className = 'gallery-lightbox-overlay';
  overlay.innerHTML = `
    <button class="gallery-lightbox-close" type="button" aria-label="Cerrar">✕</button>
    <img src="${src}" alt="${helpers.escapeHtml(alt)}">
  `;

  function close() {
    overlay.remove();
    document.removeEventListener('keydown', onKeydown);
  }
  function onKeydown(event) {
    if (event.key === 'Escape') close();
  }

  overlay.addEventListener('click', (event) => {
    if (event.target === overlay || event.target.closest('.gallery-lightbox-close')) close();
  });
  document.addEventListener('keydown', onKeydown);

  document.body.appendChild(overlay);
}

/** Alias retrocompatible: producto.js/servicio.js llaman initGallery360(images). */
function initGallery360(images) {
  initGalleryInteractions(images);
}
