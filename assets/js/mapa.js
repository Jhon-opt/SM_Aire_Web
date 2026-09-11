/* =============================================================
   Mapa de colegios - MapLibre GL JS + OpenFreeMap
   - Vista predeterminada: Bogotá (MAPA_CENTRO_* en config.php).
   - Un marcador por colegio con el color de su estado del aire (ICA)
     y el valor de PM2.5; popup con los valores y acceso al panel.
   - Se actualiza con el mismo sondeo en vivo del panel (actualizarMapa()).
   - Si OpenFreeMap no responde, se usa el mapa base raster de
     OpenStreetMap (solo como respaldo: su política no admite uso intensivo).
   ============================================================= */
const MAPA_ESTILO = 'https://tiles.openfreemap.org/styles/positron';
const MAPA_LIBRERIA_JS = 'https://unpkg.com/maplibre-gl@5/dist/maplibre-gl.js';
const MAPA_LIBRERIA_CSS = 'https://unpkg.com/maplibre-gl@5/dist/maplibre-gl.css';

// Respaldo: mosaicos raster estándar de OpenStreetMap dentro del mismo MapLibre
const MAPA_ESTILO_RESPALDO = {
    version: 8,
    sources: {
        osm: {
            type: 'raster',
            tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
            tileSize: 256,
            maxzoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        },
    },
    layers: [{ id: 'osm', type: 'raster', source: 'osm' }],
};

const MAPA = {
    map: null,
    listo: false,
    respaldo: false,
    datos: null,
    marcadores: {},   // id_colegio -> { marker, popup, elemento }
    centro: null,
    zoom: 11,
    cargaLibreria: null,
};

// ── Carga diferida de la librería (solo en páginas con mapa) ──
function cargarMapLibre() {
    if (window.maplibregl) return Promise.resolve();
    if (MAPA.cargaLibreria) return MAPA.cargaLibreria;

    MAPA.cargaLibreria = new Promise((resolve, reject) => {
        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = MAPA_LIBRERIA_CSS;
        document.head.appendChild(css);

        const js = document.createElement('script');
        js.src = MAPA_LIBRERIA_JS;
        js.async = true;
        js.onload = () => resolve();
        js.onerror = () => reject(new Error('No se pudo cargar MapLibre'));
        document.head.appendChild(js);
    });

    return MAPA.cargaLibreria;
}

// ── Inicialización ──────────────────────────────────────
async function initMapa() {
    const cont = document.getElementById('mapaContainer');
    if (!cont) return;

    MAPA.centro = [parseFloat(cont.dataset.lng), parseFloat(cont.dataset.lat)];
    MAPA.zoom = parseFloat(cont.dataset.zoom) || 11;

    try {
        await cargarMapLibre();
    } catch (e) {
        mostrarErrorMapa('No se pudo cargar la librería del mapa. Revisa la conexión a internet.');
        return;
    }

    // Descargar el estilo de OpenFreeMap; si no responde (8 s), usar el respaldo
    let estilo = MAPA_ESTILO;
    try {
        const ctrl = new AbortController();
        const t = setTimeout(() => ctrl.abort(), 8000);
        const res = await fetch(MAPA_ESTILO, { signal: ctrl.signal });
        clearTimeout(t);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        estilo = await res.json();
    } catch (e) {
        console.warn('OpenFreeMap no disponible, usando respaldo OSM:', e.message);
        estilo = MAPA_ESTILO_RESPALDO;
        MAPA.respaldo = true;
    }

    MAPA.map = new maplibregl.Map({
        container: cont,
        style: estilo,
        center: MAPA.centro,
        zoom: MAPA.zoom,
        attributionControl: { compact: false },
    });

    MAPA.map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');
    MAPA.map.addControl(new maplibregl.FullscreenControl(), 'top-right');
    MAPA.map.addControl(new maplibregl.ScaleControl({ unit: 'metric' }), 'bottom-left');

    if (MAPA.respaldo) {
        const nota = document.getElementById('mapaNota');
        if (nota) {
            nota.textContent = 'Mapa base alternativo (OpenStreetMap): el servidor de OpenFreeMap no respondió.';
            nota.classList.remove('hidden');
        }
    }

    // Errores de mosaicos sueltos no interrumpen el mapa; solo se registran
    MAPA.map.on('error', (e) => {
        if (e && e.error) console.warn('Mapa:', e.error.message || e.error);
    });

    MAPA.map.on('load', () => {
        MAPA.listo = true;
        const cargando = document.getElementById('mapaCargando');
        if (cargando) cargando.remove();
        if (MAPA.datos) renderMarcadores();
    });

    // Cargar los datos en paralelo con el estilo
    actualizarMapa();
}

