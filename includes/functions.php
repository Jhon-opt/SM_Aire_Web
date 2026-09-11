<?php

function sanitize(mixed $value, string $type = 'string'): mixed
{
    if ($value === null || $value === '') {
        return null;
    }

    return match ($type) {
        'int'    => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
        'float'  => filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
        'string' => htmlspecialchars(strip_tags(trim((string) $value)), ENT_QUOTES, 'UTF-8'),
        default => htmlspecialchars(strip_tags(trim((string) $value)), ENT_QUOTES, 'UTF-8'),
    };
}

function jsonResponse(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// =============================================================
// Parámetros medidos
// =============================================================

/** Parámetros principales: se muestran siempre (los que miden los sensores actuales). */
function parametrosPrincipales(): array
{
    return ['pm2_5', 'pm10', 'co'];
}

/** Parámetros opcionales: solo se muestran cuando existen datos. */
function parametrosOpcionales(): array
{
    return ['co2', 'o3', 'no2', 'temperatura', 'humedad'];
}

function parametrosTodos(): array
{
    return array_merge(parametrosPrincipales(), parametrosOpcionales());
}

function tieneValor(mixed $v): bool
{
    return $v !== null && $v !== '';
}

/**
 * Parámetros a mostrar para un conjunto de filas: los principales siempre,
 * más los opcionales que tengan al menos un valor no nulo.
 */
function parametrosVisibles(array $filas): array
{
    $visibles = parametrosPrincipales();

    foreach (parametrosOpcionales() as $p) {
        foreach ($filas as $f) {
            if (tieneValor($f[$p] ?? null)) {
                $visibles[] = $p;
                break;
            }
        }
    }

    return $visibles;
}

function getParametroUnidad(string $parametro): string
{
    $unidades = [
        'pm2_5'  => 'µg/m³',
        'pm10'   => 'µg/m³',
        'co'     => 'ppm',
        'co2'    => 'ppm',
        'o3'     => 'ppb',
        'no2'    => 'ppb',
        'temperatura' => '°C',
        'humedad'     => '%',
    ];

    return $unidades[$parametro] ?? '';
}

function getParametroNombre(string $parametro): string
{
    $nombres = [
        'pm2_5'  => 'PM2.5',
        'pm10'   => 'PM10',
        'co'     => 'CO',
        'co2'    => 'CO2',
        'o3'     => 'O₃',
        'no2'    => 'NO₂',
        'temperatura' => 'Temperatura',
        'humedad'     => 'Humedad',
    ];

    return $nombres[$parametro] ?? $parametro;
}

function getParametroDescripcion(string $parametro): string
{
    $desc = [
        'pm2_5'  => 'Partículas finas menores a 2.5 µm',
        'pm10'   => 'Partículas menores a 10 µm',
        'co'     => 'Monóxido de carbono',
        'co2'    => 'Dióxido de carbono',
        'o3'     => 'Ozono',
        'no2'    => 'Dióxido de nitrógeno',
        'temperatura' => 'Temperatura ambiente',
        'humedad'     => 'Humedad relativa',
    ];

    return $desc[$parametro] ?? '';
}

// =============================================================
// Índice de Calidad del Aire (ICA) - Colombia, Resolución 2254 de 2017
// =============================================================

/** Las 6 categorías del ICA, en orden de severidad. */
function icaCategorias(): array
{
    return [
        ['clave' => 'buena',      'etiqueta' => 'Buena',                        'color' => '#16A34A', 'texto' => '#15803D', 'fondo' => '#DCFCE7', 'icono' => 'fa-face-smile'],
        ['clave' => 'aceptable',  'etiqueta' => 'Aceptable',                    'color' => '#EAB308', 'texto' => '#A16207', 'fondo' => '#FEF9C3', 'icono' => 'fa-face-meh'],
        ['clave' => 'sensibles',  'etiqueta' => 'Dañina para grupos sensibles', 'color' => '#F97316', 'texto' => '#C2410C', 'fondo' => '#FFEDD5', 'icono' => 'fa-face-frown'],
        ['clave' => 'danina',     'etiqueta' => 'Dañina a la salud',            'color' => '#EF4444', 'texto' => '#B91C1C', 'fondo' => '#FEE2E2', 'icono' => 'fa-face-frown-open'],
        ['clave' => 'muy_danina', 'etiqueta' => 'Muy dañina a la salud',        'color' => '#8B5CF6', 'texto' => '#6D28D9', 'fondo' => '#EDE9FE', 'icono' => 'fa-face-dizzy'],
        ['clave' => 'peligrosa',  'etiqueta' => 'Peligrosa',                    'color' => '#881337', 'texto' => '#881337', 'fondo' => '#FFE4E6', 'icono' => 'fa-skull'],
    ];
}

/**
 * Límite superior de cada categoría (mismo orden que icaCategorias()).
 * PM2.5, PM10, CO: Res. 2254/2017. O3 y NO2: puntos de corte ICA (ppb).
 * CO2 no tiene ICA; se usan referencias de calidad de aire interior (ppm).
 */
function icaLimites(string $parametro): ?array
{
    return match ($parametro) {
        'pm2_5' => [12, 37, 55, 150, 250, 500],
        'pm10'  => [54, 154, 254, 354, 424, 604],
        'co'    => [4.4, 9.4, 12.4, 15.4, 30.4, 50.4],
        'o3'    => [54, 70, 85, 105, 200, 404],
        'no2'   => [53, 100, 360, 649, 1249, 2049],
        'co2'   => [800, 1200, 2000, 5000, 10000, 40000],
        default => null,
    };
}

function icaMensaje(int $indice): string
{
    $mensajes = [
        'La calidad del aire es satisfactoria y no representa riesgo para la salud.',
        'Calidad del aire aceptable. Las personas muy sensibles podrían presentar molestias leves.',
        'Niños, adultos mayores y personas con enfermedades respiratorias o cardíacas deben reducir la actividad física intensa al aire libre.',
        'Toda la población puede presentar efectos en la salud. Evite esfuerzos prolongados al aire libre.',
        'Alerta sanitaria: toda la población puede experimentar efectos graves. Evite actividades al aire libre.',
        'Emergencia: condiciones peligrosas para toda la población. Permanezca en espacios cerrados.',
    ];

    return $mensajes[max(0, min(5, $indice))];
}

/**
 * Categoría de un valor: índice 0-5, etiqueta, colores y posición (0-100)
 * dentro de la escala de 6 segmentos para dibujar el marcador.
 */
function icaCategoria(string $parametro, ?float $valor): ?array
{
    if ($valor === null) {
        return null;
    }

    $categorias = icaCategorias();

    // Temperatura y humedad no forman parte del ICA: categorías de confort.
    if ($parametro === 'temperatura' || $parametro === 'humedad') {
        [$indice, $fraccion] = $parametro === 'temperatura'
            ? categoriaConfort($valor, 18, 26, 12, 32)
            : categoriaConfort($valor, 30, 60, 20, 80);
        $etiquetas = ['Confortable', 'Aceptable', 'Extrema'];
        $cat = $categorias[$indice];
        $cat['etiqueta'] = $etiquetas[$indice];
        $cat['indice'] = $indice;
        $cat['posicion'] = round(($indice + $fraccion) / 3 * 100, 1);
        $cat['segmentos'] = 3;
        return $cat;
    }

    $limites = icaLimites($parametro);
    if ($limites === null) {
        $cat = $categorias[0];
        $cat['indice'] = 0;
        $cat['posicion'] = 0;
        $cat['segmentos'] = 6;
        return $cat;
    }

    $indice = count($limites) - 1;
    $inferior = 0.0;
    foreach ($limites as $i => $superior) {
        if ($valor <= $superior) {
            $indice = $i;
            break;
        }
        $inferior = $superior;
    }

    $superior = $limites[$indice];
    $fraccion = $superior > $inferior ? ($valor - $inferior) / ($superior - $inferior) : 1;
    $fraccion = max(0, min(1, $fraccion));

    $cat = $categorias[$indice];
    $cat['indice'] = $indice;
    $cat['posicion'] = round(($indice + $fraccion) / count($limites) * 100, 1);
    $cat['segmentos'] = count($limites);

    return $cat;
}

/** [indice 0-2, fracción 0-1] para rangos de confort: ideal [a,b], aceptable [c,d]. */
function categoriaConfort(float $v, float $a, float $b, float $c, float $d): array
{
    if ($v >= $a && $v <= $b) {
        return [0, ($v - $a) / max(0.001, $b - $a)];
    }
    if ($v >= $c && $v <= $d) {
        $f = $v < $a ? ($a - $v) / max(0.001, $a - $c) : ($v - $b) / max(0.001, $d - $b);
        return [1, $f];
    }
    $f = $v < $c ? min(1, ($c - $v) / max(1, $c)) : min(1, ($v - $d) / max(1, $d));
    return [2, $f];
}

/**
 * Estado general del aire a partir de las tarjetas de la última lectura:
 * la peor categoría entre los parámetros principales con dato.
 */
function estadoGeneral(array $tarjetas): ?array
{
    $peor = null;

    foreach ($tarjetas as $t) {
        if (!in_array($t['parametro'], parametrosPrincipales(), true) || empty($t['categoria'])) {
            continue;
        }
        if ($peor === null || $t['categoria']['indice'] > $peor['categoria']['indice']) {
            $peor = $t;
        }
    }

    if ($peor === null) {
        return null;
    }

    $cat = $peor['categoria'];

    return [
        'indice'    => $cat['indice'],
        'clave'     => $cat['clave'],
        'etiqueta'  => $cat['etiqueta'],
        'color'     => $cat['color'],
        'texto'     => $cat['texto'],
        'fondo'     => $cat['fondo'],
        'icono'     => $cat['icono'],
        'parametro' => $peor['nombre'],
        'mensaje'   => icaMensaje($cat['indice']),
    ];
}

// =============================================================
// Fechas y zona horaria
// =============================================================

function tzLocal(): DateTimeZone
{
    static $tz = null;
    return $tz ??= new DateTimeZone(APP_TIMEZONE);
}

function tzUtc(): DateTimeZone
{
    static $tz = null;
    return $tz ??= new DateTimeZone('UTC');
}

/** Convierte una fecha_hora UTC de la base de datos a hora local (APP_TIMEZONE). */
function utcToLocal(?string $fechaUtc): ?DateTime
{
    if (!$fechaUtc) {
        return null;
    }

    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $fechaUtc, tzUtc());
    if (!$dt) {
        return null;
    }

    return $dt->setTimezone(tzLocal());
}

