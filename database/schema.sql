-- =============================================================
-- Esquema de base de datos - Air Monitor Dashboard
-- =============================================================

CREATE DATABASE IF NOT EXISTS air_monitor
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE air_monitor;

-- =============================================================
-- Tabla: colegio
-- =============================================================
CREATE TABLE IF NOT EXISTS colegio (
    id_colegio  INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(255) NOT NULL,
    direccion   VARCHAR(500) DEFAULT NULL,
    ciudad      VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Tabla: dispositivo
-- =============================================================
CREATE TABLE IF NOT EXISTS dispositivo (
    id_dispositivo    INT AUTO_INCREMENT PRIMARY KEY,
    codigo            VARCHAR(50)  NOT NULL UNIQUE,
    modelo            VARCHAR(100) DEFAULT NULL,
    ubicacion         VARCHAR(255) DEFAULT NULL,
    estado            ENUM('activo','inactivo','mantenimiento') DEFAULT 'activo',
    fecha_instalacion DATETIME     DEFAULT CURRENT_TIMESTAMP,
    id_colegio        INT          NOT NULL,
    CONSTRAINT fk_dispositivo_colegio
        FOREIGN KEY (id_colegio) REFERENCES colegio(id_colegio)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Tabla: medicion
-- =============================================================
CREATE TABLE IF NOT EXISTS medicion (
    id_medicion   BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_dispositivo INT NOT NULL,
    pm2_5         DECIMAL(8,2) DEFAULT NULL,
    pm10          DECIMAL(8,2) DEFAULT NULL,
    co            DECIMAL(8,2) DEFAULT NULL,
    co2           DECIMAL(8,2) DEFAULT NULL,
    o3            DECIMAL(8,2) DEFAULT NULL,
    no2           DECIMAL(8,2) DEFAULT NULL,
    temperatura   DECIMAL(5,2) DEFAULT NULL,
    humedad       DECIMAL(5,2) DEFAULT NULL,
    fecha_hora    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_medicion_dispositivo
        FOREIGN KEY (id_dispositivo) REFERENCES dispositivo(id_dispositivo)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_fecha (fecha_hora),
    INDEX idx_dispositivo_fecha (id_dispositivo, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
