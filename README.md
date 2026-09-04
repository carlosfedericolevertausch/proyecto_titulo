# Proyecto de Titulo
# Sistema de Inventario Tecnológico Escolar

Sistema desarrollado en PHP 8.x, Bootstrap 5, SweetAlert2 y MySQL para la administración y trazabilidad descentralizada de activos tecnológicos escolares por colegio en Chile, adhiriendo a los lineamientos gráficos institucionales del Gobierno de Chile.

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
