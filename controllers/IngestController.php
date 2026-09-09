<?php

class IngestController
{
    private const METRICAS = ['pm2_5', 'pm10', 'co', 'co2', 'o3', 'no2', 'temperatura', 'humedad'];

    private const RANGOS = [
        'pm2_5'       => [0, 1000],
        'pm10'        => [0, 2000],
        'co'          => [0, 1000],
        'co2'         => [0, 10000],
        'o3'          => [0, 2000],
        'no2'         => [0, 2000],
        'temperatura' => [-50, 70],
        'humedad'     => [0, 100],
    ];

    public function store(): void
    {
        if (APP_MODE !== 'db') {
            jsonResponse(['ok' => false, 'error' => 'Ingesta disponible solo en modo db'], 503);
            return;
        }

        $body = file_get_contents('php://input');
        $data = json_decode($body ?: '', true);

        if (!is_array($data)) {
            jsonResponse(['ok' => false, 'error' => 'JSON inválido'], 400);
            return;
        }

        $deviceKey = isset($data['device_key']) ? trim((string) $data['device_key']) : '';
        if ($deviceKey === '') {
            jsonResponse(['ok' => false, 'error' => 'Falta device_key'], 401);
            return;
        }

        try {
            $dispositivo = Dispositivo::getByApiKey($deviceKey);
        } catch (PDOException $e) {
            error_log('Ingest DB error: ' . $e->getMessage());
            jsonResponse(['ok' => false, 'error' => 'Error de base de datos'], 500);
            return;
        }

        if ($dispositivo === null) {
            jsonResponse(['ok' => false, 'error' => 'device_key inválida'], 401);
            return;
        }

        $metricas = [];
        foreach (self::METRICAS as $campo) {
            if (!array_key_exists($campo, $data) || $data[$campo] === null || $data[$campo] === '') {
                $metricas[$campo] = null;
                continue;
            }
            if (!is_numeric($data[$campo])) {
                jsonResponse(['ok' => false, 'error' => "Valor no numérico en {$campo}"], 400);
                return;
            }
            $valor = (float) $data[$campo];
            [$min, $max] = self::RANGOS[$campo];
            if ($valor < $min || $valor > $max) {
                jsonResponse(['ok' => false, 'error' => "Valor fuera de rango en {$campo}"], 400);
                return;
            }
            $metricas[$campo] = $valor;
        }

        if (count(array_filter($metricas, fn($v) => $v !== null)) === 0) {
            jsonResponse(['ok' => false, 'error' => 'Sin métricas para guardar'], 400);
            return;
        }

        try {
            $id = Database::insert('medicion', array_merge(
                ['id_dispositivo' => (int) $dispositivo['id_dispositivo']],
                $metricas,
                ['fecha_hora' => date('Y-m-d H:i:s')]
            ));
        } catch (PDOException $e) {
            error_log('Ingest insert error: ' . $e->getMessage());
            jsonResponse(['ok' => false, 'error' => 'Error de base de datos'], 500);
            return;
        }

        jsonResponse(['ok' => true, 'id' => (int) $id], 201);
    }
}
