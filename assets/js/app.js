/* =============================================================
   Air Monitor Dashboard - Aplicación principal
   - Actualización en tiempo real: consulta la última lectura cada
     LIVE_POLL_SECONDS y, cuando hay una medición nueva, refresca
     tarjetas, gráficas, estadísticas y tabla sin recargar la página.
   - Fechas: la API entrega UTC; aquí se muestran en APP_TIMEZONE
     (hora de Colombia) sin depender de la zona horaria del navegador.
   ============================================================= */
const API = {
    filtros: 'api/filtros',
    ultimas: 'api/ultimas',
    mediciones: 'api/mediciones',
    estadisticas: 'api/estadisticas',
    tabla: 'api/tabla',
};

// Metadatos de presentación por parámetro (nombre, unidad, icono, color de gráfica)
const PARAM_META = {
    pm2_5:       { nombre: 'PM2.5',       unidad: 'µg/m³', icono: 'fa-wind',             color: '#16A34A' },
    pm10:        { nombre: 'PM10',        unidad: 'µg/m³', icono: 'fa-smog',             color: '#0D9488' },
    co:          { nombre: 'CO',          unidad: 'ppm',   icono: 'fa-fire-flame-simple', color: '#CA8A04' },
    co2:         { nombre: 'CO2',         unidad: 'ppm',   icono: 'fa-cloud',            color: '#7C3AED' },
    o3:          { nombre: 'O₃',          unidad: 'ppb',   icono: 'fa-sun',              color: '#2563EB' },
    no2:         { nombre: 'NO₂',         unidad: 'ppb',   icono: 'fa-car',              color: '#EA580C' },
    temperatura: { nombre: 'Temperatura', unidad: '°C',    icono: 'fa-temperature-half', color: '#F97316' },
    humedad:     { nombre: 'Humedad',     unidad: '%',     icono: 'fa-droplet',          color: '#0EA5E9' },
};

const state = {
    pagina: 1,
    orden: 'DESC',
    lastFecha: null,     // fecha_hora (UTC) de la última lectura mostrada
    liveTimer: null,
    tickTimer: null,
    loading: false,
    liveMode: 'ok',
};