function mostrarErrorMapa(texto) {
    const cont = document.getElementById('mapaContainer');
    if (!cont) return;
    cont.innerHTML = `<div class="mapa-cargando"><i class="fas fa-map-location-dot"></i><p>${texto}</p></div>`;
}

// ── Datos y marcadores ──────────────────────────────────
async function actualizarMapa() {
    if (!document.getElementById('mapaContainer')) return;
    try {
        const res = await fetch(APP_BASE_URL + '/api/mapa', { cache: 'no-store' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        MAPA.datos = await res.json();
        if (MAPA.map) renderMarcadores();
    } catch (e) {
        console.error('Error cargando el mapa:', e);
    }
}

function textoPin(colegio) {
    const pm = (colegio.valores || []).find(v => v.parametro === 'pm2_5');
    if (!pm || pm.valor === null || pm.valor === undefined) return '–';
    return typeof formatNum === 'function' ? formatNum(pm.valor) : String(pm.valor);
}

function htmlPopup(c) {
    const est = c.estado;
    const cuando = (typeof tiempoRelativo === 'function' && c.fecha) ? tiempoRelativo(c.fecha) : '';
    const valores = (c.valores || []).map(v => {
        const cat = v.categoria;
        return `<li>
            <span class="mapa-popup-param">${esc(v.nombre)}</span>
            <span class="mapa-popup-valor" style="color:${cat ? cat.texto : '#8A9A90'}">
                <span class="val-dot" style="background:${cat ? cat.color : '#CBDCD1'}"></span>${v.valor === null ? '—' : (typeof formatNum === 'function' ? formatNum(v.valor) : v.valor)} <small>${esc(v.unidad)}</small>
            </span>
        </li>`;
    }).join('');

    return `
        <div class="mapa-popup">
            <div class="mapa-popup-head">
                <strong>${esc(c.nombre)}</strong>
                <small>${esc([c.direccion, c.ciudad].filter(Boolean).join(' · '))}</small>
            </div>
            ${est
                ? `<span class="card-quality" style="--cat-fondo:${est.fondo};--cat-texto:${est.texto};--cat-color:${est.color}"><span class="quality-dot"></span>${esc(est.etiqueta)}</span>`
                : `<span class="card-quality"><span class="quality-dot"></span>Sin lectura reciente</span>`}
            ${valores ? `<ul class="mapa-popup-valores">${valores}</ul>` : ''}
            <div class="mapa-popup-foot">
                <span><i class="fas fa-microchip"></i> ${c.activos} de ${c.dispositivos} sensores activos</span>
                ${c.fecha ? `<span><i class="far fa-clock"></i> ${cuando} · ${esc(c.fecha_local)}</span>` : ''}
            </div>
            <a class="btn btn-primary btn-sm" href="${urlPanelColegio(c.id_colegio)}">
                <i class="fas fa-chart-line"></i> Ver estadísticas del colegio
            </a>
        </div>`;
}

function renderMarcadores() {
    if (!MAPA.map || !MAPA.datos) return;

    const vistos = new Set();
    const sinUbicacion = [];

    (MAPA.datos.colegios || []).forEach(c => {
        if (c.latitud === null || c.longitud === null) {
            sinUbicacion.push(c.nombre);
            return;
        }
        vistos.add(c.id_colegio);

        const est = c.estado;
        const color = est ? est.color : '#9CA3AF';
        const texto = textoPin(c);
        let entrada = MAPA.marcadores[c.id_colegio];

        if (!entrada) {
            const el = document.createElement('div');
            el.className = 'mapa-pin';
            el.innerHTML = '<span class="mapa-pin-valor"></span><span class="mapa-pin-etq">PM2.5</span>';
            el.title = c.nombre;

            const popup = new maplibregl.Popup({ offset: 30, maxWidth: '320px', closeButton: true });
            const marker = new maplibregl.Marker({ element: el, anchor: 'bottom' })
                .setLngLat([c.longitud, c.latitud])
                .setPopup(popup)
                .addTo(MAPA.map);

            entrada = { marker, popup, el };
            MAPA.marcadores[c.id_colegio] = entrada;
        } else {
            entrada.marker.setLngLat([c.longitud, c.latitud]);
        }

        entrada.el.style.setProperty('--pin-color', color);
        entrada.el.querySelector('.mapa-pin-valor').textContent = texto;
        entrada.el.classList.toggle('mapa-pin-sin-datos', !est);
        entrada.popup.setHTML(htmlPopup(c));
    });

    // Quitar marcadores de colegios que ya no existen
    Object.keys(MAPA.marcadores).forEach(id => {
        if (!vistos.has(Number(id))) {
            MAPA.marcadores[id].marker.remove();
            delete MAPA.marcadores[id];
        }
    });

    renderListaColegios();

    const nota = document.getElementById('mapaNota');
    if (nota && !MAPA.respaldo) {
        if (sinUbicacion.length) {
            nota.textContent = `Sin ubicación en el mapa (faltan coordenadas en la base de datos): ${sinUbicacion.join(', ')}.`;
            nota.classList.remove('hidden');
        } else {
            nota.classList.add('hidden');
        }
    }
}

// Lista lateral de la página del mapa
function renderListaColegios() {
    const lista = document.getElementById('mapaLista');
    if (!lista || !MAPA.datos) return;

    const colegios = MAPA.datos.colegios || [];
    if (colegios.length === 0) {
        lista.innerHTML = '<li class="mapa-lista-vacia">No hay colegios registrados.</li>';
        return;
    }

    lista.innerHTML = colegios.map(c => {
        const est = c.estado;
        const conUbicacion = c.latitud !== null && c.longitud !== null;
        const cuando = (typeof tiempoRelativo === 'function' && c.fecha) ? tiempoRelativo(c.fecha) : 'sin lecturas';
        return `
            <li class="mapa-item ${conUbicacion ? '' : 'mapa-item-sin-ubicacion'}" data-id="${c.id_colegio}">
                <button type="button" class="mapa-item-main" onclick="enfocarColegio(${c.id_colegio})" ${conUbicacion ? '' : 'disabled'}>
                    <span class="mapa-item-pin" style="--pin-color:${est ? est.color : '#9CA3AF'}">${textoPin(c)}</span>
                    <span class="mapa-item-text">
                        <strong>${esc(c.nombre)}</strong>
                        <small>${est ? esc(est.etiqueta) : 'Sin lectura reciente'} · ${cuando}${conUbicacion ? '' : ' · sin ubicación'}</small>
                    </span>
                </button>
                <a class="mapa-item-link" href="${urlPanelColegio(c.id_colegio)}" title="Ver estadísticas del colegio">
                    <i class="fas fa-chart-line"></i>
                </a>
            </li>`;
    }).join('');
}

function urlPanelColegio(idColegio) {
    return APP_BASE_URL + '/dashboard?colegio=' + encodeURIComponent(idColegio);
}

// ── Acciones ────────────────────────────────────────────
function centrarMapa() {
    if (!MAPA.map) return;
    MAPA.map.flyTo({ center: MAPA.centro, zoom: MAPA.zoom, essential: true });
}

function ajustarMapaColegios() {
    if (!MAPA.map) return;
    const puntos = Object.values(MAPA.marcadores).map(m => m.marker.getLngLat());
    if (puntos.length === 0) return;
    if (puntos.length === 1) {
        MAPA.map.flyTo({ center: puntos[0], zoom: 14, essential: true });
        return;
    }
    const bounds = puntos.reduce((b, p) => b.extend(p), new maplibregl.LngLatBounds(puntos[0], puntos[0]));
    MAPA.map.fitBounds(bounds, { padding: 70, maxZoom: 14, duration: 800 });
}

// Al filtrar por colegio en el panel, centrar el mapa en él (o volver a Bogotá)
function resaltarColegio(idColegio) {
    if (!MAPA.map) return;
    Object.values(MAPA.marcadores).forEach(m => m.el.classList.remove('mapa-pin-activo'));
    const entrada = MAPA.marcadores[idColegio];
    if (!entrada) {
        if (!idColegio) centrarMapa();
        return;
    }
    entrada.el.classList.add('mapa-pin-activo');
    MAPA.map.flyTo({ center: entrada.marker.getLngLat(), zoom: 14, essential: true });
}

function enfocarColegio(idColegio) {
    const entrada = MAPA.marcadores[idColegio];
    if (!entrada || !MAPA.map) return;
    resaltarColegio(idColegio);
    document.querySelectorAll('.mapa-item').forEach(li => li.classList.toggle('activo', Number(li.dataset.id) === Number(idColegio)));
    MAPA.map.once('moveend', () => {
        if (!entrada.popup.isOpen()) entrada.marker.togglePopup();
    });
    const cont = document.getElementById('mapaContainer');
    if (cont && window.innerWidth <= 900) cont.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Abre el panel de monitoreo filtrado por ese colegio
function verColegioEnPanel(idColegio) {
    window.location.href = urlPanelColegio(idColegio);
}

document.addEventListener('DOMContentLoaded', () => {
    initMapa();

    // En la página del mapa (sin panel) el mapa se refresca por su cuenta
    if (document.getElementById('mapaContainer') && !document.getElementById('cardsContainer')) {
        setInterval(() => { if (!document.hidden) actualizarMapa(); }, Math.max(15, (LIVE_POLL_SECONDS || 10) * 3) * 1000);
    }
});
