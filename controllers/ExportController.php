<?php

class ExportController
{
    public function csv(): void
    {
        $colegioId = isset($_GET['colegio']) ? sanitize($_GET['colegio'], 'int') : null;
        $dispositivoId = isset($_GET['dispositivo']) ? sanitize($_GET['dispositivo'], 'int') : null;
        $intervalo = isset($_GET['intervalo']) ? sanitize($_GET['intervalo'], 'string') : '24h';
        $fechaInicio = isset($_GET['fecha_inicio']) ? sanitize($_GET['fecha_inicio'], 'string') : null;
        $fechaFin = isset($_GET['fecha_fin']) ? sanitize($_GET['fecha_fin'], 'string') : null;

        if ($dispositivoId) {
            $data = Medicion::getHistory($dispositivoId, $intervalo, $fechaInicio, $fechaFin);
        } elseif ($colegioId) {
            $disps = Dispositivo::getByColegio($colegioId);
            $ids = array_map(fn($d) => (int) $d['id_dispositivo'], $disps);
            $todas = [];
            foreach ($ids as $did) {
                $todas = array_merge($todas, Medicion::getHistory($did, $intervalo, $fechaInicio, $fechaFin));
            }
            usort($todas, fn($a, $b) => strtotime($a['fecha_hora']) - strtotime($b['fecha_hora']));
            $data = $todas;
        } else {
            $disps = Dispositivo::getAll();
            $ids = array_map(fn($d) => (int) $d['id_dispositivo'], $disps);
            $todas = [];
            foreach ($ids as $did) {
                $todas = array_merge($todas, Medicion::getHistory($did, $intervalo, $fechaInicio, $fechaFin));
            }
            usort($todas, fn($a, $b) => strtotime($a['fecha_hora']) - strtotime($b['fecha_hora']));
            $data = $todas;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="mediciones_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        fprintf($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            'ID', 'Dispositivo', 'PM2.5 (µg/m³)', 'PM10 (µg/m³)',
            'CO (ppm)', 'CO2 (ppm)', 'O3 (ppb)', 'NO2 (ppb)',
            'Temperatura (°C)', 'Humedad (%)', 'Fecha/Hora',
        ]);

        foreach ($data as $row) {
            fputcsv($output, [
                $row['id_medicion'],
                $row['id_dispositivo'],
                $row['pm2_5'] ?? '',
                $row['pm10'] ?? '',
                $row['co'] ?? '',
                $row['co2'] ?? '',
                $row['o3'] ?? '',
                $row['no2'] ?? '',
                $row['temperatura'] ?? '',
                $row['humedad'] ?? '',
                $row['fecha_hora'],
            ]);
        }

        fclose($output);
        exit;
    }
}
