<?php
require_once __DIR__ . '/../../includes/auth.php';
requerir_autenticacion();

$usuario = obtener_usuario_actual();
$colegio_id = $usuario['colegio_id'];

// Filtro opcional para administrador general
$filtro_colegio = $_GET['colegio_id'] ?? $colegio_id;

if (!es_admin()) {
    $filtro_colegio = $colegio_id;
}

$params_query = [];
$where_colegio = "";

if (!empty($filtro_colegio)) {
    $where_colegio = "WHERE colegio_id = ?";
    $params_query[] = $filtro_colegio;
}

// 1. Total Activos
$stmt_activos = $pdo->prepare("SELECT COUNT(*) FROM activos_tecnologicos {$where_colegio}");
$stmt_activos->execute($params_query);
$total_activos = $stmt_activos->fetchColumn();

// 2. Activos Operativos
$query_operativos = !empty($filtro_colegio) ? "WHERE colegio_id = ? AND estado_activo = 'operativo'" : "WHERE estado_activo = 'operativo'";
$stmt_operativos = $pdo->prepare("SELECT COUNT(*) FROM activos_tecnologicos {$query_operativos}");
$stmt_operativos->execute($params_query);
$total_operativos = $stmt_operativos->fetchColumn();

// 3. Activos con Falla
$query_fallas = !empty($filtro_colegio) ? "WHERE colegio_id = ? AND estado_activo IN ('con_falla', 'en_reparacion', 'dado_de_baja')" : "WHERE estado_activo IN ('con_falla', 'en_reparacion', 'dado_de_baja')";
$stmt_fallas = $pdo->prepare("SELECT COUNT(*) FROM activos_tecnologicos {$query_fallas}");
$stmt_fallas->execute($params_query);
$total_fallas = $stmt_fallas->fetchColumn();

// 4. Total Usuarios
$query_usuarios = !empty($filtro_colegio) ? "WHERE colegio_id = ?" : "";
$stmt_usuarios = $pdo->prepare("SELECT COUNT(*) FROM usuarios {$query_usuarios}");
$stmt_usuarios->execute($params_query);
$total_usuarios = $stmt_usuarios->fetchColumn();

// Colegios para filtro admin
$colegios_lista = [];
if (es_admin()) {
    $colegios_lista = $pdo->query("SELECT id, nombre, rbd FROM colegios ORDER BY nombre ASC")->fetchAll();
}

// Últimos activos registrados
$where_recientes = !empty($filtro_colegio) ? "WHERE a.colegio_id = ?" : "";
$query_recientes = "
    SELECT a.*, c.nombre AS nombre_categoria, col.nombre AS nombre_colegio, d.nombre AS nombre_dependencia
    FROM activos_tecnologicos a
    JOIN categorias c ON a.categoria_id = c.id
    JOIN colegios col ON a.colegio_id = col.id
    LEFT JOIN dependencias d ON a.dependencia_id = d.id
    {$where_recientes}
    ORDER BY a.id DESC LIMIT 6
";
$stmt_recientes = $pdo->prepare($query_recientes);
$stmt_recientes->execute($params_query);
$activos_recientes = $stmt_recientes->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h2 class="h4 fw-bold text-dark mb-0">Panel Principal</h2>
        <span class="text-muted small">Resumen de inventario y usuarios del establecimiento</span>
    </div>

    <?php if (es_admin()): ?>
        <div class="mt-2 mt-md-0">
            <form method="GET" class="d-flex align-items-center gap-2">
                <select name="colegio_id" id="colegio_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todos los Establecimientos --</option>
                    <?php foreach ($colegios_lista as $col): ?>
                        <option value="<?= $col['id'] ?>" <?= ($filtro_colegio == $col['id']) ? 'selected' : '' ?>>
                            RBD <?= htmlspecialchars($col['rbd']) ?> - <?= htmlspecialchars($col['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Tarjetas KPI Simplificadas -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-laptop"></i>
            </div>
            <div>
                <div class="kpi-label">Total Activos</div>
                <div class="kpi-value text-dark"><?= $total_activos ?></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <div class="kpi-label">Operativos</div>
                <div class="kpi-value text-success"><?= $total_operativos ?></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-tools"></i>
            </div>
            <div>
                <div class="kpi-label">Con Falla</div>
                <div class="kpi-value text-dark"><?= $total_fallas ?></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-people"></i>
            </div>
            <div>
                <div class="kpi-label">Usuarios</div>
                <div class="kpi-value text-dark"><?= $total_usuarios ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Activos Recientes -->
<div class="card card-gobcl">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Últimos Activos Registrados</span>
        <?php if (in_array($usuario['perfil'], ['administrador', 'colaborador'])): ?>
            <a href="/gestionInv/modulos/inventario/activos.php?accion=nuevo" class="btn btn-gob-primario btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Registrar Activo
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código Único</th>
                        <th>Equipo</th>
                        <th>Categoría</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activos_recientes)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Sin activos registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activos_recientes as $act): ?>
                            <tr>
                                <td>
                                    <span class="codigo-unico"><?= htmlspecialchars($act['codigo_unico']) ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold"><?= htmlspecialchars($act['nombre']) ?></span>
                                    <small class="text-muted d-block" style="font-size: 0.78rem;"><?= htmlspecialchars($act['marca'] . ' ' . $act['modelo']) ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($act['nombre_categoria']) ?></span></td>
                                <td>
                                    <small><?= htmlspecialchars($act['nombre_dependencia'] ?? 'Sin asignar') ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-estado badge-<?= $act['estado_activo'] ?>">
                                        <?= str_replace('_', ' ', ucfirst($act['estado_activo'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-end py-2">
        <a href="/gestionInv/modulos/inventario/activos.php" class="text-decoration-none fw-semibold small">
            Ver Todos los Activos <i class="bi bi-chevron-right ms-1"></i>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
