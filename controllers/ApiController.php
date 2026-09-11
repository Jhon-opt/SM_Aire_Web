<?php

class ApiController
{
    private function getFiltros(): array
    {
        $colegioId = isset($_GET['colegio']) ? sanitize($_GET['colegio'], 'int') : null;
        $dispositivoId = isset($_GET['dispositivo']) ? sanitize($_GET['dispositivo'], 'int') : null;
        $intervalo = isset($_GET['intervalo']) ? sanitize($_GET['intervalo'], 'string') : '24h';
        $fechaInicio = isset($_GET['fecha_inicio']) ? sanitize($_GET['fecha_inicio'], 'string') : null;
        $fechaFin = isset($_GET['fecha_fin']) ? sanitize($_GET['fecha_fin'], 'string') : null;

        return [$colegioId, $dispositivoId, $intervalo, $fechaInicio, $fechaFin];
    }

    public function filtros(): void
    {
        $colegios = Colegio::getAll();

        $colegioId = isset($_GET['colegio']) ? sanitize($_GET['colegio'], 'int') : null;
        $dispositivos = $colegioId ? Dispositivo::getByColegio($colegioId) : [];

        // La clave de ingesta (api_key) es secreta: nunca sale en la API pública.
        $dispositivos = array_map(function ($d) {
            unset($d['api_key']);
            return $d;
        }, $dispositivos);

        jsonResponse([
            'colegios'      => $colegios,
            'dispositivos'  => $dispositivos,
        ]);
    }

    /**
     * Última lectura: tarjetas (principales siempre, opcionales solo con dato),
     * estado general del aire y datos para la actualización en tiempo real.
     */
    public function ultimas(): void
    {
        [$colegioId, $dispositivoId] = $this->getFiltros();

        $ultima = Medicion::getUltimasPorDispositivo($dispositivoId, $colegioId);
        $fecha = $ultima['fecha_hora'] ?? null;

        $tarjetas = [];
        if ($fecha) {
            foreach (parametrosVisibles([$ultima]) as $p) {
                $valor = tieneValor($ultima[$p] ?? null) ? round((float) $ultima[$p], 2) : null;
                $tarjetas[] = [
                    'parametro'   => $p,
                    'nombre'      => getParametroNombre($p),
                    'descripcion' => getParametroDescripcion($p),
                    'unidad'      => getParametroUnidad($p),
                    'valor'       => $valor,
                    'categoria'   => icaCategoria($p, $valor),
                    'principal'   => in_array($p, parametrosPrincipales(), true),
                ];
            }
        }

        jsonResponse([
            'fecha'            => $fecha,
            'fecha_local'      => formatDate($fecha, 'd/m/Y H:i:s'),
            'tarjetas'         => $tarjetas,
            'estado_general'   => estadoGeneral($tarjetas),
            'origen'           => $fecha ? [
                'dispositivo' => $ultima['dispositivo_codigo'] ?? null,
                'ubicacion'   => $ultima['dispositivo_ubicacion'] ?? null,
                'colegio'     => $ultima['colegio_nombre'] ?? null,
            ] : null,
            'total_mediciones' => Colegio::getTotalMediciones(),
        ]);
    }

    /**
     * Series para las gráficas, promediadas por intervalos de tiempo para que
     * cada gráfica tenga como máximo unos cientos de puntos. Etiquetas en hora local.
     */
    public function mediciones(): void
    {
        [$colegioId, $dispositivoId, $intervalo, $fechaInicio, $fechaFin] = $this->getFiltros();

        $ids = $dispositivoId ? [$dispositivoId] : $this->getDispositivosFromColegio($colegioId);
        if (empty($ids)) {
            jsonResponse(['error' => 'No hay dispositivos seleccionados'], 400);
            return;
        }

        $todas = $this->getHistorial($ids, $intervalo, $fechaInicio, $fechaFin);
        $parametros = parametrosVisibles($todas);

        // Ancho del rango: el nominal para intervalos predefinidos (bucket estable
        // aunque lleguen más datos) o el real de los datos para rango libre.
        $horas = intervaloHoras($intervalo);
        if ($horas !== null && !($fechaInicio && $fechaFin)) {
            $span = $horas * 3600;
        } elseif (count($todas) >= 2) {
            $span = max(3600, strtotime(end($todas)['fecha_hora'] . ' UTC') - strtotime($todas[0]['fecha_hora'] . ' UTC'));
        } else {
            $span = 3600;
        }

        $bucket = bucketSegundos($span);
        $formato = $span <= 26 * 3600 ? 'H:i' : ($bucket >= 86400 ? 'd/m/Y' : 'd/m H:i');

        $series = agregarSeries($todas, $parametros, $bucket, $formato);
        foreach ($series as $p => &$s) {
            $s['nombre'] = getParametroNombre($p);
            $s['unidad'] = getParametroUnidad($p);
            $s['principal'] = in_array($p, parametrosPrincipales(), true);
            $s['limites'] = icaLimites($p);
        }
        unset($s);

        jsonResponse([
            'series'          => $series,
            'parametros'      => $parametros,
            'total'           => count($todas),
            'bucket_segundos' => $bucket,
            'bucket_texto'    => textoBucket($bucket),
        ]);
    }

