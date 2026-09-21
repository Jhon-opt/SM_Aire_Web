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
            'lema' => 'Datos, inteligencia artificial y participación comunitaria para comprender la relación entre el ambiente y la salud en Ciudad Bolívar.',

            // Resumen de la sección "Información" (el documento completo se abre en la ventana emergente)
            'descripcion' => [
                'El LIASP-CB es una iniciativa de la Universidad Distrital Francisco José de Caldas, impulsada por los grupos de investigación Metis, Digiti, Greece y Armónico, concebida como un laboratorio urbano o living lab de carácter interinstitucional, interdisciplinario y territorial.',
                'Opera como una infraestructura permanente para la captura, integración, gestión, análisis, visualización y uso de datos ambientales, sanitarios, sociales y territoriales de Ciudad Bolívar, y devuelve sus resultados a las entidades responsables, las instituciones educativas y la comunidad mediante alertas, tableros de control, mapas y recomendaciones.',
            ],

            'destacados' => [
                ['icono' => 'fa-layer-group',   'titulo' => 'Siete capas funcionales',      'texto' => 'Captura, integración, analítica e IA, visualización, alertas, intervención y evaluación, que operan de manera articulada.'],
                ['icono' => 'fa-school',        'titulo' => 'Colegios como nodos',           'texto' => 'Las instituciones educativas de Ciudad Bolívar actúan como nodos de monitoreo, formación y ciencia ciudadana.'],
                ['icono' => 'fa-heart-pulse',   'titulo' => 'Ambiente y salud pública',     'texto' => 'Evidencia para alertas tempranas, políticas públicas y soluciones que mejoren la salud y la calidad de vida del territorio.'],
            ],

            // Líneas de trabajo del documento conceptual (una tarjeta cada una)
            'lineas_intro' => lineasTrabajoIntro(),
            'lineas'       => lineasTrabajo(),

            'proyectos' => [
                [
                    'sigla'  => 'SIMCA',
                    'nombre' => 'Sistema Inteligente de Monitoreo de Calidad del Aire basado en sensores de bajo costo',
                    'texto'  => 'Red distribuida de nodos de bajo costo instalados en instituciones educativas que mide PM2.5, PM10 y CO, con transmisión automática al servidor y un panel público en tiempo casi real con estadísticas, mapa y exportación de datos.',
                    'href'   => BASE_URL . '/dashboard',
                    'estado' => 'En operación',
                    'icono'  => 'fa-wind',
                ],
            ],

            'colaboradores' => [
                ['nombre' => 'Universidad Distrital Francisco José de Caldas',        'rol' => 'Institución',            'logo' => asset('img/logo-ud.png'), 'href' => 'https://www.udistrital.edu.co'],
                ['nombre' => 'Grupos de investigación Metis, Digiti, Greece y Armónico', 'rol' => 'Impulsan el laboratorio', 'icono' => 'fa-flask'],
                ['nombre' => 'Secretaría Distrital de Ambiente',                      'rol' => 'Actor del territorio',    'icono' => 'fa-leaf'],
                ['nombre' => 'Subred Integrada de Servicios de Salud Sur',            'rol' => 'Actor del territorio',    'icono' => 'fa-hospital'],
                ['nombre' => 'Instituciones educativas de Ciudad Bolívar',            'rol' => 'Nodos de monitoreo',      'icono' => 'fa-school'],
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
            'titulo'    => 'LIASP-CB · Laboratorio Urbano de Inteligencia Ambiental y de Salud Pública para Ciudad Bolívar',
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
