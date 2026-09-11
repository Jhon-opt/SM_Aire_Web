/* =============================================================
   Portal LIASP-CB / SIMCA - comportamiento compartido
   - Menú superior (móvil y desplegables)
   - Acciones del menú del SIMCA (información, colegios, consolidado)
   - Tarjeta SIMCA del portal: estado del aire en vivo
   ============================================================= */

function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// ── Menú superior ───────────────────────────────────────
function toggleNav() {
    const nav = document.getElementById('siteNav');
    const btn = document.getElementById('navToggle');
    if (!nav) return;
    const abierto = nav.classList.toggle('open');
    if (btn) btn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
}

// Primer clic/toque abre el desplegable; el segundo navega al enlace del padre.
function toggleDropdown(event, id) {
    const dd = document.getElementById(id);
    if (!dd) return true;
    const esTactil = window.matchMedia('(hover: none)').matches || window.innerWidth <= 900;
    if (esTactil && !dd.classList.contains('open')) {
        event.preventDefault();
        document.querySelectorAll('.nav-dropdown.open').forEach(o => { if (o !== dd) o.classList.remove('open'); });
        dd.classList.add('open');
        return false;
    }
    return true;
}

document.addEventListener('click', (e) => {
    // Cerrar desplegables al hacer clic fuera
    if (!e.target.closest('.nav-dropdown')) {
        document.querySelectorAll('.nav-dropdown.open').forEach(o => o.classList.remove('open'));
    }
    // Cerrar el menú móvil al elegir una opción
    const nav = document.getElementById('siteNav');
    if (nav && nav.classList.contains('open') && e.target.closest('#siteNav a, #siteNav button') && !e.target.closest('.nav-dropdown > a')) {
        nav.classList.remove('open');
    }
});

// ── Acciones del menú SIMCA ─────────────────────────────
function abrirInfoSimca() {
    const m = document.getElementById('infoSimcaModal');
    if (!m) { window.location.href = APP_BASE_URL + '/#proyectos'; return; }
    m.classList.remove('hidden');
    document.body.classList.add('modal-open');
}

function cerrarInfoSimca() {
    const m = document.getElementById('infoSimcaModal');
    if (m) m.classList.add('hidden');
    document.body.classList.remove('modal-open');
}

function irAColegios() {
    const sec = document.getElementById('filtros');
    if (!sec) { window.location.href = APP_BASE_URL + '/dashboard#filtros'; return; }
    sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
    const sel = document.getElementById('filterColegio');
    if (sel) setTimeout(() => sel.focus(), 400);
}

function verConsolidado() {
    const sec = document.getElementById('estado');
    if (!sec) { window.location.href = APP_BASE_URL + '/dashboard'; return; }
    if (typeof resetFilters === 'function') {
        const colegio = document.getElementById('filterColegio');
        if (colegio && colegio.value !== '') resetFilters();
    }
    sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') cerrarInfoSimca();
});

// ── Portal: estado del aire en la tarjeta SIMCA ─────────
async function actualizarEstadoPortal() {
    const el = document.getElementById('simcaEstado');
    if (!el) return;

    try {
        const res = await fetch(APP_BASE_URL + '/api/ultimas', { cache: 'no-store' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const est = data.estado_general;

        if (!est) {
            el.innerHTML = `
                <span class="simca-estado-icon"><i class="fas fa-satellite-dish"></i></span>
                <span class="simca-estado-text"><strong>Sin lecturas recientes</strong><small>La red de sensores no ha reportado datos</small></span>`;
            return;
        }

        const cuando = (typeof tiempoRelativo === 'function' && data.fecha) ? tiempoRelativo(data.fecha) : '';
        el.style.setProperty('--cat-color', est.color);
        el.style.setProperty('--cat-fondo', est.fondo);
        el.style.setProperty('--cat-texto', est.texto);
        el.innerHTML = `
            <span class="simca-estado-icon"><i class="fas ${est.icono}"></i></span>
            <span class="simca-estado-text">
                <strong>Calidad del aire: ${esc(est.etiqueta)}</strong>
                <small>Determinada por ${esc(est.parametro)}${cuando ? ' · ' + cuando : ''}${data.origen && data.origen.colegio ? ' · ' + esc(data.origen.colegio) : ''}</small>
            </span>`;
    } catch (e) {
        el.innerHTML = `
            <span class="simca-estado-icon"><i class="fas fa-plug-circle-xmark"></i></span>
            <span class="simca-estado-text"><strong>Estado no disponible</strong><small>No se pudo consultar el servidor de datos</small></span>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('simcaEstado')) {
        actualizarEstadoPortal();
        setInterval(actualizarEstadoPortal, 60 * 1000);
    }
});
