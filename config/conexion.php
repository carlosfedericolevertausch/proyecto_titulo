<?php
/**
 * Conexión PDO a MySQL e Inicialización Automática
 * Sistema de Inventario Tecnológico Escolar - Gobierno de Chile
 */

$db_host = 'localhost';
$db_user = 'root';  // Por defecto en WAMP
$db_pass = '';      // Por defecto en WAMP
$db_name = 'gestion_inventario_colegio';

try {
    // 1. Conexión al servidor MySQL
    $pdo = new PDO("mysql:host={$db_host};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    // 2. Verificar o crear la base de datos
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci");
    $pdo->exec("USE `{$db_name}`");

    // 3. Auto-instalación de tablas si no existen
    $check_table = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    if ($check_table->rowCount() === 0) {
        $sql_file = __DIR__ . '/../db/esquema.sql';
        if (file_exists($sql_file)) {
            $sql_content = file_get_contents($sql_file);
            $pdo->exec($sql_content);
        }
    } else {
        // Migración automática de columnas extendidas para Activos (PC, Notebooks, Tablets, Proyectores)
        $nuevas_columnas = [
            'nombre_equipo_so' => "VARCHAR(100) DEFAULT NULL",
            'procesador' => "VARCHAR(100) DEFAULT NULL",
            'memoria_ram' => "VARCHAR(50) DEFAULT NULL",
            'almacenamiento' => "VARCHAR(100) DEFAULT NULL",
            'sistema_operativo' => "VARCHAR(100) DEFAULT NULL",
            'estado_licencia' => "VARCHAR(100) DEFAULT NULL",
            'resolucion_nativa' => "VARCHAR(50) DEFAULT NULL",
            'lumens' => "VARCHAR(50) DEFAULT NULL",
            'horas_lampara' => "INT DEFAULT 0",
            'conectividad' => "VARCHAR(150) DEFAULT NULL",
            'accesorios' => "VARCHAR(255) DEFAULT NULL",
            'departamento_area' => "VARCHAR(100) DEFAULT NULL",
            'modalidad_ubicacion' => "VARCHAR(50) DEFAULT 'presencial'",
            'fecha_adquisicion' => "DATE DEFAULT NULL",
            'fecha_vencimiento_garantia' => "DATE DEFAULT NULL",
            'proveedor_compra' => "VARCHAR(150) DEFAULT NULL"
        ];

        foreach ($nuevas_columnas as $columna => $definicion) {
            try {
                $pdo->exec("ALTER TABLE `activos_tecnologicos` ADD COLUMN `{$columna}` {$definicion}");
            } catch (PDOException $e) {
                // Columna ya existe
            }
        }
    }

} catch (PDOException $e) {
    die("<div style='padding:20px; font-family:sans-serif; background:#f8d7da; color:#721c24; border-radius:8px; margin:20px;'>
        <h3>Error de Conexión a la Base de Datos</h3>
        <p>No se pudo conectar con el servidor MySQL local.</p>
        <p><strong>Detalle del error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
    </div>");
}

function sanear($datos) {
    return htmlspecialchars(trim($datos), ENT_QUOTES, 'UTF-8');
}

function generar_codigo_unico($colegio_id, $pdo) {
    $stmt = $pdo->prepare("SELECT rbd FROM colegios WHERE id = ?");
    $stmt->execute([$colegio_id]);
    $colegio = $stmt->fetch();
    
    $prefix_colegio = $colegio && !empty($colegio['rbd']) ? preg_replace('/[^A-Za-z0-9]/', '', $colegio['rbd']) : sprintf('%04d', $colegio_id);
    $anio = date('Y');
    
    do {
        $aleatorio = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5));
        $codigo_unico = "ACT-{$prefix_colegio}-{$anio}-{$aleatorio}";
        
        $check = $pdo->prepare("SELECT id FROM activos_tecnologicos WHERE codigo_unico = ?");
        $check->execute([$codigo_unico]);
    } while ($check->fetch());

    return $codigo_unico;
}
