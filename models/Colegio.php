<?php

class Colegio
{
    public static function getAll(): array
    {
        if (API_MODE) {
            return array_map([ApiClient::class, 'normalizeColegio'], ApiClient::getColegios());
        }
        if (FAKE_MODE) {
            return getFakeColegios();
        }
        return Database::fetchAll('SELECT * FROM colegio ORDER BY nombre');
    }

    public static function getById(int $id): ?array
    {
        if (API_MODE) {
            $colegio = ApiClient::getColegio($id);
            return $colegio ? ApiClient::normalizeColegio($colegio) : null;
        }
        if (FAKE_MODE) {
            $data = array_filter(getFakeColegios(), fn($c) => $c['id_colegio'] === $id);
            return !empty($data) ? reset($data) : null;
        }
        return Database::fetchOne('SELECT * FROM colegio WHERE id_colegio = ?', [$id]);
    }

    public static function getDispositivos(int $id): array
    {
        if (API_MODE) {
            $colegio = ApiClient::getColegio($id);
            $disps = $colegio['dispositivos'] ?? [];
            return array_map([ApiClient::class, 'normalizeDispositivo'], $disps);
        }
        if (FAKE_MODE) {
            return array_values(array_filter(getFakeDispositivos(), fn($d) => $d['id_colegio'] === $id));
        }
        return Database::fetchAll(
            'SELECT d.*, d.id_dispositivo AS id
             FROM dispositivo d
             WHERE d.id_colegio = ?
             ORDER BY d.codigo',
            [$id]
        );
    }

    public static function getTotalSensores(): int
    {
        if (API_MODE) {
            $disps = Dispositivo::getAll();
            return count(array_filter($disps, fn($d) => $d['estado'] === 'activo'));
        }
        if (FAKE_MODE) {
            return count(array_filter(getFakeDispositivos(), fn($d) => $d['estado'] === 'activo'));
        }
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM dispositivo WHERE estado = ?', ['activo']);
    }

    public static function getTotalColegios(): int
    {
        if (API_MODE) {
            return count(self::getAll());
        }
        if (FAKE_MODE) {
            return count(getFakeColegios());
        }
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM colegio');
    }

    public static function getTotalMediciones(): int
    {
        if (API_MODE) {
            $res = ApiClient::getMediciones(['limit' => 1]);
            return (int) ($res['total'] ?? 0);
        }
        if (FAKE_MODE) {
            return count(getFakeMediciones());
        }
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM medicion');
    }

    public static function getUltimaActualizacion(): ?string
    {
        if (API_MODE) {
            $res = ApiClient::getMediciones(['limit' => 1]);
            $data = $res['data'] ?? [];
            return !empty($data) ? ApiClient::dt($data[0]['fecha_hora']) : null;
        }
        if (FAKE_MODE) {
            $mediciones = getFakeMediciones();
            if (empty($mediciones)) return null;
            $ultima = end($mediciones);
            return $ultima['fecha_hora'];
        }
        return Database::fetchColumn('SELECT MAX(fecha_hora) FROM medicion');
    }
}
