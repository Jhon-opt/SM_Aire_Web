<?php

class Medicion
{
    public static function getUltimasPorDispositivo(?int $idDispositivo = null, ?int $idColegio = null): array
    {
        if (API_MODE) {
            if ($idDispositivo) {
                $row = ApiClient::getUltimaMedicion($idDispositivo);
                return $row ? ApiClient::normalizeMedicion($row) : [];
            }

            if ($idColegio) {
                $disps = Dispositivo::getByColegio($idColegio);
            } else {
                $disps = Dispositivo::getAll();
            }

            $mejor = null;
            foreach ($disps as $d) {
                $row = ApiClient::getUltimaMedicion($d['id_dispositivo']);
                if (!$row) continue;
                $norm = ApiClient::normalizeMedicion($row);
                if ($mejor === null || strtotime($norm['fecha_hora']) > strtotime($mejor['fecha_hora'])) {
                    $mejor = $norm;
                }
            }
            return $mejor ?: [];
        }

        if (FAKE_MODE) {
            $row = getFakeUltimaMedicion($idDispositivo, $idColegio);
            return $row ?: [];
        }

        $sql = "SELECT m.*,
                       d.codigo    AS dispositivo_codigo,
                       d.ubicacion AS dispositivo_ubicacion,
                       c.nombre    AS colegio_nombre
                FROM medicion m
                JOIN dispositivo d ON m.id_dispositivo = d.id_dispositivo
                LEFT JOIN colegio c ON d.id_colegio = c.id_colegio";
        $params = [];
        $conditions = [];

        if ($idDispositivo) {
            $conditions[] = 'm.id_dispositivo = ?';
            $params[] = $idDispositivo;
        } elseif ($idColegio) {
            $conditions[] = 'd.id_colegio = ?';
            $params[] = $idColegio;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY m.fecha_hora DESC LIMIT 1';

        return Database::fetchOne($sql, $params) ?: [];
    }

    public static function getHistory(
        int $idDispositivo,
        string $intervalo = '24h',
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        if (API_MODE) {
            [$desde, $hasta] = ApiClient::buildRange($intervalo, $fechaInicio, $fechaFin);

            $query = ['id_dispositivo' => $idDispositivo];
            if ($desde) $query['desde'] = $desde;
            if ($hasta) $query['hasta'] = $hasta;

            $rows = array_map([ApiClient::class, 'normalizeMedicion'], ApiClient::getAllMediciones($query));
            usort($rows, fn($a, $b) => strtotime($a['fecha_hora']) - strtotime($b['fecha_hora']));
            return $rows;
        }

        if (FAKE_MODE) {
            return filterFakeMediciones($idDispositivo, null, $intervalo, $fechaInicio, $fechaFin);
        }

        $sql = "SELECT m.* FROM medicion m WHERE m.id_dispositivo = ?";
        $params = [$idDispositivo];

        [$fi, $ff] = normalizarRangoFechas($fechaInicio, $fechaFin);
        if ($fi !== null) {
            $sql .= ' AND m.fecha_hora >= ? AND m.fecha_hora <= ?';
            $params[] = $fi;
            $params[] = $ff;
        } else {
            $sql .= condicionIntervalo('m.fecha_hora', $intervalo);
        }

        $sql .= ' ORDER BY m.fecha_hora ASC';

        return Database::fetchAll($sql, $params);
    }

    public static function getEstadisticas(
        int $idDispositivo,
        string $intervalo = '24h',
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        if (API_MODE) {
            $filtradas = self::getHistory($idDispositivo, $intervalo, $fechaInicio, $fechaFin);
            return self::computarEstadisticas($filtradas);
        }

        if (FAKE_MODE) {
            return getFakeEstadisticas($idDispositivo, null, $intervalo, $fechaInicio, $fechaFin);
        }

        $campos = ['pm2_5', 'pm10', 'co', 'co2', 'o3', 'no2', 'temperatura', 'humedad'];
        $selects = [];
        foreach ($campos as $c) {
            $selects[] = "ROUND(AVG({$c}), 2) as avg_{$c}";
            $selects[] = "ROUND(MAX({$c}), 2) as max_{$c}";
            $selects[] = "ROUND(MIN({$c}), 2) as min_{$c}";
        }

        $sql = "SELECT " . implode(', ', $selects) . ", COUNT(*) as total_registros
                FROM medicion WHERE id_dispositivo = ?";
        $params = [$idDispositivo];

        [$fi, $ff] = normalizarRangoFechas($fechaInicio, $fechaFin);
        if ($fi !== null) {
            $sql .= ' AND fecha_hora >= ? AND fecha_hora <= ?';
            $params[] = $fi;
            $params[] = $ff;
        } else {
            $sql .= condicionIntervalo('fecha_hora', $intervalo);
        }

        $result = Database::fetchOne($sql, $params);
        return $result ?: [];
    }

    public static function getTabla(
        int $idDispositivo,
        int $pagina = 1,
        string $orden = 'DESC',
        string $intervalo = '24h',
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        if (API_MODE) {
            [$desde, $hasta] = ApiClient::buildRange($intervalo, $fechaInicio, $fechaFin);

            $query = ['id_dispositivo' => $idDispositivo];
            if ($desde) $query['desde'] = $desde;
            if ($hasta) $query['hasta'] = $hasta;

            $total = (int) (ApiClient::getMediciones(array_merge($query, ['limit' => 1]))['total'] ?? 0);

            $res = ApiClient::getMediciones(array_merge($query, [
                'limit'  => ITEMS_PER_PAGE,
                'offset' => ($pagina - 1) * ITEMS_PER_PAGE,
            ]));

            $rows = array_map([ApiClient::class, 'normalizeMedicion'], $res['data'] ?? []);

            if (strtoupper($orden) === 'ASC') {
                usort($rows, fn($a, $b) => strtotime($a['fecha_hora']) - strtotime($b['fecha_hora']));
            }

            $totalPaginas = max(1, ceil($total / ITEMS_PER_PAGE));

            return [
                'data'          => $rows,
                'total'         => $total,
                'pagina'        => $pagina,
                'total_paginas' => $totalPaginas,
                'por_pagina'    => ITEMS_PER_PAGE,
            ];
        }

        if (FAKE_MODE) {
            return getFakeTabla($idDispositivo, null, $pagina, $orden, $intervalo, $fechaInicio, $fechaFin);
        }

        $orden = strtoupper($orden) === 'ASC' ? 'ASC' : 'DESC';
        $offset = ($pagina - 1) * ITEMS_PER_PAGE;

        $sql = "SELECT m.* FROM medicion m WHERE m.id_dispositivo = ?";
        $countSql = "SELECT COUNT(*) FROM medicion WHERE id_dispositivo = ?";
        $params = [$idDispositivo];

        if ($fechaInicio && $fechaFin) {
            [$fi, $ff] = normalizarRangoFechas($fechaInicio, $fechaFin);
        } else {
            $fi = $ff = null;
        }

        if ($fi !== null) {
            $sql .= ' AND m.fecha_hora >= ? AND m.fecha_hora <= ?';
            $countSql .= ' AND fecha_hora >= ? AND fecha_hora <= ?';
            $params[] = $fi;
            $params[] = $ff;
            $countParams = $params;
        } else {
            $sql .= condicionIntervalo('m.fecha_hora', $intervalo);
            $countSql .= condicionIntervalo('fecha_hora', $intervalo);
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

    /**
     * Parámetros opcionales que tienen al menos un dato en el rango, para un
     * conjunto de dispositivos (solo modo db; los otros modos lo calculan en memoria).
     */
    public static function opcionalesConDatos(
        array $ids,
        string $intervalo = '24h',
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        if (empty($ids)) {
            return [];
        }

        $selects = [];
        foreach (parametrosOpcionales() as $p) {
            $selects[] = "COUNT({$p}) AS n_{$p}";
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT " . implode(', ', $selects) . " FROM medicion WHERE id_dispositivo IN ({$placeholders})";
        $params = array_values($ids);

        [$fi, $ff] = normalizarRangoFechas($fechaInicio, $fechaFin);
        if ($fi !== null) {
            $sql .= ' AND fecha_hora >= ? AND fecha_hora <= ?';
            $params[] = $fi;
            $params[] = $ff;
        } else {
            $sql .= condicionIntervalo('fecha_hora', $intervalo);
        }

        $row = Database::fetchOne($sql, $params) ?: [];

        return array_values(array_filter(
            parametrosOpcionales(),
            fn($p) => (int) ($row["n_{$p}"] ?? 0) > 0
        ));
    }

    private static function computarEstadisticas(array $filtradas): array
    {
        if (empty($filtradas)) return [];

        $result = ['total_registros' => count($filtradas)];

        foreach (parametrosTodos() as $p) {
            // Conservar ceros reales: solo se descartan nulos.
            $vals = array_map('floatval', array_filter(
                array_map(fn($m) => $m[$p] ?? null, $filtradas),
                'tieneValor'
            ));
            if (empty($vals)) {
                $result["avg_{$p}"] = null;
                $result["max_{$p}"] = null;
                $result["min_{$p}"] = null;
            } else {
                $result["avg_{$p}"] = round(array_sum($vals) / count($vals), 2);
                $result["max_{$p}"] = round(max($vals), 2);
                $result["min_{$p}"] = round(min($vals), 2);
            }
        }

        return $result;
    }
}
