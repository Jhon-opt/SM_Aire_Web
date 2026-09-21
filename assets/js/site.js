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

// ── Ventanas emergentes con los documentos (LIASP-CB / SIMCA) ──
function abrirModal(id) {
    const m = document.getElementById(id);
    if (!m) {
        // La ventana vive en la otra página: ir al portal o al panel y abrirla allí
        const destino = id === 'modal-liasp' ? APP_BASE_URL + '/' : APP_BASE_URL + '/dashboard';
        window.location.href = destino + '?abrir=' + encodeURIComponent(id);
        return;
    }
    m.classList.remove('hidden');
    document.body.classList.add('modal-open');
    const cuerpo = m.querySelector('.doc-body');
    if (cuerpo) {
        cuerpo.scrollTop = 0;
        marcarSeccionVisible(m);
        if (!cuerpo.dataset.scrollspy) {
            cuerpo.dataset.scrollspy = '1';
            cuerpo.addEventListener('scroll', () => marcarSeccionVisible(m), { passive: true });
        }
    }
}

// Resalta en la barra de secciones la que está visible en el cuerpo de la ventana
function marcarSeccionVisible(modal) {
    const cuerpo = modal.querySelector('.doc-body');
    const secciones = [...modal.querySelectorAll('.doc-seccion')];
    const chips = [...modal.querySelectorAll('.doc-nav-item')];
    if (!cuerpo || !secciones.length || !chips.length) return;
    const limite = cuerpo.scrollTop + 80;
    let indice = 0;
    secciones.forEach((sec, i) => {
        if (i < chips.length && sec.offsetTop - cuerpo.offsetTop <= limite) indice = i;
    });
    chips.forEach((c, i) => c.classList.toggle('activo', i === indice));
}

function cerrarModal(id) {
    const m = id ? document.getElementById(id) : document.querySelector('.info-modal-overlay:not(.hidden)');
    if (m) m.classList.add('hidden');
    if (!document.querySelector('.info-modal-overlay:not(.hidden)')) {
        document.body.classList.remove('modal-open');
    }
}

// Desplaza el cuerpo de la ventana hasta la sección elegida y marca el botón activo
function irASeccionModal(idModal, idSeccion, boton) {
    const modal = document.getElementById(idModal);
    const seccion = document.getElementById(idSeccion);
    if (!modal || !seccion) return;
    const cuerpo = modal.querySelector('.doc-body');
    cuerpo.scrollTo({ top: seccion.offsetTop - cuerpo.offsetTop, behavior: 'smooth' });
    modal.querySelectorAll('.doc-nav-item').forEach(b => b.classList.toggle('activo', b === boton));
}

// Compatibilidad con llamadas anteriores
function abrirInfoSimca() { abrirModal('modal-simca'); }
function cerrarInfoSimca() { cerrarModal(); }

// Tarjetas de líneas de trabajo: mostrar / ocultar el texto completo
function toggleLinea(boton) {
    const card = boton.closest('.linea-card');
    if (!card) return;
    const abierta = card.classList.toggle('abierta');
    boton.innerHTML = abierta
        ? '<i class="fas fa-chevron-up"></i> Leer menos'
        : '<i class="fas fa-chevron-down"></i> Leer más';
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
    if (e.key === 'Escape') cerrarModal();
});

// ?abrir=modal-xxx en la URL: abrir esa ventana al cargar (enlaces desde la otra página)
document.addEventListener('DOMContentLoaded', () => {
    const abrir = new URLSearchParams(window.location.search).get('abrir');
    if (abrir && document.getElementById(abrir)) abrirModal(abrir);
});

// ── Carrusel en bucle (líneas de trabajo) ─────────────────
// Se clonan las primeras y últimas tarjetas en los extremos de la pista; al
// llegar a un clon se salta sin animación a la tarjeta real equivalente, de
// modo que el recorrido es infinito en ambos sentidos. Avanza solo cada
// CARRUSEL_AUTO_MS y se pausa mientras el usuario está sobre él o lo toca.
const CARRUSEL_AUTO_MS = 8000;
const CARRUSEL_CLONES = 3;   // tarjetas clonadas en cada extremo (máximo visible a la vez)

function carruselPartes(id) {
    const c = typeof id === 'string' ? document.getElementById(id) : id;
    if (!c || !c._carrusel) return null;
    return c._carrusel;
}

// Ancho de una tarjeta más el espacio entre tarjetas
function carruselPaso(p) {
    const estilo = getComputedStyle(p.track);
    const gap = parseFloat(estilo.columnGap || estilo.gap) || 0;
    return p.todas[0].getBoundingClientRect().width + gap;
}

// Índice dentro de la pista completa (incluye clones)
function carruselIndicePista(p) {
    const paso = carruselPaso(p);
    return paso ? Math.round(p.track.scrollLeft / paso) : 0;
}

// Índice de la tarjeta real (0 .. n-1)
function carruselIndice(p) {
    return ((carruselIndicePista(p) - CARRUSEL_CLONES) % p.n + p.n) % p.n;
}

