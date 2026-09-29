-- =============================================================
-- Plantilla: agregar un nodo de monitoreo (institución + dispositivo)
-- Copiar, reemplazar los valores entre <> y ejecutar en phpMyAdmin > SQL.
--
-- La clave (api_key) identifica al nodo: es lo único que el Arduino envía
-- para que el servidor sepa a qué institución pertenece la medición.
-- Genera una distinta para cada nodo (64 caracteres hexadecimales), por
-- ejemplo con SELECT SHA2(UUID(), 256); y guárdala fuera del repositorio.
-- =============================================================

-- 1) Institución (las coordenadas se obtienen con clic derecho en Google Maps)
INSERT INTO colegio (nombre, direccion, ciudad, latitud, longitud)
VALUES ('<Nombre de la institución>', '<Dirección>', 'Bogotá D.C.', <latitud>, <longitud>);

-- 2) Dispositivo asociado a esa institución
INSERT INTO dispositivo (codigo, modelo, ubicacion, estado, fecha_instalacion, id_colegio, api_key)
VALUES ('<SNS-XXX-01>', 'SIMCA-v1 (UNO R4 WiFi + PMS7003 + MQ-7)', '<Ubicación del sensor>',
        'activo', NOW(), LAST_INSERT_ID(), '<clave de 64 caracteres>');

-- 3) La misma clave va en el sketch del Arduino de ese nodo:
--    #define DEVICE_KEY "<clave de 64 caracteres>"

-- 4) Comprobación
-- SELECT c.id_colegio, c.nombre, c.latitud, c.longitud, d.codigo, d.estado
-- FROM colegio c LEFT JOIN dispositivo d ON d.id_colegio = c.id_colegio ORDER BY c.id_colegio;
