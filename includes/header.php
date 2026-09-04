<?php
require_once __DIR__ . '/auth.php';
$usuario_actual = obtener_usuario_actual();
?>
<!DOCTYPE html>
<html lang="es-CL">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Tecnológico Escolar - GobCL</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="/gestionInv/assets/css/estilo_gobcl.css">
</head>
<body>

    <div class="top-gob-stripe"></div>

    <nav class="navbar navbar-expand-lg navbar-gobcl navbar-dark">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand d-flex align-items-center me-4" href="/gestionInv/modulos/dashboard/inicio.php">
                <i class="bi bi-laptop me-2 text-danger"></i>
                <span>Inventario Escolar</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarGobCL" aria-controls="navbarGobCL" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarGobCL">
                <?php if ($usuario_actual): ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="/gestionInv/modulos/dashboard/inicio.php">Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/gestionInv/modulos/inventario/activos.php">Activos Tecnológicos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/gestionInv/modulos/inventario/categorias.php">Categorías</a>
                        </li>
                        <?php if (in_array($usuario_actual['perfil'], ['administrador', 'colaborador'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/gestionInv/modulos/administracion/dependencias.php">Salas / Dependencias</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/gestionInv/modulos/administracion/usuarios.php">Usuarios</a>
                            </li>
                        <?php endif; ?>
                        <?php if ($usuario_actual['perfil'] === 'administrador'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/gestionInv/modulos/administracion/colegios.php">Colegios</a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <div class="d-flex align-items-center gap-2 text-white">
                        <div class="text-end me-2 d-none d-md-block" style="line-height: 1.2;">
                            <div class="fw-bold small"><?= htmlspecialchars($usuario_actual['nombre'] . ' ' . $usuario_actual['apellido']) ?></div>
                            <small class="text-white-50" style="font-size: 0.75rem;">
                                <?= htmlspecialchars($usuario_actual['nombre_colegio'] ?? 'Administración Central') ?>
                            </small>
                        </div>
                        <span class="badge badge-perfil-<?= $usuario_actual['perfil'] ?> text-capitalize">
                            <?= $usuario_actual['perfil'] ?>
                        </span>
                        <a href="/gestionInv/modulos/auth/cerrar_sesion.php" class="btn btn-outline-light btn-sm ms-2" title="Cerrar Sesión">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container-fluid py-4 px-3 px-md-4 flex-grow-1">