    public function estadisticas(): void
    {
        [$colegioId, $dispositivoId, $intervalo, $fechaInicio, $fechaFin] = $this->getFiltros();

        if (!$dispositivoId) {
            $ids = $this->getDispositivosFromColegio($colegioId);
            if (empty($ids)) {
                jsonResponse(['error' => 'No hay dispositivos seleccionados'], 400);
                return;
            }
            $statsAgregadas = [];
            foreach ($ids as $did) {
                $statsAgregadas[] = Medicion::getEstadisticas($did, $intervalo, $fechaInicio, $fechaFin);
            }
            $stats = $this->combinarEstadisticas($statsAgregadas);
        } else {
            $stats = Medicion::getEstadisticas($dispositivoId, $intervalo, $fechaInicio, $fechaFin);
        }

        // Principales siempre; opcionales solo si tienen promedio (hay datos).
        $conDatos = array_filter(parametrosOpcionales(), fn($p) => tieneValor($stats["avg_{$p}"] ?? null));
        $parametros = array_merge(parametrosPrincipales(), array_values($conDatos));

        $resultado = [];
        foreach ($parametros as $p) {
            $promedio = tieneValor($stats["avg_{$p}"] ?? null) ? round((float) $stats["avg_{$p}"], 2) : null;
            $resultado[] = [
                'parametro' => $p,
                'nombre'    => getParametroNombre($p),
                'unidad'    => getParametroUnidad($p),
                'promedio'  => $promedio,
                'maximo'    => tieneValor($stats["max_{$p}"] ?? null) ? round((float) $stats["max_{$p}"], 2) : null,
                'minimo'    => tieneValor($stats["min_{$p}"] ?? null) ? round((float) $stats["min_{$p}"], 2) : null,
                'categoria' => icaCategoria($p, $promedio),
                'principal' => in_array($p, parametrosPrincipales(), true),
            ];
        }

        jsonResponse([
            'estadisticas'    => $resultado,
            'total_registros' => (int) ($stats['total_registros'] ?? 0),
        ]);
    }

    public function tabla(): void
    {
        [$colegioId, $dispositivoId, $intervalo, $fechaInicio, $fechaFin] = $this->getFiltros();
        $pagina = max(1, (int) (isset($_GET['pagina']) ? sanitize($_GET['pagina'], 'int') : 1));
        $orden = isset($_GET['orden']) ? sanitize($_GET['orden'], 'string') : 'DESC';

        $ids = $dispositivoId ? [$dispositivoId] : $this->getDispositivosFromColegio($colegioId);
        if (empty($ids)) {
            jsonResponse(['error' => 'No hay dispositivos seleccionados'], 400);
            return;
        }

        if ($dispositivoId) {
            $result = Medicion::getTabla($dispositivoId, $pagina, $orden, $intervalo, $fechaInicio, $fechaFin);
        } else {
            $result = $this->getTablaMultiDispositivo($ids, $colegioId, $pagina, $orden, $intervalo, $fechaInicio, $fechaFin);
        }

        // Columnas: principales siempre + opcionales con datos en el rango filtrado.
        if (API_MODE) {
            $opcionales = array_values(array_diff(parametrosVisibles($result['data']), parametrosPrincipales()));
        } elseif (FAKE_MODE) {
            $todas = filterFakeMediciones($dispositivoId, $colegioId, $intervalo, $fechaInicio, $fechaFin);
            $opcionales = array_values(array_diff(parametrosVisibles($todas), parametrosPrincipales()));
        } else {
            $opcionales = Medicion::opcionalesConDatos($ids, $intervalo, $fechaInicio, $fechaFin);
        }

        $result['columnas'] = array_merge(parametrosPrincipales(), $opcionales);
        $result['dispositivos'] = $this->mapaDispositivos($ids);

        jsonResponse($result);
    }

