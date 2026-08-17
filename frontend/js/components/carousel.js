/**
 * Carrusel de imágenes genérico (sección 41: home de marketplace) — avance
 * automático, flechas, puntos de navegación y pausa al pasar el mouse. Cada
 * slide se arma con datos REALES del catálogo (foto + nombre + precio de un
 * producto de verdad, nunca una promoción inventada) — ver home.js.
 */
function carouselMarkup(id, slides) {
  if (slides.length === 0) return '';

  return `
    <div class="hero-carousel" id="${id}">
      <div class="hero-carousel__track">
        ${slides.map((slide, index) => `
          <a class="hero-carousel__slide ${index === 0 ? 'is-active' : ''}" href="${slide.href}" data-index="${index}">
            <img src="${slide.image}" alt="${helpers.escapeHtml(slide.title)}" loading="${index === 0 ? 'eager' : 'lazy'}">
            <div class="hero-carousel__caption">
              <span class="hero-carousel__title">${helpers.escapeHtml(slide.title)}</span>
              ${slide.subtitle ? `<span class="hero-carousel__subtitle">${slide.subtitle}</span>` : ''}
            </div>
          </a>
        `).join('')}
      </div>
      ${slides.length > 1 ? `
        <button class="hero-carousel__arrow hero-carousel__arrow--prev" type="button" aria-label="Anterior">‹</button>
        <button class="hero-carousel__arrow hero-carousel__arrow--next" type="button" aria-label="Siguiente">›</button>
        <div class="hero-carousel__dots">
          ${slides.map((_, index) => `<button class="hero-carousel__dot ${index === 0 ? 'is-active' : ''}" data-index="${index}" aria-label="Ir a la foto ${index + 1}"></button>`).join('')}
        </div>
      ` : ''}
    </div>
  `;
}

function initCarousel(id, { interval = 5000 } = {}) {
  const root = document.getElementById(id);
  if (!root) return;

  const slides = Array.from(root.querySelectorAll('.hero-carousel__slide'));
  const dots = Array.from(root.querySelectorAll('.hero-carousel__dot'));
  if (slides.length <= 1) return;

  let current = 0;
  let timer = null;

  function show(index) {
    current = ((index % slides.length) + slides.length) % slides.length;
    slides.forEach((slide, i) => slide.classList.toggle('is-active', i === current));
    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === current));
  }

  function next() { show(current + 1); }
  function prev() { show(current - 1); }

  function startAuto() {
    stopAuto();
    timer = setInterval(next, interval);
  }
  function stopAuto() {
    if (timer) clearInterval(timer);
  }

  root.querySelector('.hero-carousel__arrow--next')?.addEventListener('click', (event) => {
    event.preventDefault();
    next();
    startAuto();
  });
  root.querySelector('.hero-carousel__arrow--prev')?.addEventListener('click', (event) => {
    event.preventDefault();
    prev();
    startAuto();
  });
  dots.forEach((dot) => {
    dot.addEventListener('click', (event) => {
      event.preventDefault();
      show(Number(dot.dataset.index));
      startAuto();
    });
  });

  root.addEventListener('mouseenter', stopAuto);
  root.addEventListener('mouseleave', startAuto);

  startAuto();
}
