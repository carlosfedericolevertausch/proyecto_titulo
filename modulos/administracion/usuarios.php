<?php
require_once __DIR__ . '/../../includes/auth.php';
requerir_perfil(['administrador', 'colaborador']);

$usuario_logueado = obtener_usuario_actual();
$errores = [];

// Procesar Acciones CRUD (Crear, Editar, Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $rut = sanear($_POST['rut'] ?? '');
        $nombre = sanear($_POST['nombre'] ?? '');
        $apellido = sanear($_POST['apellido'] ?? '');
        $email = sanear($_POST['email'] ?? '');
        $perfil = sanear($_POST['perfil'] ?? 'alumno');
        $colegio_id = !empty($_POST['colegio_id']) ? (int)$_POST['colegio_id'] : null;
        $estado = sanear($_POST['estado'] ?? 'activo');
        $clave_input = $_POST['clave'] ?? '';

        if (!es_admin()) {
            $colegio_id = $usuario_logueado['colegio_id'];
            if ($perfil === 'administrador') {
                $perfil = 'colaborador';
            }
        }

        if (empty($rut) || empty($nombre) || empty($apellido) || empty($email)) {
            set_alerta('error', 'Campos Incompletos', 'Por favor complete todos los campos obligatorios.');
        } else {
            if ($id) {
                if (!empty($clave_input)) {
                    $clave_hash = password_hash($clave_input, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE usuarios 
                        SET rut = ?, nombre = ?, apellido = ?, email = ?, perfil = ?, colegio_id = ?, estado = ?, clave = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$rut, $nombre, $apellido, $email, $perfil, $colegio_id, $estado, $clave_hash, $id]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE usuarios 
                        SET rut = ?, nombre = ?, apellido = ?, email = ?, perfil = ?, colegio_id = ?, estado = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$rut, $nombre, $apellido, $email, $perfil, $colegio_id, $estado, $id]);
                }
                set_alerta('success', 'Usuario Actualizado', "Los datos de {$nombre} {$apellido} han sido actualizados.");
            } else {
                if (empty($clave_input)) {
                    set_alerta('error', 'Contraseña Requerida', 'Debe especificar una contraseña para el nuevo usuario.');
                } else {
                    $clave_hash = password_hash($clave_input, PASSWORD_DEFAULT);
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO usuarios (rut, nombre, apellido, email, clave, perfil, colegio_id, estado)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$rut, $nombre, $apellido, $email, $clave_hash, $perfil, $colegio_id, $estado]);
                        set_alerta('success', 'Usuario Creado', "El usuario {$nombre} {$apellido} ha sido registrado correctamente.");
                    } catch (PDOException $e) {
                        set_alerta('error', 'Error al Guardar', 'El RUT o Correo ya se encuentra registrado en el sistema.');
                    }
                }
            }
            header('Location: usuarios.php');
            exit;
        }
    }
}

// Acción Eliminar Usuario via GET
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    
    if ($id_eliminar === $usuario_logueado['id']) {
        set_alerta('error', 'Acción No Permitida', 'No puede eliminar su propia cuenta de usuario activa.');
    } else {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id_eliminar]);
        set_alerta('success', 'Usuario Eliminado', 'El registro de usuario ha sido eliminado.');
    }
    header('Location: usuarios.php');
    exit;
}

// Obtener datos para edición si aplica
$usuario_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id_editar]);
    $usuario_editar = $stmt->fetch();
}

// Filtros de búsqueda para Usuarios
$busqueda = sanear($_GET['b'] ?? '');
$filtro_perfil = sanear($_GET['perfil'] ?? '');
$filtro_colegio = !empty($_GET['colegio_id']) ? (int)$_GET['colegio_id'] : null;

$where_clauses = [];
$params_usuario = [];

if (!es_admin()) {
    $where_clauses[] = "u.colegio_id = ?";
    $params_usuario[] = $usuario_logueado['colegio_id'];
} elseif (!empty($filtro_colegio)) {
    $where_clauses[] = "u.colegio_id = ?";
    $params_usuario[] = $filtro_colegio;
}