/**
 * Formatea para mostrar. Las fechas con hora se interpretan como UTC y se
 * convierten a hora local; las fechas sin hora (Y-m-d) se muestran tal cual.
 */
function formatDate(?string $date, string $format = 'd/m/Y H:i'): string
{
    if (!$date) return '—';

    $local = utcToLocal($date);
    if ($local) {
        return $local->format($format);
    }

    $soloFecha = DateTime::createFromFormat('Y-m-d', $date, tzLocal());
    return $soloFecha ? $soloFecha->format($format) : $date;
}

function ahoraLocal(string $format = 'd/m/Y H:i'): string
{
    return (new DateTime('now', tzLocal()))->format($format);
}

function relativeTime(?string $fechaUtc): string
{
    $dt = $fechaUtc ? DateTime::createFromFormat('Y-m-d H:i:s', $fechaUtc, tzUtc()) : null;
    if (!$dt) return '—';

    $diff = time() - $dt->getTimestamp();

    if ($diff < 60) return 'hace ' . max(0, $diff) . ' segundos';
    if ($diff < 3600) return 'hace ' . floor($diff / 60) . ' minutos';
    if ($diff < 86400) return 'hace ' . floor($diff / 3600) . ' horas';
    return 'hace ' . floor($diff / 86400) . ' días';
}

