-- =============================================================
-- Migración: clave API por dispositivo (ingesta de sensores)
-- Importar DENTRO de tu base en phpMyAdmin > Importar.
-- =============================================================

ALTER TABLE dispositivo
    ADD COLUMN api_key VARCHAR(64) NULL UNIQUE AFTER id_colegio;
