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

CREATE TABLE servicio (
    id_servicio INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    descripcion TEXT NULL DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uk_servicio_nombre (nombre),
    KEY idx_servicio_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitud (
    id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(11) NOT NULL,
    id_usuario INT NULL DEFAULT NULL,
    nombre_contacto VARCHAR(100) NOT NULL,
    telefono_contacto VARCHAR(10) NOT NULL,
    sector ENUM('Industrial', 'Comercial', 'Residencial') NOT NULL,
    escala ENUM('Grande', 'Mediano', 'Pequeño') NOT NULL,
    id_servicio INT NOT NULL,
    descripcion TEXT NOT NULL,
    ubicacion VARCHAR(200) NOT NULL,
    canal_origen ENUM('Web', 'WhatsApp', 'Llamada', 'Recomendación') NOT NULL DEFAULT 'Web',
    prioridad TINYINT UNSIGNED NOT NULL,
    estado ENUM(
        'Enviada',
        'En revisión',
        'Visita agendada',
        'Presupuesto',
        'Aceptada',
        'En progreso',
        'Finalizada',
        'Rechazada',
        'Cancelada'
    ) NOT NULL DEFAULT 'Enviada',
    fecha_visita DATETIME NULL DEFAULT NULL,
    notas_internas TEXT NULL DEFAULT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_solicitud_folio (folio),
    KEY idx_solicitud_usuario (id_usuario),
    KEY idx_solicitud_servicio (id_servicio),
    KEY idx_solicitud_telefono (telefono_contacto),
    KEY idx_solicitud_sector (sector),
    KEY idx_solicitud_escala (escala),
    KEY idx_solicitud_canal (canal_origen),
    KEY idx_solicitud_prioridad_fecha (prioridad, fecha_registro),
    KEY idx_solicitud_estado (estado),
    KEY idx_solicitud_fecha_visita (fecha_visita),
    KEY idx_solicitud_fecha_registro (fecha_registro),
    CONSTRAINT fk_solicitud_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_solicitud_servicio
        FOREIGN KEY (id_servicio) REFERENCES servicio (id_servicio)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
