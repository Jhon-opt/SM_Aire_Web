<?php

/**
 * Recepción de mediciones de los nodos (POST /api/ingest).
 *
 * Además de las variables ambientales acepta metadatos de calidad del dato,
 * todos opcionales para no romper los nodos con firmware anterior (la guía de
 * aseguramiento de calidad lo llama tolerancia de versiones, paso S.6):
 *   n_muestras ....... lecturas válidas que sustentan el promedio del minuto
 *   measured_at ...... hora de captura en el nodo (epoch UTC, por NTP)
 *   firmware_version . versión del programa cargado en el nodo
 *   r0_mq7 / calibrado_en ... calibración vigente del sensor de CO
 *
 * Los tres últimos describen al dispositivo, no a la medición, así que se
 * guardan en la tabla dispositivo y no se repiten en cada fila.
 */
class IngestController
{
    /** Margen aceptado hacia el futuro para la hora del nodo (reloj adelantado). */
    private const MARGEN_FUTURO_SEG = 300;

    /** Antigüedad máxima de la hora de una medición (evita relojes disparatados). */
    private const ANTIGUEDAD_MAXIMA_SEG = 604800;        // 7 días

    /** Antigüedad máxima de una fecha de calibración (puede ser de hace meses). */
    private const ANTIGUEDAD_CALIBRACION_SEG = 157680000; // 5 años

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

        // Metadatos de calidad; si el nodo no los envía, quedan en null.
        $extra = [];

        $nMuestras = $this->enteroPositivo($data['n_muestras'] ?? null, 65535);
        if ($nMuestras !== null) {
            $extra['n_muestras'] = $nMuestras;
        }

        $medidoEn = $this->fechaDelNodo($data['measured_at'] ?? null);
        if ($medidoEn !== null) {
            $extra['medido_en'] = $medidoEn;
        }

        try {
            $id = Database::insert('medicion', array_merge(
                ['id_dispositivo' => (int) $dispositivo['id_dispositivo']],
                $metricas,
                $extra,
                ['fecha_hora' => gmdate('Y-m-d H:i:s')] // siempre UTC
            ));
        } catch (PDOException $e) {
            error_log('Ingest insert error: ' . $e->getMessage());
            jsonResponse(['ok' => false, 'error' => 'Error de base de datos'], 500);
            return;
        }

        // Estado del nodo. Va después de guardar la medición y con su propio
        // try/catch: un fallo aquí (p. ej. migración sin aplicar) no debe
        // impedir que la medición se dé por recibida.
        $this->actualizarEstadoDispositivo((int) $dispositivo['id_dispositivo'], $data);

        // Se devuelven el dispositivo y el colegio para que quien instala el nodo
        // pueda confirmar en el monitor serie a qué institución está enviando.
        jsonResponse([
            'ok'          => true,
            'id'          => (int) $id,
            'dispositivo' => $dispositivo['codigo'] ?? null,
            'colegio'     => $dispositivo['colegio_nombre'] ?? null,
        ], 201);
    }

    /** Entero entre 0 y $max, o null si el valor no sirve. */
    private function enteroPositivo(mixed $valor, int $max): ?int
    {
        if ($valor === null || $valor === '' || !is_numeric($valor)) {
            return null;
        }
        $n = (int) $valor;
        return ($n >= 0 && $n <= $max) ? $n : null;
    }

    /**
     * Convierte la hora del nodo (epoch UTC) a 'Y-m-d H:i:s'.
     * Descarta relojes sin sincronizar o absurdos: si el valor no es creíble
     * se devuelve null y queda como referencia la hora de recepción.
     */
    private function fechaDelNodo(mixed $epoch, ?int $antiguedadMaxima = null): ?string
    {
        if ($epoch === null || $epoch === '' || !is_numeric($epoch)) {
            return null;
        }

        $t = (int) $epoch;
        $ahora = time();
        $limite = $antiguedadMaxima ?? self::ANTIGUEDAD_MAXIMA_SEG;

        if ($t > $ahora + self::MARGEN_FUTURO_SEG) return null;
        if ($t < $ahora - $limite) return null;

        return gmdate('Y-m-d H:i:s', $t);
    }

    /**
     * Guarda en el dispositivo la versión de firmware, la calibración vigente
     * del MQ-7 y el momento de la última conexión.
     */
    private function actualizarEstadoDispositivo(int $idDispositivo, array $data): void
    {
        $campos = ['ultima_conexion = ?'];
        $params = [gmdate('Y-m-d H:i:s')];

        $version = isset($data['firmware_version']) ? trim((string) $data['firmware_version']) : '';
        if ($version !== '') {
            $campos[] = 'firmware_version = ?';
            $params[] = mb_substr($version, 0, 20);
        }

        if (isset($data['r0_mq7']) && is_numeric($data['r0_mq7'])) {
            $r0 = (float) $data['r0_mq7'];
            if ($r0 > 0 && $r0 < 100000) {
                $campos[] = 'r0_mq7 = ?';
                $params[] = round($r0, 3);
            }
        }

        $calibrado = $this->fechaDelNodo($data['calibrado_en'] ?? null, self::ANTIGUEDAD_CALIBRACION_SEG);
        if ($calibrado !== null) {
            $campos[] = 'calibrado_en = ?';
            $params[] = $calibrado;
        }

        $params[] = $idDispositivo;

        try {
            Database::query(
                'UPDATE dispositivo SET ' . implode(', ', $campos) . ' WHERE id_dispositivo = ?',
                $params
            );
        } catch (PDOException $e) {
            // Columnas ausentes (migración no aplicada): no afecta a la medición.
            error_log('Ingest estado dispositivo: ' . $e->getMessage());
        }
    }
}
