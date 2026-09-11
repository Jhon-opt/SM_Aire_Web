/* =============================================================
   Air Monitor Dashboard - Gráficas (Chart.js)
   - Una gráfica por parámetro; las principales siempre, las
     opcionales solo cuando tienen datos (lo decide la API).
   - Franjas de fondo con los rangos del ICA.
   - En actualizaciones en vivo los datos se reemplazan en la
     gráfica existente (sin parpadeo ni animación).
   ============================================================= */
let chartsExpanded = true;
let lastSeries = null;
let currentCharts = {};
let modalChart = null;

const CHART_TEXT = '#56685D';
const CHART_GRID = '#E9F0EB';

Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = CHART_TEXT;

function getChartColor(param) {
    return (PARAM_META[param] && PARAM_META[param].color) || '#16A34A';
}

function hexToRgba(hex, alpha) {
    const h = hex.replace('#', '');
    const n = parseInt(h.length === 3 ? h.split('').map(c => c + c).join('') : h, 16);
    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alpha})`;
}

function getGradient(ctx, color, height) {
    const gradient = ctx.createLinearGradient(0, 0, 0, height || 220);
    gradient.addColorStop(0, hexToRgba(color, .28));
    gradient.addColorStop(1, hexToRgba(color, .02));
    return gradient;
}

// Franjas horizontales con los rangos de cada categoría del ICA
const icaBandsPlugin = {
    id: 'icaBands',
    beforeDatasetsDraw(chart, args, opts) {
        const limites = opts && opts.limites;
        if (!limites || !limites.length) return;

        const { ctx, chartArea, scales } = chart;
        const y = scales.y;
        if (!chartArea || !y) return;

        ctx.save();
        let inferior = y.min;
        limites.forEach((superior, i) => {
            if (inferior >= y.max) return;
            const top = y.getPixelForValue(Math.min(superior, y.max));
            const bottom = y.getPixelForValue(Math.max(inferior, y.min));
            if (bottom > top) {
                ctx.fillStyle = hexToRgba(ICA.categorias[i].color, .08);
                ctx.fillRect(chartArea.left, top, chartArea.right - chartArea.left, bottom - top);
            }
            inferior = superior;
        });
        ctx.restore();
    },
};
Chart.register(icaBandsPlugin);

function downsample(s, maxPoints) {
    let labels = s.labels || [];
    let values = s.values || [];
    if (labels.length > maxPoints) {
        const step = Math.ceil(labels.length / maxPoints);
        labels = labels.filter((_, i) => i % step === 0);
        values = values.filter((_, i) => i % step === 0);
    }
    return { labels, values };
}

function chartOptions(param, s, { modal = false } = {}) {
    const limites = s.limites || null;

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { display: false },
            icaBands: { limites },
            tooltip: {
                backgroundColor: '#fff',
                titleColor: '#1B2B22',
                bodyColor: '#56685D',
                borderColor: '#E1EBE4',
                borderWidth: 1,
                padding: 10,
                cornerRadius: 10,
                displayColors: false,
                callbacks: {
                    title: items => items[0].label,
                    label: item => {
                        const cat = (typeof categoriaDe === 'function') ? categoriaDe(param, item.raw) : null;
                        return `${s.nombre}: ${item.raw} ${s.unidad}` + (cat ? ` · ${cat.etiqueta}` : '');
                    },
                },
            },
        },
        scales: {
            x: {
                display: true,
                grid: { color: CHART_GRID, drawBorder: false },
                ticks: {
                    color: CHART_TEXT,
                    maxTicksLimit: modal ? 15 : 8,
                    maxRotation: 0,
                    font: { size: modal ? 11 : 10 },
                },
            },
            y: {
                display: true,
                grid: { color: CHART_GRID, drawBorder: false },
                ticks: { color: CHART_TEXT, font: { size: modal ? 11 : 10 } },
                beginAtZero: param !== 'temperatura',
                // Mostrar al menos la franja "Buena" completa para dar contexto
                suggestedMax: limites ? limites[0] : undefined,
            },
        },
        animation: { duration: modal ? 400 : 500, easing: 'easeInOutQuart' },
    };
}

function chartDataset(ctx, param, s, values, height) {
    const color = getChartColor(param);
    return {
        label: s.nombre,
        data: values,
        borderColor: color,
        backgroundColor: getGradient(ctx, color, height),
        borderWidth: 2,
        pointRadius: 0,
        pointHitRadius: 10,
        pointHoverRadius: 4,
        pointHoverBackgroundColor: color,
        pointHoverBorderColor: '#fff',
        pointHoverBorderWidth: 2,
        fill: true,
        tension: 0.3,
    };
}

function actualizarBucketBadge(meta) {
    const badge = document.getElementById('bucketBadge');
    if (!badge) return;
    if (meta && meta.bucketTexto && meta.total) {
        badge.textContent = meta.bucketTexto;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

function renderCharts(series, meta = {}, { live = false } = {}) {
    const container = document.getElementById('chartsContainer');

    if (series === null) {
        destroyCharts();
        lastSeries = null;
        actualizarBucketBadge(null);
        container.innerHTML = `
            <div class="card card-placeholder">
                <div class="card-empty">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>No se pudieron cargar las gráficas</p>
                </div>
            </div>`;
        return;
    }

    const params = (meta.parametros && meta.parametros.length) ? meta.parametros : Object.keys(series || {});
    lastSeries = series || null;
    actualizarBucketBadge(meta);

    const hasAnyData = params.some(p => series[p] && series[p].values && series[p].values.length > 0);
    if (!hasAnyData) {
        destroyCharts();
        const rango = (typeof textoRango === 'function') ? textoRango() : '';
        container.innerHTML = `
            <div class="card card-placeholder">
                <div class="card-empty">
                    <i class="fas fa-chart-line"></i>
                    <p>No hay mediciones en el rango seleccionado<br><small>${rango}</small></p>
                </div>
            </div>`;
        return;
    }

    // Actualización en vivo con el mismo conjunto de gráficas: reemplazar datos sin redibujar todo
    const existentes = Object.keys(currentCharts);
    const mismoConjunto = live
        && existentes.length === params.length
        && params.every(p => currentCharts[p] || (series[p] && series[p].values.length === 0 && document.getElementById(`chart-empty-${p}`)));

    if (mismoConjunto && params.every(p => currentCharts[p] ? series[p].values.length > 0 : true)) {
        params.forEach(p => {
            const ch = currentCharts[p];
            if (!ch) return;
            const { labels, values } = downsample(series[p], 600);
            ch.data.labels = labels;
            ch.data.datasets[0].data = values;
            ch.update('none');
            const badge = document.getElementById(`chart-points-${p}`);
            if (badge) badge.textContent = `${series[p].values.length} puntos`;
        });
        return;
    }

    destroyCharts();

    container.innerHTML = params.map(p => {
        const s = series[p];
        const color = getChartColor(p);
        const meta = PARAM_META[p] || {};
        const nombre = (s && s.nombre) || meta.nombre || p;
        const unidad = (s && s.unidad) || meta.unidad || '';
        const tiene = s && s.values && s.values.length > 0;

        return `
            <div class="chart-card">
                <div class="chart-card-header">
                    <span class="chart-card-title">
                        <span class="chart-color" style="background:${color}"></span>
                        ${nombre} <small>(${unidad})</small>
                    </span>
                    <span class="chart-card-actions">
                        <span class="chart-card-status" id="chart-points-${p}">${tiene ? `${s.values.length} puntos` : 'sin datos'}</span>
                        ${tiene ? `
                        <button class="chart-expand-btn" onclick="openChartModal('${p}')" title="Agrandar gráfica">
                            <i class="fas fa-expand"></i>
                        </button>` : ''}
                    </span>
                </div>
                ${tiene
                    ? `<div class="chart-wrapper"><canvas id="chart-${p}"></canvas></div>`
                    : `<div class="chart-empty" id="chart-empty-${p}"><i class="fas fa-chart-line"></i>Sin datos en este rango</div>`}
            </div>`;
    }).join('');

    container.querySelectorAll('[id^="chart-"]').forEach(canvas => {
        if (canvas.tagName !== 'CANVAS') return;
        const param = canvas.id.replace('chart-', '');
        const s = series[param];
        if (!s || !s.values || s.values.length === 0) return;

        const ctx = canvas.getContext('2d');
        const { labels, values } = downsample(s, 600);

        currentCharts[param] = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets: [chartDataset(ctx, param, s, values, 220)] },
            options: chartOptions(param, s),
        });
    });
}

function destroyCharts() {
    Object.values(currentCharts).forEach(c => c.destroy());
    currentCharts = {};
}

function toggleAllCharts() {
    chartsExpanded = !chartsExpanded;
    document.querySelectorAll('.chart-card').forEach((el, i) => {
        el.style.display = chartsExpanded ? '' : 'none';
        if (chartsExpanded) {
            el.style.animation = 'none';
            el.offsetHeight;
            el.style.animation = `fadeIn .4s ease ${i * 0.05}s forwards`;
        }
    });
    if (chartsExpanded) {
        Object.values(currentCharts).forEach(c => c.resize());
    }
}

// ── Modal de gráfica expandida ───────────────────────────
function openChartModal(param) {
    if (!lastSeries || !lastSeries[param]) return;

    const s = lastSeries[param];
    if (!s.values || s.values.length === 0) return;

    let overlay = document.getElementById('chartModal');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'chartModal';
        overlay.className = 'chart-modal-overlay hidden';
        overlay.innerHTML = `
            <div class="chart-modal">
                <div class="chart-modal-header">
                    <span class="chart-modal-title"></span>
                    <button class="chart-modal-close" onclick="closeChartModal()" title="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="chart-modal-body">
                    <canvas id="chartModalCanvas"></canvas>
                </div>
            </div>`;
        document.body.appendChild(overlay);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeChartModal();
        });
    }

    document.querySelector('.chart-modal-title').textContent = `${s.nombre} (${s.unidad})`;

    const { labels, values } = downsample(s, 1500);
    const canvas = document.getElementById('chartModalCanvas');
    const ctx = canvas.getContext('2d');

    if (modalChart) modalChart.destroy();

    modalChart = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets: [chartDataset(ctx, param, s, values, 600)] },
        options: chartOptions(param, s, { modal: true }),
    });

    overlay.classList.remove('hidden');
    document.body.classList.add('modal-open');
    setTimeout(() => modalChart.resize(), 50);
}

function closeChartModal() {
    const overlay = document.getElementById('chartModal');
    if (overlay) overlay.classList.add('hidden');
    document.body.classList.remove('modal-open');
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeChartModal();
});
