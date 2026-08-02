<?php

class DashboardController
{
    public function index(): void
    {
        $totalColegios = Colegio::getTotalColegios();
        $totalSensores = Colegio::getTotalSensores();
        $totalMediciones = Colegio::getTotalMediciones();
        $ultimaActualizacion = Colegio::getUltimaActualizacion();
        $colegios = Colegio::getAll();
        $estados = Dispositivo::getEstadoCounts();

        view('header', ['titulo' => 'Dashboard - Monitoreo de Calidad del Aire']);
        view('dashboard', [
            'totalColegios'       => $totalColegios,
            'totalSensores'       => $totalSensores,
            'totalMediciones'     => $totalMediciones,
            'ultimaActualizacion' => $ultimaActualizacion,
            'colegios'            => $colegios,
            'estados'             => $estados,
        ]);
        view('footer');
    }
}
