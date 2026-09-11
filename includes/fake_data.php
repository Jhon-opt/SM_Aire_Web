<?php

function getFakeColegios(): array
{
    return [
        ['id_colegio' => 1, 'nombre' => 'Colegio San José',               'direccion' => 'Localidad de Fontibón', 'ciudad' => 'Bogotá D.C.', 'latitud' => 4.682955, 'longitud' => -74.145167],
        ['id_colegio' => 2, 'nombre' => 'Colegio Santa María',            'direccion' => 'Localidad de Chapinero', 'ciudad' => 'Bogotá D.C.', 'latitud' => 4.648600, 'longitud' => -74.062800],
        ['id_colegio' => 3, 'nombre' => 'Colegio Alexander von Humboldt', 'direccion' => 'Localidad de Usaquén',  'ciudad' => 'Bogotá D.C.', 'latitud' => 4.712000, 'longitud' => -74.032000],
        ['id_colegio' => 4, 'nombre' => 'Colegio Sagrados Corazones',     'direccion' => 'Localidad de Kennedy',  'ciudad' => 'Bogotá D.C.', 'latitud' => 4.628000, 'longitud' => -74.153000],
        // Sin coordenadas: sirve para probar el aviso de "colegios sin ubicación"
        ['id_colegio' => 5, 'nombre' => 'Colegio Euroamericano',          'direccion' => 'Localidad de Suba',     'ciudad' => 'Bogotá D.C.', 'latitud' => null,     'longitud' => null],
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

/**
 * Sensores que trae cada modelo (para probar que las variables opcionales
 * solo se muestren cuando existen datos):
 *   AirQ-100: PM2.5, PM10 y CO (como el hardware real)
 *   AirQ-200: + temperatura y humedad
 *   AirQ-300: todos los parámetros
 */
function getFakeSensoresModelo(string $modelo): array
{
    return match ($modelo) {
        'AirQ-100' => ['pm2_5', 'pm10', 'co'],
        'AirQ-200' => ['pm2_5', 'pm10', 'co', 'temperatura', 'humedad'],
        default    => ['pm2_5', 'pm10', 'co', 'co2', 'o3', 'no2', 'temperatura', 'humedad'],
    };
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
    $modelos = [];
    foreach (getFakeDispositivos() as $d) {
        $modelos[$d['id_dispositivo']] = $d['modelo'];
    }

    for ($i = 0; $i < $totalPuntos; $i++) {
        $timestamp = $now - ($totalPuntos - $i) * $segundosEntre;
        $hora = (int) (new DateTime('@' . $timestamp))->setTimezone(tzLocal())->format('G');
        $esNoche = $hora < 6 || $hora > 22;
        $esPico = ($hora >= 7 && $hora <= 9) || ($hora >= 17 && $hora <= 20);
        $esMedianoche = $hora >= 0 && $hora <= 4;

        $ruido = fn($max) => rand(-$max, $max) / 10;

        $did = $dispositivosActivos[array_rand($dispositivosActivos)];
        $basePm25 = $esMedianoche ? rand(3, 8) : ($esNoche ? rand(5, 15) : ($esPico ? rand(18, 45) : rand(8, 25)));
        $basePm10 = $basePm25 * (1.5 + rand(0, 10) / 10);

        $valores = [
            'pm2_5'  => round(max(0, $basePm25 + $ruido(30)), 2),
            'pm10'   => round(max(0, $basePm10 + $ruido(50)), 2),
            'co'     => round(max(0, rand(10, 80) / 10 + $ruido(10)), 2),
            'co2'    => round(max(300, rand(350, 1000) + ($esPico ? rand(100, 400) : 0) + $ruido(50)), 2),
            'o3'     => round(max(0, rand(5, 75) + ($esNoche ? -rand(0, 20) : 0) + $ruido(10)), 2),
            'no2'    => round(max(0, rand(5, 70) + ($esPico ? rand(10, 40) : 0) + $ruido(15)), 2),
            'temperatura' => round(rand(160, 340) / 10 + $ruido(5), 2),
            'humedad'     => round(rand(250, 850) / 10 + $ruido(10), 2),
        ];

        // Solo los sensores que trae el modelo; el resto queda en null
        $sensores = getFakeSensoresModelo($modelos[$did] ?? 'AirQ-300');
        foreach ($valores as $param => $v) {
            if (!in_array($param, $sensores, true)) {
                $valores[$param] = null;
            }
        }

        $mediciones[] = array_merge(
            ['id_medicion' => $i + 1, 'id_dispositivo' => $did],
            $valores,
            ['fecha_hora' => gmdate('Y-m-d H:i:s', $timestamp)]
        );
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

    if (empty($filtradas)) {
        return null;
    }

    $ultima = $filtradas[array_key_last($filtradas)];

    foreach (getFakeDispositivos() as $d) {
        if ($d['id_dispositivo'] === $ultima['id_dispositivo']) {
            $ultima['dispositivo_codigo'] = $d['codigo'];
            $ultima['dispositivo_ubicacion'] = $d['ubicacion'];
            foreach (getFakeColegios() as $c) {
                if ($c['id_colegio'] === $d['id_colegio']) {
                    $ultima['colegio_nombre'] = $c['nombre'];
                }
            }
            break;
        }
    }

    return $ultima;
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
    $limite = $intervalo === 'all' ? 0 : ($limites[$intervalo] ?? strtotime('-24 hours'));

    [$fi, $ff] = normalizarRangoFechas($fechaInicio, $fechaFin);

    $result = array_values(array_filter($todas, function ($m) use ($dispositivosIds, $limite, $fi, $ff) {
        if (!in_array($m['id_dispositivo'], $dispositivosIds)) return false;
        if ($fi !== null) {
            return $m['fecha_hora'] >= $fi && $m['fecha_hora'] <= $ff;
        }
        return strtotime($m['fecha_hora'] . ' UTC') >= $limite;
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
