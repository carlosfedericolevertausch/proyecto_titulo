<?php
require_once __DIR__ . '/../../includes/auth.php';
requerir_perfil(['administrador', 'colaborador']);

$usuario_logueado = obtener_usuario_actual();

// Procesar Acciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $nombre = sanear($_POST['nombre'] ?? '');
        $ubicacion = sanear($_POST['ubicacion'] ?? '');
        $colegio_id = !empty($_POST['colegio_id']) ? (int)$_POST['colegio_id'] : $usuario_logueado['colegio_id'];

        if (!es_admin()) {
            $colegio_id = $usuario_logueado['colegio_id'];
        }

        if (empty($nombre) || empty($colegio_id)) {
            set_alerta('error', 'Campos Incompletos', 'El Nombre de la dependencia y el Colegio son obligatorios.');
        } else {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE dependencias SET nombre = ?, ubicacion = ?, colegio_id = ? WHERE id = ?");
                $stmt->execute([$nombre, $ubicacion, $colegio_id, $id]);
                set_alerta('success', 'Dependencia Actualizada', "La sala/dependencia '{$nombre}' fue modificada.");
            } else {
                $stmt = $pdo->prepare("INSERT INTO dependencias (nombre, ubicacion, colegio_id) VALUES (?, ?, ?)");
                $stmt->execute([$nombre, $ubicacion, $colegio_id]);
                set_alerta('success', 'Dependencia Creada', "La sala/dependencia '{$nombre}' ha sido registrada.");
            }
            header('Location: dependencias.php');
            exit;
        }
    }
}

// Eliminar
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM dependencias WHERE id = ?");
    $stmt->execute([$id_eliminar]);
    set_alerta('success', 'Dependencia Eliminada', 'La dependencia ha sido eliminada.');
    header('Location: dependencias.php');
    exit;
}

// Editar
$dependencia_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM dependencias WHERE id = ?");
    $stmt->execute([$id_editar]);
    $dependencia_editar = $stmt->fetch();
}

// Consulta de Dependencias
$where = !es_admin() ? "WHERE d.colegio_id = {$usuario_logueado['colegio_id']}" : "";
$query_dependencias = "
    SELECT d.*, c.nombre AS nombre_colegio, c.rbd,
           (SELECT COUNT(*) FROM activos_tecnologicos WHERE dependencia_id = d.id) AS total_activos
    FROM dependencias d
    JOIN colegios c ON d.colegio_id = c.id
    {$where}
    ORDER BY d.nombre ASC
";
$lista_dependencias = $pdo->query($query_dependencias)->fetchAll();

// Colegios para select
$colegios_select = $pdo->query("SELECT id, nombre, rbd FROM colegios ORDER BY nombre ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <div>
        <h2 class="h3 fw-bold text-dark mb-1">
            <i class="bi bi-door-open-fill text-primary me-2"></i>Dependencias y Salas Escolares
        </h2>
        <p class="text-muted mb-0">Gestión de espacios físicos (laboratorios, bibliotecas, salas de clase) por colegio.</p>
    </div>
    <button class="btn btn-gob-primario" data-bs-toggle="modal" data-bs-target="#modalDependencia">
        <i class="bi bi-plus-lg me-1"></i> Registrar Sala / Dependencia
    </button>
</div>

<div class="card card-gobcl">
    <div class="card-header">
        <span><i class="bi bi-building me-2"></i> Listado de Dependencias</span>
        <span class="badge bg-secondary"><?= count($lista_dependencias) ?> salas</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre Sala / Dependencia</th>
                        <th>Ubicación Interna</th>
                        <th>Colegio</th>
                        <th class="text-center">Activos Asignados</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lista_dependencias)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No se han registrado dependencias o salas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lista_dependencias as $dep): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($dep['nombre']) ?></td>
                                <td><?= htmlspecialchars($dep['ubicacion'] ?? 'No especificada') ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        RBD <?= htmlspecialchars($dep['rbd']) ?> - <?= htmlspecialchars($dep['nombre_colegio']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill fs-6"><?= $dep['total_activos'] ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="dependencias.php?editar=<?= $dep['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="dependencias.php?eliminar=<?= $dep['id'] ?>" 
                                       class="btn btn-sm btn-outline-danger btn-confirmar-eliminar" 
                                       data-nombre="<?= htmlspecialchars($dep['nombre']) ?>"
                                       title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal ModalDependencia (Crear / Editar) -->
<div class="modal fade" id="modalDependencia" tabindex="-1" aria-labelledby="modalDependenciaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="dependencias.php" method="POST" autocomplete="off">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id" value="<?= $dependencia_editar['id'] ?? '' ?>">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalDependenciaLabel">
                        <i class="bi bi-door-open me-2"></i>
                        <?= $dependencia_editar ? 'Editar Dependencia' : 'Registrar Nueva Sala' ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-bold">Nombre de la Sala / Dependencia *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?= htmlspecialchars($dependencia_editar['nombre'] ?? '') ?>" placeholder="Ej: Laboratorio de Computación 1" required>
                    </div>

                    <div class="mb-3">
                        <label for="ubicacion" class="form-label fw-bold">Ubicación Interna</label>
                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                               value="<?= htmlspecialchars($dependencia_editar['ubicacion'] ?? '') ?>" placeholder="Ej: Piso 2, Ala Norte">
                    </div>

                    <div class="mb-3">
                        <label for="colegio_id" class="form-label fw-bold">Colegio *</label>
                        <select name="colegio_id" id="colegio_id" class="form-select" <?= !es_admin() ? 'disabled' : '' ?>>
                            <?php foreach ($colegios_select as $col): ?>
                                <option value="<?= $col['id'] ?>" 
                                    <?= ($dependencia_editar['colegio_id'] ?? $usuario_logueado['colegio_id']) == $col['id'] ? 'selected' : '' ?>>
                                    RBD <?= htmlspecialchars($col['rbd']) ?> - <?= htmlspecialchars($col['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!es_admin()): ?>
                            <input type="hidden" name="colegio_id" value="<?= $usuario_logueado['colegio_id'] ?>">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gob-primario">
                        <i class="bi bi-save me-1"></i> Guardar Dependencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($dependencia_editar): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('modalDependencia'));
        myModal.show();
    });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