function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/') . '?v=' . APP_VERSION;
}

/**
 * Horas que cubre un intervalo predefinido.
 * null = sin límite inferior ("Todo el historial").
 */
function intervaloHoras(string $intervalo): ?int
{
    return match ($intervalo) {
        '24h' => 24,
        '7d'  => 24 * 7,
        '30d' => 24 * 30,
        default => null,
    };
}

/**
 * Condición SQL para un intervalo predefinido (rango relativo al momento actual).
 * Se usa UTC_TIMESTAMP() porque fecha_hora se guarda en UTC, sin depender de
 * la zona horaria configurada en MySQL.
 * Devuelve '' cuando no hay límite (todo el historial).
 */
function condicionIntervalo(string $columna, string $intervalo): string
{
    $horas = intervaloHoras($intervalo);
    if ($horas === null) {
        return '';
    }
    return " AND {$columna} >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL {$horas} HOUR)";
}

/**
 * Normaliza un rango personalizado (inputs date YYYY-MM-DD, en hora local) a
 * día completo y lo convierte a UTC para consultar la base de datos.
 * Devuelve [inicio, fin] en 'Y-m-d H:i:s' UTC, o [null, null] si es inválido.
 */
function normalizarRangoFechas(?string $inicio, ?string $fin): array
{
    $ok = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v);
    if (!$ok($inicio) || !$ok($fin)) {
        return [null, null];
    }
    if ($inicio > $fin) {
        [$inicio, $fin] = [$fin, $inicio];
    }

    $ini = DateTime::createFromFormat('Y-m-d H:i:s', $inicio . ' 00:00:00', tzLocal());
    $ff  = DateTime::createFromFormat('Y-m-d H:i:s', $fin . ' 23:59:59', tzLocal());
    if (!$ini || !$ff) {
        return [null, null];
    }

    return [
        $ini->setTimezone(tzUtc())->format('Y-m-d H:i:s'),
        $ff->setTimezone(tzUtc())->format('Y-m-d H:i:s'),
    ];
}

