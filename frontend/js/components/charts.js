/**
 * Gráficas simples en SVG hechas a mano, sin librerías externas (sección 28:
 * "gráficas que muestran cómo va el negocio") — consistente con el resto
 * del frontend (vanilla JS, sin build step). Solo grafican datos reales que
 * ya vienen del backend (DashboardController) — nunca números inventados.
 */

/** Barras verticales (ej. ingresos por día). */
function barChartMarkup(series, { valueKey, labelKey, formatValue = String } = {}) {
  const width = 320;
  const height = 160;
  const paddingBottom = 22;
  const max = Math.max(1, ...series.map((d) => Number(d[valueKey]) || 0));
  const barGap = 4;
  const barWidth = (width / series.length) - barGap;
  const labelEvery = series.length > 10 ? 2 : 1;

  const bars = series.map((d, i) => {
    const value = Number(d[valueKey]) || 0;
    const barHeight = Math.max(1, Math.round((value / max) * (height - paddingBottom - 8)));
    const x = i * (barWidth + barGap);
    const y = height - paddingBottom - barHeight;
    return `<rect class="chart-bar" x="${x}" y="${y}" width="${Math.max(barWidth, 1)}" height="${barHeight}" rx="2"><title>${helpers.escapeHtml(String(d[labelKey]))}: ${formatValue(value)}</title></rect>`;
  }).join('');

  const labels = series.map((d, i) => {
    if (i % labelEvery !== 0) return '';
    const x = i * (barWidth + barGap) + barWidth / 2;
    return `<text class="chart-axis-label" x="${x}" y="${height - 6}" text-anchor="middle">${helpers.escapeHtml(String(d[labelKey]))}</text>`;
  }).join('');

  return `<svg class="chart-bars" viewBox="0 0 ${width} ${height}" preserveAspectRatio="xMidYMid meet">${bars}${labels}</svg>`;
}

/** Barras horizontales con etiqueta + valor (ej. pedidos por estado). */
function horizontalBarsMarkup(rows) {
  if (rows.length === 0) return '<p class="empty-state">Todavía no hay datos suficientes.</p>';

  const max = Math.max(1, ...rows.map((r) => r.value));

  return rows.map((row) => `
    <div class="status-bar-row">
      <span class="status-bar-row__label">${helpers.escapeHtml(row.label)}</span>
      <div class="status-bar-row__track"><div class="status-bar-row__fill" style="width:${Math.round((row.value / max) * 100)}%"></div></div>
      <span class="status-bar-row__count">${row.value}</span>
    </div>
  `).join('');
}

function statCardMarkup(icon, label, value) {
  return `
    <div class="stat-card">
      <span class="stat-card__icon">${icon}</span>
      <div>
        <div class="stat-card__value">${value}</div>
        <div class="stat-card__label">${label}</div>
      </div>
    </div>
  `;
}