function carruselSaltar(p, indicePista) {
    p.track.style.scrollBehavior = 'auto';
    p.track.scrollLeft = Math.round(indicePista * carruselPaso(p));
    p.track.style.scrollBehavior = '';
}

// Ir a una tarjeta real con animación (dir indica el sentido cuando se usa el bucle)
function irACarrusel(id, i, manual = true) {
    const p = carruselPartes(id);
    if (!p) return;
    const destino = CARRUSEL_CLONES + ((i % p.n) + p.n) % p.n;
    p.track.scrollTo({ left: Math.round(destino * carruselPaso(p)), behavior: 'smooth' });
    if (manual) carruselProgramar(p);
}

function moverCarrusel(id, dir, manual = true) {
    const p = carruselPartes(id);
    if (!p) return;
    // Se desplaza una posición sobre la pista completa; si cae en un clon, se
    // corrige al terminar el desplazamiento (ver carruselAlDetenerse).
    const destino = carruselIndicePista(p) + dir;
    p.track.scrollTo({ left: Math.round(destino * carruselPaso(p)), behavior: 'smooth' });
    if (manual) carruselProgramar(p);
}

// Al detenerse el desplazamiento: si estamos sobre un clon, saltar a la tarjeta real
function carruselAlDetenerse(p) {
    const idx = carruselIndicePista(p);
    if (idx < CARRUSEL_CLONES) carruselSaltar(p, idx + p.n);
    else if (idx >= CARRUSEL_CLONES + p.n) carruselSaltar(p, idx - p.n);
    actualizarCarrusel(p);
}

function actualizarCarrusel(p) {
    const i = carruselIndice(p);
    const dots = [...p.c.querySelectorAll('.carrusel-dot')];
    dots.forEach((d, k) => d.classList.toggle('activo', k === i));
}

function construirDotsCarrusel(p) {
    const cont = p.c.querySelector('.carrusel-dots');
    if (!cont) return;
    cont.innerHTML = '';
    for (let k = 0; k < p.n; k++) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'carrusel-dot';
        b.setAttribute('aria-label', 'Ir a la línea ' + (k + 1));
        b.addEventListener('click', () => irACarrusel(p.c, k));
        cont.appendChild(b);
    }
}

// Reinicia el temporizador del avance automático (tras una acción del usuario)
function carruselProgramar(p) {
    if (p.timer) clearInterval(p.timer);
    p.timer = setInterval(() => {
        if (p.pausado || document.hidden || p.c.matches(':hover')) return;
        moverCarrusel(p.c, 1, false);
    }, CARRUSEL_AUTO_MS);
}

function initCarruseles() {
    document.querySelectorAll('.carrusel').forEach(c => {
        const track = c.querySelector('.carrusel-track');
        const reales = [...c.querySelectorAll('.carrusel-item')];
        if (!track || reales.length < 2) return;

        // Clones: las últimas al principio y las primeras al final
        const k = Math.min(CARRUSEL_CLONES, reales.length);
        const clonar = (el) => {
            const cl = el.cloneNode(true);
            cl.classList.add('carrusel-clon');
            cl.setAttribute('aria-hidden', 'true');
            cl.querySelectorAll('button, a').forEach(b => b.setAttribute('tabindex', '-1'));
            return cl;
        };
        reales.slice(-k).forEach(el => track.insertBefore(clonar(el), track.firstChild));
        reales.slice(0, k).forEach(el => track.appendChild(clonar(el)));

        const p = {
            c, track, reales,
            n: reales.length,
            todas: [...track.querySelectorAll('.carrusel-item')],
            timer: null,
            pausado: false,
            detener: null,
        };
        c._carrusel = p;

        construirDotsCarrusel(p);
        carruselSaltar(p, CARRUSEL_CLONES);   // empezar en la primera tarjeta real
        actualizarCarrusel(p);

        // Fin de desplazamiento: unos ms sin eventos de scroll
        track.addEventListener('scroll', () => {
            actualizarCarrusel(p);
            clearTimeout(p.detener);
            p.detener = setTimeout(() => carruselAlDetenerse(p), 120);
        }, { passive: true });

        // Al cambiar el ancho, mantener la tarjeta actual alineada
        window.addEventListener('resize', () => {
            const i = carruselIndice(p);
            carruselSaltar(p, CARRUSEL_CLONES + i);
            actualizarCarrusel(p);
        });

        // Teclado
        track.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') { e.preventDefault(); moverCarrusel(c, 1); }
            if (e.key === 'ArrowLeft')  { e.preventDefault(); moverCarrusel(c, -1); }
        });

        // Pausar mientras se toca o arrastra; al soltar, el avance continúa
        ['pointerdown', 'touchstart'].forEach(ev => c.addEventListener(ev, () => { p.pausado = true; }, { passive: true }));
        ['pointerup', 'pointercancel', 'touchend', 'touchcancel', 'mouseleave'].forEach(ev =>
            c.addEventListener(ev, () => { p.pausado = false; carruselProgramar(p); }, { passive: true })
        );

        carruselProgramar(p);
    });
}

document.addEventListener('DOMContentLoaded', initCarruseles);

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
