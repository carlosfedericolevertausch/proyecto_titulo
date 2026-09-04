<?php
require_once __DIR__ . '/../../includes/auth.php';
requerir_autenticacion();

$usuario_logueado = obtener_usuario_actual();
$puede_editar = in_array($usuario_logueado['perfil'], ['administrador', 'colaborador']);

// Procesar Acciones CRUD si tiene permisos
if ($puede_editar && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $nombre = sanear($_POST['nombre'] ?? '');
        $descripcion = sanear($_POST['descripcion'] ?? '');

        if (empty($nombre)) {
            set_alerta('error', 'Nombre Requerido', 'El nombre de la categoría es obligatorio.');
        } else {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?");
                $stmt->execute([$nombre, $descripcion, $id]);
                set_alerta('success', 'Categoría Actualizada', "La categoría '{$nombre}' ha sido modificada.");
            } else {
                $stmt = $pdo->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
                $stmt->execute([$nombre, $descripcion]);
                set_alerta('success', 'Categoría Creada', "La categoría '{$nombre}' ha sido registrada.");
            }
            header('Location: categorias.php');
            exit;
        }
    }
}

// Eliminar
if ($puede_editar && isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->execute([$id_eliminar]);
        set_alerta('success', 'Categoría Eliminada', 'La categoría de activos ha sido eliminada.');
    } catch (PDOException $e) {
        set_alerta('error', 'No se puede eliminar', 'Existen activos tecnológicos vinculados a esta categoría.');
    }
    header('Location: categorias.php');
    exit;
}

// Editar
$categoria_editar = null;
if ($puede_editar && isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$id_editar]);
    $categoria_editar = $stmt->fetch();
}

// Consultar Categorías con conteo de activos
$query_categorias = "
    SELECT c.*, 
           (SELECT COUNT(*) FROM activos_tecnologicos WHERE categoria_id = c.id) AS total_activos
    FROM categorias c
    ORDER BY c.nombre ASC
";
$lista_categorias = $pdo->query($query_categorias)->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <div>
        <h2 class="h3 fw-bold text-dark mb-1">
            <i class="bi bi-tags-fill text-primary me-2"></i>Categorías de Equipos Tecnológicos
        </h2>
        <p class="text-muted mb-0">Clasificación de hardware, componentes y dispositivos institucionales.</p>
    </div>
    <?php if ($puede_editar): ?>
        <button class="btn btn-gob-primario" data-bs-toggle="modal" data-bs-target="#modalCategoria">
            <i class="bi bi-plus-lg me-1"></i> Registrar Categoría
        </button>
    <?php endif; ?>
</div>

<div class="row g-4">
    <?php foreach ($lista_categorias as $cat): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card card-gobcl h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-primary"><i class="bi bi-tag me-1"></i> <?= htmlspecialchars($cat['nombre']) ?></span>
                    <?php if ($puede_editar): ?>
                        <div>
                            <a href="categorias.php?editar=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="categorias.php?eliminar=<?= $cat['id'] ?>" 
                               class="btn btn-sm btn-outline-danger btn-confirmar-eliminar" 
                               data-nombre="<?= htmlspecialchars($cat['nombre']) ?>"
                               title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        <?= htmlspecialchars($cat['descripcion'] ?? 'Sin descripción ingresada.') ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-auto">
                        <small class="text-muted">Equipos registrados:</small>
                        <span class="badge bg-primary rounded-pill fs-6"><?= $cat['total_activos'] ?> activos</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal ModalCategoria -->
<?php if ($puede_editar): ?>
<div class="modal fade" id="modalCategoria" tabindex="-1" aria-labelledby="modalCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="categorias.php" method="POST" autocomplete="off">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id" value="<?= $categoria_editar['id'] ?? '' ?>">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalCategoriaLabel">
                        <i class="bi bi-tag me-2"></i>
                        <?= $categoria_editar ? 'Editar Categoría' : 'Nueva Categoría de Equipos' ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-bold">Nombre de la Categoría *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?= htmlspecialchars($categoria_editar['nombre'] ?? '') ?>" placeholder="Ej: Computadores Portátiles" required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label fw-bold">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                  placeholder="Detalles sobre los equipos contemplados en esta categoría..."><?= htmlspecialchars($categoria_editar['descripcion'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gob-primario">
                        <i class="bi bi-save me-1"></i> Guardar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($categoria_editar): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('modalCategoria'));
        myModal.show();
    });
</script>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
