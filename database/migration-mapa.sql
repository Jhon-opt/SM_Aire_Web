-- =============================================================
-- Migración: coordenadas de los colegios (para el mapa)
-- Importar DENTRO de tu base en phpMyAdmin > Importar (o pegar en SQL).
-- =============================================================

-- 1) Columnas nuevas (latitud/longitud en grados decimales)
ALTER TABLE colegio
    ADD COLUMN latitud  DECIMAL(9,6) NULL AFTER ciudad,
    ADD COLUMN longitud DECIMAL(9,6) NULL AFTER latitud;

-- 2) Coordenadas de cada colegio.
--    Cómo obtenerlas: en Google Maps, clic derecho sobre el colegio > copiar
--    los dos números. El primero es la latitud y el segundo la longitud
--    (en Colombia la longitud es negativa). Ejemplo: 4.6097, -74.0817
UPDATE colegio SET latitud = 4.682955, longitud = -74.145167 WHERE id_colegio = 1;
