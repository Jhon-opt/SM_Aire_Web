-- =============================================================
-- Migración: metadatos de calidad del dato (Guía de aseguramiento, grupo A)
-- Importar DENTRO de la base del proyecto (phpMyAdmin > pestaña SQL).
--
-- Todas las columnas son NULL: los nodos con firmware anterior siguen
-- funcionando sin cambios y esas columnas quedan vacías en sus mediciones.
-- =============================================================

-- 1) Metadatos por medición -----------------------------------
--    n_muestras: lecturas válidas que sustentan el promedio del minuto.
--                Un promedio de 58 muestras no es igual de confiable que uno de 3.
--    medido_en : hora de la medición en el nodo (UTC, obtenida por NTP).
--                Se conserva fecha_hora como hora de recepción en el servidor;
--                la diferencia entre ambas es la latencia del dato.
ALTER TABLE medicion
    ADD COLUMN n_muestras SMALLINT UNSIGNED NULL AFTER humedad,
    ADD COLUMN medido_en  DATETIME          NULL AFTER n_muestras;

-- 2) Estado y calibración de cada nodo ------------------------
--    Se guardan en dispositivo y no en cada medición: son valores que cambian
--    muy poco y repetirlos en millones de filas solo ocuparía espacio.
ALTER TABLE dispositivo
    ADD COLUMN firmware_version  VARCHAR(20)   NULL,
    ADD COLUMN ultima_conexion   DATETIME      NULL,
    ADD COLUMN r0_mq7            DECIMAL(8,3)  NULL,
    ADD COLUMN calibrado_en      DATETIME      NULL;

-- 3) Comprobación ---------------------------------------------
-- SELECT d.codigo, d.firmware_version, d.ultima_conexion, d.r0_mq7, d.calibrado_en
-- FROM dispositivo d ORDER BY d.codigo;
--
-- SELECT id_medicion, fecha_hora, medido_en,
--        TIMESTAMPDIFF(SECOND, medido_en, fecha_hora) AS latencia_seg, n_muestras
-- FROM medicion WHERE medido_en IS NOT NULL ORDER BY id_medicion DESC LIMIT 20;
