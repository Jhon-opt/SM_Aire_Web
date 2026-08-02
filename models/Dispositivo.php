<?php

class Dispositivo
{
    public static function getAll(): array
    {
        if (FAKE_MODE) {
            $result = getFakeDispositivos();
            usort($result, fn($a, $b) => strcmp($a['codigo'], $b['codigo']));
            return $result;
        }
        return Database::fetchAll('SELECT * FROM dispositivo ORDER BY codigo');
    }

    public static function getById(int $id): ?array
    {
        if (FAKE_MODE) {
            $data = array_filter(getFakeDispositivos(), fn($d) => $d['id_dispositivo'] === $id);
            return !empty($data) ? reset($data) : null;
        }
        return Database::fetchOne('SELECT * FROM dispositivo WHERE id_dispositivo = ?', [$id]);
    }

    public static function getByColegio(int $idColegio): array
    {
        if (FAKE_MODE) {
            $result = array_values(array_filter(
                getFakeDispositivos(),
                fn($d) => $d['id_colegio'] === $idColegio
            ));
            usort($result, fn($a, $b) => strcmp($a['codigo'], $b['codigo']));
            return $result;
        }
        return Database::fetchAll(
            'SELECT * FROM dispositivo WHERE id_colegio = ? ORDER BY codigo',
            [$idColegio]
        );
    }

    public static function getEstadoCounts(): array
    {
        if (FAKE_MODE) {
            $disps = getFakeDispositivos();
            $result = ['activo' => 0, 'inactivo' => 0, 'mantenimiento' => 0];
            foreach ($disps as $d) {
                $result[$d['estado']] = ($result[$d['estado']] ?? 0) + 1;
            }
            return $result;
        }
        $rows = Database::fetchAll(
            "SELECT estado, COUNT(*) as total FROM dispositivo GROUP BY estado"
        );
        $result = ['activo' => 0, 'inactivo' => 0, 'mantenimiento' => 0];
        foreach ($rows as $row) {
            $result[$row['estado']] = (int) $row['total'];
        }
        return $result;
    }
}