// =============================================================
// Agregación de series para las gráficas
// =============================================================

/**
 * Tamaño de bucket (segundos) para que una serie que cubre $spanSeg segundos
 * tenga como máximo ~$maxPuntos puntos.
 */
function bucketSegundos(int $spanSeg, int $maxPuntos = 400): int
{
    foreach ([60, 120, 300, 600, 900, 1800, 3600, 7200, 10800, 21600, 43200, 86400] as $b) {
        if ($spanSeg / $b <= $maxPuntos) {
            return $b;
        }
    }
    return 86400;
}

function textoBucket(int $bucket): string
{
    if ($bucket <= 60) return 'una lectura por minuto';
    if ($bucket < 3600) return 'promedio cada ' . intdiv($bucket, 60) . ' min';
    if ($bucket === 3600) return 'promedio por hora';
    if ($bucket < 86400) return 'promedio cada ' . intdiv($bucket, 3600) . ' h';
    return 'promedio diario';
}

/**
 * Agrupa filas (ordenadas por fecha_hora UTC) en buckets y promedia cada
 * parámetro. Devuelve [param => ['labels' => [...], 'values' => [...]]] con
 * etiquetas en hora local.
 */
function agregarSeries(array $filas, array $parametros, int $bucket, string $formatoEtiqueta): array
{
    $acum = [];

    foreach ($filas as $f) {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $f['fecha_hora'] ?? '', tzUtc());
        if (!$dt) continue;

        $b = intdiv($dt->getTimestamp(), $bucket) * $bucket;

        foreach ($parametros as $p) {
            if (!tieneValor($f[$p] ?? null)) continue;
            $acum[$b][$p][0] = ($acum[$b][$p][0] ?? 0) + (float) $f[$p];
            $acum[$b][$p][1] = ($acum[$b][$p][1] ?? 0) + 1;
        }
    }

    ksort($acum);

    $series = [];
    foreach ($parametros as $p) {
        $series[$p] = ['labels' => [], 'values' => []];
    }

    foreach ($acum as $b => $porParam) {
        $label = (new DateTime('@' . $b))->setTimezone(tzLocal())->format($formatoEtiqueta);
        foreach ($porParam as $p => [$suma, $n]) {
            $series[$p]['labels'][] = $label;
            $series[$p]['values'][] = round($suma / $n, 2);
        }
    }

    return $series;
}