// ── Utilidades ──────────────────────────────────────────
async function apiGet(endpoint, params = {}) {
    const url = new URL(APP_BASE_URL + '/' + endpoint);
    Object.entries(params).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') url.searchParams.set(k, v);
    });

    const res = await fetch(url.toString(), { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
}

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function formatNum(v) {
    if (v === null || v === undefined || v === '') return '—';
    const n = Number(v);
    if (isNaN(n)) return escapeHtml(v);
    return String(parseFloat(n.toFixed(2)));
}

// ── Fechas (UTC de la API -> hora de Colombia) ──────────
function parseUtc(str) {
    if (!str) return null;
    const d = new Date(String(str).replace(' ', 'T') + 'Z');
    return isNaN(d.getTime()) ? null : d;
}

const FMT_FECHA = new Intl.DateTimeFormat('es-CO', {
    timeZone: APP_TIMEZONE, day: '2-digit', month: '2-digit', year: 'numeric',
});
const FMT_HORA = new Intl.DateTimeFormat('es-CO', {
    timeZone: APP_TIMEZONE, hour: '2-digit', minute: '2-digit', hour12: false,
});
const FMT_HORA_SEG = new Intl.DateTimeFormat('es-CO', {
    timeZone: APP_TIMEZONE, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
});

function formatFechaLocal(str, conSegundos = false) {
    const d = parseUtc(str);
    if (!d) return '—';
    return `${FMT_FECHA.format(d)} ${(conSegundos ? FMT_HORA_SEG : FMT_HORA).format(d)}`;
}

function formatHoraLocal(str) {
    const d = parseUtc(str);
    return d ? FMT_HORA.format(d) : '—';
}

function tiempoRelativo(str) {
    const d = parseUtc(str);
    if (!d) return '—';
    const diff = Math.max(0, Math.round((Date.now() - d.getTime()) / 1000));

    if (diff < 5) return 'ahora mismo';
    if (diff < 60) return `hace ${diff} s`;
    if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `hace ${Math.floor(diff / 3600)} h`;
    return `hace ${Math.floor(diff / 86400)} d`;
}

// Refresca todos los "hace X" de la página (se ejecuta cada segundo)
function actualizarRelativos() {
    document.querySelectorAll('.rel[data-fecha]').forEach(el => {
        el.textContent = tiempoRelativo(el.dataset.fecha);
    });
    actualizarLiveBadge();
}

// ── Categorías ICA (espejo de includes/functions.php) ───
function categoriaDe(param, valor) {
    if (valor === null || valor === undefined || valor === '') return null;
    const v = Number(valor);
    if (isNaN(v)) return null;

    const cats = ICA.categorias;

    if (param === 'temperatura' || param === 'humedad') {
        const [a, b, c, d] = param === 'temperatura' ? [18, 26, 12, 32] : [30, 60, 20, 80];
        const etiquetas = ['Confortable', 'Aceptable', 'Extrema'];
        const i = (v >= a && v <= b) ? 0 : ((v >= c && v <= d) ? 1 : 2);
        return { ...cats[i], etiqueta: etiquetas[i], indice: i };
    }

    const lim = ICA.limites[param];
    if (!lim) return { ...cats[0], indice: 0 };

    let i = lim.length - 1;
    for (let k = 0; k < lim.length; k++) {
        if (v <= lim[k]) { i = k; break; }
    }
    return { ...cats[i], indice: i };
}

function catVars(cat) {
    if (!cat) return '';
    return `--cat-color:${cat.color};--cat-fondo:${cat.fondo};--cat-texto:${cat.texto}`;
}

// ── Estado de conexión / errores ────────────────────────
function showLoading() { document.getElementById('loadingOverlay').classList.remove('hidden'); }
function hideLoading() { document.getElementById('loadingOverlay').classList.add('hidden'); }

function setConnectionStatus(connected) {
    const dot = document.querySelector('.status-dot');
    const text = document.querySelector('.status-text');
    if (!dot || !text) return;
    dot.className = connected ? 'status-dot connected' : 'status-dot disconnected';
    text.textContent = connected ? 'Conectado' : 'Desconectado';
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
function getFilters() {
    return {
        colegio: document.getElementById('filterColegio').value,
        dispositivo: document.getElementById('filterDispositivo').value,
        intervalo: document.getElementById('filterIntervalo').value,
        fecha_inicio: document.getElementById('filterFechaInicio').value || null,
        fecha_fin: document.getElementById('filterFechaFin').value || null,
    };
}

// Texto legible del rango activo (el día fin siempre incluye el día completo).
function textoRango() {
    const intervalo = document.getElementById('filterIntervalo').value;
    if (intervalo === 'custom') {
        const ini = document.getElementById('filterFechaInicio').value;
        const fin = document.getElementById('filterFechaFin').value;
        const fmt = v => {
            const p = v.split('-');
            return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : v;
        };
        if (ini && fin) return `${fmt(ini)} – ${fmt(fin)} (día completo)`;
        return 'rango personalizado incompleto';
    }
    const nombres = {
        '24h': 'Últimas 24 horas',
        '7d': 'Últimos 7 días',
        '30d': 'Últimos 30 días',
        'all': 'Todo el historial',
    };
    return nombres[intervalo] || intervalo;
}

function actualizarRangoBadge() {
    const el = document.getElementById('rangoBadge');
    if (el) el.textContent = textoRango();
}

// Llena el selector de dispositivos del colegio elegido
async function cargarDispositivos(colegioId) {
    const select = document.getElementById('filterDispositivo');
    select.innerHTML = '<option value="">Todos los dispositivos</option>';
    if (!colegioId) return;

    try {
        const data = await apiGet(API.filtros, { colegio: colegioId });
        (data.dispositivos || []).forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.id_dispositivo;
            opt.textContent = `${d.codigo} - ${d.modelo || ''} (${d.ubicacion || ''})`;
            select.appendChild(opt);
        });
    } catch (e) {
        console.error('Error cargando dispositivos:', e);
    }
}

