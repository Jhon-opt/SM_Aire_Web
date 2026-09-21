<?php

/**
 * Contenido institucional del portal, tomado de los documentos conceptuales:
 *   - LIASP-CB_Documento_Conceptual (14/09/2026)
 *   - Documento_Conceptual_SIMCA_LIASP-CB (14/09/2026)
 *
 * Cada documento se muestra en una ventana emergente (views/partials/modal_documento.php).
 * Tipos de bloque: p (párrafo), h (subtítulo), ul / ol (listas), tabla (encabezado + filas),
 * capas (lista numerada con título y texto).
 */

function documentoLiasp(): array
{
    return [
        'id'     => 'modal-liasp',
        'sigla'  => 'LIASP-CB',
        'titulo' => 'Laboratorio Urbano de Inteligencia Ambiental y de Salud Pública para la Localidad de Ciudad Bolívar',
        'secciones' => [
            [
                'id' => 'introduccion',
                'titulo' => 'Introducción',
                'bloques' => [
                    ['p', 'Las ciudades generan de manera continua grandes volúmenes de datos relacionados con el ambiente, la salud, la movilidad, el territorio, el clima, la actividad económica, los servicios públicos y las dinámicas sociales. Sin embargo, disponer de datos no implica, por sí mismo, contar con conocimiento útil para intervenir sobre los problemas urbanos. El reto contemporáneo consiste en integrar fuentes heterogéneas, transformar los datos en información, analizarlos para generar conocimiento y convertir ese conocimiento en decisiones, alertas, políticas e intervenciones territoriales basadas en evidencia.'],
                    ['p', 'Durante las últimas dos décadas se ha consolidado un nuevo tipo de infraestructura de conocimiento en las ciudades: los laboratorios urbanos de inteligencia. Estos se entienden como espacios permanentes de investigación, experimentación, innovación y colaboración en los que universidades, gobiernos, comunidades, sectores productivos y otros actores articulan datos, tecnologías digitales y conocimiento interdisciplinario para comprender fenómenos urbanos, experimentar soluciones, evaluar sus resultados y apoyar la toma de decisiones en contextos reales.'],
                    ['p', 'Estos laboratorios combinan sensores, plataformas de información, analítica avanzada e inteligencia artificial con procesos de investigación situada y participación ciudadana. De esta manera, constituyen un mecanismo para abordar problemas complejos de carácter ambiental, sanitario y social que difícilmente pueden ser resueltos por una sola institución. Su naturaleza es esencialmente experimental: diseñan, desarrollan, prueban, validan y transfieren soluciones. Por tanto, su propósito no se limita a identificar qué está ocurriendo, sino que busca comprender por qué ocurre, anticipar qué puede suceder y determinar qué acciones pueden producir mejores resultados.'],
                    ['p', 'El Laboratorio Urbano de Inteligencia Ambiental y de Salud Pública para Ciudad Bolívar (LIASP-CB) surge en este contexto como una iniciativa de la Universidad Distrital Francisco José de Caldas, impulsada por los grupos de investigación Metis, Digiti, Greece y Armónico. Su propósito es contribuir al abordaje de una problemática crítica y persistente en la localidad de Ciudad Bolívar: la exposición de la población a factores ambientales adversos —asociados, entre otros aspectos, con actividades extractivas, condiciones de vulnerabilidad habitacional y limitaciones de infraestructura— y su posible relación con la morbilidad respiratoria y otros efectos sobre la salud, relación que aún requiere mayor documentación sistemática y análisis integrado.'],
                    ['p', 'La pertinencia del LIASP-CB radica en su capacidad para articular de manera estructurada actores que actualmente disponen de información valiosa, pero fragmentada: la Secretaría Distrital de Ambiente, que gestiona información histórica y en tiempo real sobre calidad del aire; la Subred Integrada de Servicios de Salud Sur, que aporta información sanitaria y de vigilancia epidemiológica; la Universidad Distrital Francisco José de Caldas, que contribuye con capacidades científicas y tecnológicas en ciencia de datos, modelado, analítica e inteligencia artificial; y las instituciones educativas de Ciudad Bolívar, que pueden actuar como nodos de monitoreo, formación y ciencia ciudadana. La integración de estas capacidades en un sistema de inteligencia territorial permitirá transformar datos aislados en conocimiento accionable, generar alertas tempranas, producir evidencia para la formulación de políticas públicas y desarrollar soluciones orientadas a mejorar la salud, la calidad de vida, la equidad y la sostenibilidad del territorio.'],
                ],
            ],
            [
                'id' => 'descripcion',
                'titulo' => 'Descripción',
                'bloques' => [
                    ['p', 'El LIASP-CB se concibe como un laboratorio urbano o living lab de carácter interinstitucional, interdisciplinario y territorial. Su función es operar como una infraestructura permanente para la captura, integración, gestión, análisis, visualización y uso de datos ambientales, sanitarios, sociales y territoriales de Ciudad Bolívar. El laboratorio funcionará como un servicio continuo para el territorio: recopilará información de manera sistemática, la transformará en indicadores, modelos y conocimiento, y devolverá sus resultados a las entidades responsables, las instituciones educativas y la comunidad mediante alertas, tableros de control, mapas, recomendaciones y otros mecanismos de apoyo a la toma de decisiones.'],
                    ['p', 'En este sentido, el LIASP-CB conformará un ecosistema territorial y multiinstitucional de investigación, desarrollo tecnológico, innovación y apropiación social del conocimiento. Este ecosistema integrará infraestructura de monitoreo, sistemas de información, ciencia de datos, analítica avanzada e inteligencia artificial para comprender, monitorear y anticipar los efectos de los determinantes ambientales sobre la salud; cocrear soluciones con los actores del territorio; y generar evidencia que apoye la prevención, la gestión del riesgo, la formulación de políticas públicas y el mejoramiento sostenible de la calidad de vida.'],
                    ['p', 'El LIASP-CB se estructura en siete capas funcionales que operan de manera articulada:'],
                    ['capas', [
                        ['titulo' => 'Capa de captura de datos', 'texto' => 'Recolección sistemática de información ambiental —por ejemplo, calidad del aire y variables meteorológicas— mediante sensores IoT y fuentes oficiales de la Secretaría Distrital de Ambiente, complementada con información sanitaria, social y territorial proveniente de la Subred Integrada de Servicios de Salud Sur, otras fuentes institucionales y procesos de ciencia ciudadana desarrollados en instituciones educativas.'],
                        ['titulo' => 'Capa de integración y gestión de datos', 'texto' => 'Consolidación de los datos ambientales, sanitarios, sociales y territoriales en una plataforma interoperable que armonice formatos, asegure la calidad y la trazabilidad de la información y facilite el análisis georreferenciado. Las fuentes podrán incluir, entre otras, RMCAB, IBOCA, Salud Data, registros de vigilancia epidemiológica, información de morbilidad respiratoria, variables meteorológicas, datos demográficos y socioeconómicos, usos del suelo, infraestructura, imágenes satelitales y datos generados por sensores propios.'],
                        ['titulo' => 'Capa de analítica e inteligencia artificial', 'texto' => 'Aplicación de técnicas de analítica descriptiva, diagnóstica, predictiva y prescriptiva, así como de modelos de inteligencia artificial y aprendizaje automático, para caracterizar riesgos, detectar patrones, explorar relaciones entre variables y anticipar posibles efectos sobre la salud asociados con la exposición ambiental.'],
                        ['titulo' => 'Capa de visualización e inteligencia territorial', 'texto' => 'Desarrollo de un portal de inteligencia ambiental y de salud pública que integre mapas, indicadores, series temporales, tableros de control, visualizaciones espaciotemporales y mecanismos de exploración de datos adaptados a las necesidades de investigadores, autoridades, directivos escolares, docentes, estudiantes y comunidades.'],
                        ['titulo' => 'Capa de alerta y apoyo a la toma de decisiones', 'texto' => 'Conversión de los resultados del monitoreo y la analítica en alertas tempranas, recomendaciones y elementos de apoyo a la decisión dirigidos a entidades distritales, instituciones educativas y comunidades. Los mecanismos de alerta deberán articularse con los criterios y protocolos oficiales de la Secretaría Distrital de Ambiente y la Subred Integrada de Servicios de Salud Sur, con el fin de garantizar consistencia, oportunidad y claridad en la comunicación del riesgo.'],
                        ['titulo' => 'Capa de intervención, participación y ciencia ciudadana', 'texto' => 'Diseño, implementación y evaluación de soluciones en el territorio mediante procesos de cocreación y participación comunitaria. Estas soluciones podrán incluir estrategias relacionadas con horarios de actividad física, rutas escolares, arborización, educación sobre exposición ambiental, campañas comunitarias, prototipos tecnológicos, alfabetización de datos y mecanismos de comunicación del riesgo.'],
                        ['titulo' => 'Capa de evaluación, aprendizaje y transferencia', 'texto' => 'Evaluación del funcionamiento, la aceptación, el impacto, los costos, la sostenibilidad, la replicabilidad y la escalabilidad de las soluciones e intervenciones desarrolladas. Los resultados se transformarán en conocimiento transferible mediante datos, modelos, metodologías, software, protocolos, publicaciones, informes técnicos, materiales educativos, cursos, recomendaciones y documentos de política pública.'],
                    ]],
                    ['p', 'De este modo, el LIASP-CB no será únicamente una plataforma tecnológica, sino un espacio de investigación aplicada, experimentación territorial y gobernanza compartida de los datos y el conocimiento. La Universidad aportará capacidades científicas, tecnológicas y metodológicas; las entidades distritales contribuirán con información oficial, conocimiento institucional, capacidad normativa y posibilidades de intervención; y las instituciones educativas y comunidades aportarán conocimiento del territorio, participación, validación social y capacidad de apropiación y transformación local.'],
                ],
            ],
            [
                'id' => 'objetivos',
                'titulo' => 'Objetivos',
                'bloques' => [
                    ['h', 'Objetivo general'],
                    ['p', 'Implementar un laboratorio urbano de inteligencia ambiental y salud pública en Ciudad Bolívar que integre datos ambientales, sanitarios, sociales y territoriales mediante tecnologías de monitoreo, sistemas de información, analítica avanzada e inteligencia artificial, articulados con procesos de investigación y participación comunitaria, para identificar, monitorear, analizar y predecir riesgos ambientales y sus efectos sobre la salud, generando alertas tempranas, conocimiento y soluciones innovadoras que apoyen la prevención, la toma de decisiones basada en evidencia y el mejoramiento de la salud, la calidad de vida, la equidad y la sostenibilidad del territorio.'],
                    ['h', 'Objetivos específicos'],
                    ['ol', [
                        'Implementar una red de monitoreo ambiental basada en sensores IoT en puntos estratégicos de Ciudad Bolívar, incorporando instituciones educativas como nodos de captura de información, con el fin de complementar, ampliar y densificar espacial y temporalmente los datos oficiales sobre calidad del aire y otras variables ambientales relevantes para la salud.',
                        'Diseñar e implementar una plataforma interoperable de datos ambientales, sanitarios, sociales y territoriales que permita integrar, almacenar, gestionar, consultar, analizar y compartir información proveniente de la Secretaría Distrital de Ambiente, la Subred Integrada de Servicios de Salud Sur, otras fuentes institucionales y los sistemas de monitoreo desplegados en instituciones educativas y otros puntos de Ciudad Bolívar, garantizando la calidad, trazabilidad, seguridad, interoperabilidad y disponibilidad de los datos para la investigación y la toma de decisiones.',
                        'Desarrollar herramientas de analítica de datos, inteligencia artificial, visualización e inteligencia territorial que integren tableros de control, sistemas de información geográfica, mapas de riesgo, análisis espaciotemporales y mecanismos interactivos de exploración de datos, con el propósito de identificar, caracterizar, analizar y predecir riesgos ambientales y examinar su relación con la morbilidad respiratoria y otros efectos sobre la salud de la población de Ciudad Bolívar.',
                        'Diseñar e implementar mecanismos de alerta temprana y apoyo a la toma de decisiones que integren información proveniente del monitoreo ambiental, la analítica de datos, los modelos predictivos y criterios ambientales y sanitarios, con el fin de generar alertas y recomendaciones oportunas, comprensibles y territorialmente contextualizadas para las entidades distritales, las instituciones educativas, las comunidades y demás actores vinculados al territorio.',
                        'Diseñar, implementar y evaluar soluciones tecnológicas, sociales y educativas orientadas a prevenir, mitigar o reducir los riesgos ambientales y sus efectos sobre la salud de la población de Ciudad Bolívar mediante procesos de experimentación, innovación, cocreación y validación en contextos reales, promoviendo su apropiación, sostenibilidad, replicabilidad y escalamiento.',
                        'Fortalecer la participación comunitaria y la ciencia ciudadana mediante la vinculación de las instituciones educativas de Ciudad Bolívar como nodos de monitoreo ambiental y espacios de formación en educación ambiental y alfabetización de datos, promoviendo la participación de estudiantes, docentes y comunidades en la generación, interpretación, validación y uso de la información, así como en la apropiación de las alertas, recomendaciones y soluciones producidas por el LIASP-CB.',
                        'Generar, gestionar, apropiar y transferir conocimiento científico, tecnológico y aplicado derivado de las actividades del LIASP-CB mediante la producción de publicaciones científicas, informes técnicos, modelos, metodologías, prototipos, productos tecnológicos, materiales educativos, recomendaciones y documentos de política pública, con el propósito de fortalecer la formulación, implementación y evaluación de políticas ambientales y de salud pública basadas en evidencia en Ciudad Bolívar y en otros territorios con problemáticas similares.',
                        'Fortalecer las capacidades científicas, tecnológicas, investigativas e institucionales asociadas con el LIASP-CB mediante la formación y vinculación de estudiantes, jóvenes investigadores, profesores, servidores públicos y actores comunitarios en procesos de investigación, desarrollo tecnológico, innovación, apropiación social y transferencia de conocimiento, así como mediante la consolidación de redes de cooperación nacionales e internacionales que contribuyan a la sostenibilidad, institucionalización, replicabilidad y escalamiento del laboratorio.',
                    ]],
                ],
            ],
        ],
    ];
}

