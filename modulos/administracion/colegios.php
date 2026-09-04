<?php
require_once __DIR__ . '/../../includes/auth.php';
requerir_perfil('administrador');

// Procesar Acciones CRUD (Crear / Editar / Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $rbd = sanear($_POST['rbd'] ?? '');
        $nombre = sanear($_POST['nombre'] ?? '');
        $direccion = sanear($_POST['direccion'] ?? '');
        $comuna = sanear($_POST['comuna'] ?? '');
        $telefono = sanear($_POST['telefono'] ?? '');

        if (empty($rbd) || empty($nombre)) {
            set_alerta('error', 'Campos Incompletos', 'El RBD y el Nombre del Colegio son obligatorios.');
        } else {
            if ($id) {
                // Actualizar Colegio
                $stmt = $pdo->prepare("
                    UPDATE colegios 
                    SET rbd = ?, nombre = ?, direccion = ?, comuna = ?, telefono = ?
                    WHERE id = ?
                ");
                $stmt->execute([$rbd, $nombre, $direccion, $comuna, $telefono, $id]);
                set_alerta('success', 'Colegio Actualizado', "El establecimiento {$nombre} fue actualizado.");
            } else {
                // Crear Nuevo Colegio
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO colegios (rbd, nombre, direccion, comuna, telefono)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$rbd, $nombre, $direccion, $comuna, $telefono]);
                    set_alerta('success', 'Colegio Registrado', "El colegio {$nombre} ha sido ingresado exitosamente.");
                } catch (PDOException $e) {
                    set_alerta('error', 'RBD Duplicado', 'El código RBD ingresado ya existe en la base de datos.');
                }
            }
            header('Location: colegios.php');
            exit;
        }
    }
}

// Eliminar Colegio
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    try {
        $stmt = $pdo->prepare("DELETE FROM colegios WHERE id = ?");
        $stmt->execute([$id_eliminar]);
        set_alerta('success', 'Colegio Eliminado', 'El establecimiento ha sido eliminado.');
    } catch (PDOException $e) {
        set_alerta('error', 'No se puede eliminar', 'Este colegio tiene activos o usuarios vinculados.');
    }
    header('Location: colegios.php');
    exit;
}

// Editar
$colegio_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM colegios WHERE id = ?");
    $stmt->execute([$id_editar]);
    $colegio_editar = $stmt->fetch();
}

// Consultar Lista de Colegios con contadores de activos y usuarios
$query_colegios = "
    SELECT c.*, 
           (SELECT COUNT(*) FROM activos_tecnologicos WHERE colegio_id = c.id) AS total_activos,
           (SELECT COUNT(*) FROM usuarios WHERE colegio_id = c.id) AS total_usuarios,
           (SELECT CONCAT(nombre, ' ', apellido) FROM usuarios WHERE colegio_id = c.id AND perfil = 'colaborador' LIMIT 1) AS encargado_nombre
    FROM colegios c
    ORDER BY c.nombre ASC
";
$lista_colegios = $pdo->query($query_colegios)->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <div>
        <h2 class="h3 fw-bold text-dark mb-1">
            <i class="bi bi-bank2 text-primary me-2"></i>Gestión de Colegios y Establecimientos
        </h2>
        <p class="text-muted mb-0">Administración descentralizada por RBD escolar y responsable asignado.</p>
    </div>
    <button class="btn btn-gob-primario" data-bs-toggle="modal" data-bs-target="#modalColegio">
        <i class="bi bi-plus-circle-fill me-1"></i> Registrar Nuevo Colegio
    </button>
</div>

<div class="row g-4">
    <?php if (empty($lista_colegios)): ?>
        <div class="col-12 text-center py-5 text-muted card card-gobcl">
            <i class="bi bi-building fs-1 d-block mb-2"></i>
            No se han registrado colegios en la plataforma.
        </div>
    <?php else: ?>
        <?php foreach ($lista_colegios as $col): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card card-gobcl h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="badge bg-primary fs-6">RBD <?= htmlspecialchars($col['rbd']) ?></span>
                        <div>
                            <a href="colegios.php?editar=<?= $col['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="colegios.php?eliminar=<?= $col['id'] ?>" 
                               class="btn btn-sm btn-outline-danger btn-confirmar-eliminar" 
                               data-nombre="<?= htmlspecialchars($col['nombre']) ?>"
                               title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($col['nombre']) ?></h5>
                        <p class="text-muted small mb-3">
                            <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($col['direccion'] ?? 'Sin dirección') ?>, <?= htmlspecialchars($col['comuna'] ?? '') ?><br>
                            <i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($col['telefono'] ?? 'Sin teléfono') ?>
                        </p>

                        <div class="p-2 bg-light rounded mb-3">
                            <small class="text-muted d-block style-sub">Encargado de Inventario:</small>
                            <span class="fw-semibold text-dark">
                                <i class="bi bi-person-badge text-primary me-1"></i>
                                <?= htmlspecialchars($col['encargado_nombre'] ?? 'Sin encargado asignado') ?>
                            </span>
                        </div>

                        <div class="row text-center border-top pt-2">
                            <div class="col-6 border-end">
                                <span class="d-block h5 fw-bold text-primary mb-0"><?= $col['total_activos'] ?></span>
                                <small class="text-muted">Activos</small>
                            </div>
                            <div class="col-6">
                                <span class="d-block h5 fw-bold text-success mb-0"><?= $col['total_usuarios'] ?></span>
                                <small class="text-muted">Usuarios</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal ModalColegio (Crear / Editar) -->
<div class="modal fade" id="modalColegio" tabindex="-1" aria-labelledby="modalColegioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="colegios.php" method="POST" autocomplete="off">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id" value="<?= $colegio_editar['id'] ?? '' ?>">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalColegioLabel">
                        <i class="bi bi-bank me-2"></i>
                        <?= $colegio_editar ? 'Editar Colegio' : 'Registrar Nuevo Colegio' ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rbd" class="form-label fw-bold">RBD (Rol Base Datos) *</label>
                        <input type="text" class="form-control" id="rbd" name="rbd" 
                               value="<?= htmlspecialchars($colegio_editar['rbd'] ?? '') ?>" placeholder="Ej: 12345-6" required>
                    </div>

                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-bold">Nombre del Colegio *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?= htmlspecialchars($colegio_editar['nombre'] ?? '') ?>" placeholder="Ej: Liceo Bicentenario..." required>
                    </div>

                    <div class="mb-3">
                        <label for="direccion" class="form-label fw-bold">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" 
                               value="<?= htmlspecialchars($colegio_editar['direccion'] ?? '') ?>" placeholder="Av. Principal 123">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="comuna" class="form-label fw-bold">Comuna</label>
                            <input type="text" class="form-control" id="comuna" name="comuna" 
                                   value="<?= htmlspecialchars($colegio_editar['comuna'] ?? '') ?>" placeholder="Santiago">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label fw-bold">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" 
                                   value="<?= htmlspecialchars($colegio_editar['telefono'] ?? '') ?>" placeholder="+56 2 2345 6789">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gob-primario">
                        <i class="bi bi-save me-1"></i> Guardar Colegio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($colegio_editar): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('modalColegio'));
        myModal.show();
    });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