async function onColegioChange() {
    const colegioId = document.getElementById('filterColegio').value;
    await cargarDispositivos(colegioId);
    onFilterChange();
}

function onFilterChange() {
    state.pagina = 1;
    state.lastFecha = null;
    actualizarRangoBadge();
    loadAllData(true);
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
    document.getElementById('filterIntervalo').value = '24h';
    document.getElementById('filterFechaInicio').value = '';
    document.getElementById('filterFechaFin').value = '';
    document.querySelectorAll('.filter-date').forEach(el => el.classList.add('hidden'));
    document.getElementById('applyDateBtn').classList.add('hidden');
    onFilterChange();
}

// ── Estado general (hero) ───────────────────────────────
function renderEstado(estado, fecha, origen) {
    const el = document.getElementById('estadoGeneral');
    if (!el) return;

    if (!estado) {
        el.innerHTML = `
            <div class="status-card status-card-empty">
                <i class="fas fa-satellite-dish"></i>
                <p>${fecha ? 'Sin datos suficientes para calcular el estado' : 'Sin lecturas recientes para los filtros seleccionados'}</p>
            </div>`;
        return;
    }

    const origenTxt = origen && origen.dispositivo
        ? [origen.dispositivo, origen.ubicacion, origen.colegio].filter(Boolean).map(escapeHtml).join(' · ')
        : '';

    el.innerHTML = `
        <div class="status-card" style="${catVars(estado)}">
            <div class="status-head">
                <div class="status-icon"><i class="fas ${estado.icono}"></i></div>
                <div>
                    <div class="status-kicker">Estado general del aire</div>
                    <div class="status-label">${escapeHtml(estado.etiqueta)}</div>
                    <div class="status-param">Determinado por <strong>${escapeHtml(estado.parametro)}</strong></div>
                </div>
            </div>
            <div class="status-message">${escapeHtml(estado.mensaje)}</div>
            <div class="status-foot">
                <span><i class="far fa-clock"></i><span class="rel" data-fecha="${escapeHtml(fecha)}">${tiempoRelativo(fecha)}</span> · ${formatFechaLocal(fecha, true)}</span>
                ${origenTxt ? `<span><i class="fas fa-microchip"></i>${origenTxt}</span>` : ''}
            </div>
        </div>`;
}

// ── Tarjetas ────────────────────────────────────────────
function renderCards(tarjetas, fecha, { flash = false } = {}) {
    const container = document.getElementById('cardsContainer');

    if (tarjetas === null) {
        container.innerHTML = `
            <div class="card card-placeholder">
                <div class="card-empty">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>No se pudieron cargar los datos</p>
                </div>
            </div>`;
        return;
    }

    if (!tarjetas || tarjetas.length === 0) {
        container.innerHTML = `
            <div class="card card-placeholder">
                <div class="card-empty">
                    <i class="fas fa-microchip"></i>
                    <p>Sin lecturas para los filtros seleccionados</p>
                </div>
            </div>`;
        return;
    }

    container.innerHTML = tarjetas.map(t => {
        const cat = t.categoria;
        const sinDato = t.valor === null || t.valor === undefined;
        const meta = PARAM_META[t.parametro] || {};
        const segmentos = cat ? cat.segmentos : 6;
        const segs = ICA.categorias.slice(0, segmentos).map((c, i) =>
            `<span class="card-scale-seg ${cat && i === cat.indice ? 'on' : ''}" style="background:${c.color}"></span>`
        ).join('');

        return `
            <div class="card ${sinDato ? 'card-nodata' : ''} ${flash ? 'flash' : ''}" style="${catVars(cat)}" data-param="${t.parametro}">
                <div class="card-header">
                    <div>
                        <span class="card-title">${escapeHtml(t.nombre)}</span>
                        <span class="card-subtitle">${escapeHtml(t.descripcion || '')}</span>
                    </div>
                    <div class="card-icon"><i class="fas ${meta.icono || 'fa-chart-simple'}"></i></div>
                </div>
                <div class="card-value-row">
                    <span class="card-value">${sinDato ? '—' : formatNum(t.valor)}</span>
                    <span class="card-unit">${escapeHtml(t.unidad)}</span>
                </div>
                <div class="card-quality"><span class="quality-dot"></span>${cat ? escapeHtml(cat.etiqueta) : 'Sin dato reciente'}</div>
                <div class="card-scale" title="Posición dentro de la escala ICA">
                    ${segs}
                    ${cat ? `<span class="card-scale-marker" style="left:${cat.posicion}%"></span>` : ''}
                </div>
                <div class="card-date">
                    <i class="far fa-clock"></i>
                    <span class="rel" data-fecha="${escapeHtml(fecha)}">${tiempoRelativo(fecha)}</span>
                    <span>· ${formatFechaLocal(fecha)}</span>
                </div>
            </div>`;
    }).join('');
}

