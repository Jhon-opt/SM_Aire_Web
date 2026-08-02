<?php

function getFakeColegios(): array
{
    return [
        ['id_colegio' => 1, 'nombre' => 'Colegio San José',       'direccion' => 'Av. Principal 123', 'ciudad' => 'Lima'],
        ['id_colegio' => 2, 'nombre' => 'Colegio Santa María',    'direccion' => 'Jr. Las Flores 456', 'ciudad' => 'Arequipa'],
        ['id_colegio' => 3, 'nombre' => 'Colegio Alexander von Humboldt',
                                                                    'direccion' => 'Calle Los Olivos 789', 'ciudad' => 'Cusco'],
        ['id_colegio' => 4, 'nombre' => 'Colegio Sagrados Corazones',
                                                                    'direccion' => 'Av. La Marina 321', 'ciudad' => 'Trujillo'],
        ['id_colegio' => 5, 'nombre' => 'Colegio Euroamericano',  'direccion' => 'Pasaje El Sol 654', 'ciudad' => 'Piura'],
    ];
}

function getFakeDispositivos(): array
{
    return [
        ['id_dispositivo' => 1,  'codigo' => 'SNS-001', 'modelo' => 'AirQ-200', 'ubicacion' => 'Azotea',        'estado' => 'activo',  'fecha_instalacion' => '2025-01-15 08:00:00', 'id_colegio' => 1],
        ['id_dispositivo' => 2,  'codigo' => 'SNS-002', 'modelo' => 'AirQ-100', 'ubicacion' => 'Patio principal','estado' => 'activo',  'fecha_instalacion' => '2025-02-10 10:30:00', 'id_colegio' => 1],
        ['id_dispositivo' => 3,  'codigo' => 'SNS-003', 'modelo' => 'AirQ-300', 'ubicacion' => 'Azotea',        'estado' => 'activo',  'fecha_instalacion' => '2025-01-20 09:00:00', 'id_colegio' => 2],
        ['id_dispositivo' => 4,  'codigo' => 'SNS-004', 'modelo' => 'AirQ-200', 'ubicacion' => 'Biblioteca',    'estado' => 'activo',  'fecha_instalacion' => '2025-03-05 11:00:00', 'id_colegio' => 2],
        ['id_dispositivo' => 5,  'codigo' => 'SNS-005', 'modelo' => 'AirQ-100', 'ubicacion' => 'Aula 101',      'estado' => 'mantenimiento', 'fecha_instalacion' => '2025-04-01 08:30:00', 'id_colegio' => 2],
        ['id_dispositivo' => 6,  'codigo' => 'SNS-006', 'modelo' => 'AirQ-200', 'ubicacion' => 'Azotea',        'estado' => 'activo',  'fecha_instalacion' => '2025-02-28 10:00:00', 'id_colegio' => 3],
        ['id_dispositivo' => 7,  'codigo' => 'SNS-007', 'modelo' => 'AirQ-300', 'ubicacion' => 'Laboratorio',   'estado' => 'activo',  'fecha_instalacion' => '2025-03-12 14:00:00', 'id_colegio' => 3],
        ['id_dispositivo' => 8,  'codigo' => 'SNS-008', 'modelo' => 'AirQ-100', 'ubicacion' => 'Azotea',        'estado' => 'activo',  'fecha_instalacion' => '2025-01-25 07:45:00', 'id_colegio' => 4],
        ['id_dispositivo' => 9,  'codigo' => 'SNS-009', 'modelo' => 'AirQ-200', 'ubicacion' => 'Gimnasio',      'estado' => 'inactivo','fecha_instalacion' => '2025-05-10 09:15:00', 'id_colegio' => 4],
        ['id_dispositivo' => 10, 'codigo' => 'SNS-010', 'modelo' => 'AirQ-300', 'ubicacion' => 'Comedor',       'estado' => 'activo',  'fecha_instalacion' => '2025-04-20 11:30:00', 'id_colegio' => 4],
        ['id_dispositivo' => 11, 'codigo' => 'SNS-011', 'modelo' => 'AirQ-200', 'ubicacion' => 'Azotea',        'estado' => 'activo',  'fecha_instalacion' => '2025-02-14 08:00:00', 'id_colegio' => 5],
        ['id_dispositivo' => 12, 'codigo' => 'SNS-012', 'modelo' => 'AirQ-100', 'ubicacion' => 'Patio',         'estado' => 'activo',  'fecha_instalacion' => '2025-03-30 10:00:00', 'id_colegio' => 5],
    ];
}

function getFakeMediciones(): array
{
    static $mediciones;

    if ($mediciones !== null) return $mediciones;

    $mediciones = [];
    $now = time();
    $totalPuntos = 480;
    $segundosEntre = 5400;

    $dispositivosActivos = [1, 2, 3, 4, 6, 7, 8, 10, 11, 12];

    for ($i = 0; $i < $totalPuntos; $i++) {
        $timestamp = $now - ($totalPuntos - $i) * $segundosEntre;
        $hora = (int) date('G', $timestamp);
        $esNoche = $hora < 6 || $hora > 22;
        $esPico = ($hora >= 7 && $hora <= 9) || ($hora >= 17 && $hora <= 20);
        $esMedianoche = $hora >= 0 && $hora <= 4;

        $ruido = fn($max) => rand(-$max, $max) / 10;

        $did = $dispositivosActivos[array_rand($dispositivosActivos)];
        $basePm25 = $esMedianoche ? rand(3, 8) : ($esNoche ? rand(5, 15) : ($esPico ? rand(18, 45) : rand(8, 25)));
        $basePm10 = $basePm25 * (1.5 + rand(0, 10) / 10);

        $mediciones[] = [
            'id_medicion'    => $i + 1,
            'id_dispositivo' => $did,
            'pm2_5'  => round(max(0, $basePm25 + $ruido(30)), 2),
            'pm10'   => round(max(0, $basePm10 + $ruido(50)), 2),
            'co'     => round(max(0, rand(10, 80) / 10 + $ruido(10)), 2),
            'co2'    => round(max(300, rand(350, 1000) + ($esPico ? rand(100, 400) : 0) + $ruido(50)), 2),
            'o3'     => round(max(0, rand(5, 75) + ($esNoche ? -rand(0, 20) : 0) + $ruido(10)), 2),
            'no2'    => round(max(0, rand(5, 70) + ($esPico ? rand(10, 40) : 0) + $ruido(15)), 2),
            'temperatura' => round(rand(160, 340) / 10 + $ruido(5), 2),
            'humedad'     => round(rand(250, 850) / 10 + $ruido(10), 2),
            'fecha_hora'  => date('Y-m-d H:i:s', $timestamp),
        ];
    }

    return $mediciones;
}

