-- ============================================================
-- Flownergy - Base de datos actualizada
-- Servidor: localhost (XAMPP/MariaDB)
-- Versión: 2.0 - Incluye todos los campos PZEM-004T y alertas
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS `flownergyy`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE `flownergyy`;

-- ────────────────────────────────────────────────────────────
-- Tabla: inicio_sesion
-- Usuarios del sistema (identificados por su número de ID)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `inicio_sesion` (
  `Id_usuario`  int(11)     NOT NULL AUTO_INCREMENT,
  `Contraseña`  varchar(60) NOT NULL,
  PRIMARY KEY (`Id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Datos de prueba
INSERT IGNORE INTO `inicio_sesion` (`Id_usuario`, `Contraseña`) VALUES
(110, '123'),
(111, '1107856486'),
(120, '1107856486');

-- ────────────────────────────────────────────────────────────
-- Tabla: registro
-- Máquinas registradas por cada usuario
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `registro` (
  `Id_serial`       varchar(50) NOT NULL,
  `Nombre_tipo`     varchar(100) NOT NULL,
  `Voltaje_nominal` float       NOT NULL DEFAULT 110,
  `Version`         varchar(20) NOT NULL DEFAULT '1.0',
  `Id_usuario`      int(11)     NOT NULL,           -- FK al usuario dueño
  `Fecha_registro`  timestamp   NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id_serial`),
  KEY `fk_usuario` (`Id_usuario`),
  CONSTRAINT `fk_usuario` FOREIGN KEY (`Id_usuario`)
      REFERENCES `inicio_sesion` (`Id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dato de prueba (vinculado al usuario 110)
INSERT IGNORE INTO `registro`
    (`Id_serial`, `Nombre_tipo`, `Voltaje_nominal`, `Version`, `Id_usuario`)
VALUES
    ('009', 'Motor manufactura', 180, '3.3', 110);

-- ────────────────────────────────────────────────────────────
-- Tabla: sensores_energia
-- Lecturas del PZEM-004T, guardadas cada 5 minutos
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sensores_energia` (
  `Id_lectura`       int(11)     NOT NULL AUTO_INCREMENT,
  `Id_serial`        varchar(50) NOT NULL,
  `Voltaje`          float       NOT NULL,  -- Voltios (V)
  `Corriente`        float       NOT NULL,  -- Amperios (A)
  `Potencia`         float       NOT NULL,  -- Vatios (W)
  `Energia`          float       NOT NULL,  -- Kilovatios-hora (kWh)
  `Frecuencia`       float       NOT NULL,  -- Hertzios (Hz)
  `Factor_potencia`  float       NOT NULL,  -- cos φ (0 a 1)
  `Estado`           varchar(30) NOT NULL DEFAULT 'ok',
  `Fecha_hora`       timestamp   NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id_lectura`),
  KEY `idx_serial_fecha` (`Id_serial`, `Fecha_hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- Tabla: alertas
-- Picos, bajos, voltaje anormal, factor de potencia bajo,
-- recomendaciones de mantenimiento preventivo
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `alertas` (
  `Id_alerta`  int(11)      NOT NULL AUTO_INCREMENT,
  `Id_serial`  varchar(50)  NOT NULL,
  `Tipo`       enum(
                 'pico_potencia',
                 'bajo_potencia',
                 'voltaje_alto',
                 'voltaje_bajo',
                 'sobrecarga',
                 'fp_bajo',
                 'mantenimiento'
               )              NOT NULL,
  `Mensaje`    varchar(255)  NOT NULL,
  `Valor`      float         NOT NULL,   -- Valor que disparó la alerta
  `Umbral`     float         NOT NULL,   -- Umbral configurado
  `Vista`      tinyint(1)    NOT NULL DEFAULT 0,
  `Fecha_hora` timestamp     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id_alerta`),
  KEY `idx_serial_vista` (`Id_serial`, `Vista`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
