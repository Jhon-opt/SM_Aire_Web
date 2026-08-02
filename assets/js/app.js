/* =============================================================
   Air Monitor Dashboard - Aplicación principal
   ============================================================= */
const API = {
    filtros: 'api/filtros',
    ultimas: 'api/ultimas',
    mediciones: 'api/mediciones',
    estadisticas: 'api/estadisticas',
    tabla: 'api/tabla',
};

let refreshInterval = null;
let refreshCountdown = 60;
let currentCharts = {};
let currentOrden = 'DESC';
let currentPagina = 1;

async function apiGet(endpoint, params = {}) {
    const url = new URL(APP_BASE_URL + '/' + endpoint);
    Object.entries(params).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') url.searchParams.set(k, v);
    });

    try {
        const res = await fetch(url.toString());
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
    } catch (e) {
        console.error('API Error:', e);
        throw e;
    }
}

function getFilters() {
    return {
        colegio: document.getElementById('filterColegio').value,
        dispositivo: document.getElementById('filterDispositivo').value,
        intervalo: document.getElementById('filterIntervalo').value,
        fecha_inicio: document.getElementById('filterFechaInicio').value || null,
        fecha_fin: document.getElementById('filterFechaFin').value || null,
    };
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

function setConnectionStatus(connected) {
    const dot = document.querySelector('.status-dot');
    const text = document.querySelector('.status-text');
    if (connected) {
        dot.className = 'status-dot connected';
        text.textContent = 'Conectado';
    } else {
        dot.className = 'status-dot disconnected';
        text.textContent = 'Desconectado';
    }
}

function showApiError(message) {
    const banner = document.getElementById('apiErrorBanner');
    if (!banner) return;
    document.getElementById('apiErrorText').textContent = message || 'No se pudo conectar con la API.';
    banner.classList.remove('hidden');
}

function hideApiError() {
    const banner = document.getElementById('apiErrorBanner');
    if (banner) banner.classList.add('hidden');
}

// ── Filtros ─────────────────────────────────────────────
async function onColegioChange() {
    const colegioId = document.getElementById('filterColegio').value;
    const select = document.getElementById('filterDispositivo');
    select.innerHTML = '<option value="">Todos los dispositivos</option>';

    if (colegioId) {
        try {
            const data = await apiGet(API.filtros, { colegio: colegioId });
            if (data.dispositivos) {
                data.dispositivos.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id_dispositivo;
                    opt.textContent = `${d.codigo} - ${d.modelo || ''} (${d.ubicacion || ''})`;
                    select.appendChild(opt);
                });
            }
        } catch (e) {
            console.error('Error cargando dispositivos:', e);
        }
    }

    onFilterChange();
}

function onFilterChange() {
    currentPagina = 1;
    loadAllData();
    resetAutoRefresh();
}

function onIntervaloChange() {
    const val = document.getElementById('filterIntervalo').value;
    const dateFields = document.querySelectorAll('.filter-date');
    const applyBtn = document.getElementById('applyDateBtn');

    if (val === 'custom') {
        dateFields.forEach(el => el.classList.remove('hidden'));
        applyBtn.classList.remove('hidden');
    } else {
        dateFields.forEach(el => el.classList.add('hidden'));
        applyBtn.classList.add('hidden');
        onFilterChange();
    }
}

function applyCustomDate() {
    onFilterChange();
}

function resetFilters() {
    document.getElementById('filterColegio').value = '';
    document.getElementById('filterDispositivo').innerHTML = '<option value="">Todos los dispositivos</option>';
    document.getElementById('filterIntervalo').value = '30d';
    document.getElementById('filterFechaInicio').value = '';
    document.getElementById('filterFechaFin').value = '';
    document.querySelectorAll('.filter-date').forEach(el => el.classList.add('hidden'));
    document.getElementById('applyDateBtn').classList.add('hidden');
    onFilterChange();
}

// ── Cards ───────────────────────────────────────────────
function getLevelClass(level) {
    return level === 'bueno' ? 'bueno' : level === 'moderado' ? 'moderado' : 'malo';
}

function getIcon(param) {
    const icons = {
        pm2_5: 'fa-wind',
        pm10: 'fa-smog',
        co: 'fa-industry',
        co2: 'fa-dumpster',
        o3: 'fa-sun',
        no2: 'fa-car',
        temperatura: 'fa-temperature-high',
        humedad: 'fa-droplet',
    };
    return icons[param] || 'fa-chart-simple';
}