/** Párrafo introductorio de la sección "Líneas de trabajo" del portal. */
function lineasTrabajoIntro(): string
{
    return 'Las líneas de trabajo del Laboratorio Urbano de Inteligencia Ambiental y de Salud Pública de Ciudad Bolívar —LIASP-CB— orientan de manera articulada sus actividades de investigación, desarrollo tecnológico, innovación, experimentación territorial, formación, apropiación social y transferencia de conocimiento. Estas líneas responden a los objetivos del laboratorio y estructuran el ciclo mediante el cual los datos ambientales, sanitarios, sociales y territoriales se transforman en información, conocimiento, alertas, recomendaciones y soluciones para apoyar la prevención, la gestión del riesgo, la toma de decisiones y el mejoramiento de la calidad de vida en el territorio.';
}

/** Las siete líneas de trabajo: una tarjeta cada una. */
function lineasTrabajo(): array
{
    return [
        [
            'icono'  => 'fa-microchip',
            'titulo' => 'Monitoreo ambiental inteligente, Internet de las Cosas e inteligencia en el borde',
            'texto'  => 'Esta línea se orienta al diseño, desarrollo, implementación y evaluación de sistemas inteligentes para la captura continua y distribuida de información ambiental en Ciudad Bolívar. Integra sensores, tecnologías de Internet de las Cosas (IoT), sistemas embebidos, comunicaciones y procesamiento de datos en el borde para ampliar y complementar las capacidades existentes de monitoreo ambiental. Comprende la medición de contaminantes atmosféricos y variables meteorológicas, el despliegue de estaciones de bajo costo, la calibración y validación de sensores, el aseguramiento de la calidad de los datos, la detección de anomalías y la gestión de la infraestructura tecnológica. Las instituciones educativas podrán actuar como nodos territoriales de monitoreo, experimentación y generación de información ambiental.',
        ],
        [
            'icono'  => 'fa-database',
            'titulo' => 'Ingeniería, interoperabilidad y gobernanza de datos ambientales, sanitarios, sociales y territoriales',
            'texto'  => 'Esta línea tiene como propósito diseñar y consolidar la infraestructura tecnológica, metodológica y organizacional requerida para integrar, gestionar, proteger y compartir los datos utilizados y generados por el LIASP-CB. Comprende arquitecturas y plataformas de datos, servicios de interoperabilidad, APIs, procesos de integración, bases de datos temporales y geoespaciales, catálogos de información, metadatos, calidad y trazabilidad de datos, así como mecanismos de seguridad, privacidad, anonimización y control de acceso. Su desarrollo permitirá articular información proveniente de fuentes ambientales, sanitarias, epidemiológicas, sociales, territoriales, meteorológicas y de los sistemas de monitoreo propios del laboratorio, garantizando su disponibilidad y confiabilidad para la investigación, el análisis y la toma de decisiones.',
        ],
        [
            'icono'  => 'fa-brain',
            'titulo' => 'Ciencia de datos, inteligencia artificial y modelado integrado ambiente–salud–territorio',
            'texto'  => 'Esta línea se orienta al desarrollo y aplicación de métodos de ciencia de datos, estadística, inteligencia artificial y modelado computacional para transformar datos heterogéneos en conocimiento sobre riesgos ambientales, exposición y posibles efectos sobre la salud. Integra analítica descriptiva, diagnóstica, predictiva y prescriptiva; análisis espacial y espaciotemporal; series de tiempo; aprendizaje automático; modelos de exposición; clasificación territorial y pronóstico. Su propósito es identificar patrones, caracterizar zonas y poblaciones vulnerables, analizar relaciones entre condiciones ambientales, sociales y sanitarias y anticipar escenarios de riesgo. Los modelos desarrollados incorporarán criterios de validación, explicabilidad e incertidumbre que fortalezcan su uso responsable en procesos de investigación y apoyo a la toma de decisiones.',
        ],
        [
            'icono'  => 'fa-map',
            'titulo' => 'Visualización de datos, inteligencia territorial y gemelos digitales',
            'texto'  => 'Esta línea busca desarrollar mecanismos para representar, integrar, explorar y comunicar información y conocimiento sobre las condiciones ambientales, sanitarias, sociales y territoriales de Ciudad Bolívar. Comprende sistemas de información geográfica, mapas temáticos e interactivos, tableros de control, indicadores, análisis visual y representaciones espaciotemporales dirigidas a investigadores, entidades públicas, instituciones educativas y comunidades. Como componente integrador se desarrollará progresivamente el Portal de Inteligencia Ambiental y de Salud Pública del LIASP-CB. En fases de mayor madurez tecnológica podrán incorporarse modelos de simulación y gemelos digitales de sectores piloto del territorio para analizar escenarios, visualizar dinámicas complejas y apoyar la evaluación de posibles intervenciones.',
        ],
        [
            'icono'  => 'fa-heart-pulse',
            'titulo' => 'Salud pública digital, vigilancia integrada, alertas tempranas y apoyo a la toma de decisiones',
            'texto'  => 'Esta línea integra información ambiental, sanitaria, epidemiológica, social y territorial con el propósito de fortalecer los procesos de vigilancia, prevención, gestión del riesgo y toma de decisiones en salud pública. Comprende el desarrollo de indicadores y modelos de riesgo, mecanismos de estratificación territorial, sistemas de alerta temprana, herramientas de apoyo a decisiones y estrategias de comunicación del riesgo. Las soluciones desarrolladas deberán articularse con los criterios, competencias y protocolos de las entidades ambientales y sanitarias, de manera que las alertas y recomendaciones sean oportunas, comprensibles, confiables y territorialmente contextualizadas. La línea contribuirá especialmente a identificar condiciones de riesgo y poblaciones vulnerables y a orientar medidas preventivas y de respuesta basadas en evidencia.',
        ],
        [
            'icono'  => 'fa-people-group',
            'titulo' => 'Ciencia ciudadana, educación ambiental, alfabetización de datos y apropiación social del conocimiento',
            'texto'  => 'Esta línea promueve la participación de instituciones educativas, estudiantes, docentes, organizaciones sociales y comunidades de Ciudad Bolívar en los procesos de generación, interpretación y utilización de información ambiental y sanitaria. Integra estrategias de ciencia ciudadana, educación ambiental, alfabetización de datos, proyectos STEAM, formación comunitaria, microlearning, comunicación del riesgo y cocreación de soluciones. Las instituciones educativas tendrán un papel estratégico como nodos territoriales del LIASP-CB, articulando actividades de monitoreo, investigación escolar, formación y participación comunitaria. Su propósito es fortalecer capacidades para comprender los datos, apropiarse del conocimiento generado por el laboratorio y participar de manera informada en procesos de prevención, cuidado, innovación y transformación del territorio.',
        ],
        [
            'icono'  => 'fa-lightbulb',
            'titulo' => 'Gestión del conocimiento, innovación social, transferencia, políticas públicas y sostenibilidad',
            'texto'  => 'Esta línea orienta la transformación de los resultados científicos, tecnológicos y sociales del LIASP-CB en conocimiento transferible, capacidades institucionales, soluciones aplicables y evidencia para la formulación y evaluación de políticas públicas. Comprende gestión del conocimiento, sistematización de experiencias, comunidades de práctica, innovación abierta, cocreación, evaluación de impacto, transferencia tecnológica y social, fortalecimiento de capacidades, cooperación nacional e internacional y generación de recomendaciones de política. Asimismo, aborda los procesos de sostenibilidad, institucionalización, replicabilidad y escalamiento del laboratorio y de sus soluciones. Su desarrollo permitirá consolidar metodologías, productos tecnológicos, modelos, protocolos, publicaciones y paquetes de transferencia que puedan ser utilizados tanto en Ciudad Bolívar como en otros territorios con problemáticas ambientales y sanitarias similares.',
        ],
    ];
}

