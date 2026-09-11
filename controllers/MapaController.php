<?php

/**
 * Página del mapa de colegios (SIMCA). Los datos los entrega api/mapa.
 */
class MapaController
{
    public function index(): void
    {
        view('header', [
            'titulo'    => 'Mapa de colegios - SIMCA',
            'nav'       => 'mapa',
            'sitio'     => 'simca',
            'navActivo' => 'mapa',
        ]);
        view('mapa', [
            'totalColegios' => Colegio::getTotalColegios(),
        ]);
        view('footer');
    }
}
