<?php
require_once __DIR__ . '/includes/auth.php';

if (usuario_autenticado()) {
    header('Location: /gestionInv/modulos/dashboard/inicio.php');
} else {
    header('Location: /gestionInv/modulos/auth/login.php');
}
exit;