    /**
     * Datos para el mapa: cada colegio con sus coordenadas, número de sensores,
     * última lectura y estado general del aire (peor categoría ICA).
     */
    public function mapa(): void
    {
        $porColegio = [];
        foreach (Dispositivo::getAll() as $d) {
            $porColegio[(int) $d['id_colegio']][] = $d;
        }

        $colegios = [];
        foreach (Colegio::getAll() as $c) {
            $id = (int) $c['id_colegio'];
            $ultima = Medicion::getUltimasPorDispositivo(null, $id);
            $fecha = $ultima['fecha_hora'] ?? null;

            $valores = [];
            if ($fecha) {
                foreach (parametrosVisibles([$ultima]) as $p) {
                    $valor = tieneValor($ultima[$p] ?? null) ? round((float) $ultima[$p], 2) : null;
                    $valores[] = [
                        'parametro' => $p,
                        'nombre'    => getParametroNombre($p),
                        'unidad'    => getParametroUnidad($p),
                        'valor'     => $valor,
                        'categoria' => icaCategoria($p, $valor),
                    ];
                }
            }

            $disps = $porColegio[$id] ?? [];

            $colegios[] = [
                'id_colegio'   => $id,
                'nombre'       => $c['nombre'] ?? '',
                'direccion'    => $c['direccion'] ?? '',
                'ciudad'       => $c['ciudad'] ?? '',
                // null si la migración de coordenadas no se ha aplicado o no se han cargado
                'latitud'      => tieneValor($c['latitud'] ?? null) ? (float) $c['latitud'] : null,
                'longitud'     => tieneValor($c['longitud'] ?? null) ? (float) $c['longitud'] : null,
                'dispositivos' => count($disps),
                'activos'      => count(array_filter($disps, fn($d) => ($d['estado'] ?? '') === 'activo')),
                'fecha'        => $fecha,
                'fecha_local'  => formatDate($fecha, 'd/m/Y H:i'),
                'valores'      => $valores,
                'estado'       => estadoGeneral($valores),
            ];
        }

        jsonResponse([
            'colegios' => $colegios,
            'centro'   => [MAPA_CENTRO_LNG, MAPA_CENTRO_LAT],
            'zoom'     => MAPA_ZOOM,
        ]);
    }

    private function getDispositivosFromColegio(?int $colegioId): array
    {
        if ($colegioId) {
            $disps = Dispositivo::getByColegio($colegioId);
        } else {
            $disps = Dispositivo::getAll();
        }
        return array_map(fn($d) => (int) $d['id_dispositivo'], $disps);
    }

    /** Historial de uno o varios dispositivos, ordenado por fecha. */
    private function getHistorial(array $ids, string $intervalo, ?string $fechaInicio, ?string $fechaFin): array
    {
        if (count($ids) === 1) {
            return Medicion::getHistory($ids[0], $intervalo, $fechaInicio, $fechaFin);
        }

        $todas = [];
        foreach ($ids as $did) {
            $todas = array_merge($todas, Medicion::getHistory($did, $intervalo, $fechaInicio, $fechaFin));
        }
        usort($todas, fn($a, $b) => strcmp($a['fecha_hora'], $b['fecha_hora']));

        return $todas;
    }

    /** [id_dispositivo => ['codigo' => ..., 'ubicacion' => ...]] para etiquetar filas. */
    private function mapaDispositivos(array $ids): array
    {
        $mapa = [];
        foreach (Dispositivo::getAll() as $d) {
            if (in_array((int) $d['id_dispositivo'], $ids, true)) {
                $mapa[(int) $d['id_dispositivo']] = [
                    'codigo'    => $d['codigo'] ?? '',
                    'ubicacion' => $d['ubicacion'] ?? '',
                ];
            }
        }
        return $mapa;
    }

