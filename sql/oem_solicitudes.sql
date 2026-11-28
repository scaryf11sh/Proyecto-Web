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
    id_usuario INT NOT NULL,
    sector ENUM('Industrial', 'Comercial', 'Residencial') NOT NULL,
    escala ENUM('Grande', 'Mediano', 'Pequeño') NOT NULL,
    id_servicio INT NOT NULL,
    descripcion TEXT NOT NULL,
    ubicacion VARCHAR(200) NOT NULL,
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
    KEY idx_solicitud_sector (sector),
    KEY idx_solicitud_escala (escala),
    KEY idx_solicitud_prioridad_fecha (prioridad, fecha_registro),
    KEY idx_solicitud_estado (estado),
    KEY idx_solicitud_fecha_visita (fecha_visita),
    KEY idx_solicitud_fecha_registro (fecha_registro),
    CONSTRAINT fk_solicitud_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_solicitud_servicio
        FOREIGN KEY (id_servicio) REFERENCES servicio (id_servicio)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas de prueba: admin@oem.com / AdminOEM2026!; los clientes usan Cliente2026!
INSERT INTO usuario (id_usuario, nombre, correo, telefono, contrasena, rol, fecha_registro) VALUES
(1, 'Administrador OEM', 'admin@oem.com', '6640000000', '$2y$12$4w82NJLMOAoCDKa5DWXa7uiYpPCqebBzvi8b1BchSUgW6cfrgF/52', 'Administrador', '2026-06-15 09:00:00'),
(2, 'Laura Gómez', 'laura.gomez@ejemplo.com', '6640000000', '$2y$12$6.5g/Ad60PSdMhIg5QFhc.q7jpL/7R.i4WUgN7XSa/PVjuOUgbneO', 'Cliente', '2026-07-02 10:15:00'),
(3, 'Roberto Mendoza', 'roberto@manunorte.mx', '6641122334', '$2y$12$ORs0vBXcwmajBVakobdOu.58AvX9.WeB0er2qxFaF3yKz97Br5kim', 'Cliente', '2026-07-05 12:30:00'),
(4, 'Carlos Medina', 'carlos.medina@ejemplo.com', '6642233445', '$2y$12$7XkXxj3C8PfpAKQmvLRwj.qvEfLwgQvoIO2VD6qe7X0Cl0UbVMPTe', 'Cliente', '2026-07-09 16:45:00'),
(5, 'Sofía Herrera', 'sofia@sanrafael.mx', '6643344556', '$2y$12$nJDC77iZRLx7YYl9Rfkr5ux9GqQbyr/rdTBZ3FwVaVOSB..DDyZjC', 'Cliente', '2026-07-14 11:20:00'),
(6, 'Norma Aguilar', 'norma.aguilar@ejemplo.com', '6645500106', '$2y$12$6.5g/Ad60PSdMhIg5QFhc.q7jpL/7R.i4WUgN7XSa/PVjuOUgbneO', 'Cliente', '2026-07-01 08:00:00'),
(7, 'Fernando Zamora', 'fernando.zamora@ejemplo.com', '6645500117', '$2y$12$6.5g/Ad60PSdMhIg5QFhc.q7jpL/7R.i4WUgN7XSa/PVjuOUgbneO', 'Cliente', '2026-07-01 08:30:00'),
(8, 'Héctor Ibarra', 'hector.ibarra@ejemplo.com', '6645500122', '$2y$12$6.5g/Ad60PSdMhIg5QFhc.q7jpL/7R.i4WUgN7XSa/PVjuOUgbneO', 'Cliente', '2026-07-01 09:00:00'),
(9, 'Karla Domínguez', 'karla.dominguez@ejemplo.com', '6645500127', '$2y$12$6.5g/Ad60PSdMhIg5QFhc.q7jpL/7R.i4WUgN7XSa/PVjuOUgbneO', 'Cliente', '2026-07-01 09:30:00');

