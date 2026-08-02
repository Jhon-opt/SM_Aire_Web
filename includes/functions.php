<?php

function sanitize(mixed $value, string $type = 'string'): mixed
{
    if ($value === null || $value === '') {
        return null;
    }

    return match ($type) {
        'int'    => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
        'float'  => filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
        'string' => htmlspecialchars(strip_tags(trim((string) $value)), ENT_QUOTES, 'UTF-8'),
        default => htmlspecialchars(strip_tags(trim((string) $value)), ENT_QUOTES, 'UTF-8'),
    };
}

function jsonResponse(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getQualityLevel(string $parametro, float $valor): string
{
    $limites = [
        'pm2_5'  => [12, 35],
        'pm10'   => [54, 154],
        'co'     => [4.4, 9.4],
        'co2'    => [800, 1200],
        'o3'     => [54, 70],
        'no2'    => [53, 100],
        'temperatura' => [15, 30, 35],
        'humedad'     => [30, 60],
    ];

    if (!isset($limites[$parametro])) {
        return 'bueno';
    }

    $lim = $limites[$parametro];

    if ($parametro === 'temperatura') {
        if ($valor >= 15 && $valor <= 30) return 'bueno';
        if (($valor >= 10 && $valor < 15) || ($valor > 30 && $valor <= 35)) return 'moderado';
        return 'malo';
    }

    if ($parametro === 'humedad') {
        if ($valor >= 30 && $valor <= 60) return 'bueno';
        if (($valor >= 20 && $valor < 30) || ($valor > 60 && $valor <= 80)) return 'moderado';
        return 'malo';
    }

    if ($valor <= $lim[0]) return 'bueno';
    if ($valor <= $lim[1]) return 'moderado';
    return 'malo';
}

function getQualityColor(string $level): string
{
    return match ($level) {
        'bueno'    => '#10B981',
        'moderado' => '#F59E0B',
        'malo'     => '#EF4444',
        default    => '#6B7280',
    };
}

function formatDate(?string $date, string $format = 'd/m/Y H:i'): string
{
    if (!$date) return '—';
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date)
        ?: DateTime::createFromFormat('Y-m-d', $date);
    return $dt ? $dt->format($format) : $date;
}

function relativeTime(string $date): string
{
    $now = new DateTime();
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date);
    if (!$dt) return $date;

    $diff = $now->getTimestamp() - $dt->getTimestamp();

    if ($diff < 60) return 'hace ' . $diff . ' segundos';
    if ($diff < 3600) return 'hace ' . floor($diff / 60) . ' minutos';
    if ($diff < 86400) return 'hace ' . floor($diff / 3600) . ' horas';
    return 'hace ' . floor($diff / 86400) . ' días';
}

function getParametroUnidad(string $parametro): string
{
    $unidades = [
        'pm2_5'  => 'µg/m³',
        'pm10'   => 'µg/m³',
        'co'     => 'ppm',
        'co2'    => 'ppm',
        'o3'     => 'ppb',
        'no2'    => 'ppb',
        'temperatura' => '°C',
        'humedad'     => '%',
    ];

    return $unidades[$parametro] ?? '';
}

function getParametroNombre(string $parametro): string
{
    $nombres = [
        'pm2_5'  => 'PM2.5',
        'pm10'   => 'PM10',
        'co'     => 'CO',
        'co2'    => 'CO2',
        'o3'     => 'O₃',
        'no2'    => 'NO₂',
        'temperatura' => 'Temperatura',
        'humedad'     => 'Humedad',
    ];

    return $nombres[$parametro] ?? $parametro;
}

function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

function view(string $name, array $data = []): void
{
    extract($data);
    $path = BASE_PATH . '/views/' . $name . '.php';
    if (file_exists($path)) {
        include $path;
    }
}
