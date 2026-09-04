<?php
require_once __DIR__ . '/../../includes/auth.php';

// Destruir variables de sesión
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Iniciar nueva sesión sólo para pasar el mensaje de notificación
session_start();
set_alerta('info', 'Sesión Finalizada', 'Ha cerrado sesión exitosamente de la plataforma.');

header('Location: /gestionInv/modulos/auth/login.php');
exit;
