# Proyecto de Titulo
# Sistema de Inventario Tecnológico Escolar

Sistema desarrollado en PHP 8.x, Bootstrap 5, SweetAlert2 y MySQL para la administración y trazabilidad descentralizada de activos tecnológicos escolares por colegio en Chile, adhiriendo a los lineamientos gráficos institucionales del Gobierno de Chile.

URL Muestra : http://carloslever.gt.tc/gestionInv

---

## Cuentas de Acceso Institucionales (`@escuelapabloneruda.cl`)

| Perfil / Rol | Correo Electrónico Institucional | RUT | Contraseña |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@educacion.gob.cl` | `11.111.111-1` | `admin123` |
| **Colaborador** | `carlos.gonzalez@escuelapabloneruda.cl` | `22.222.222-2` | `encargado123` |
| **Profesor** | `maria.rojas@escuelapabloneruda.cl` | `33.333.333-3` | `profe123` |
| **Alumno** | `juan.perez@escuelapabloneruda.cl` | `44.444.444-4` | `alumno123` |

---

**Base de Datos**:
   - Conexión PDO automática a MySQL. Las tablas e inventario semilla se crean o actualizan automáticamente en la base `gestion_inventario_colegio`.
   - Script SQL de respaldo disponible en `db/esquema.sql`.

---

**Script Creacion base de datos**
-cambiar 'gestion_inventario_colegio' por el nombre de la base de datos creada.

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