function renderCards(tarjetas, fecha) {
    const container = document.getElementById('cardsContainer');

    if (tarjetas === null) {
        container.innerHTML = `
            <div class="card card-placeholder" colspan="8">
                <div class="card-empty">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>No se pudieron cargar los datos</p>
                </div>
            </div>`;
        return;
    }

    if (!tarjetas || tarjetas.length === 0) {
        container.innerHTML = `
            <div class="card card-placeholder" colspan="8">
                <div class="card-empty">
                    <i class="fas fa-microchip"></i>
                    <p>${fecha ? 'Sin lecturas recientes para los filtros seleccionados' : 'Selecciona un dispositivo para ver los datos'}</p>
                </div>
            </div>`;
        return;
    }

    container.innerHTML = tarjetas.map(t => `
        <div class="card">
            <div class="card-header">
                <span class="card-title">${t.nombre}</span>
                <div class="card-icon" style="background: ${t.color}22; color: ${t.color}">
                    <i class="fas ${getIcon(t.parametro)}"></i>
                </div>
            </div>
            <div class="card-value" style="color: ${t.color}">${t.valor}</div>
            <div class="card-unit">${t.unidad}</div>
            <div class="card-date">${t.fecha ? formatDate(t.fecha) : '—'}</div>
            <div class="card-quality ${getLevelClass(t.nivel)}">
                <span class="quality-dot" style="background: ${t.color}"></span>
                ${t.nivel}
            </div>
        </div>
    `).join('');
}

