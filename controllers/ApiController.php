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

        jsonResponse([
            'colegios'      => $colegios,
            'dispositivos'  => $dispositivos,
        ]);
    }

    public function ultimas(): void
    {
        [$colegioId, $dispositivoId] = $this->getFiltros();

        $ultima = Medicion::getUltimasPorDispositivo($dispositivoId, $colegioId);

        $parametros = ['pm2_5', 'pm10', 'co', 'co2', 'o3', 'no2', 'temperatura', 'humedad'];
        $tarjetas = [];

        foreach ($parametros as $p) {
            if (isset($ultima[$p]) && $ultima[$p] !== null) {
                $nivel = getQualityLevel($p, (float) $ultima[$p]);
                $tarjetas[] = [
                    'parametro' => $p,
                    'nombre'    => getParametroNombre($p),
                    'valor'     => (float) $ultima[$p],
                    'unidad'    => getParametroUnidad($p),
                    'nivel'     => $nivel,
                    'color'     => getQualityColor($nivel),
                    'fecha'     => $ultima['fecha_hora'] ?? null,
                ];
            }
        }

        jsonResponse([
            'tarjetas'  => $tarjetas,
            'fecha'     => $ultima['fecha_hora'] ?? null,
        ]);
    }

    public function mediciones(): void
    {
        [$colegioId, $dispositivoId, $intervalo, $fechaInicio, $fechaFin] = $this->getFiltros();

        if (!$dispositivoId) {
            $ids = $this->getDispositivosFromColegio($colegioId);
            if (empty($ids)) {
                jsonResponse(['error' => 'No hay dispositivos seleccionados'], 400);
                return;
            }
            $todas = [];
            foreach ($ids as $did) {
                $data = Medicion::getHistory($did, $intervalo, $fechaInicio, $fechaFin);
                $todas = array_merge($todas, $data);
            }
            usort($todas, fn($a, $b) => strtotime($a['fecha_hora']) - strtotime($b['fecha_hora']));
        } else {
            $todas = Medicion::getHistory($dispositivoId, $intervalo, $fechaInicio, $fechaFin);
        }

        $parametros = ['pm2_5', 'pm10', 'co', 'co2', 'o3', 'no2', 'temperatura', 'humedad'];
        $series = [];

        foreach ($parametros as $p) {
            $series[$p] = [
                'labels' => [],
                'values' => [],
                'nombre' => getParametroNombre($p),
                'unidad' => getParametroUnidad($p),
            ];
        }

        foreach ($todas as $medicion) {
            $label = date('d/m H:i', strtotime($medicion['fecha_hora']));
            foreach ($parametros as $p) {
                if (isset($medicion[$p]) && $medicion[$p] !== null) {
                    $series[$p]['labels'][] = $label;
                    $series[$p]['values'][] = (float) $medicion[$p];
                }
            }
        }

        jsonResponse([
            'series'    => $series,
            'total'     => count($todas),
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
                $data = Medicion::getEstadisticas($did, $intervalo, $fechaInicio, $fechaFin);
                $statsAgregadas[] = $data;
            }
            $stats = $this->combinarEstadisticas($statsAgregadas);
        } else {
            $stats = Medicion::getEstadisticas($dispositivoId, $intervalo, $fechaInicio, $fechaFin);
        }

        $parametros = ['pm2_5', 'pm10', 'co', 'co2', 'o3', 'no2', 'temperatura', 'humedad'];
        $resultado = [];

        foreach ($parametros as $p) {
            $resultado[] = [
                'parametro' => $p,
                'nombre'    => getParametroNombre($p),
                'unidad'    => getParametroUnidad($p),
                'promedio'  => isset($stats["avg_{$p}"]) ? round((float) $stats["avg_{$p}"], 2) : null,
                'maximo'    => isset($stats["max_{$p}"]) ? round((float) $stats["max_{$p}"], 2) : null,
                'minimo'    => isset($stats["min_{$p}"]) ? round((float) $stats["min_{$p}"], 2) : null,
            ];
        }

        jsonResponse([
            'estadisticas' => $resultado,
            'total_registros' => $stats['total_registros'] ?? 0,
        ]);
    }

    public function tabla(): void
    {
        [$colegioId, $dispositivoId, $intervalo, $fechaInicio, $fechaFin] = $this->getFiltros();
        $pagina = isset($_GET['pagina']) ? sanitize($_GET['pagina'], 'int') : 1;
        $orden = isset($_GET['orden']) ? sanitize($_GET['orden'], 'string') : 'DESC';

        if (!$dispositivoId) {
            $ids = $this->getDispositivosFromColegio($colegioId);
            if (empty($ids)) {
                jsonResponse(['error' => 'No hay dispositivos seleccionados'], 400);
                return;
            }
            $result = $this->getTablaMultiDispositivo($ids, $pagina, $orden, $intervalo, $fechaInicio, $fechaFin);
        } else {
            $result = Medicion::getTabla($dispositivoId, $pagina, $orden, $intervalo, $fechaInicio, $fechaFin);
        }

        jsonResponse($result);
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

    private function getTablaMultiDispositivo(array $ids, int $pagina, string $orden, string $intervalo, ?string $fechaInicio, ?string $fechaFin): array
    {
        $orden = strtoupper($orden) === 'ASC' ? 'ASC' : 'DESC';
        $offset = ($pagina - 1) * ITEMS_PER_PAGE;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT m.* FROM medicion m WHERE m.id_dispositivo IN ({$placeholders})";
        $countSql = "SELECT COUNT(*) FROM medicion WHERE id_dispositivo IN ({$placeholders})";

        if ($fechaInicio && $fechaFin) {
            $sql .= ' AND m.fecha_hora >= ? AND m.fecha_hora <= ?';
            $countSql .= ' AND fecha_hora >= ? AND fecha_hora <= ?';
            $params = array_merge($ids, [$fechaInicio, $fechaFin]);
            $countParams = $params;
        } else {
            $limites = ['24h' => '-24 HOUR', '7d' => '-7 DAY', '30d' => '-30 DAY'];
            $intervalo_sql = $limites[$intervalo] ?? '-24 HOUR';
            $sql .= ' AND m.fecha_hora >= DATE_SUB(NOW(), INTERVAL ' . $intervalo_sql . ')';
            $countSql .= ' AND fecha_hora >= DATE_SUB(NOW(), INTERVAL ' . $intervalo_sql . ')';
            $params = $ids;
            $countParams = $params;
        }

        $total = (int) Database::fetchColumn($countSql, $countParams);
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

        $parametros = ['pm2_5', 'pm10', 'co', 'co2', 'o3', 'no2', 'temperatura', 'humedad'];
        $resultado = ['total_registros' => 0];

        foreach ($parametros as $p) {
            $avgs = array_filter(array_map(fn($d) => $d["avg_{$p}"] ?? null, $datos));
            $maxs = array_filter(array_map(fn($d) => $d["max_{$p}"] ?? null, $datos));
            $mins = array_filter(array_map(fn($d) => $d["min_{$p}"] ?? null, $datos));

            $resultado["avg_{$p}"] = !empty($avgs) ? array_sum($avgs) / count($avgs) : 0;
            $resultado["max_{$p}"] = !empty($maxs) ? max($maxs) : 0;
            $resultado["min_{$p}"] = !empty($mins) ? min($mins) : 0;
        }

        foreach ($datos as $d) {
            $resultado['total_registros'] += (int) ($d['total_registros'] ?? 0);
        }

        return $resultado;
    }
}
