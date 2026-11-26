SET NAMES utf8mb4;
SET time_zone = '-07:00';

CREATE DATABASE IF NOT EXISTS oem_solicitudes
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE oem_solicitudes;

DROP TABLE IF EXISTS solicitud;
DROP TABLE IF EXISTS servicio;
DROP TABLE IF EXISTS usuario;

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    empresa VARCHAR(100) NULL DEFAULT NULL,
    correo VARCHAR(120) NOT NULL,
    telefono VARCHAR(10) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('Cliente', 'Administrador') NOT NULL DEFAULT 'Cliente',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuario_correo (correo),
    KEY idx_usuario_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