function formatDate(dateStr) {
    const d = new Date(dateStr.replace(' ', 'T') + 'Z');
    if (isNaN(d.getTime())) return dateStr;
    const now = new Date();
    const diff = (now - d) / 1000;

    if (diff < 60) return 'Ahora';
    if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `hace ${Math.floor(diff / 3600)}h`;
    return d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

// ── Stats ───────────────────────────────────────────────
function renderStats(estadisticas, totalRegistros) {
    const container = document.getElementById('statsContainer');
    document.getElementById('totalRegistrosBadge').textContent =
        `${(totalRegistros || 0).toLocaleString()} registros`;

    if (estadisticas === null) {
        container.innerHTML = `
            <div class="table-responsive">
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>No se pudieron cargar las estadísticas</p>
                </div>
            </div>`;
        return;
    }

    if (!estadisticas || estadisticas.length === 0) {
        container.innerHTML = `
            <div class="table-responsive">
                <div class="empty-state">
                    <i class="fas fa-calculator"></i>
                    <p>No hay estadísticas disponibles</p>
                </div>
            </div>`;
        return;
    }

    container.innerHTML = `
        <div class="table-responsive">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Parámetro</th>
                        <th>Promedio</th>
                        <th>Máximo</th>
                        <th>Mínimo</th>
                    </tr>
                </thead>
                <tbody>
                    ${estadisticas.map(s => `
                        <tr>
                            <td><strong>${s.nombre}</strong> <span class="text-light">(${s.unidad})</span></td>
                            <td class="cell-value">${s.promedio !== null ? s.promedio : '—'}</td>
                            <td class="cell-value" style="color: #EF4444">${s.maximo !== null ? s.maximo : '—'}</td>
                            <td class="cell-value" style="color: #10B981">${s.minimo !== null ? s.minimo : '—'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>`;
}

// ── Table ───────────────────────────────────────────────
function renderTablaError() {
    const container = document.getElementById('tableContainer');
    container.innerHTML = `
        <div class="table-responsive">
            <div class="empty-state">
                <i class="fas fa-exclamation-triangle"></i>
                <p>No se pudo cargar la tabla</p>
            </div>
        </div>`;
    document.getElementById('paginationContainer').innerHTML = '';
}

function renderTabla(data) {
    const container = document.getElementById('tableContainer');

    if (!data.data || data.data.length === 0) {
        container.innerHTML = `
            <div class="table-responsive">
                <div class="empty-state">
                    <i class="fas fa-table"></i>
                    <p>No hay mediciones disponibles</p>
                </div>
            </div>`;
        document.getElementById('paginationContainer').innerHTML = '';
        return;
    }

    const cols = ['fecha_hora', 'pm2_5', 'pm10', 'co', 'co2', 'o3', 'no2', 'temperatura', 'humedad'];
    const headers = ['Fecha/Hora', 'PM2.5', 'PM10', 'CO', 'CO2', 'O₃', 'NO₂', 'Temp', 'Humedad'];
    const units = ['', 'µg/m³', 'µg/m³', 'ppm', 'ppm', 'ppb', 'ppb', '°C', '%'];

    container.innerHTML = `
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        ${headers.map((h, i) => `
                            <th onclick="sortTable('${cols[i]}')">
                                ${h}
                                ${i === 0 ? `<i class="fas fa-sort-${currentOrden === 'DESC' ? 'down' : 'up'}"></i>` : ''}
                                ${i > 0 ? '<span class="text-light"> (' + units[i] + ')</span>' : ''}
                            </th>
                        `).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${data.data.map(row => `
                        <tr>
                            <td>${formatDate(row.fecha_hora)}</td>
                            ${cols.slice(1).map(c => `
                                <td class="cell-value">${row[c] !== null ? row[c] : '—'}</td>
                            `).join('')}
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>`;

    renderPagination(data);
}

function renderPagination(data) {
    const container = document.getElementById('paginationContainer');
    if (data.total_paginas <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';
    const p = data.pagina;
    const total = data.total_paginas;

    html += `<button class="page-btn" onclick="goToPage(${p - 1})" ${p <= 1 ? 'disabled' : ''}>
        <i class="fas fa-chevron-left"></i>
    </button>`;

    const start = Math.max(1, p - 2);
    const end = Math.min(total, p + 2);

    if (start > 1) {
        html += `<button class="page-btn" onclick="goToPage(1)">1</button>`;
        if (start > 2) html += `<span class="page-sep">...</span>`;
    }

    for (let i = start; i <= end; i++) {
        html += `<button class="page-btn ${i === p ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
    }

    if (end < total) {
        if (end < total - 1) html += `<span class="page-sep">...</span>`;
        html += `<button class="page-btn" onclick="goToPage(${total})">${total}</button>`;
    }

    html += `<button class="page-btn" onclick="goToPage(${p + 1})" ${p >= total ? 'disabled' : ''}>
        <i class="fas fa-chevron-right"></i>
    </button>`;

    container.innerHTML = html;
}

function goToPage(pagina) {
    currentPagina = pagina;
    loadTabla();
}

function sortTable(col) {
    currentOrden = currentOrden === 'DESC' ? 'ASC' : 'DESC';
    document.getElementById('ordenBtn').textContent = currentOrden === 'DESC' ? '↓ Más recientes' : '↑ Más antiguos';
    loadTabla();
}

function toggleOrden() {
    currentOrden = currentOrden === 'DESC' ? 'ASC' : 'DESC';
    document.getElementById('ordenBtn').textContent = currentOrden === 'DESC' ? '↓ Más recientes' : '↑ Más antiguos';
    loadTabla();
}

// ── Data Loading ────────────────────────────────────────
async function loadAllData() {
    const filters = getFilters();
    showLoading();

    const results = await Promise.allSettled([
        loadUltimas(filters),
        loadMediciones(filters),
        loadEstadisticas(filters),
        loadTabla(filters),
    ]);

    const failed = results.filter(r => r.status === 'rejected');

    if (failed.length === results.length) {
        setConnectionStatus(false);
        showApiError('No se pudo conectar con la API. Verifica tu conexión o intenta de nuevo.');
    } else {
        setConnectionStatus(true);
        hideApiError();
        if (failed.length > 0) {
            showApiError('Algunos datos no pudieron cargarse. Reintentando en el próximo ciclo…');
        }
    }

    hideLoading();
}

async function loadUltimas(filters) {
    try {
        const data = await apiGet(API.ultimas, filters);
        renderCards(data.tarjetas, data.fecha);
        if (data.fecha) {
            document.getElementById('ultimaActualizacion').textContent = formatDate(data.fecha);
        }
    } catch (e) {
        console.error('Error loading ultimas:', e);
        renderCards(null, null);
        throw e;
    }
}

async function loadMediciones(filters) {
    try {
        const data = await apiGet(API.mediciones, filters);
        renderCharts(data.series, data.total);
    } catch (e) {
        console.error('Error loading mediciones:', e);
        renderCharts(null, 0);
        throw e;
    }
}

async function loadEstadisticas(filters) {
    try {
        const data = await apiGet(API.estadisticas, filters);
        renderStats(data.estadisticas, data.total_registros);
    } catch (e) {
        console.error('Error loading estadisticas:', e);
        renderStats(null, 0);
        throw e;
    }
}

async function loadTabla(filters = null) {
    if (!filters) filters = getFilters();
    try {
        const data = await apiGet(API.tabla, { ...filters, pagina: currentPagina, orden: currentOrden });
        renderTabla(data);
    } catch (e) {
        console.error('Error loading tabla:', e);
        renderTablaError();
        throw e;
    }
}

// ── Auto Refresh ────────────────────────────────────────
function startAutoRefresh() {
    stopAutoRefresh();
    refreshCountdown = REFRESH_INTERVAL || 60;
    updateCountdown();

    refreshInterval = setInterval(() => {
        refreshCountdown--;
        updateCountdown();
        if (refreshCountdown <= 0) {
            refreshCountdown = REFRESH_INTERVAL || 60;
            loadAllData();
        }
    }, 1000);
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}

function resetAutoRefresh() {
    refreshCountdown = REFRESH_INTERVAL || 60;
    updateCountdown();
}

function updateCountdown() {
    const el = document.getElementById('refreshCountdown');
    if (el) el.textContent = refreshCountdown;
}

// ── Sidebar ─────────────────────────────────────────────
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

// ── Dark Mode ───────────────────────────────────────────
function toggleDarkMode() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('air-monitor-theme', next);

    const icon = document.getElementById('darkModeIcon');
    icon.className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}

function initDarkMode() {
    const saved = localStorage.getItem('air-monitor-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = saved || (prefersDark ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', theme);
    const icon = document.getElementById('darkModeIcon');
    icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}

function exportExcel() {
    const filters = getFilters();
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => {
        if (v) params.set(k, v);
    });
    window.open(APP_BASE_URL + '/export/excel?' + params.toString(), '_blank');
}

// ── Init ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    loadAllData().then(() => startAutoRefresh());
});