// =============================================================
// Portal: sitios y menús (estructura del portal LIASP-CB / SIMCA)
// =============================================================

/** Datos de cabecera de cada sitio: sigla, nombre completo y página de inicio. */
function sitioInfo(string $sitio): array
{
    if ($sitio === 'simca') {
        return [
            'sigla'  => 'SIMCA',
            'nombre' => 'Sistema Inteligente de Monitoreo de Calidad del Aire basado en sensores de bajo costo',
            'inicio' => BASE_URL . '/dashboard',
        ];
    }

    return [
        'sigla'  => 'LIASP-CB',
        'nombre' => 'Laboratorio Urbano de Inteligencia Ambiental y Salud Pública',
        'inicio' => BASE_URL . '/',
    ];
}

/**
 * Menú principal de cada sitio, según la estructura del portal.
 * tipo: 'link' (href), 'dropdown' (hijos), 'accion' (onclick JS) o 'pronto' (aún no disponible).
 */
function menuSitio(string $sitio): array
{
    $base = BASE_URL;

    if ($sitio === 'simca') {
        return [
            ['clave' => 'volver',      'texto' => 'Volver al portal',  'icono' => 'fa-arrow-left',      'href' => $base . '/', 'solo_icono' => true, 'titulo' => 'Volver al portal LIASP-CB'],
            ['clave' => 'informacion', 'texto' => 'Información SIMCA', 'icono' => 'fa-circle-info',     'tipo' => 'accion', 'onclick' => 'abrirInfoSimca()'],
            ['clave' => 'colegios',    'texto' => 'Colegios',          'icono' => 'fa-school',          'tipo' => 'accion', 'onclick' => 'irAColegios()'],
            ['clave' => 'consolidado', 'texto' => 'Consolidado',       'icono' => 'fa-layer-group',     'tipo' => 'accion', 'onclick' => 'verConsolidado()'],
            ['clave' => 'mapa',        'texto' => 'Mapa',              'icono' => 'fa-map-location-dot','href' => $base . '/mapa'],
            ['clave' => 'exportar',    'texto' => 'Exportar',          'icono' => 'fa-file-excel',      'tipo' => 'accion', 'onclick' => 'exportExcel()'],
            ['clave' => 'contacto',    'texto' => 'Contáctanos',       'icono' => 'fa-envelope',        'href' => $base . '/#contacto', 'derecha' => true],
        ];
    }

    return [
        ['clave' => 'home',          'texto' => 'Inicio',                'icono' => 'fa-house',        'href' => $base . '/', 'solo_icono' => true, 'titulo' => 'Inicio'],
        ['clave' => 'informacion',   'texto' => 'Información LIASP-CB',  'icono' => 'fa-circle-info',  'href' => $base . '/#informacion'],
        ['clave' => 'colaboradores', 'texto' => 'Colaboradores',         'icono' => 'fa-handshake',    'href' => $base . '/#colaboradores'],
        ['clave' => 'proyectos',     'texto' => 'Proyectos',             'icono' => 'fa-diagram-project', 'tipo' => 'dropdown', 'href' => $base . '/#proyectos', 'hijos' => [
            ['texto' => 'SIMCA', 'detalle' => 'Monitoreo de calidad del aire en colegios', 'icono' => 'fa-wind', 'href' => $base . '/dashboard'],
        ]],
        ['clave' => 'contacto',      'texto' => 'Contáctanos',           'icono' => 'fa-envelope',     'href' => $base . '/#contacto', 'derecha' => true],
    ];
}

function view(string $name, array $data = []): void
{
    extract($data);
    $path = BASE_PATH . '/views/' . $name . '.php';
    if (file_exists($path)) {
        include $path;
    }
}
