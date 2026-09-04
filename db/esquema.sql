-- Base de datos: gestion_inventario_colegio
-- Sistema de Inventario Tecnológico Escolar - Gobierno de Chile

CREATE DATABASE IF NOT EXISTS `gestion_inventario_colegio` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE `gestion_inventario_colegio`;

-- Tabla: colegios
CREATE TABLE IF NOT EXISTS `colegios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rbd` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `direccion` VARCHAR(200) DEFAULT NULL,
  `comuna` VARCHAR(100) DEFAULT NULL,
  `telefono` VARCHAR(30) DEFAULT NULL,
  `creado_en` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla: usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rut` VARCHAR(12) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `clave` VARCHAR(255) NOT NULL,
  `perfil` ENUM('administrador', 'colaborador', 'profesor', 'alumno') NOT NULL DEFAULT 'alumno',
  `colegio_id` INT DEFAULT NULL,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `creado_en` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`colegio_id`) REFERENCES `colegios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla: categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla: dependencias (salas / laboratorios del colegio)
CREATE TABLE IF NOT EXISTS `dependencias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `colegio_id` INT NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `ubicacion` VARCHAR(150) DEFAULT NULL,
  FOREIGN KEY (`colegio_id`) REFERENCES `colegios`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla: activos_tecnologicos (Especificaciones extendidas)
CREATE TABLE IF NOT EXISTS `activos_tecnologicos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_unico` VARCHAR(50) NOT NULL UNIQUE,
  `colegio_id` INT NOT NULL,
  `categoria_id` INT NOT NULL,
  `dependencia_id` INT DEFAULT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `marca` VARCHAR(100) DEFAULT NULL,
  `modelo` VARCHAR(100) DEFAULT NULL,
  `numero_serie` VARCHAR(100) DEFAULT NULL,
  `nombre_equipo_so` VARCHAR(100) DEFAULT NULL,
  
  -- Especificaciones técnicas (PC / Notebooks / Tablets)
  `procesador` VARCHAR(100) DEFAULT NULL,
  `memoria_ram` VARCHAR(50) DEFAULT NULL,
  `almacenamiento` VARCHAR(100) DEFAULT NULL,
  `sistema_operativo` VARCHAR(100) DEFAULT NULL,
  `estado_licencia` VARCHAR(100) DEFAULT NULL,
  
  -- Especificaciones técnicas (Proyectores)
  `resolucion_nativa` VARCHAR(50) DEFAULT NULL,
  `lumens` VARCHAR(50) DEFAULT NULL,
  `horas_lampara` INT DEFAULT 0,
  `conectividad` VARCHAR(150) DEFAULT NULL,
  `accesorios` VARCHAR(255) DEFAULT NULL,
  
  -- Ubicación y asignación extendida
  `departamento_area` VARCHAR(100) DEFAULT NULL,
  `modalidad_ubicacion` VARCHAR(50) DEFAULT 'presencial',
  `responsable_usuario_id` INT DEFAULT NULL,
  
  -- Control administrativo y ciclo de vida
  `estado_activo` ENUM('operativo', 'regular', 'con_falla', 'en_reparacion', 'dado_de_baja') NOT NULL DEFAULT 'operativo',
  `fecha_adquisicion` DATE DEFAULT NULL,
  `fecha_vencimiento_garantia` DATE DEFAULT NULL,
  `proveedor_compra` VARCHAR(150) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `creado_en` DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`colegio_id`) REFERENCES `colegios`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`dependencia_id`) REFERENCES `dependencias`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`responsable_usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Datos Semilla (Colegios)
INSERT INTO `colegios` (`id`, `rbd`, `nombre`, `direccion`, `comuna`, `telefono`) VALUES
(1, '60.924.545-K', 'Escuela Básica Pablo Neruda', 'Pasaje 4 número 100, Población Eleuterio Ramírez', 'Curanilahue', '+56 41 2692273'),


