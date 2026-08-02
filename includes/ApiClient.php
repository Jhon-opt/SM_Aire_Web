<?php

class ApiClient
{
    public static function getColegios(): array
    {
        return self::request('/api/colegios');
    }

    public static function getColegio(int $id): ?array
    {
        try {
            return self::request('/api/colegios/' . $id);
        } catch (ApiNotFoundException) {
            return null;
        }
    }

    public static function getDispositivos(): array
    {
        return self::request('/api/dispositivos');
    }

    public static function getDispositivo(int $id): ?array
    {
        try {
            return self::request('/api/dispositivos/' . $id);
        } catch (ApiNotFoundException) {
            return null;
        }
    }

    public static function getMediciones(array $query = []): array
    {
        return self::request('/api/mediciones', $query);
    }

    public static function getUltimaMedicion(int $dispositivoId): ?array
    {
        try {
            return self::request('/api/mediciones/ultimas/' . $dispositivoId);
        } catch (ApiNotFoundException) {
            return null;
        }
    }

    public static function getAllMediciones(array $query, int $maxRows = 50000): array
    {
        $rows = [];
        $limit = 1000;
        $offset = 0;

        while (true) {
            $res = self::getMediciones(array_merge($query, ['limit' => $limit, 'offset' => $offset]));
            $batch = $res['data'] ?? [];
            $rows = array_merge($rows, $batch);
            $total = (int) ($res['total'] ?? 0);
            $offset += $limit;

            if (empty($batch) || $offset >= $total || count($rows) >= $maxRows) {
                break;
            }
        }

        return array_slice($rows, 0, $maxRows);
    }

    public static function buildRange(?string $intervalo, ?string $fechaInicio, ?string $fechaFin): array
    {
        if ($fechaInicio && $fechaFin) {
            $desde = self::toIso($fechaInicio . ' 00:00:00');
            $hasta = self::toIso($fechaFin . ' 23:59:59');
            return [$desde, $hasta];
        }

        $horas = match ($intervalo) {
            '7d'  => 7 * 24,
            '30d' => 30 * 24,
            default => 24,
        };

        return [self::toIso(date('Y-m-d H:i:s', time() - $horas * 3600)), null];
    }

    public static function normalizeColegio(array $row): array
    {
        return [
            'id_colegio' => (int) ($row['id_colegio'] ?? 0),
            'nombre'     => $row['nombre'] ?? '',
            'direccion'  => $row['direccion'] ?? null,
            'ciudad'     => $row['ciudad'] ?? null,
        ];
    }

    public static function normalizeDispositivo(array $row): array
    {
        $id = (int) ($row['id_dispositivo'] ?? 0);

        return [
            'id_dispositivo'    => $id,
            'codigo'            => $row['codigo'] ?? 'SNS-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT),
            'modelo'            => $row['modelo'] ?? 'AirQ-200',
            'ubicacion'         => $row['ubicacion'] ?? '',
            'estado'            => $row['estado'] ?? 'activo',
            'fecha_instalacion' => $row['fecha_instalacion'] ? self::dt($row['fecha_instalacion']) : null,
            'id_colegio'        => (int) ($row['id_colegio'] ?? 0),
            'colegio_nombre'    => $row['colegio_nombre'] ?? null,
            'ciudad'            => $row['ciudad'] ?? null,
        ];
    }

    public static function normalizeMedicion(array $row): array
    {
        return [
            'id_medicion'    => (int) ($row['id'] ?? 0),
            'id_dispositivo' => (int) ($row['id_dispositivo'] ?? 0),
            'pm2_5'          => $row['pm2_5'] ?? null,
            'pm10'           => $row['pm_10'] ?? null,
            'co'             => $row['co'] ?? null,
            'co2'            => null,
            'o3'             => $row['o3'] ?? null,
            'no2'            => $row['no2'] ?? null,
            'temperatura'    => $row['temperatura'] ?? null,
            'humedad'        => $row['humedad'] ?? null,
            'fecha_hora'     => $row['fecha_hora'] ? self::dt($row['fecha_hora']) : null,
        ];
    }

    public static function dt(string $iso): string
    {
        $ts = strtotime($iso);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : $iso;
    }

    private static function toIso(string $dateTime): string
    {
        $ts = strtotime($dateTime);
        return $ts !== false ? gmdate('Y-m-d\TH:i:s\Z', $ts) : $dateTime;
    }

    private static function request(string $path, array $query = []): array
    {
        $url = API_URL . $path;
        $query = array_filter($query, fn($v) => $v !== null && $v !== '');

        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 30,
                'header'  => "Accept: application/json\r\n",
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);

        if ($body === false) {
            throw new RuntimeException('No se pudo conectar con la API en ' . $url);
        }

        $statusLine = $http_response_header[0] ?? '';
        preg_match('#HTTP/\S+\s(\d+)#', $statusLine, $m);
        $code = isset($m[1]) ? (int) $m[1] : 200;

        $data = json_decode($body, true);

        if ($code === 404) {
            throw new ApiNotFoundException('Recurso no encontrado en la API');
        }

        if ($code >= 400) {
            $msg = is_array($data) && isset($data['error']) ? $data['error'] : ('HTTP ' . $code);
            throw new RuntimeException('Error de la API: ' . $msg);
        }

        return is_array($data) ? $data : [];
    }
}

class ApiNotFoundException extends Exception
{
}
