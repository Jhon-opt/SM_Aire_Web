/* =============================================================
   Air Monitor Dashboard - Gráficas (Chart.js)
   ============================================================= */
let chartsExpanded = true;
let lastSeries = null;
let modalChart = null;

Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#94A3B8';

function getChartColor(param) {
    const colors = {
        pm2_5: '#4F46E5',
        pm10: '#7C3AED',
        co: '#F59E0B',
        co2: '#EF4444',
        o3: '#10B981',
        no2: '#F97316',
        temperatura: '#3B82F6',
        humedad: '#06B6D4',
    };
    return colors[param] || '#4F46E5';
}

function getGradient(ctx, color) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, color + '33');
    gradient.addColorStop(1, color + '02');
    return gradient;
}

function renderCharts(series, total) {
    const container = document.getElementById('chartsContainer');

    Object.values(currentCharts).forEach(c => c.destroy());
    currentCharts = {};
    lastSeries = series || null;

    if (series === null) {
        container.innerHTML = `
            <div class="card card-placeholder">
                <div class="card-empty">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>No se pudieron cargar las gráficas</p>
                </div>
            </div>`;
        return;
    }

    if (!series || Object.keys(series).length === 0) {
        container.innerHTML = `
            <div class="card card-placeholder">
                <div class="card-empty">
                    <i class="fas fa-chart-line"></i>
                    <p>No hay datos de mediciones disponibles</p>
                </div>
            </div>`;
        return;
    }

    const hasAnyData = Object.values(series).some(s => s && s.values && s.values.length > 0);
    if (!hasAnyData) {
        container.innerHTML = `
            <div class="card card-placeholder">
                <div class="card-empty">
                    <i class="fas fa-chart-line"></i>
                    <p>No hay datos de mediciones en el rango seleccionado</p>
                </div>
            </div>`;
        return;
    }

    const paramOrder = ['pm2_5', 'pm10', 'co', 'co2', 'o3', 'no2', 'temperatura', 'humedad'];

    container.innerHTML = paramOrder.map(p => {
        const s = series[p];
        if (!s || !s.values || s.values.length === 0) return '';

        const color = getChartColor(p);
        return `
            <div class="chart-card">
                <div class="chart-card-header">
                    <span class="chart-card-title">
                        <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:${color};margin-right:.5rem"></span>
                        ${s.nombre} (${s.unidad})
                    </span>
                    <span class="chart-card-actions">
                        <span class="chart-card-status" style="background:${color}15;color:${color}">
                            ${s.values.length} puntos
                        </span>
                        <button class="chart-expand-btn" onclick="openChartModal('${p}')" title="Agrandar gráfica">
                            <i class="fas fa-expand"></i>
                        </button>
                    </span>
                </div>
                <div class="chart-wrapper">
                    <canvas id="chart-${p}"></canvas>
                </div>
            </div>`;
    }).join('');

    container.querySelectorAll('[id^="chart-"]').forEach(canvas => {
        const param = canvas.id.replace('chart-', '');
        const s = series[param];
        if (!s || !s.values || s.values.length === 0) return;

        const ctx = canvas.getContext('2d');
        const color = getChartColor(param);
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const labelColor = isDark ? '#94A3B8' : '#64748B';
        const gridColor = isDark ? '#334155' : '#E2E8F0';

        // Downsample for performance if too many points
        let labels = s.labels;
        let values = s.values;
        const maxPoints = 200;
        if (labels.length > maxPoints) {
            const step = Math.ceil(labels.length / maxPoints);
            labels = labels.filter((_, i) => i % step === 0);
            values = values.filter((_, i) => i % step === 0);
        }

        currentCharts[param] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: s.nombre,
                    data: values,
                    borderColor: color,
                    backgroundColor: getGradient(ctx, color),
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHitRadius: 10,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: color,
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                    fill: true,
                    tension: 0.3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#1E293B' : '#fff',
                        titleColor: isDark ? '#E2E8F0' : '#1E293B',
                        bodyColor: isDark ? '#94A3B8' : '#64748B',
                        borderColor: isDark ? '#334155' : '#E2E8F0',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            title: function(items) {
                                return items[0].label;
                            },
                            label: function(item) {
                                return `${s.nombre}: ${item.raw} ${s.unidad}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        display: true,
                        grid: { color: gridColor, drawBorder: false },
                        ticks: {
                            color: labelColor,
                            maxTicksLimit: 10,
                            maxRotation: 0,
                            font: { size: 10 },
                        },
                    },
                    y: {
                        display: true,
                        grid: { color: gridColor, drawBorder: false },
                        ticks: {
                            color: labelColor,
                            font: { size: 10 },
                        },
                        beginAtZero: param === 'temperatura' ? false : true,
                    },
                },
                animation: {
                    duration: 500,
                    easing: 'easeInOutQuart',
                },
            },
        });
    });
}

function toggleAllCharts() {
    chartsExpanded = !chartsExpanded;
    const charts = document.querySelectorAll('.chart-card');
    charts.forEach(c => {
        c.style.display = chartsExpanded ? '' : 'none';
    });
    // Re-trigger animation
    document.querySelectorAll('.chart-card').forEach((el, i) => {
        el.style.animation = 'none';
        el.offsetHeight;
        el.style.animation = `fadeIn .4s ease ${i * 0.05}s forwards`;
    });
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

    const color = getChartColor(param);
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const labelColor = isDark ? '#94A3B8' : '#64748B';
    const gridColor = isDark ? '#334155' : '#E2E8F0';

    document.querySelector('.chart-modal-title').textContent = `${s.nombre} (${s.unidad})`;

    // Downsample suave solo para datasets muy grandes, para no saturar el render
    let labels = s.labels;
    let values = s.values;
    const maxPoints = 1500;
    if (labels.length > maxPoints) {
        const step = Math.ceil(labels.length / maxPoints);
        labels = labels.filter((_, i) => i % step === 0);
        values = values.filter((_, i) => i % step === 0);
    }

    const canvas = document.getElementById('chartModalCanvas');
    const ctx = canvas.getContext('2d');

    if (modalChart) modalChart.destroy();

    modalChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: s.nombre,
                data: values,
                borderColor: color,
                backgroundColor: getGradient(ctx, color),
                borderWidth: 2,
                pointRadius: 0,
                pointHitRadius: 10,
                pointHoverRadius: 4,
                pointHoverBackgroundColor: color,
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
                fill: true,
                tension: 0.3,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#1E293B' : '#fff',
                    titleColor: isDark ? '#E2E8F0' : '#1E293B',
                    bodyColor: isDark ? '#94A3B8' : '#64748B',
                    borderColor: isDark ? '#334155' : '#E2E8F0',
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        title: function(items) {
                            return items[0].label;
                        },
                        label: function(item) {
                            return `${s.nombre}: ${item.raw} ${s.unidad}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    display: true,
                    grid: { color: gridColor, drawBorder: false },
                    ticks: {
                        color: labelColor,
                        maxTicksLimit: 15,
                        maxRotation: 0,
                        font: { size: 11 },
                    },
                },
                y: {
                    display: true,
                    grid: { color: gridColor, drawBorder: false },
                    ticks: {
                        color: labelColor,
                        font: { size: 11 },
                    },
                    beginAtZero: param === 'temperatura' ? false : true,
                },
            },
            animation: {
                duration: 400,
                easing: 'easeInOutQuart',
            },
        },
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