-- Usuarios semilla con correos institucionales @escuelapabloneruda.cl
INSERT INTO `usuarios` (`id`, `rut`, `nombre`, `apellido`, `email`, `clave`, `perfil`, `colegio_id`, `estado`) VALUES
(1, '11.111.111-1', 'Administrador', 'General', 'admin@educacion.gob.cl', '$2y$10$ih/p2ACV8UlSkWjXa85o0umY.jpHWFBRx4AeN1zaldIaUmBBEGF0e', 'administrador', NULL, 'activo'),
(2, '22.222.222-2', 'Carlos', 'González (Encargado)', 'carlos.gonzalez@escuelapabloneruda.cl', '$2y$10$IVeuOrk.txEk48d2AdeXauacsByGpgl5aJmg5P7lV5C2Xr2v3lwP6', 'colaborador', 1, 'activo'),
(3, '33.333.333-3', 'María', 'Rojas (Profesora)', 'maria.rojas@escuelapabloneruda.cl', '$2y$10$qwFSuUMCJqaE/TZi4hQPJOoeaoi41QME8Umyseo5bMesHdxlmB9i.', 'profesor', 1, 'activo'),
(4, '44.444.444-4', 'Juan', 'Pérez (Alumno)', 'juan.perez@escuelapabloneruda.cl', '$2y$10$rjManE1oji21/EbZNnSv6uCMBUkTQ5J.m92JgAMnx/sehWDJXIpKC', 'alumno', 1, 'activo');

-- Categorías Semilla
INSERT INTO `categorias` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Computadores Portátiles (Laptops)', 'Equipos portátiles para docentes y alumnos'),
(2, 'Proyectores y Pantallas', 'Proyectores multimedia y pantallas interactivas'),
(3, 'Tablets Educativas', 'Tablets para laboratorios móviles'),
(4, 'Redes y Telecomunicaciones', 'Routers, switches y puntos de acceso WiFi'),
(5, 'Impresoras y Escáneres', 'Equipos multifuncionales y de impresión');

-- Dependencias Semilla
INSERT INTO `dependencias` (`id`, `colegio_id`, `nombre`, `ubicacion`) VALUES
(1, 1, 'Laboratorio de Computación 1', 'Piso 2, Ala Norte'),
(2, 1, 'Sala de Profesores', 'Piso 1, Edificio Central'),
(3, 1, 'Biblioteca CRA', 'Piso 1, Ala Sur'),
(4, 2, 'Laboratorio Enlaces', 'Piso 2');

