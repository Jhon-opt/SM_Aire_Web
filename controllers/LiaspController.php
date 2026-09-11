<?php

/**
 * Portal del Laboratorio Urbano de Inteligencia Ambiental y Salud Pública (LIASP-CB).
 * Página de inicio del sitio; enlaza con el proyecto SIMCA (dashboard de monitoreo).
 *
 * Todo el texto del portal se edita en $contenido (más abajo).
 */
class LiaspController
{
    public function index(): void
    {
        $contenido = [
            'lema' => 'Ciencia de datos, sensores de bajo costo y salud pública para entender el aire que respiramos.',

            'descripcion' => [
                'El Laboratorio Urbano de Inteligencia Ambiental y Salud Pública (LIASP-CB) de la Universidad Distrital '
                . 'Francisco José de Caldas integra sensores de bajo costo, ciencia de datos y salud pública para observar, '
                . 'comprender y mejorar el ambiente urbano.',
                'Trabajamos con instituciones educativas y comunidades para generar información abierta y en tiempo real '
                . 'que apoye la toma de decisiones sobre calidad del aire y salud.',
            ],

            'destacados' => [
                ['icono' => 'fa-microchip',      'titulo' => 'Sensores de bajo costo', 'texto' => 'Dispositivos IoT diseñados y calibrados por el laboratorio.'],
                ['icono' => 'fa-chart-line',     'titulo' => 'Datos abiertos',         'texto' => 'Mediciones en tiempo real, históricos y exportación para investigación.'],
                ['icono' => 'fa-heart-pulse',    'titulo' => 'Salud pública',          'texto' => 'Índices y recomendaciones basados en la normativa colombiana (ICA).'],
            ],

            // Las 4 tarjetas de "Líneas de trabajo" (edítalas libremente)
            'lineas' => [
                ['icono' => 'fa-wind',            'titulo' => 'Calidad del aire urbano',     'texto' => 'Monitoreo de material particulado y gases con redes de sensores de bajo costo.'],
                ['icono' => 'fa-heart-pulse',     'titulo' => 'Salud pública y ambiente',   'texto' => 'Relación entre exposición ambiental y salud en entornos escolares y comunitarios.'],
                ['icono' => 'fa-brain',           'titulo' => 'Inteligencia ambiental',     'texto' => 'Analítica de datos, calibración de sensores y modelos para la toma de decisiones.'],
                ['icono' => 'fa-people-group',    'titulo' => 'Ciudad y comunidad',         'texto' => 'Apropiación social del conocimiento con colegios, barrios y entidades públicas.'],
            ],

            'proyectos' => [
                [
                    'sigla'  => 'SIMCA',
                    'nombre' => 'Sistema Inteligente de Monitoreo de Calidad del Aire basado en sensores de bajo costo',
                    'texto'  => 'Red de sensores instalados en colegios que mide PM2.5, PM10 y CO, con un panel de monitoreo en tiempo real, estadísticas y exportación de datos.',
                    'href'   => BASE_URL . '/dashboard',
                    'estado' => 'En operación',
                    'icono'  => 'fa-wind',
                ],
            ],

            'colaboradores' => [
                ['nombre' => 'Universidad Distrital Francisco José de Caldas', 'rol' => 'Institución', 'logo' => asset('img/logo-ud.png'), 'href' => 'https://www.udistrital.edu.co'],
                ['nombre' => 'Instituciones educativas participantes',        'rol' => 'Colegios',    'icono' => 'fa-school'],
                ['nombre' => 'Por definir',                                    'rol' => 'Colaborador', 'icono' => 'fa-handshake'],
            ],

            // Deja en '' lo que aún no esté definido; se mostrará "Por definir".
            'contacto' => [
                'correo'    => '',
                'telefono'  => '',
                'direccion' => 'Universidad Distrital Francisco José de Caldas · Bogotá D.C., Colombia',
                'horario'   => '',
            ],
        ];

        // Cifras del SIMCA para la tarjeta del portal (si la base de datos falla, el portal se muestra igual)
        try {
            $stats = [
                'colegios'   => Colegio::getTotalColegios(),
                'sensores'   => Colegio::getTotalSensores(),
                'mediciones' => Colegio::getTotalMediciones(),
            ];
        } catch (Throwable $e) {
            error_log('LIASP stats: ' . $e->getMessage());
            $stats = null;
        }

        $portada = file_exists(BASE_PATH . '/assets/img/portada.jpg') ? asset('img/portada.jpg') : null;

        view('header', [
            'titulo'    => 'LIASP-CB · Laboratorio Urbano de Inteligencia Ambiental y Salud Pública',
            'nav'       => 'liasp',
            'sitio'     => 'liasp',
            'navActivo' => 'home',
        ]);
        view('liasp', [
            'contenido' => $contenido,
            'stats'     => $stats,
            'portada'   => $portada,
        ]);
        view('footer');
    }
}
