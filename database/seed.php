<?php
/**
 * Script de siembra de datos de prueba.
 *
 * Uso: php database/seed.php
 *
 * Genera colegios, dispositivos y mediciones simuladas.
 * Se puede ejecutar múltiples veces (trunca antes de insertar).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "🌱 Sembrando datos de prueba...\n";

// ── Colegios ──────────────────────────────────────────────
$colegios = [
    ['nombre' => 'Colegio San José',       'direccion' => 'Av. Principal 123',           'ciudad' => 'Lima'],
    ['nombre' => 'Colegio Santa María',    'direccion' => 'Jr. Las Flores 456',          'ciudad' => 'Arequipa'],
    ['nombre' => 'Colegio Alexander von Humboldt',
                                             'direccion' => 'Calle Los Olivos 789',       'ciudad' => 'Cusco'],
    ['nombre' => 'Colegio Sagrados Corazones',
                                             'direccion' => 'Av. La Marina 321',          'ciudad' => 'Trujillo'],
    ['nombre' => 'Colegio Euroamericano',  'direccion' => 'Pasaje El Sol 654',           'ciudad' => 'Piura'],
];

$colegioIds = [];
foreach ($colegios as $c) {
    $id = Database::insert('colegio', $c);
    $colegioIds[] = $id;
    echo "  + Colegio '{$c['nombre']}' (ID {$id})\n";
}

// ── Dispositivos ──────────────────────────────────────────
$modelos = ['AirQ-100', 'AirQ-200', 'AirQ-300'];
$ubicaciones = ['Azotea', 'Patio principal', 'Aula 101', 'Biblioteca', 'Laboratorio', 'Gimnasio', 'Comedor'];
$dispositivoIds = [];

$contador = 1;
foreach ($colegioIds as $cid) {
    $numDisps = rand(2, 3);
    for ($i = 0; $i < $numDisps; $i++) {
        $codigo = sprintf('SNS-%03d', $contador);
        $data = [
            'codigo'            => $codigo,
            'modelo'            => $modelos[array_rand($modelos)],
            'ubicacion'         => $ubicaciones[array_rand($ubicaciones)],
            'estado'            => rand(1, 10) > 2 ? 'activo' : (rand(0, 1) ? 'inactivo' : 'mantenimiento'),
            'fecha_instalacion' => date('Y-m-d H:i:s', strtotime('-' . rand(30, 365) . ' days')),
            'id_colegio'        => $cid,
        ];
        $id = Database::insert('dispositivo', $data);
        $dispositivoIds[] = $id;
        echo "  + Dispositivo '{$codigo}' (ID {$id}) en colegio ID {$cid}\n";
        $contador++;
    }
}

// ── Mediciones ────────────────────────────────────────────
echo "\n  Generando mediciones...\n";

$now = time();
$totalMediciones = 0;
$batchSize = 500;

foreach ($dispositivoIds as $did) {
    $dias = rand(7, 30);
    $intervalo = rand(5, 15); // minutos entre mediciones
    $numMediciones = intdiv($dias * 24 * 60, $intervalo);

    echo "  → Dispositivo ID {$did}: {$numMediciones} mediciones en {$dias} días\n";

    $batch = [];

    for ($m = 0; $m < $numMediciones; $m++) {
        $timestamp = $now - ($numMediciones - $m) * $intervalo * 60;
        $hora = (int) date('G', $timestamp);
        $esNoche = $hora < 6 || $hora > 22;
        $esPico = $hora >= 7 && $hora <= 9 || $hora >= 17 && $hora <= 20;

        $basePm25 = $esNoche ? rand(5, 15) : ($esPico ? rand(15, 40) : rand(8, 25));
        $basePm10 = $basePm25 * rand(15, 25) / 10;

        $batch[] = [
            'id_dispositivo' => $did,
            'pm2_5'  => round($basePm25 + (rand(-30, 30) / 10), 2),
            'pm10'   => round($basePm10 + (rand(-50, 50) / 10), 2),
            'co'     => round(rand(10, 80) / 10, 2),
            'co2'    => round(rand(350, 1200) + ($esPico ? rand(100, 400) : 0), 2),
            'o3'     => round(rand(0, 80) + ($esNoche ? -rand(0, 20) : 0), 2),
            'no2'    => round(rand(5, 80) + ($esPico ? rand(10, 30) : 0), 2),
            'temperatura' => round(rand(180, 320) / 10, 2),
            'humedad'     => round(rand(300, 800) / 10, 2),
            'fecha_hora'  => date('Y-m-d H:i:s', $timestamp),
        ];

        if (count($batch) >= $batchSize) {
            insertBatch($batch);
            $totalMediciones += count($batch);
            $batch = [];
        }
    }

    if (!empty($batch)) {
        insertBatch($batch);
        $totalMediciones += count($batch);
    }
}

echo "\n✅ Siembra completada.\n";
echo "   Colegios:     " . count($colegios) . "\n";
echo "   Dispositivos: " . count($dispositivoIds) . "\n";
echo "   Mediciones:   {$totalMediciones}\n";

// ── Helper ────────────────────────────────────────────────
function insertBatch(array $rows): void
{
    if (empty($rows)) return;

    $columns = implode(', ', array_keys($rows[0]));
    $placeholders = '(' . implode(', ', array_fill(0, count($rows[0]), '?')) . ')';
    $values = [];
    $sql = "INSERT INTO medicion ({$columns}) VALUES ";

    $parts = [];
    foreach ($rows as $row) {
        $parts[] = $placeholders;
        $values = array_merge($values, array_values($row));
    }

    $sql .= implode(', ', $parts);

    Database::query($sql, $values);
}
