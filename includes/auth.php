<?php
/**
 * Middleware de Autenticación y Autorización por Perfiles
 * Sistema de Inventario Tecnológico Escolar - Gobierno de Chile
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/conexion.php';

/**
 * Verifica si existe una sesión activa válida
 */
function usuario_autenticado() {
    return isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['id']);
}

/**
 * Obliga inicio de sesión; redirige al login si no está autenticado
 */
function requerir_autenticacion() {
    if (!usuario_autenticado()) {
        set_alerta('warning', 'Sesión Requerida', 'Por favor inicie sesión para acceder al sistema.');
        header('Location: /gestionInv/modulos/auth/login.php');
        exit;
    }
}

/**
 * Restringe el acceso únicamente a perfiles autorizados
 * Perfiles: 'administrador', 'colaborador', 'profesor', 'alumno'
 */
function requerir_perfil($perfiles_permitidos = []) {
    requerir_autenticacion();
    
    $perfil_actual = $_SESSION['usuario']['perfil'] ?? '';
    
    if (!is_array($perfiles_permitidos)) {
        $perfiles_permitidos = [$perfiles_permitidos];
    }
    
    if (!in_array($perfil_actual, $perfiles_permitidos)) {
        set_alerta('error', 'Acceso Restringido', 'No cuenta con los permisos necesarios para realizar esta acción.');
        header('Location: /gestionInv/modulos/dashboard/inicio.php');
        exit;
    }
}

/**
 * Obtener los datos del usuario actual
 */
function obtener_usuario_actual() {
    return $_SESSION['usuario'] ?? null;
}

/**
 * Helper para verificar si es Administrador General
 */
function es_admin() {
    return (($_SESSION['usuario']['perfil'] ?? '') === 'administrador');
}

/**
 * Helper para verificar si es Colaborador / Encargado de Colegio
 */
function es_colaborador() {
    return (($_SESSION['usuario']['perfil'] ?? '') === 'colaborador');
}

/**
 * Almacena alertas en sesión para ser mostradas por SweetAlert2
 */
function set_alerta($tipo, $titulo, $mensaje) {
    $_SESSION['alerta'] = [
        'tipo' => $tipo, // success, error, warning, info
        'titulo' => $titulo,
        'mensaje' => $mensaje
    ];
}

/**
 * Muestra el script de SweetAlert2 si hay una alerta pendiente en sesión
 */
function mostrar_alerta() {
    if (isset($_SESSION['alerta'])) {
        $alerta = $_SESSION['alerta'];
        $tipo = json_encode($alerta['tipo']);
        $titulo = json_encode($alerta['titulo']);
        $mensaje = json_encode($alerta['mensaje']);
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: {$tipo},
                    title: {$titulo},
                    text: {$mensaje},
                    confirmButtonColor: '#0f69b4',
                    confirmButtonText: 'Aceptar'
                });
            });
        </script>";
        unset($_SESSION['alerta']);
    }
}
