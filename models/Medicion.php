<?php

class Medicion
{
    public static function getUltimasPorDispositivo(?int $idDispositivo = null, ?int $idColegio = null): array
    {
        if (FAKE_MODE) {
            $row = getFakeUltimaMedicion($idDispositivo, $idColegio);
            return $row ?: [];
        }

        $sql = "SELECT m.*
                FROM medicion m
                JOIN dispositivo d ON m.id_dispositivo = d.id_dispositivo";
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
        if (FAKE_MODE) {
            return filterFakeMediciones($idDispositivo, null, $intervalo, $fechaInicio, $fechaFin);
        }

        $sql = "SELECT m.* FROM medicion m WHERE m.id_dispositivo = ?";
        $params = [$idDispositivo];

        if ($fechaInicio && $fechaFin) {
            $sql .= ' AND m.fecha_hora >= ? AND m.fecha_hora <= ?';
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        } else {
            $limites = [
                '24h' => '-24 HOUR',
                '7d'  => '-7 DAY',
                '30d' => '-30 DAY',
            ];
            $intervalo_sql = $limites[$intervalo] ?? '-24 HOUR';
            $sql .= ' AND m.fecha_hora >= DATE_SUB(NOW(), INTERVAL ' . $intervalo_sql . ')';
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

        if ($fechaInicio && $fechaFin) {
            $sql .= ' AND fecha_hora >= ? AND fecha_hora <= ?';
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        } else {
            $limites = [
                '24h' => '-24 HOUR',
                '7d'  => '-7 DAY',
                '30d' => '-30 DAY',
            ];
            $intervalo_sql = $limites[$intervalo] ?? '-24 HOUR';
            $sql .= ' AND fecha_hora >= DATE_SUB(NOW(), INTERVAL ' . $intervalo_sql . ')';
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
        if (FAKE_MODE) {
            return getFakeTabla($idDispositivo, null, $pagina, $orden, $intervalo, $fechaInicio, $fechaFin);
        }

        $orden = strtoupper($orden) === 'ASC' ? 'ASC' : 'DESC';
        $offset = ($pagina - 1) * ITEMS_PER_PAGE;

        $sql = "SELECT m.* FROM medicion m WHERE m.id_dispositivo = ?";
        $countSql = "SELECT COUNT(*) FROM medicion WHERE id_dispositivo = ?";
        $params = [$idDispositivo];

        if ($fechaInicio && $fechaFin) {
            $sql .= ' AND m.fecha_hora >= ? AND m.fecha_hora <= ?';
            $countSql .= ' AND fecha_hora >= ? AND fecha_hora <= ?';
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
            $countParams = $params;
        } else {
            $limites = [
                '24h' => '-24 HOUR',
                '7d'  => '-7 DAY',
                '30d' => '-30 DAY',
            ];
            $intervalo_sql = $limites[$intervalo] ?? '-24 HOUR';
            $sql .= ' AND m.fecha_hora >= DATE_SUB(NOW(), INTERVAL ' . $intervalo_sql . ')';
            $countSql .= ' AND fecha_hora >= DATE_SUB(NOW(), INTERVAL ' . $intervalo_sql . ')';
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
}