if (!empty($filtro_perfil)) {
    $where_clauses[] = "u.perfil = ?";
    $params_usuario[] = $filtro_perfil;
}

if (!empty($busqueda)) {
    $where_clauses[] = "(u.rut LIKE ? OR u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ?)";
    $term = "%{$busqueda}%";
    array_push($params_usuario, $term, $term, $term, $term);
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$query_usuarios = "
    SELECT u.*, c.nombre AS nombre_colegio, c.rbd
    FROM usuarios u
    LEFT JOIN colegios c ON u.colegio_id = c.id
    {$where_sql}
    ORDER BY u.nombre ASC
";
$stmt_list = $pdo->prepare($query_usuarios);
$stmt_list->execute($params_usuario);
$lista_usuarios = $stmt_list->fetchAll();

// Lista de Colegios para select
$colegios_select = $pdo->query("SELECT id, nombre, rbd FROM colegios ORDER BY nombre ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
    <div>
        <h2 class="h4 fw-bold text-dark mb-0">Gestión de Usuarios y Perfiles</h2>
        <span class="text-muted small">Administración de perfiles (Administrador, Colaborador, Profesor, Alumno) por colegio</span>
    </div>
    <button class="btn btn-gob-primario btn-sm mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#modalUsuario">
        <i class="bi bi-person-plus-fill me-1"></i> Registrar Nuevo Usuario
    </button>
</div>

<!-- Filtros de Búsqueda por Perfil y Colegio -->
<div class="card card-gobcl mb-3">
    <div class="card-body p-2 bg-white">
        <form method="GET" class="row g-2 align-items-center">
            
            <?php if (es_admin()): ?>
                <div class="col-md-3">
                    <select name="colegio_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Todos los Colegios --</option>
                        <?php foreach ($colegios_select as $col): ?>
                            <option value="<?= $col['id'] ?>" <?= ($filtro_colegio == $col['id']) ? 'selected' : '' ?>>
                                RBD <?= htmlspecialchars($col['rbd']) ?> - <?= htmlspecialchars($col['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-3">
                <select name="perfil" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todos los Perfiles --</option>
                    <option value="administrador" <?= ($filtro_perfil === 'administrador') ? 'selected' : '' ?>>Administrador</option>
                    <option value="colaborador" <?= ($filtro_perfil === 'colaborador') ? 'selected' : '' ?>>Colaborador (Encargado)</option>
                    <option value="profesor" <?= ($filtro_perfil === 'profesor') ? 'selected' : '' ?>>Profesor</option>
                    <option value="alumno" <?= ($filtro_perfil === 'alumno') ? 'selected' : '' ?>>Alumno</option>
                </select>
            </div>

            <div class="<?= es_admin() ? 'col-md-5' : 'col-md-8' ?>">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" name="b" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar por RUT, nombre, apellido o correo...">
                </div>
            </div>

            <div class="col-md-1 text-end">
                <a href="usuarios.php" class="btn btn-outline-secondary btn-sm w-100" title="Limpiar Filtros"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Usuarios -->
<div class="card card-gobcl">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>RUT</th>
                        <th>Nombre Completo</th>
                        <th>Correo Electrónico</th>
                        <th>Perfil / Rol</th>
                        <th>Colegio Asignado</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lista_usuarios)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No se encontraron usuarios para los criterios seleccionados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lista_usuarios as $usr): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($usr['rut']) ?></td>
                                <td>
                                    <?= htmlspecialchars($usr['nombre'] . ' ' . $usr['apellido']) ?>
                                </td>
                                <td><?= htmlspecialchars($usr['email']) ?></td>
                                <td>
                                    <span class="badge badge-perfil-<?= $usr['perfil'] ?> text-capitalize">
                                        <?= $usr['perfil'] ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($usr['nombre_colegio'] ?? 'Administración Central') ?></small>
                                </td>
                                <td>
                                    <?php if ($usr['estado'] === 'activo'): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="usuarios.php?editar=<?= $usr['id'] ?>" class="btn btn-sm btn-link text-primary p-0 me-2" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($usr['id'] !== $usuario_logueado['id']): ?>
                                        <a href="usuarios.php?eliminar=<?= $usr['id'] ?>" 
                                           class="btn btn-sm btn-link text-danger p-0 btn-confirmar-eliminar" 
                                           data-nombre="<?= htmlspecialchars($usr['nombre'] . ' ' . $usr['apellido']) ?>"
                                           title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal ModalUsuario (Crear / Editar) -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="usuarios.php" method="POST" autocomplete="off">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id" value="<?= $usuario_editar['id'] ?? '' ?>">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6" id="modalUsuarioLabel">
                        <?= $usuario_editar ? 'Editar Usuario' : 'Registrar Nuevo Usuario' ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="rut" class="form-label small fw-bold">RUT (Ej: 12.345.678-9) *</label>
                            <input type="text" class="form-control" id="rut" name="rut" 
                                   value="<?= htmlspecialchars($usuario_editar['rut'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label small fw-bold">Correo Electrónico *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($usuario_editar['email'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="nombre" class="form-label small fw-bold">Nombres *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= htmlspecialchars($usuario_editar['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="apellido" class="form-label small fw-bold">Apellidos *</label>
                            <input type="text" class="form-control" id="apellido" name="apellido" 
                                   value="<?= htmlspecialchars($usuario_editar['apellido'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="perfil" class="form-label small fw-bold">Perfil / Rol de Acceso *</label>
                            <select name="perfil" id="perfil" class="form-select" required>
                                <?php if (es_admin()): ?>
                                    <option value="administrador" <?= ($usuario_editar['perfil'] ?? '') === 'administrador' ? 'selected' : '' ?>>Administrador General</option>
                                <?php endif; ?>
                                <option value="colaborador" <?= ($usuario_editar['perfil'] ?? '') === 'colaborador' ? 'selected' : '' ?>>Colaborador (Encargado)</option>
                                <option value="profesor" <?= ($usuario_editar['perfil'] ?? '') === 'profesor' ? 'selected' : '' ?>>Profesor</option>
                                <option value="alumno" <?= ($usuario_editar['perfil'] ?? '') === 'alumno' ? 'selected' : '' ?>>Alumno</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="colegio_id" class="form-label small fw-bold">Colegio Asignado</label>
                            <select name="colegio_id" id="colegio_id" class="form-select" <?= !es_admin() ? 'disabled' : '' ?>>
                                <option value="">-- Sin Colegio (Administración Central) --</option>
                                <?php foreach ($colegios_select as $col): ?>
                                    <option value="<?= $col['id'] ?>" 
                                        <?= ($usuario_editar['colegio_id'] ?? $usuario_logueado['colegio_id']) == $col['id'] ? 'selected' : '' ?>>
                                        RBD <?= htmlspecialchars($col['rbd']) ?> - <?= htmlspecialchars($col['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!es_admin()): ?>
                                <input type="hidden" name="colegio_id" value="<?= $usuario_logueado['colegio_id'] ?>">
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="clave" class="form-label small fw-bold">
                                Contraseña <?= $usuario_editar ? '(Dejar en blanco para mantener actual)' : '*' ?>
                            </label>
                            <input type="password" class="form-control" id="clave" name="clave" 
                                   placeholder="••••••••" <?= $usuario_editar ? '' : 'required' ?>>
                        </div>

                        <div class="col-md-6">
                            <label for="estado" class="form-label small fw-bold">Estado</label>
                            <select name="estado" id="estado" class="form-select">
                                <option value="activo" <?= ($usuario_editar['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= ($usuario_editar['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gob-primario btn-sm">
                        <i class="bi bi-save me-1"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($usuario_editar): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('modalUsuario'));
        myModal.show();
    });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