    private function getTablaMultiDispositivo(array $ids, ?int $colegioId, int $pagina, string $orden, string $intervalo, ?string $fechaInicio, ?string $fechaFin): array
    {
        $orden = strtoupper($orden) === 'ASC' ? 'ASC' : 'DESC';

        if (FAKE_MODE) {
            return getFakeTabla(null, $colegioId, $pagina, $orden, $intervalo, $fechaInicio, $fechaFin);
        }

        if (API_MODE) {
            [$desde, $hasta] = ApiClient::buildRange($intervalo, $fechaInicio, $fechaFin);

            $todas = [];
            $queryBase = [];
            if ($desde) $queryBase['desde'] = $desde;
            if ($hasta) $queryBase['hasta'] = $hasta;

            foreach ($ids as $did) {
                $rows = ApiClient::getAllMediciones(array_merge($queryBase, ['id_dispositivo' => $did]));
                $todas = array_merge($todas, array_map([ApiClient::class, 'normalizeMedicion'], $rows));
            }

            usort($todas, fn($a, $b) => strtotime($b['fecha_hora']) - strtotime($a['fecha_hora']));

            $total = count($todas);
            $totalPaginas = max(1, ceil($total / ITEMS_PER_PAGE));

            $offset = ($pagina - 1) * ITEMS_PER_PAGE;
            $rows = array_slice($todas, $offset, ITEMS_PER_PAGE);

            if ($orden === 'ASC') {
                $rows = array_reverse($rows);
            }

            return [
                'data'          => $rows,
                'total'         => $total,
                'pagina'        => $pagina,
                'total_paginas' => $totalPaginas,
                'por_pagina'    => ITEMS_PER_PAGE,
            ];
        }

        $offset = ($pagina - 1) * ITEMS_PER_PAGE;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT m.* FROM medicion m WHERE m.id_dispositivo IN ({$placeholders})";
        $countSql = "SELECT COUNT(*) FROM medicion WHERE id_dispositivo IN ({$placeholders})";

        if ($fechaInicio && $fechaFin) {
            [$fi, $ff] = normalizarRangoFechas($fechaInicio, $fechaFin);
        } else {
            $fi = $ff = null;
        }

        if ($fi !== null) {
            $sql .= ' AND m.fecha_hora >= ? AND m.fecha_hora <= ?';
            $countSql .= ' AND fecha_hora >= ? AND fecha_hora <= ?';
            $params = array_merge($ids, [$fi, $ff]);
        } else {
            $sql .= condicionIntervalo('m.fecha_hora', $intervalo);
            $countSql .= condicionIntervalo('fecha_hora', $intervalo);
            $params = $ids;
        }

        $total = (int) Database::fetchColumn($countSql, $params);
        $totalPaginas = max(1, ceil($total / ITEMS_PER_PAGE));

        $sql .= " ORDER BY m.fecha_hora {$orden} LIMIT " . ITEMS_PER_PAGE . " OFFSET {$offset}";
        $rows = Database::fetchAll($sql, $params);

        return [
            'data'          => $rows,
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => $totalPaginas,
            'por_pagina'    => ITEMS_PER_PAGE,
        ];
    }

    private function combinarEstadisticas(array $datos): array
    {
        if (empty($datos)) return [];

        $resultado = ['total_registros' => 0];

        foreach (parametrosTodos() as $p) {
            // Conservar ceros reales: solo se descartan nulos.
            $avgs = array_filter(array_map(fn($d) => $d["avg_{$p}"] ?? null, $datos), 'tieneValor');
            $maxs = array_filter(array_map(fn($d) => $d["max_{$p}"] ?? null, $datos), 'tieneValor');
            $mins = array_filter(array_map(fn($d) => $d["min_{$p}"] ?? null, $datos), 'tieneValor');

            $resultado["avg_{$p}"] = !empty($avgs) ? array_sum($avgs) / count($avgs) : null;
            $resultado["max_{$p}"] = !empty($maxs) ? max($maxs) : null;
            $resultado["min_{$p}"] = !empty($mins) ? min($mins) : null;
        }

        foreach ($datos as $d) {
            $resultado['total_registros'] += (int) ($d['total_registros'] ?? 0);
        }

        return $resultado;
    }
}