// ── Estadísticas ────────────────────────────────────────
function renderStats(estadisticas, totalRegistros) {
    const container = document.getElementById('statsContainer');
    document.getElementById('totalRegistrosBadge').textContent =
        `${(totalRegistros || 0).toLocaleString('es-CO')} registros`;

    if (estadisticas === null) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-exclamation-triangle"></i>
                <p>No se pudieron cargar las estadísticas</p>
            </div>`;
        return;
    }

    if (!estadisticas || estadisticas.length === 0 || !totalRegistros) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calculator"></i>
                <p>No hay estadísticas en el rango seleccionado<br><small>${textoRango()}</small></p>
            </div>`;
        return;
    }

    container.innerHTML = `
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Parámetro</th>
                    <th>Promedio</th>
                    <th>Estado (promedio)</th>
                    <th>Máximo</th>
                    <th>Mínimo</th>
                </tr>
            </thead>
            <tbody>
                ${estadisticas.map(s => {
                    const cat = s.categoria;
                    return `
                    <tr>
                        <td class="stats-param">${escapeHtml(s.nombre)}<small>(${escapeHtml(s.unidad)})</small></td>
                        <td class="cell-value">${formatNum(s.promedio)}</td>
                        <td>${cat
                            ? `<span class="card-quality" style="${catVars(cat)}"><span class="quality-dot"></span>${escapeHtml(cat.etiqueta)}</span>`
                            : '<span class="text-light">Sin datos</span>'}</td>
                        <td class="cell-value">${formatNum(s.maximo)}</td>
                        <td class="cell-value">${formatNum(s.minimo)}</td>
                    </tr>`;
                }).join('')}
            </tbody>
        </table>`;
}

// ── Tabla de mediciones ─────────────────────────────────
function renderTablaError() {
    document.getElementById('tableContainer').innerHTML = `
        <div class="empty-state">
            <i class="fas fa-exclamation-triangle"></i>
            <p>No se pudo cargar la tabla</p>
        </div>`;
    document.getElementById('paginationContainer').innerHTML = '';
}