function documentoSimca(): array
{
    return [
        'id'     => 'modal-simca',
        'sigla'  => 'SIMCA',
        'titulo' => 'Sistema Inteligente de Monitoreo de Calidad del Aire con Sensores de Bajo Costo',
        'secciones' => [
            [
                'id' => 'introduccion',
                'titulo' => 'Introducción',
                'bloques' => [
                    ['p', 'La calidad del aire constituye un componente fundamental del ambiente urbano, con incidencia directa sobre la salud, el bienestar y la calidad de vida de la población. En territorios caracterizados por dinámicas heterogéneas de movilidad, actividad económica, densidad poblacional, topografía y usos del suelo, disponer de información ambiental con una adecuada resolución espacial y temporal resulta esencial para fortalecer los procesos de observación, análisis, prevención y toma de decisiones basadas en evidencia.'],
                    ['p', 'En este escenario, los sensores de bajo costo representan una alternativa tecnológica complementaria a las redes convencionales de vigilancia de la calidad del aire, ya que permiten ampliar la cobertura territorial y aumentar la densidad de los puntos de observación. Su valor se incrementa cuando estos dispositivos se integran en redes distribuidas de monitoreo y se articulan con mecanismos confiables de adquisición, transmisión, almacenamiento, procesamiento, consulta y visualización de datos.'],
                    ['p', 'Como parte del Laboratorio Urbano de Inteligencia Ambiental y de Salud Pública para la Localidad de Ciudad Bolívar (LIASP-CB), se desarrolla el Sistema Inteligente de Monitoreo de Calidad del Aire basado en Sensores de Bajo Costo (SIMCA). Este sistema constituye una solución tecnológica orientada a fortalecer y densificar el monitoreo ambiental mediante el despliegue de nodos de medición de bajo costo en puntos estratégicos del territorio, inicialmente en instituciones educativas. Estos nodos capturan variables relacionadas con la calidad del aire y transmiten de manera automática y periódica las mediciones a una infraestructura tecnológica centralizada, desde la cual la información puede ser almacenada, procesada, consultada y visualizada a través de una plataforma web de acceso público.'],
                    ['p', 'El SIMCA constituye uno de los sistemas tecnológicos estratégicos del LIASP-CB para la generación de inteligencia ambiental territorial. Su arquitectura ha sido concebida para integrar dispositivos de Internet de las Cosas (IoT), infraestructura de comunicaciones, servicios de recepción y procesamiento de datos, mecanismos de validación, bases de datos y herramientas de consulta y visualización. De esta manera, el sistema transforma las mediciones capturadas en territorio en información organizada y disponible para apoyar procesos de seguimiento ambiental, investigación, educación, apropiación social del conocimiento y toma de decisiones.'],
                    ['p', 'En su versión actualmente implementada, el SIMCA permite medir concentraciones de material particulado PM2.5 y PM10, así como monóxido de carbono (CO). Los nodos realizan la captura de las variables ambientales, calculan promedios de las mediciones en intervalos de un minuto y transmiten automáticamente los datos al servidor central del proyecto, sin requerir un computador conectado ni intervención humana permanente. Esta capacidad favorece la operación continua de una red distribuida de monitoreo y facilita el seguimiento de las variaciones ambientales tanto en tiempo casi real como mediante la consulta de información histórica.'],
                    ['p', 'La integración del SIMCA con el LIASP-CB adquiere especial relevancia al permitir que las instituciones educativas se conviertan en nodos territoriales de observación ambiental, articulando infraestructura tecnológica, generación de datos, investigación y participación de la comunidad educativa. En consecuencia, el SIMCA no se concibe únicamente como un conjunto de dispositivos de medición, sino como un sistema distribuido de monitoreo ambiental que integra sensores, conectividad, servicios de adquisición y validación de datos, almacenamiento, visualización, consulta y exportación de información.'],
                    ['p', 'Esta arquitectura proporciona, además, una base tecnológica escalable para la evolución del sistema. A partir de la infraestructura existente será posible incorporar progresivamente nuevas variables ambientales y meteorológicas, ampliar la red de nodos de monitoreo e integrar modelos de análisis de datos, inteligencia artificial, análisis espaciotemporal, generación de indicadores, detección de anomalías, sistemas de alertas tempranas y capacidades predictivas. De esta forma, el SIMCA podrá evolucionar desde una plataforma de monitoreo hacia un componente integral de inteligencia ambiental del LIASP-CB, orientado a apoyar la comprensión de las dinámicas territoriales, la prevención de riesgos ambientales y la toma de decisiones basada en datos.'],
                ],
            ],
            [
                'id' => 'descripcion',
                'titulo' => 'Descripción',
                'bloques' => [
                    ['p', 'El Sistema Inteligente de Monitoreo de Calidad del Aire (SIMCA) es un sistema distribuido de monitoreo ambiental basado en sensores de bajo costo, tecnologías de Internet de las Cosas (IoT) y servicios web, diseñado para realizar la medición continua de variables asociadas con la calidad del aire. El sistema permite adquirir datos en campo, procesarlos y transmitirlos automáticamente hacia una infraestructura centralizada, donde son almacenados de manera estructurada y posteriormente puestos a disposición de los usuarios mediante herramientas de visualización, consulta, análisis descriptivo y exportación de información.'],
                    ['p', 'En su versión actualmente operativa, el carácter “inteligente” de SIMCA está asociado principalmente con la automatización del ciclo de gestión de los datos. Este proceso comprende la adquisición de las mediciones, verificación básica de su validez, agregación temporal mediante promedios, transmisión automática y segura, almacenamiento estructurado, actualización del panel de visualización y clasificación orientativa del estado de la calidad del aire. De esta manera, el sistema transforma de forma automática las mediciones obtenidas por los sensores en información disponible para el seguimiento y análisis ambiental.'],
                    ['p', 'Dentro del LIASP-CB, SIMCA constituye la infraestructura tecnológica de captura, monitoreo y observación de datos asociados con la calidad del aire. La información generada por el sistema puede apoyar procesos de investigación científica, educación ambiental, ciencia ciudadana, análisis territorial y toma de decisiones basada en evidencia. En fases posteriores, estos datos podrán integrarse con información sanitaria, social, demográfica, meteorológica y geográfica, contribuyendo al estudio de las relaciones entre las condiciones ambientales, las dinámicas territoriales y la salud de la población.'],
                    ['p', 'En este sentido, SIMCA proporciona una capa de información ambiental de alta frecuencia y mayor granularidad espacial, destinada a complementar otras fuentes de información institucional y los sistemas oficiales de vigilancia ambiental. Su despliegue mediante nodos distribuidos permite incrementar la disponibilidad de información en puntos específicos del territorio y avanzar hacia una caracterización más detallada de las condiciones ambientales de Ciudad Bolívar.'],
                    ['p', 'Las variables que monitorea SIMCA son las siguientes:'],
                    ['tabla', [
                        'encabezado' => ['Variable', 'Sensor', 'Unidad', 'Observación'],
                        'filas' => [
                            ['Material particulado PM2.5', 'PMS7003', 'µg/m³', 'Valor calibrado de fábrica; indicador principal de calidad del aire.'],
                            ['Material particulado PM10', 'PMS7003', 'µg/m³', 'Valor calibrado de fábrica.'],
                            ['Monóxido de carbono (CO)', 'MQ-7', 'ppm', 'Valor estimado, sujeto a calibración; se interpreta como indicador orientativo.'],
                        ],
                    ]],
                    ['p', 'El nodo toma lecturas aproximadamente cada segundo, acumula las muestras y calcula un promedio por minuto. Este promedio reduce el ruido de las lecturas y disminuye el volumen de registros almacenados. Cada minuto se realiza el envío automático al servidor. El panel web consulta periódicamente la información y actualiza la visualización sin requerir una recarga manual de la página. La interfaz operativa reporta que los datos se actualizan automáticamente cada minuto.'],
                    ['p', 'La plataforma clasifica cada lectura según los rangos del Índice de Calidad del Aire (ICA) definidos en la Resolución 2254 de 2017 del Ministerio de Ambiente y Desarrollo Sostenible, presentando categorías comprensibles para el público general (Buena, Aceptable, Dañina para grupos sensibles, entre otras), como referencia rápida del estado del aire en el punto monitoreado.'],
                    ['p', 'La arquitectura del sistema está preparada para incorporar en el futuro variables adicionales como temperatura, humedad, CO2, O3 y NO2, así como nuevos nodos de medición en otras sedes educativas de la localidad, sin necesidad de modificar la base de datos ni la plataforma web.'],
                ],
            ],
            [
                'id' => 'objetivos',
                'titulo' => 'Objetivos',
                'bloques' => [
                    ['h', 'Objetivo general'],
                    ['p', 'Implementar un sistema inteligente y distribuido de monitoreo de calidad del aire basado en sensores de bajo costo que permita captar, transmitir, almacenar, procesar, visualizar y consultar de manera continua datos ambientales en instituciones educativas de Ciudad Bolívar, con el fin de generar información ambiental continua, confiable y de acceso público para la localidad de Ciudad Bolívar, como insumo para la vigilancia ambiental y la salud pública en el marco del LIASP-CB.'],
                    ['h', 'Objetivos específicos'],
                    ['ol', [
                        'Diseñar, implementar y desplegar una red distribuida de nodos de monitoreo de bajo costo en instituciones educativas y otros puntos estratégicos de Ciudad Bolívar, capaz de medir de manera autónoma, continua y georreferenciada variables asociadas con la calidad del aire, inicialmente material particulado PM2.5 y PM10 y monóxido de carbono (CO).',
                        'Capturar, validar y procesar las mediciones generadas por los nodos de monitoreo, mediante mecanismos de control básico de calidad y agregación temporal de los datos, que favorezcan su consistencia, estabilidad y trazabilidad.',
                        'Implementar mecanismos automáticos y seguros de transmisión de datos desde los nodos de monitoreo hacia la infraestructura central del SIMCA, utilizando tecnologías de conectividad y protocolos de comunicación que garanticen la disponibilidad e integridad de la información.',
                        'Consolidar y gestionar las mediciones en una infraestructura centralizada de almacenamiento de datos, estructurada para integrar información proveniente de múltiples dispositivos, instituciones y puntos de monitoreo, facilitando su consulta, trazabilidad, interoperabilidad y análisis histórico.',
                        'Desarrollar y mantener una plataforma web pública de monitoreo y visualización que permita consultar, en tiempo casi real, el estado de las variables medidas, sus tendencias temporales, estadísticas descriptivas, registros históricos y distribución geográfica de los puntos de monitoreo.',
                        'Facilitar el acceso, comprensión e interpretación de la información sobre calidad del aire por parte de la comunidad educativa, la ciudadanía y otros actores del territorio, mediante visualizaciones, indicadores, filtros territoriales y mecanismos de interpretación basados en referentes de calidad del aire.',
                        'Promover el acceso, descarga y reutilización de los datos generados por SIMCA para apoyar actividades de investigación, educación ambiental, análisis territorial, innovación tecnológica y desarrollo de nuevos servicios y aplicaciones en el marco del LIASP-CB.',
                        'Establecer una arquitectura tecnológica modular, interoperable y escalable que permita ampliar progresivamente la red de monitoreo, incorporar nuevas instituciones, sensores y variables ambientales, e integrar mecanismos de alerta, analítica avanzada y modelos de inteligencia artificial.',
                        'Generar información ambiental de línea base y series de datos territoriales que contribuyan al desarrollo de estudios posteriores de inteligencia ambiental y salud pública, así como al análisis de las relaciones entre condiciones ambientales, territorio y salud en el marco del LIASP-CB.',
                    ]],
                ],
            ],
            [
                'id' => 'funcionalidades',
                'titulo' => 'Funcionalidades',
                'bloques' => [
                    ['p', 'Las funcionalidades de SIMCA se organizan de acuerdo con el ciclo de gestión del dato ambiental, desde su captura en territorio hasta su consulta y aprovechamiento por los usuarios.'],
                    ['h', 'Captura automática de variables de calidad del aire'],
                    ['ul', [
                        'Medición de PM2.5 y PM10 mediante sensor óptico PMS7003.',
                        'Estimación de CO mediante sensor MQ-7 en la versión actual del prototipo.',
                        'Toma continua de muestras en el nodo sin intervención de un operador.',
                        'Validación de las tramas del sensor de material particulado mediante checksum antes de utilizar las lecturas.',
                    ]],
                    ['h', 'Procesamiento local y agregación temporal'],
                    ['ul', [
                        'Acumulación de las lecturas capturadas durante cada intervalo.',
                        'Cálculo de promedios de PM2.5, PM10 y CO cada minuto.',
                        'Control básico de rangos antes del envío para evitar el rechazo de la medición completa por valores fuera de los límites aceptados por el servidor.',
                        'Reconexión automática a la red Wi-Fi cuando se pierde la conectividad.',
                    ]],
                    ['h', 'Transmisión segura e identificación del dispositivo'],
                    ['ul', [
                        'Envío automático de datos mediante peticiones HTTPS al servidor del proyecto.',
                        'Identificación de cada nodo por medio de una clave de dispositivo.',
                        'Recepción y verificación del código de respuesta del servidor para confirmar el almacenamiento de la medición.',
                    ]],
                    ['h', 'Validación y almacenamiento centralizado'],
                    ['ul', [
                        'Validación de credenciales y rangos de datos.',
                        'Almacenamiento de las mediciones en una base de datos MySQL.',
                        'Asignación de la fecha y hora en el servidor y conversión a hora de Colombia para la presentación en la interfaz.',
                        'Estructura de datos preparada para incorporar variables adicionales como temperatura, humedad y otros contaminantes.',
                    ]],
                    ['h', 'Monitoreo en tiempo real y estado actual'],
                    ['ul', [
                        'Presentación del estado general de la calidad del aire para las instituciones monitoreadas.',
                        'Visualización de la última lectura disponible por variable.',
                        'Actualización automática del panel cuando se reciben nuevas mediciones.',
                        'Indicadores de cantidad de colegios, sensores activos, número de mediciones y tiempo transcurrido desde la última lectura.',
                    ]],
                    ['h', 'Consulta histórica, filtros y análisis descriptivo'],
                    ['ul', [
                        'Filtros por colegio, dispositivo e intervalo temporal.',
                        'Gráficas de evolución de las variables para el periodo seleccionado.',
                        'Cálculo y presentación de estadísticas descriptivas del periodo consultado.',
                        'Tabla de mediciones ordenable y consultable para revisar registros históricos.',
                    ]],
                    ['h', 'Clasificación orientativa de la calidad del aire'],
                    ['ul', [
                        'Presentación de categorías de calidad del aire: Buena, Aceptable, Dañina.',
                        'Uso de puntos de corte referenciados en la Resolución 2254 de 2017 para orientar la lectura del estado mostrado.',
                        'Debe interpretarse como referencia visual del sistema: la documentación técnica aclara que aplicar dichos rangos a la última lectura de un minuto no equivale al cálculo oficial del ICA, que utiliza ventanas temporales regulatorias.',
                    ]],
                    ['h', 'Visualización territorial'],
                    ['ul', [
                        'Ubicación de los colegios y puntos de monitoreo en un mapa.',
                        'Consulta de la información asociada a cada punto dentro del contexto territorial de Bogotá y, progresivamente, de Ciudad Bolívar.',
                        'Base para integrar en el futuro capas geográficas, sociales, ambientales y sanitarias del LIASP-CB.',
                    ]],
                    ['h', 'Exportación y reutilización de datos'],
                    ['ul', [
                        'Exportación de las mediciones a Excel desde el panel web.',
                        'Disponibilidad de datos para análisis externo, investigación, ejercicios académicos y procesos de educación ambiental.',
                        'Posibilidad de utilizar la información como insumo para modelos analíticos y servicios adicionales del laboratorio.',
                    ]],
                    ['h', 'Escalabilidad y evolución tecnológica'],
                    ['ul', [
                        'Capacidad de incorporar nuevos dispositivos e instituciones al panel.',
                        'Arquitectura de base de datos preparada para variables que actualmente no están siendo medidas por el nodo.',
                        'Posibilidad de añadir sensores de temperatura, humedad y otros contaminantes.',
                        'Proyección hacia calibración mejorada, control de calidad de datos, alertas tempranas, análisis espaciotemporal, modelos predictivos e integración con datos de salud, siempre que estas capacidades sean desarrolladas y validadas en fases posteriores.',
                    ]],
                    ['p', 'SIMCA constituye la capa de observación de calidad del aire del LIASP-CB. Su valor no se limita al dispositivo sensor: reside en la integración de una red de nodos de bajo costo con una arquitectura de comunicación, almacenamiento y visualización que transforma mediciones locales en información territorial consultable. El sistema actualmente implementado demuestra la viabilidad de este enfoque y proporciona una base escalable para ampliar la cobertura, mejorar la calidad metrológica, integrar nuevas variables y evolucionar hacia capacidades de analítica ambiental y salud pública más avanzadas.'],
                ],
            ],
        ],
    ];
}