-- Amplio Inventario Tecnológico Semilla
INSERT INTO `activos_tecnologicos` (`id`, `codigo_unico`, `colegio_id`, `categoria_id`, `dependencia_id`, `nombre`, `marca`, `modelo`, `numero_serie`, `nombre_equipo_so`, `procesador`, `memoria_ram`, `almacenamiento`, `sistema_operativo`, `estado_licencia`, `resolucion_nativa`, `lumens`, `horas_lampara`, `conectividad`, `accesorios`, `departamento_area`, `modalidad_ubicacion`, `responsable_usuario_id`, `estado_activo`, `fecha_adquisicion`, `fecha_vencimiento_garantia`, `proveedor_compra`, `observaciones`) VALUES
(1, 'ACT-12345-2026-A101', 1, 1, 1, 'Notebook Lenovo ThinkPad E14', 'Lenovo', 'ThinkPad E14 Gen 4', 'SN-LN-998231', 'PC-LAB-01', 'Intel Core i5-1235U', '16 GB DDR4', '512 GB SSD NVMe', 'Windows 11 Pro', 'Original Activada', NULL, NULL, 0, NULL, NULL, 'Departamento de Informática', 'presencial', 2, 'operativo', '2024-03-15', '2027-03-15', 'Lenovo Chile SpA', 'Equipo asignado al laboratorio principal'),
(2, 'ACT-12345-2026-A102', 1, 2, 2, 'Proyector Epson PowerLite X49', 'Epson', 'PowerLite X49', 'SN-EP-334120', NULL, NULL, NULL, NULL, NULL, NULL, '1024x768 XGA', '3600 ANSI Lúmenes', 350, 'HDMI, VGA, USB', 'Control remoto y cable HDMI 5m', 'Unidad Técnico Pedagógica', 'presencial', 3, 'operativo', '2023-08-10', '2025-08-10', 'Suministros Educación Ltda', 'Montado en soporte de techo'),
(3, 'ACT-12345-2026-A103', 1, 3, 3, 'Tablet Samsung Galaxy Tab A8', 'Samsung', 'SM-X200', 'SN-SS-554190', 'TAB-CRA-08', 'Octa-Core 2.0GHz', '4 GB', '64 GB eMMC', 'Android 13', 'Gratuita Google Play', NULL, NULL, 0, NULL, NULL, 'Biblioteca CRA', 'presencial', 2, 'regular', '2024-01-20', '2025-01-20', 'TecnoEscolar Chile', 'Batería presenta desgaste moderado'),
(4, 'ACT-12345-2026-B104', 1, 1, 1, 'All-in-One HP ProOne 440 G9', 'HP', 'ProOne 440 G9', 'SN-HP-887412', 'PC-LAB1-02', 'Intel Core i7-12700', '16 GB DDR4', '512 GB SSD NVMe', 'Windows 11 Pro', 'Original Activada', NULL, NULL, 0, NULL, NULL, 'Laboratorio de Computación', 'presencial', 2, 'operativo', '2024-04-10', '2027-04-10', 'HP Chile Directo', 'Equipo All-in-One pantalla 23.8 pulgadas Full HD con teclado y mouse'),
(5, 'ACT-12345-2026-B105', 1, 1, 2, 'Notebook Dell Latitude 3420', 'Dell', 'Latitude 3420', 'SN-DL-552199', 'LAP-DOCENTE-01', 'Intel Core i5-1135G7', '8 GB DDR4', '256 GB SSD', 'Windows 10 Pro', 'Original Activada', NULL, NULL, 0, NULL, NULL, 'Unidad Técnico Pedagógica', 'teletrabajo', 3, 'operativo', '2023-11-05', '2026-11-05', 'Dell Commercial Chile', 'Asignado en modalidad teletrabajo a docente de computación'),
(6, 'ACT-12345-2026-B106', 1, 2, 3, 'Proyector BenQ MX560 XGA', 'BenQ', 'MX560', 'SN-BQ-991204', NULL, NULL, NULL, NULL, NULL, NULL, '1024x768 XGA', '4000 ANSI Lúmenes', 120, 'HDMI x2, VGA, USB, RS232', 'Control remoto, cable HDMI 2m, bolso', 'Biblioteca CRA', 'presencial', 2, 'operativo', '2024-02-18', '2026-02-18', 'Mercado Público Licitación #77812', 'Proyector portátil de alta luminosidad para actos institucionales'),
(7, 'ACT-12345-2026-B107', 1, 3, 1, 'iPad 9na Generación Apple 10.2"', 'Apple', 'iPad 9th Gen A2602', 'SN-AP-773821', 'IPAD-LAB-01', 'Apple A13 Bionic', '3 GB', '64 GB', 'iPadOS 17', 'Licencia Educación Apple', NULL, NULL, 0, NULL, NULL, 'Laboratorio Móvil', 'presencial', 2, 'operativo', '2024-05-01', '2025-05-01', 'MacOnline Chile SpA', 'Incluye funda de protección industrial y Apple Pencil'),
(8, 'ACT-12345-2026-B108', 1, 5, 2, 'Impresora Multifuncional HP LaserJet Pro MFP M428fdw', 'HP', 'LaserJet Pro MFP M428fdw', 'SN-HP-332901', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Gigabit Ethernet, WiFi Dual Band', NULL, 'Sala de Profesores', 'presencial', 3, 'operativo', '2023-09-12', '2025-09-12', 'Ofimax Chile Ltda', 'Impresora multifuncional láser monocromática de red dúplex'),
(9, 'ACT-12345-2026-B109', 1, 4, 1, 'Access Point Ubiquiti UniFi AP AC Pro', 'Ubiquiti', 'UAP-AC-PRO', 'SN-UB-449102', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Gigabit Ethernet PoE, Wi-Fi 5', NULL, 'Redes e Infraestructura', 'presencial', 2, 'operativo', '2024-01-15', '2026-01-15', 'Redes y Telecomunicaciones Chile', 'Punto de acceso WiFi de alta densidad montado en techo');