function renderTabla(data) {
    const container = document.getElementById('tableContainer');

    if (!data.data || data.data.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-table"></i>
                <p>No hay mediciones en el rango seleccionado<br><small>${textoRango()}</small></p>
            </div>`;
        document.getElementById('paginationContainer').innerHTML = '';
        return;
    }

    // Columnas: principales siempre + opcionales con datos (las decide la API)
    const cols = data.columnas || ['pm2_5', 'pm10', 'co'];
    const dispositivos = data.dispositivos || {};
    const multi = Object.keys(dispositivos).length > 1;

    container.innerHTML = `
        <table class="data-table">
            <thead>
                <tr>
                    <th class="sortable" onclick="toggleOrden()">
                        Fecha / Hora <span class="text-light">(Colombia)</span>
                        <i class="fas fa-sort-${state.orden === 'DESC' ? 'down' : 'up'}"></i>
                    </th>
                    ${multi ? '<th>Dispositivo</th>' : ''}
                    ${cols.map(c => {
                        const m = PARAM_META[c] || { nombre: c, unidad: '' };
                        return `<th>${escapeHtml(m.nombre)} <span class="text-light">(${escapeHtml(m.unidad)})</span></th>`;
                    }).join('')}
                </tr>
            </thead>
            <tbody>
                ${data.data.map(row => `
                    <tr>
                        <td class="cell-date">${formatFechaLocal(row.fecha_hora, true)}</td>
                        ${multi ? `<td class="cell-device">${escapeHtml((dispositivos[row.id_dispositivo] || {}).codigo || row.id_dispositivo)}</td>` : ''}
                        ${cols.map(c => {
                            const v = row[c];
                            if (v === null || v === undefined || v === '') {
                                return '<td class="cell-value text-light">—</td>';
                            }
                            const cat = categoriaDe(c, v);
                            return `<td class="cell-value"><span class="val-dot" style="background:${cat ? cat.color : ''}"></span>${formatNum(v)}</td>`;
                        }).join('')}
                    </tr>
                `).join('')}
            </tbody>
        </table>`;

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
    state.pagina = pagina;
    loadTabla();
}

function toggleOrden() {
    state.orden = state.orden === 'DESC' ? 'ASC' : 'DESC';
    document.getElementById('ordenBtn').textContent = state.orden === 'DESC' ? '↓ Más recientes' : '↑ Más antiguos';
    loadTabla();
}

// ── Carga de datos ──────────────────────────────────────
async function loadAllData(showOverlay = true) {
    const filters = getFilters();
    state.loading = true;
    if (showOverlay) showLoading();

    const results = await Promise.allSettled([
        loadUltimas(filters),
        loadMediciones(filters),
        loadEstadisticas(filters),
        loadTabla(filters),
    ]);

    if (typeof actualizarMapa === 'function') actualizarMapa();

    const failed = results.filter(r => r.status === 'rejected');

    if (failed.length === results.length) {
        setConnectionStatus(false);
        setLive('offline');
        showApiError('No se pudo conectar con la API. Verifica tu conexión o intenta de nuevo.');
    } else {
        setConnectionStatus(true);
        setLive('ok');
        hideApiError();
        if (failed.length > 0) {
            showApiError('Algunos datos no pudieron cargarse. Se reintentará automáticamente.');
        }
    }

    state.loading = false;
    hideLoading();
}

// Devuelve true si la lectura recibida es más nueva que la mostrada
async function loadUltimas(filters, { live = false } = {}) {
    try {
        const data = await apiGet(API.ultimas, filters);
        const nueva = !!data.fecha && data.fecha !== state.lastFecha;
        state.lastFecha = data.fecha || null;

        renderCards(data.tarjetas, data.fecha, { flash: live && nueva });
        renderEstado(data.estado_general, data.fecha, data.origen);

        const ult = document.getElementById('ultimaActualizacion');
        if (ult) {
            if (data.fecha) {
                ult.classList.add('rel');
                ult.dataset.fecha = data.fecha;
                ult.textContent = tiempoRelativo(data.fecha);
            } else {
                ult.classList.remove('rel');
                delete ult.dataset.fecha;
                ult.textContent = '—';
            }
        }

        const tot = document.getElementById('totalMediciones');
        if (tot && typeof data.total_mediciones === 'number') {
            tot.textContent = data.total_mediciones.toLocaleString('es-CO');
        }

        return nueva;
    } catch (e) {
        console.error('Error loading ultimas:', e);
        if (!live) {
            renderCards(null, null);
            renderEstado(null, null, null);
        }
        throw e;
    }
}

async function loadMediciones(filters, { live = false } = {}) {
    try {
        const data = await apiGet(API.mediciones, filters);
        renderCharts(data.series, {
            parametros: data.parametros,
            bucketTexto: data.bucket_texto,
            total: data.total,
        }, { live });
    } catch (e) {
        console.error('Error loading mediciones:', e);
        if (!live) renderCharts(null, {}, {});
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
        const data = await apiGet(API.tabla, { ...filters, pagina: state.pagina, orden: state.orden });
        renderTabla(data);
    } catch (e) {
        console.error('Error loading tabla:', e);
        renderTablaError();
        throw e;
    }
}

// ── Actualización en tiempo real ────────────────────────
function startLive() {
    stopLive();
    state.liveTimer = setInterval(liveTick, Math.max(3, LIVE_POLL_SECONDS || 10) * 1000);
    state.tickTimer = setInterval(actualizarRelativos, 1000);
}

function stopLive() {
    if (state.liveTimer) clearInterval(state.liveTimer);
    if (state.tickTimer) clearInterval(state.tickTimer);
    state.liveTimer = state.tickTimer = null;
}

async function liveTick() {
    if (document.hidden || state.loading) return;

    const filters = getFilters();
    state.loading = true;
    setLive('updating');

    try {
        const nueva = await loadUltimas(filters, { live: true });

        if (nueva) {
            // Llegó una medición nueva: refrescar gráficas, estadísticas y,
            // si el usuario está en la primera página, la tabla.
            await Promise.allSettled([
                loadMediciones(filters, { live: true }),
                loadEstadisticas(filters),
                state.pagina === 1 ? loadTabla(filters) : Promise.resolve(),
                typeof actualizarMapa === 'function' ? actualizarMapa() : Promise.resolve(),
            ]);
        }

        setConnectionStatus(true);
        hideApiError();
        setLive('ok');
    } catch (e) {
        setConnectionStatus(false);
        setLive('offline');
    } finally {
        state.loading = false;
    }
}

function setLive(mode) {
    state.liveMode = mode;
    actualizarLiveBadge();
}

function actualizarLiveBadge() {
    const badge = document.getElementById('liveBadge');
    const text = document.getElementById('liveText');
    if (!badge || !text) return;

    let mode = state.liveMode;
    let label = 'En vivo';

    if (mode === 'offline') {
        label = 'Sin conexión · reintentando';
    } else if (mode === 'updating') {
        label = 'Actualizando…';
    } else if (state.lastFecha) {
        const d = parseUtc(state.lastFecha);
        const edad = d ? (Date.now() - d.getTime()) / 1000 : 0;
        if (edad > 5 * 60) {
            mode = 'stale';
            label = `Sin datos nuevos · última ${tiempoRelativo(state.lastFecha)}`;
        } else {
            label = `En vivo · última ${tiempoRelativo(state.lastFecha)}`;
        }
    }

    badge.className = 'badge badge-live' + (mode === 'ok' ? '' : ' ' + mode);
    text.textContent = label;
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
document.addEventListener('DOMContentLoaded', async () => {
    if (!document.getElementById('cardsContainer')) return;

    // Llegada desde el mapa u otro enlace: /dashboard?colegio=ID (y opcionalmente &dispositivo=ID)
    const params = new URLSearchParams(window.location.search);
    const colegio = params.get('colegio');
    const selColegio = document.getElementById('filterColegio');
    if (colegio && selColegio) {
        selColegio.value = colegio;
        if (selColegio.value === colegio) {
            await cargarDispositivos(colegio);
            const dispositivo = params.get('dispositivo');
            const selDisp = document.getElementById('filterDispositivo');
            if (dispositivo && selDisp) selDisp.value = dispositivo;
        }
    }

    actualizarRangoBadge();
    loadAllData(true).then(() => startLive());

    // Al volver a la pestaña, consultar de inmediato
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) liveTick();
    });
});