-- El servicio 8 queda inactivo y el 7 sin solicitudes para probar eliminar/desactivar.
INSERT INTO servicio (id_servicio, nombre, descripcion, activo) VALUES
(1, 'Instalación nueva', 'Obra eléctrica desde cero para naves industriales, locales comerciales o residencias.', 1),
(2, 'Revisión o diagnóstico', 'Inspección técnica de tableros, circuitos, variaciones de voltaje y detección de fallas.', 1),
(3, 'Ampliación o remodelación', 'Adición de circuitos, reubicación de salidas, tableros derivados y adecuación de carga.', 1),
(4, 'Reparación', 'Corrección puntual de cortocircuitos, contactos, apagadores, luminarias o cableado dañado.', 1),
(5, 'Mantenimiento preventivo', 'Limpieza, reapriete de conexiones, balanceo de cargas y pruebas en tableros eléctricos.', 1),
(6, 'Instalación de subestación o transformador', 'Montaje y conexión de equipos de media a baja tensión para industria y comercio.', 1),
(7, 'Sistema de tierras físicas y pararrayos', 'Instalación y medición de red de puesta a tierra para protección de equipos e inmuebles.', 1),
(8, 'Certificación UVIE (Dictamen eléctrico)', 'Trámite de dictamen de verificación de instalaciones eléctricas (temporalmente suspendido).', 0);

