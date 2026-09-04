<?php
require_once __DIR__ . '/../../includes/auth.php';

if (usuario_autenticado()) {
    header('Location: /gestionInv/modulos/dashboard/inicio.php');
    exit;
}

$error_login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = sanear($_POST['identificador'] ?? '');
    $clave = $_POST['clave'] ?? '';

    if (empty($identificador) || empty($clave)) {
        $error_login = 'Por favor ingrese su RUT/Email y contraseña.';
    } else {
        $stmt = $pdo->prepare("
            SELECT u.*, c.nombre AS nombre_colegio, c.rbd
            FROM usuarios u
            LEFT JOIN colegios c ON u.colegio_id = c.id
            WHERE u.email = ? OR u.rut = ?
        ");
        $stmt->execute([$identificador, $identificador]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($clave, $usuario['clave'])) {
            if ($usuario['estado'] !== 'activo') {
                $error_login = 'Su cuenta se encuentra inactiva.';
            } else {
                $_SESSION['usuario'] = [
                    'id' => $usuario['id'],
                    'rut' => $usuario['rut'],
                    'nombre' => $usuario['nombre'],
                    'apellido' => $usuario['apellido'],
                    'email' => $usuario['email'],
                    'perfil' => $usuario['perfil'],
                    'colegio_id' => $usuario['colegio_id'],
                    'nombre_colegio' => $usuario['nombre_colegio'] ?? 'Administración Central',
                    'rbd' => $usuario['rbd'] ?? 'CENTRAL'
                ];

                set_alerta('success', '¡Bienvenido(a)!', "Hola {$usuario['nombre']}, has ingresado como " . ucfirst($usuario['perfil']) . '.');
                header('Location: /gestionInv/modulos/dashboard/inicio.php');
                exit;
            }
        } else {
            $error_login = 'Credenciales incorrectas. Verifique su RUT/Email y contraseña.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es-CL">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Inventario Tecnológico Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="/gestionInv/assets/css/estilo_gobcl.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <div class="top-gob-stripe"></div>

    <div class="container d-flex flex-column justify-content-center align-items-center flex-grow-1 py-4">
        <div class="card card-gobcl w-100" style="max-width: 480px;">
            <div class="card-body p-4">

                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-laptop fs-3 text-primary"></i>
                        <span class="fw-bold fs-5 text-dark">Inventario Escolar</span>
                    </div>
                    <p class="text-muted small mb-0">Gobierno de Chile &bull; Ministerio de Educación</p>
                </div>

                <?php if (!empty($error_login)): ?>
                    <div class="alert alert-danger py-2 small alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($error_login) ?>
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" autocomplete="off" id="formLogin">
                    <div class="mb-3">
                        <label for="identificador" class="form-label small fw-bold">RUT o Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-person text-muted"></i></span>
                            <input type="text" class="form-control" id="identificador" name="identificador" 
                                   placeholder="admin@educacion.gob.cl o 11.111.111-1" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="clave" class="form-label small fw-bold">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-lock text-muted"></i></span>
                            <input type="password" class="form-control" id="clave" name="clave" 
                                   placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gob-primario w-100 py-2 fw-bold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                    </button>
                </form>

                <!-- Botones y Lista de Credenciales Demostrativas -->
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <small class="fw-bold text-dark"><i class="bi bi-key-fill text-primary me-1"></i> Cuentas Institucionales:</small>
                        <small class="text-muted" style="font-size: 0.72rem;">(Haz clic para autocompletar)</small>
                    </div>

                    <!-- Botones Rápidos -->
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill" onclick="llenarLogin('admin@educacion.gob.cl', 'admin123')">
                            Admin
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="llenarLogin('carlos.gonzalez@escuelapabloneruda.cl', 'encargado123')">
                            Colaborador
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm flex-fill" onclick="llenarLogin('maria.rojas@escuelapabloneruda.cl', 'profe123')">
                            Profesor
                        </button>
                        <button type="button" class="btn btn-outline-dark btn-sm flex-fill" onclick="llenarLogin('juan.perez@escuelapabloneruda.cl', 'alumno123')">
                            Alumno
                        </button>
                    </div>

                    <!-- Lista Desglosada -->
                    <div class="list-group list-group-flush rounded border small">
                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" onclick="llenarLogin('admin@educacion.gob.cl', 'admin123')">
                            <div>
                                <span class="badge badge-perfil-administrador me-1">Administrador</span>
                                <code class="text-dark">admin@educacion.gob.cl</code>
                            </div>
                            <span class="badge bg-light text-dark border">admin123</span>
                        </button>

                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" onclick="llenarLogin('carlos.gonzalez@escuelapabloneruda.cl', 'encargado123')">
                            <div>
                                <span class="badge badge-perfil-colaborador me-1">Colaborador</span>
                                <code class="text-dark">carlos.gonzalez@escuelapabloneruda.cl</code>
                            </div>
                            <span class="badge bg-light text-dark border">encargado123</span>
                        </button>

                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" onclick="llenarLogin('maria.rojas@escuelapabloneruda.cl', 'profe123')">
                            <div>
                                <span class="badge badge-perfil-profesor me-1">Profesor</span>
                                <code class="text-dark">maria.rojas@escuelapabloneruda.cl</code>
                            </div>
                            <span class="badge bg-light text-dark border">profe123</span>
                        </button>

                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" onclick="llenarLogin('juan.perez@escuelapabloneruda.cl', 'alumno123')">
                            <div>
                                <span class="badge badge-perfil-alumno me-1">Alumno</span>
                                <code class="text-dark">juan.perez@escuelapabloneruda.cl</code>
                            </div>
                            <span class="badge bg-light text-dark border">alumno123</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/gestionInv/assets/js/script_alertas.js"></script>
    <script>
        function llenarLogin(usuario, clave) {
            document.getElementById('identificador').value = usuario;
            document.getElementById('clave').value = clave;
            
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'success',
                title: 'Credenciales autocompletadas'
            });
        }
    </script>

    <?php mostrar_alerta(); ?>
</body>
</html>
