<?php

require_once __DIR__ . '/../includes/XlsxBuilder.php';

class ExportController
{
    private const PARAMS = ['pm2_5', 'pm10', 'co', 'co2', 'o3', 'no2', 'temperatura', 'humedad'];

    public function excel(): void
    {
        $colegioId = isset($_GET['colegio']) ? sanitize($_GET['colegio'], 'int') : null;
        $dispositivoId = isset($_GET['dispositivo']) ? sanitize($_GET['dispositivo'], 'int') : null;
        $intervalo = isset($_GET['intervalo']) ? sanitize($_GET['intervalo'], 'string') : '24h';
        $fechaInicio = isset($_GET['fecha_inicio']) ? sanitize($_GET['fecha_inicio'], 'string') : null;
        $fechaFin = isset($_GET['fecha_fin']) ? sanitize($_GET['fecha_fin'], 'string') : null;

        $data = $this->getData($colegioId, $dispositivoId, $intervalo, $fechaInicio, $fechaFin);
        $dispositivos = $this->getDispositivoMap();

        $filtros = $this->describirFiltros($colegioId, $dispositivoId, $intervalo, $fechaInicio, $fechaFin);

        $fileName = 'mediciones_' . date('Y-m-d_H-i') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $this->buildXlsx($data, $dispositivos, $filtros);
        exit;
    }

    private function getData(?int $colegioId, ?int $dispositivoId, string $intervalo, ?string $fechaInicio, ?string $fechaFin): array
    {
        if ($dispositivoId) {
            return Medicion::getHistory($dispositivoId, $intervalo, $fechaInicio, $fechaFin);
        }

        $disps = $colegioId ? Dispositivo::getByColegio($colegioId) : Dispositivo::getAll();
        $todas = [];
        foreach ($disps as $d) {
            $rows = Medicion::getHistory($d['id_dispositivo'], $intervalo, $fechaInicio, $fechaFin);
            foreach ($rows as $r) {
                $todas[] = $r;
            }
        }
        usort($todas, fn($a, $b) => strtotime($a['fecha_hora']) - strtotime($b['fecha_hora']));
        return $todas;
    }

    private function getDispositivoMap(): array
    {
        $map = [];
        foreach (Dispositivo::getAll() as $d) {
            $map[$d['id_dispositivo']] = $d;
        }
        return $map;
    }

    private function describirFiltros(?int $colegioId, ?int $dispositivoId, string $intervalo, ?string $fechaInicio, ?string $fechaFin): array
    {
        $colegio = $colegioId ? Colegio::getById($colegioId) : null;
        $dispositivo = $dispositivoId ? Dispositivo::getById($dispositivoId) : null;

        if ($fechaInicio && $fechaFin) {
            $rango = formatDate($fechaInicio, 'd/m/Y') . ' – ' . formatDate($fechaFin, 'd/m/Y');
        } else {
            $rango = match ($intervalo) {
                '7d'  => 'Últimos 7 días',
                '30d' => 'Últimos 30 días',
                default => 'Últimas 24 horas',
            };
        }

        return [
            'colegio'     => $colegio['nombre'] ?? 'Todos los colegios',
            'dispositivo' => $dispositivo
                ? $dispositivo['codigo'] . ' – ' . $dispositivo['modelo'] . ' (' . $dispositivo['ubicacion'] . ')'
                : 'Todos los dispositivos',
            'rango'       => $rango,
            'generado'    => date('d/m/Y H:i'),
        ];
    }

    private function buildXlsx(array $data, array $dispositivos, array $filtros): string
    {
        $builder = new XlsxBuilder('Mediciones');
        $builder->setColWidths([10, 22, 12, 12, 10, 12, 10, 10, 12, 10, 10]);

        $builder->addRow([['v' => 'Reporte de Mediciones – Calidad del Aire', 's' => 1]]);

        $builder->addRow([
            ['v' => 'Colegio: ' . $filtros['colegio'], 's' => 2],
            ['v' => 'Dispositivo: ' . $filtros['dispositivo'], 's' => 2],
            ['v' => 'Rango: ' . $filtros['rango'], 's' => 2],
        ]);

        $builder->addRow([['v' => 'Generado: ' . $filtros['generado'], 's' => 2]]);

        $builder->addRow([]);

        $headers = ['#', 'Fecha / Hora', 'PM2.5 (µg/m³)', 'PM10 (µg/m³)', 'CO (ppm)', 'CO2 (ppm)', 'O₃ (ppb)', 'NO₂ (ppb)', 'Temperatura (°C)', 'Humedad (%)'];
        if ($this->hasMultipleDevices($data)) {
            array_splice($headers, 1, 0, ['Dispositivo']);
        }
        $builder->addRow(array_map(fn($h) => ['v' => $h, 's' => 3], $headers));

        $idx = 1;
        $lastDevice = null;
        foreach ($data as $row) {
            $did = (int) $row['id_dispositivo'];

            if ($this->hasMultipleDevices($data) && $did !== $lastDevice) {
                if ($lastDevice !== null) {
                    $builder->addRow([]);
                }
                $disp = $dispositivos[$did] ?? [];
                $etiqueta = ($disp['codigo'] ?? 'SNS-' . str_pad((string) $did, 3, '0', STR_PAD_LEFT))
                    . ' – ' . ($disp['modelo'] ?? 'AirQ-200') . ' (' . ($disp['ubicacion'] ?? '') . ')';
                $builder->addRow([['v' => 'Dispositivo: ' . $etiqueta, 's' => 4]]);
                $lastDevice = $did;
            }

            $cells = [
                ['v' => (string) $idx, 's' => 5],
                ['v' => formatDate($row['fecha_hora'] ?? null, 'Y-m-d H:i:s'), 's' => 6],
            ];
            if ($this->hasMultipleDevices($data)) {
                array_splice($cells, 1, 0, [['v' => ($dispositivos[$did]['codigo'] ?? ''), 's' => 6]]);
            }
            foreach (self::PARAMS as $p) {
                $val = $row[$p] ?? null;
                $cells[] = $val !== null && $val !== ''
                    ? ['v' => (string) $val, 's' => 5]
                    : ['v' => '', 's' => 6];
            }

            $builder->addRow($cells);
            $idx++;
        }

        return $builder->output();
    }

    private function hasMultipleDevices(array $data): bool
    {
        $ids = array_unique(array_map(fn($r) => (int) $r['id_dispositivo'], $data));
        return count($ids) > 1;
    }
}