-- 64 solicitudes del 01/07/2026 al 25/10/2026; prioridad según la matriz sector x escala.
INSERT INTO solicitud (id_solicitud, folio, id_usuario, sector, escala, id_servicio, descripcion, ubicacion, prioridad, estado, fecha_visita, notas_internas, fecha_registro, fecha_actualizacion) VALUES
(1, 'OEM-26-0001', 6, 'Residencial', 'Mediano', 3, 'Remodelación con tablero derivado y nuevas salidas.', 'Col. Aviación 205, Tijuana', 4, 'Presupuesto', '2026-07-06 17:00:00', NULL, '2026-07-04 16:40:00', '2026-07-06 17:00:00'),
(2, 'OEM-26-0002', 7, 'Comercial', 'Pequeño', 8, 'Verificación de instalación eléctrica para dictamen.', 'Calle Segunda 650, Zona Centro, Tijuana', 2, 'Finalizada', '2026-07-07 14:30:00', NULL, '2026-07-05 14:10:00', '2026-07-08 14:30:00'),
(3, 'OEM-26-0003', 8, 'Comercial', 'Pequeño', 4, 'Contactos y apagadores que no funcionan en varias áreas.', 'Av. Sánchez Taboada 9800, Tijuana', 2, 'Rechazada', NULL, NULL, '2026-07-10 14:00:00', '2026-07-15 14:00:00'),
(4, 'OEM-26-0004', 5, 'Comercial', 'Mediano', 8, 'Dictamen eléctrico UVIE para trámite de contrato de suministro.', 'Calle Segunda 650, Zona Centro, Tijuana', 6, 'Finalizada', '2026-07-18 14:30:00', 'Llamar antes de llegar; hay vigilancia en acceso.', '2026-07-15 12:30:00', '2026-07-19 14:30:00'),
(5, 'OEM-26-0005', 9, 'Industrial', 'Mediano', 3, 'Ampliación de circuitos y reubicación de salidas en la nave.', 'Parque industrial Pacífico, Tijuana', 8, 'Rechazada', NULL, NULL, '2026-07-16 16:15:00', '2026-07-19 16:15:00'),
(6, 'OEM-26-0006', 2, 'Comercial', 'Pequeño', 4, 'Reparación de cortocircuito en un circuito de el local.', 'Blvd. Agua Caliente 2210, Col. Revolución, Tijuana', 2, 'Finalizada', '2026-07-18 15:00:00', 'Requiere permiso de acceso al parque industrial.', '2026-07-16 17:00:00', '2026-07-23 15:00:00'),
(7, 'OEM-26-0007', 4, 'Comercial', 'Mediano', 2, 'Revisión del tablero por disparos frecuentes del interruptor.', 'Calle Segunda 650, Zona Centro, Tijuana', 6, 'Finalizada', '2026-07-20 14:30:00', NULL, '2026-07-17 12:15:00', '2026-07-26 14:30:00'),
(8, 'OEM-26-0008', 3, 'Industrial', 'Pequeño', 2, 'Revisión del tablero por disparos frecuentes del interruptor.', 'Parque industrial Nueva Tijuana, Tijuana', 3, 'Finalizada', '2026-07-24 11:30:00', NULL, '2026-07-22 09:45:00', '2026-07-26 11:30:00'),
(9, 'OEM-26-0009', 6, 'Residencial', 'Mediano', 2, 'Diagnóstico de variaciones de voltaje y calentamiento en cables.', 'Fracc. Santa Fe 88, Tijuana', 4, 'Finalizada', '2026-07-25 10:30:00', NULL, '2026-07-22 15:10:00', '2026-07-28 10:30:00'),
(10, 'OEM-26-0010', 7, 'Industrial', 'Grande', 3, 'Ampliación de circuitos y reubicación de salidas en la nave.', 'Zona industrial Otay, Tijuana', 9, 'Finalizada', '2026-07-27 15:30:00', NULL, '2026-07-24 10:50:00', '2026-07-31 15:30:00'),
(11, 'OEM-26-0011', 8, 'Industrial', 'Grande', 2, 'Diagnóstico de variaciones de voltaje y calentamiento en cables.', 'Parque industrial Mesa de Otay, Tijuana', 9, 'Finalizada', '2026-07-27 15:30:00', NULL, '2026-07-26 14:30:00', '2026-08-01 15:30:00'),
(12, 'OEM-26-0012', 9, 'Comercial', 'Mediano', 2, 'Revisión del tablero por disparos frecuentes del interruptor.', 'Plaza Río, Zona Río, Tijuana', 6, 'Rechazada', '2026-07-31 09:00:00', NULL, '2026-07-29 14:50:00', '2026-08-02 09:00:00'),
(13, 'OEM-26-0013', 4, 'Residencial', 'Grande', 3, 'Ampliación de circuitos y reubicación de salidas en la casa.', 'Fracc. Las Brisas 24, Tijuana', 5, 'En progreso', '2026-08-03 13:30:00', NULL, '2026-08-01 09:30:00', '2026-08-06 13:30:00'),
(14, 'OEM-26-0014', 5, 'Comercial', 'Grande', 6, 'Montaje de subestación para ampliación de carga.', 'Calle Segunda 650, Zona Centro, Tijuana', 7, 'Rechazada', '2026-08-04 16:30:00', 'Cliente recurrente, seguimiento prioritario.', '2026-08-01 12:20:00', '2026-08-05 16:30:00'),
(15, 'OEM-26-0015', 2, 'Comercial', 'Pequeño', 5, 'Reapriete de terminales y pruebas de carga en tableros.', 'Blvd. Agua Caliente 2210, Col. Revolución, Tijuana', 2, 'Cancelada', NULL, 'Requiere permiso de acceso al parque industrial.', '2026-08-02 11:20:00', '2026-08-06 11:20:00'),
(16, 'OEM-26-0016', 4, 'Residencial', 'Grande', 1, 'Obra eléctrica nueva con tablero principal y salidas de fuerza.', 'Fracc. Lomas Altas 41, Tijuana', 5, 'Finalizada', '2026-08-08 09:00:00', NULL, '2026-08-02 16:10:00', '2026-08-10 09:00:00'),
(17, 'OEM-26-0017', 6, 'Industrial', 'Grande', 2, 'Revisión del tablero por disparos frecuentes del interruptor.', 'Zona industrial Otay, Tijuana', 9, 'Presupuesto', '2026-08-06 15:30:00', 'Pedir plano del tablero antes de cotizar.', '2026-08-03 08:40:00', '2026-08-09 15:30:00'),
(18, 'OEM-26-0018', 7, 'Comercial', 'Mediano', 2, 'Diagnóstico de variaciones de voltaje y calentamiento en cables.', 'Calle Segunda 650, Zona Centro, Tijuana', 6, 'Finalizada', '2026-08-07 13:00:00', 'Pedir plano del tablero antes de cotizar.', '2026-08-03 10:50:00', '2026-08-08 13:00:00'),
(19, 'OEM-26-0019', 2, 'Residencial', 'Pequeño', 4, 'Cambio de un apagador que chispea en la sala.', 'Av. Las Palmas 412, Col. Chapultepec, Tijuana', 1, 'Finalizada', NULL, 'Resuelto por teléfono con la clienta; sin visita.', '2026-08-03 12:00:00', '2026-08-09 12:00:00'),
(20, 'OEM-26-0020', 5, 'Comercial', 'Mediano', 2, 'Diagnóstico de variaciones de voltaje y calentamiento en cables.', 'Calle Segunda 650, Zona Centro, Tijuana', 6, 'Finalizada', '2026-08-20 13:00:00', 'Confirmar disponibilidad de energía para pruebas.', '2026-08-18 11:20:00', '2026-08-22 13:00:00'),
(21, 'OEM-26-0021', 3, 'Industrial', 'Grande', 2, 'Diagnóstico de variaciones de voltaje y calentamiento en cables.', 'Parque industrial El Florido, Tijuana', 9, 'En progreso', '2026-08-22 12:30:00', NULL, '2026-08-18 11:50:00', '2026-08-26 12:30:00'),
(22, 'OEM-26-0022', 8, 'Comercial', 'Mediano', 3, 'Ampliación de circuitos y reubicación de salidas en el local.', 'Av. Sánchez Taboada 9800, Tijuana', 6, 'Finalizada', '2026-08-24 15:30:00', NULL, '2026-08-23 17:15:00', '2026-08-25 15:30:00'),
(23, 'OEM-26-0023', 3, 'Industrial', 'Mediano', 1, 'Obra eléctrica nueva con tablero principal y salidas de fuerza.', 'Parque industrial Nueva Tijuana, Tijuana', 8, 'Aceptada', '2026-08-25 13:30:00', NULL, '2026-08-24 09:10:00', '2026-08-26 13:30:00'),
(24, 'OEM-26-0024', 3, 'Industrial', 'Pequeño', 2, 'Revisión del tablero por disparos frecuentes del interruptor.', 'Zona industrial Otay, Tijuana', 3, 'Finalizada', '2026-08-27 12:00:00', NULL, '2026-08-24 15:50:00', '2026-08-31 12:00:00'),
(25, 'OEM-26-0025', 9, 'Comercial', 'Pequeño', 2, 'Diagnóstico de variaciones de voltaje y calentamiento en cables.', 'Calle Segunda 650, Zona Centro, Tijuana', 2, 'Finalizada', '2026-08-27 11:30:00', 'Llamar antes de llegar; hay vigilancia en acceso.', '2026-08-25 11:20:00', '2026-08-28 11:30:00'),
(26, 'OEM-26-0026', 2, 'Residencial', 'Pequeño', 3, 'Remodelación con tablero derivado y nuevas salidas.', 'Fracc. Hacienda Agua Caliente 52, Tijuana', 1, 'Finalizada', '2026-09-04 15:00:00', 'Llamar antes de llegar; hay vigilancia en acceso.', '2026-08-29 08:45:00', '2026-09-10 15:00:00'),
(27, 'OEM-26-0027', 4, 'Industrial', 'Mediano', 5, 'Mantenimiento preventivo anual de tableros y conexiones.', 'Parque industrial Nueva Tijuana, Tijuana', 8, 'Presupuesto', '2026-09-05 17:00:00', NULL, '2026-09-03 16:10:00', '2026-09-07 17:00:00'),
(28, 'OEM-26-0028', 6, 'Industrial', 'Mediano', 1, 'Obra eléctrica nueva con tablero principal y salidas de fuerza.', 'Parque industrial Pacífico, Tijuana', 8, 'Aceptada', '2026-09-05 13:00:00', NULL, '2026-09-04 09:15:00', '2026-09-05 13:00:00'),
(29, 'OEM-26-0029', 7, 'Industrial', 'Pequeño', 4, 'Reparación de cortocircuito en un circuito de la nave.', 'Parque industrial El Florido, Tijuana', 3, 'Finalizada', '2026-09-09 13:30:00', 'Cliente prefiere visita por la mañana.', '2026-09-05 16:30:00', '2026-09-10 13:30:00'),
(30, 'OEM-26-0030', 8, 'Comercial', 'Grande', 6, 'Montaje de subestación para ampliación de carga.', 'Av. Revolución 1100, Zona Centro, Tijuana', 7, 'En progreso', '2026-09-08 15:00:00', 'Cliente recurrente, seguimiento prioritario.', '2026-09-06 12:50:00', '2026-09-08 15:00:00'),
(31, 'OEM-26-0031', 9, 'Residencial', 'Mediano', 1, 'Obra eléctrica nueva con tablero principal y salidas de fuerza.', 'Fracc. Santa Fe 88, Tijuana', 4, 'En progreso', '2026-09-10 14:00:00', NULL, '2026-09-07 10:10:00', '2026-09-11 14:00:00'),
(32, 'OEM-26-0032', 2, 'Industrial', 'Grande', 6, 'Instalación de transformador y acometida en media tensión.', 'Blvd. Industrial 4100, Tijuana', 9, 'Finalizada', '2026-09-11 09:00:00', 'Reprogramar si llueve; trabajo en azotea.', '2026-09-10 14:45:00', '2026-09-16 09:00:00'),
(33, 'OEM-26-0033', 3, 'Industrial', 'Pequeño', 4, 'Reparación de cortocircuito en un circuito de la nave.', 'Carretera Libre Tijuana-Tecate km 14, Tijuana', 3, 'Cancelada', NULL, NULL, '2026-09-11 11:40:00', '2026-09-16 11:40:00'),
(34, 'OEM-26-0034', 4, 'Industrial', 'Grande', 5, 'Reapriete de terminales y pruebas de carga en tableros.', 'Parque industrial Mesa de Otay, Tijuana', 9, 'Finalizada', '2026-09-16 10:30:00', NULL, '2026-09-14 10:50:00', '2026-09-18 10:30:00'),
(35, 'OEM-26-0035', 5, 'Comercial', 'Pequeño', 2, 'Revisión del tablero por disparos frecuentes del interruptor.', 'Av. Revolución 1100, Zona Centro, Tijuana', 2, 'Aceptada', '2026-09-17 09:00:00', 'Cliente recurrente, seguimiento prioritario.', '2026-09-14 17:00:00', '2026-09-23 09:00:00'),
(36, 'OEM-26-0036', 5, 'Comercial', 'Mediano', 2, 'Diagnóstico de variaciones de voltaje y calentamiento en cables.', 'Blvd. Díaz Ordaz 150, Col. Cacho, Tijuana', 6, 'Presupuesto', '2026-09-18 10:30:00', 'Cliente prefiere visita por la mañana.', '2026-09-15 11:45:00', '2026-09-23 10:30:00'),
(37, 'OEM-26-0037', 4, 'Residencial', 'Grande', 2, 'Diagnóstico de variaciones de voltaje y calentamiento en cables.', 'Fracc. Santa Fe 88, Tijuana', 5, 'Rechazada', '2026-09-19 11:00:00', NULL, '2026-09-17 08:20:00', '2026-09-22 11:00:00'),
(38, 'OEM-26-0038', 2, 'Residencial', 'Mediano', 3, 'Agregar circuitos para una recámara nueva y cambiar el tablero.', 'Av. Las Palmas 412, Col. Chapultepec, Tijuana', 4, 'Visita agendada', '2026-10-28 11:00:00', NULL, '2026-09-21 10:00:00', '2026-09-22 10:00:00'),
(39, 'OEM-26-0039', 4, 'Residencial', 'Grande', 5, 'Mantenimiento preventivo anual de tableros y conexiones.', 'Col. Playas de Tijuana 118, Tijuana', 5, 'Cancelada', NULL, 'Pedir plano del tablero antes de cotizar.', '2026-09-24 15:45:00', '2026-09-25 15:45:00'),
(40, 'OEM-26-0040', 6, 'Comercial', 'Grande', 1, 'Instalación eléctrica completa para el local nuevo.', 'Av. Revolución 1100, Zona Centro, Tijuana', 7, 'Aceptada', '2026-09-28 13:00:00', 'Requiere permiso de acceso al parque industrial.', '2026-09-26 16:00:00', '2026-10-03 13:00:00'),
(41, 'OEM-26-0041', 4, 'Residencial', 'Mediano', 2, 'Revisión del tablero y circuitos por apagones recurrentes en la casa.', 'Calle Sexta 215, Col. Madero, Tijuana', 4, 'Finalizada', '2026-09-30 16:00:00', NULL, '2026-09-28 11:00:00', '2026-10-02 16:00:00'),
(42, 'OEM-26-0042', 7, 'Comercial', 'Mediano', 3, 'Nuevos circuitos para soldadoras y compresor en el taller.', 'Calle Ocampo 740, Col. Libertad, Tijuana', 6, 'En progreso', '2026-10-01 12:00:00', 'Material pedido, inicio del trabajo en lunes.', '2026-09-29 08:30:00', '2026-10-05 12:00:00'),
(43, 'OEM-26-0043', 8, 'Residencial', 'Grande', 1, 'Instalación eléctrica completa para casa nueva de dos niveles.', 'Fracc. Valle Bonito 33, Tijuana', 5, 'Presupuesto', '2026-10-02 09:00:00', 'Cotizar con tablero de 24 circuitos.', '2026-09-30 09:00:00', '2026-10-04 09:00:00'),
(44, 'OEM-26-0044', 5, 'Comercial', 'Pequeño', 4, 'Apagadores y luminarias del área de mostrador con falsos contactos.', 'Av. Revolución 1520, Zona Centro, Tijuana', 2, 'Visita agendada', '2026-10-27 10:00:00', NULL, '2026-10-01 10:00:00', '2026-10-02 10:00:00'),
(45, 'OEM-26-0045', 9, 'Industrial', 'Pequeño', 4, 'Se apagan las lámparas de un pasillo de la bodega 2.', 'Parque industrial, Otay, Tijuana', 3, 'En revisión', NULL, 'Acceso por la puerta 2.', '2026-10-02 09:00:00', '2026-10-03 17:00:00'),
(46, 'OEM-26-0046', 2, 'Residencial', 'Pequeño', 4, 'Un contacto de la cocina dejó de funcionar.', 'Av. Las Palmas 412, Col. Chapultepec, Tijuana', 1, 'Enviada', NULL, NULL, '2026-10-03 16:40:00', '2026-10-03 16:40:00'),
(47, 'OEM-26-0047', 2, 'Comercial', 'Mediano', 2, 'Variaciones de voltaje que afectan los refrigeradores de la cocina.', 'Calle Primera 890, Zona Centro, Tijuana', 6, 'Enviada', NULL, NULL, '2026-10-04 10:00:00', '2026-10-04 10:00:00'),
(48, 'OEM-26-0048', 4, 'Comercial', 'Grande', 3, 'Ampliación de carga y tableros derivados para tres locales nuevos.', 'Blvd. Industrial 2300, Plaza Oriente, Tijuana', 7, 'Enviada', NULL, NULL, '2026-10-05 11:00:00', '2026-10-05 11:00:00'),
(49, 'OEM-26-0049', 6, 'Industrial', 'Mediano', 2, 'Disparos frecuentes de interruptores en la línea de empaque.', 'Zona industrial Pacífico, Tijuana', 8, 'Enviada', NULL, NULL, '2026-10-06 14:15:00', '2026-10-06 14:15:00'),
(50, 'OEM-26-0050', 3, 'Industrial', 'Grande', 1, 'Instalación eléctrica completa para nueva línea de ensamblado en nave de 1,200 m².', 'Parque industrial El Florido, Tijuana', 9, 'Enviada', NULL, NULL, '2026-10-07 09:30:00', '2026-10-07 09:30:00'),
(51, 'OEM-26-0051', 7, 'Industrial', 'Grande', 3, 'Remodelación con tablero derivado y nuevas salidas.', 'Zona industrial Otay, Tijuana', 9, 'En progreso', '2026-10-10 13:00:00', NULL, '2026-10-08 17:40:00', '2026-10-12 13:00:00'),
(52, 'OEM-26-0052', 8, 'Comercial', 'Pequeño', 4, 'Reparación de cortocircuito en un circuito de el local.', 'Calle Cuarta 301, Zona Centro, Tijuana', 2, 'Finalizada', '2026-10-12 11:30:00', 'Cliente prefiere visita por la mañana.', '2026-10-09 08:00:00', '2026-10-15 11:30:00'),
(53, 'OEM-26-0053', 9, 'Comercial', 'Mediano', 3, 'Ampliación de circuitos y reubicación de salidas en el local.', 'Plaza Río, Zona Río, Tijuana', 6, 'En progreso', '2026-10-14 14:00:00', NULL, '2026-10-10 08:30:00', '2026-10-15 14:00:00'),
(54, 'OEM-26-0054', 2, 'Industrial', 'Pequeño', 4, 'Contactos y apagadores que no funcionan en varias áreas.', 'Parque industrial El Florido, Tijuana', 3, 'Finalizada', '2026-10-14 11:00:00', NULL, '2026-10-11 17:45:00', '2026-10-18 11:00:00'),
(55, 'OEM-26-0055', 4, 'Comercial', 'Mediano', 3, 'Remodelación con tablero derivado y nuevas salidas.', 'Blvd. Díaz Ordaz 150, Col. Cacho, Tijuana', 6, 'En progreso', '2026-10-15 14:00:00', NULL, '2026-10-12 09:40:00', '2026-10-17 14:00:00'),
(56, 'OEM-26-0056', 3, 'Industrial', 'Grande', 1, 'Obra eléctrica nueva con tablero principal y salidas de fuerza.', 'Blvd. Industrial 4100, Tijuana', 9, 'Aceptada', '2026-10-15 14:00:00', 'Reprogramar si llueve; trabajo en azotea.', '2026-10-13 15:10:00', '2026-10-18 14:00:00'),
(57, 'OEM-26-0057', 6, 'Residencial', 'Mediano', 5, 'Reapriete de terminales y pruebas de carga en tableros.', 'Fracc. Villa Fontana 76, Tijuana', 4, 'Presupuesto', '2026-10-17 16:30:00', NULL, '2026-10-14 10:20:00', '2026-10-19 16:30:00'),
(58, 'OEM-26-0058', 7, 'Residencial', 'Pequeño', 4, 'Reparación de cortocircuito en un circuito de la casa.', 'Fracc. Santa Fe 88, Tijuana', 1, 'En revisión', NULL, NULL, '2026-10-15 09:30:00', '2026-10-17 09:30:00'),
(59, 'OEM-26-0059', 4, 'Residencial', 'Grande', 2, 'Diagnóstico de variaciones de voltaje y calentamiento en cables.', 'Col. Playas de Tijuana 118, Tijuana', 5, 'Presupuesto', '2026-10-19 09:00:00', NULL, '2026-10-16 13:00:00', '2026-10-20 09:00:00'),
(60, 'OEM-26-0060', 8, 'Residencial', 'Pequeño', 2, 'Diagnóstico de variaciones de voltaje y calentamiento en cables.', 'Fracc. Hacienda Agua Caliente 52, Tijuana', 1, 'En revisión', NULL, NULL, '2026-10-18 11:30:00', '2026-10-22 11:30:00'),
(61, 'OEM-26-0061', 3, 'Industrial', 'Mediano', 5, 'Mantenimiento preventivo anual de tableros y conexiones.', 'Parque industrial Mesa de Otay, Tijuana', 8, 'En revisión', NULL, 'Llamar antes de llegar; hay vigilancia en acceso.', '2026-10-19 14:30:00', '2026-10-21 14:30:00'),
(62, 'OEM-26-0062', 5, 'Comercial', 'Pequeño', 5, 'Mantenimiento preventivo anual de tableros y conexiones.', 'Plaza Río, Zona Río, Tijuana', 2, 'En revisión', NULL, NULL, '2026-10-21 16:30:00', '2026-10-22 16:30:00'),
(63, 'OEM-26-0063', 9, 'Comercial', 'Pequeño', 5, 'Reapriete de terminales y pruebas de carga en tableros.', 'Blvd. Díaz Ordaz 150, Col. Cacho, Tijuana', 2, 'Visita agendada', '2026-10-26 11:00:00', 'Pedir plano del tablero antes de cotizar.', '2026-10-23 10:05:00', '2026-10-24 10:05:00'),
(64, 'OEM-26-0064', 2, 'Residencial', 'Pequeño', 4, 'Reparación de cortocircuito en un circuito de la casa.', 'Fracc. Las Brisas 24, Tijuana', 1, 'Visita agendada', '2026-10-29 17:30:00', 'Llamar antes de llegar; hay vigilancia en acceso.', '2026-10-25 12:45:00', '2026-10-25 16:45:00');