function getFakeUltimaMedicion(?int $idDispositivo, ?int $idColegio): ?array
{
    $mediciones = getFakeMediciones();

    $filtradas = array_values(array_filter($mediciones, function ($m) use ($idDispositivo, $idColegio) {
        if ($idDispositivo && $m['id_dispositivo'] !== $idDispositivo) return false;
        if ($idColegio) {
            $disps = getFakeDispositivos();
            $ids = array_map(fn($d) => $d['id_dispositivo'],
                array_filter($disps, fn($d) => $d['id_colegio'] === $idColegio));
            if (!in_array($m['id_dispositivo'], $ids)) return false;
        }
        return true;
    }));

    return !empty($filtradas) ? $filtradas[array_key_last($filtradas)] : null;
}

function filterFakeMediciones(
    ?int $idDispositivo,
    ?int $idColegio,
    string $intervalo = '24h',
    ?string $fechaInicio = null,
    ?string $fechaFin = null
): array {
    $todas = getFakeMediciones();

    $dispositivosIds = [];
    if ($idDispositivo) {
        $dispositivosIds = [$idDispositivo];
    } elseif ($idColegio) {
        $disps = array_filter(getFakeDispositivos(), fn($d) => $d['id_colegio'] === $idColegio);
        $dispositivosIds = array_map(fn($d) => $d['id_dispositivo'], $disps);
    } else {
        $dispositivosIds = array_unique(array_map(fn($m) => $m['id_dispositivo'], $todas));
    }

    $limites = [
        '24h' => strtotime('-24 hours'),
        '7d'  => strtotime('-7 days'),
        '30d' => strtotime('-30 days'),
    ];
    $limite = $limites[$intervalo] ?? strtotime('-24 hours');

    $result = array_values(array_filter($todas, function ($m) use ($dispositivosIds, $limite, $fechaInicio, $fechaFin) {
        if (!in_array($m['id_dispositivo'], $dispositivosIds)) return false;
        $ts = strtotime($m['fecha_hora']);
        if ($fechaInicio && $fechaFin) {
            return $ts >= strtotime($fechaInicio) && $ts <= strtotime($fechaFin . ' 23:59:59');
        }
        return $ts >= $limite;
    }));

    usort($result, fn($a, $b) => strtotime($a['fecha_hora']) - strtotime($b['fecha_hora']));

    return $result;
}

function getFakeEstadisticas(
    ?int $idDispositivo,
    ?int $idColegio,
    string $intervalo = '24h',
    ?string $fechaInicio = null,
    ?string $fechaFin = null
): array {
    $filtradas = filterFakeMediciones($idDispositivo, $idColegio, $intervalo, $fechaInicio, $fechaFin);

    if (empty($filtradas)) return [];

    $parametros = ['pm2_5', 'pm10', 'co', 'co2', 'o3', 'no2', 'temperatura', 'humedad'];
    $result = ['total_registros' => count($filtradas)];

    foreach ($parametros as $p) {
        $vals = array_filter(array_map(fn($m) => $m[$p] ?? null, $filtradas));
        if (empty($vals)) {
            $result["avg_{$p}"] = 0;
            $result["max_{$p}"] = 0;
            $result["min_{$p}"] = 0;
        } else {
            $result["avg_{$p}"] = round(array_sum($vals) / count($vals), 2);
            $result["max_{$p}"] = round(max($vals), 2);
            $result["min_{$p}"] = round(min($vals), 2);
        }
    }

    return $result;
}

function getFakeTabla(
    ?int $idDispositivo,
    ?int $idColegio,
    int $pagina = 1,
    string $orden = 'DESC',
    string $intervalo = '24h',
    ?string $fechaInicio = null,
    ?string $fechaFin = null
): array {
    $filtradas = filterFakeMediciones($idDispositivo, $idColegio, $intervalo, $fechaInicio, $fechaFin);

    if (strtoupper($orden) === 'ASC') {
        usort($filtradas, fn($a, $b) => strtotime($a['fecha_hora']) - strtotime($b['fecha_hora']));
    } else {
        usort($filtradas, fn($a, $b) => strtotime($b['fecha_hora']) - strtotime($a['fecha_hora']));
    }

    $total = count($filtradas);
    $totalPaginas = max(1, ceil($total / ITEMS_PER_PAGE));
    $offset = ($pagina - 1) * ITEMS_PER_PAGE;
    $data = array_slice($filtradas, $offset, ITEMS_PER_PAGE);

    return [
        'data'          => $data,
        'total'         => $total,
        'pagina'        => $pagina,
        'total_paginas' => $totalPaginas,
        'por_pagina'    => ITEMS_PER_PAGE,
    ];
}
