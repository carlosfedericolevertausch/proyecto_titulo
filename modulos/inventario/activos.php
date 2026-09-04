<?php
require_once __DIR__ . '/../../includes/auth.php';
requerir_autenticacion();

$usuario_logueado = obtener_usuario_actual();
$puede_gestionar = in_array($usuario_logueado['perfil'], ['administrador', 'colaborador']);

// Procesar Formulario CRUD de Activos Tecnológicos
if ($puede_gestionar && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $colegio_id = !empty($_POST['colegio_id']) ? (int)$_POST['colegio_id'] : $usuario_logueado['colegio_id'];
        $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $dependencia_id = !empty($_POST['dependencia_id']) ? (int)$_POST['dependencia_id'] : null;
        
        // Identificación
        $nombre = sanear($_POST['nombre'] ?? '');
        $marca = sanear($_POST['marca'] ?? '');
        $modelo = sanear($_POST['modelo'] ?? '');
        $numero_serie = sanear($_POST['numero_serie'] ?? '');
        $nombre_equipo_so = sanear($_POST['nombre_equipo_so'] ?? '');

        // Especificaciones técnicas (PC / Notebooks / Tablets)
        $procesador = sanear($_POST['procesador'] ?? '');
        $memoria_ram = sanear($_POST['memoria_ram'] ?? '');
        $almacenamiento = sanear($_POST['almacenamiento'] ?? '');
        $sistema_operativo = sanear($_POST['sistema_operativo'] ?? '');
        $estado_licencia = sanear($_POST['estado_licencia'] ?? '');

        // Especificaciones técnicas (Proyectores)
        $resolucion_nativa = sanear($_POST['resolucion_nativa'] ?? '');
        $lumens = sanear($_POST['lumens'] ?? '');
        $horas_lampara = !empty($_POST['horas_lampara']) ? (int)$_POST['horas_lampara'] : 0;
        $conectividad = sanear($_POST['conectividad'] ?? '');
        $accesorios = sanear($_POST['accesorios'] ?? '');

        // Ubicación y Asignación
        $departamento_area = sanear($_POST['departamento_area'] ?? '');
        $modalidad_ubicacion = sanear($_POST['modalidad_ubicacion'] ?? 'presencial');
        $responsable_usuario_id = !empty($_POST['responsable_usuario_id']) ? (int)$_POST['responsable_usuario_id'] : null;

        // Control administrativo y ciclo de vida
        $estado_activo = sanear($_POST['estado_activo'] ?? 'operativo');
        $fecha_adquisicion = !empty($_POST['fecha_adquisicion']) ? $_POST['fecha_adquisicion'] : null;
        $fecha_vencimiento_garantia = !empty($_POST['fecha_vencimiento_garantia']) ? $_POST['fecha_vencimiento_garantia'] : null;
        $proveedor_compra = sanear($_POST['proveedor_compra'] ?? '');
        $observaciones = sanear($_POST['observaciones'] ?? '');

        if (!es_admin()) {
            $colegio_id = $usuario_logueado['colegio_id'];
        }

        if (empty($nombre) || empty($categoria_id) || empty($colegio_id)) {
            set_alerta('error', 'Campos Incompletos', 'Nombre, Categoría y Colegio son campos obligatorios.');
        } else {
            if ($id) {
                $stmt = $pdo->prepare("
                    UPDATE activos_tecnologicos 
                    SET colegio_id = ?, categoria_id = ?, dependencia_id = ?, nombre = ?, 
                        marca = ?, modelo = ?, numero_serie = ?, nombre_equipo_so = ?,
                        procesador = ?, memoria_ram = ?, almacenamiento = ?, sistema_operativo = ?, estado_licencia = ?,
                        resolucion_nativa = ?, lumens = ?, horas_lampara = ?, conectividad = ?, accesorios = ?,
                        departamento_area = ?, modalidad_ubicacion = ?, responsable_usuario_id = ?,
                        estado_activo = ?, fecha_adquisicion = ?, fecha_vencimiento_garantia = ?, proveedor_compra = ?, observaciones = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $colegio_id, $categoria_id, $dependencia_id, $nombre,
                    $marca, $modelo, $numero_serie, $nombre_equipo_so,
                    $procesador, $memoria_ram, $almacenamiento, $sistema_operativo, $estado_licencia,
                    $resolucion_nativa, $lumens, $horas_lampara, $conectividad, $accesorios,
                    $departamento_area, $modalidad_ubicacion, $responsable_usuario_id,
                    $estado_activo, $fecha_adquisicion, $fecha_vencimiento_garantia, $proveedor_compra, $observaciones,
                    $id
                ]);
                set_alerta('success', 'Activo Actualizado', "El equipo '{$nombre}' fue actualizado correctamente.");
            } else {
                $codigo_unico = generar_codigo_unico($colegio_id, $pdo);

                $stmt = $pdo->prepare("
                    INSERT INTO activos_tecnologicos 
                    (codigo_unico, colegio_id, categoria_id, dependencia_id, nombre, marca, modelo, numero_serie, nombre_equipo_so,
                     procesador, memoria_ram, almacenamiento, sistema_operativo, estado_licencia,
                     resolucion_nativa, lumens, horas_lampara, conectividad, accesorios,
                     departamento_area, modalidad_ubicacion, responsable_usuario_id,
                     estado_activo, fecha_adquisicion, fecha_vencimiento_garantia, proveedor_compra, observaciones)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $codigo_unico, $colegio_id, $categoria_id, $dependencia_id, $nombre, $marca, $modelo, $numero_serie, $nombre_equipo_so,
                    $procesador, $memoria_ram, $almacenamiento, $sistema_operativo, $estado_licencia,
                    $resolucion_nativa, $lumens, $horas_lampara, $conectividad, $accesorios,
                    $departamento_area, $modalidad_ubicacion, $responsable_usuario_id,
                    $estado_activo, $fecha_adquisicion, $fecha_vencimiento_garantia, $proveedor_compra, $observaciones
                ]);

                set_alerta('success', 'Activo Registrado', "Se generó el Código Único: {$codigo_unico}");
            }
            header('Location: activos.php');
            exit;
        }
    }
}

// Eliminar Activo
if ($puede_gestionar && isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM activos_tecnologicos WHERE id = ?");
    $stmt->execute([$id_eliminar]);
    set_alerta('success', 'Activo Eliminado', 'El activo tecnológico fue eliminado.');
    header('Location: activos.php');
    exit;
}

// Editar Activo
$activo_editar = null;
if ($puede_gestionar && isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM activos_tecnologicos WHERE id = ?");
    $stmt->execute([$id_editar]);
    $activo_editar = $stmt->fetch();
}

// Filtros de búsqueda
$busqueda = sanear($_GET['b'] ?? '');
$filtro_estado = sanear($_GET['estado'] ?? '');
$filtro_categoria = !empty($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : null;
$filtro_colegio = !empty($_GET['colegio_id']) ? (int)$_GET['colegio_id'] : null;

$where_clauses = [];
$params_search = [];

if (!es_admin()) {
    $where_clauses[] = "a.colegio_id = ?";
    $params_search[] = $usuario_logueado['colegio_id'];
} elseif (!empty($filtro_colegio)) {
    $where_clauses[] = "a.colegio_id = ?";
    $params_search[] = $filtro_colegio;
}

if (!empty($busqueda)) {
    $where_clauses[] = "(a.codigo_unico LIKE ? OR a.nombre LIKE ? OR a.marca LIKE ? OR a.modelo LIKE ? OR a.numero_serie LIKE ? OR a.procesador LIKE ?)";
    $term = "%{$busqueda}%";
    array_push($params_search, $term, $term, $term, $term, $term, $term);
}

if (!empty($filtro_estado)) {
    $where_clauses[] = "a.estado_activo = ?";
    $params_search[] = $filtro_estado;
}

if (!empty($filtro_categoria)) {
    $where_clauses[] = "a.categoria_id = ?";
    $params_search[] = $filtro_categoria;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$query_activos = "
    SELECT a.*, 
           cat.nombre AS nombre_categoria,
           col.nombre AS nombre_colegio, col.rbd,
           dep.nombre AS nombre_dependencia,
           CONCAT(u.nombre, ' ', u.apellido) AS nombre_responsable
    FROM activos_tecnologicos a
    JOIN categorias cat ON a.categoria_id = cat.id
    JOIN colegios col ON a.colegio_id = col.id
    LEFT JOIN dependencias dep ON a.dependencia_id = dep.id
    LEFT JOIN usuarios u ON a.responsable_usuario_id = u.id
    {$where_sql}
    ORDER BY a.id DESC
";
$stmt_act = $pdo->prepare($query_activos);
$stmt_act->execute($params_search);
$lista_activos = $stmt_act->fetchAll();

$colegio_filtro_id = !es_admin() ? $usuario_logueado['colegio_id'] : ($activo_editar['colegio_id'] ?? $usuario_logueado['colegio_id']);

$categorias_lista = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC")->fetchAll();
$colegios_lista = $pdo->query("SELECT id, nombre, rbd FROM colegios ORDER BY nombre ASC")->fetchAll();

$dependencias_lista = [];
$usuarios_lista = [];
if (!empty($colegio_filtro_id)) {
    $stmt_dep = $pdo->prepare("SELECT id, nombre FROM dependencias WHERE colegio_id = ? ORDER BY nombre ASC");
    $stmt_dep->execute([$colegio_filtro_id]);
    $dependencias_lista = $stmt_dep->fetchAll();

    $stmt_usr = $pdo->prepare("SELECT id, nombre, apellido, perfil FROM usuarios WHERE colegio_id = ? ORDER BY nombre ASC");
    $stmt_usr->execute([$colegio_filtro_id]);
    $usuarios_lista = $stmt_usr->fetchAll();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
    <div>
        <h2 class="h4 fw-bold text-dark mb-0">Inventario Tecnológico</h2>
        <span class="text-muted small">Gestión de especificidades técnicas para Computadores, Notebooks, Tablets y Proyectores</span>
    </div>
    <?php if ($puede_gestionar): ?>
        <button class="btn btn-gob-primario btn-sm mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#modalActivo">
            <i class="bi bi-plus-lg me-1"></i> Registrar Nuevo Activo
        </button>
    <?php endif; ?>
</div>

<!-- Filtros de Búsqueda -->
<div class="card card-gobcl mb-3">
    <div class="card-body p-2 bg-white">
        <form method="GET" class="row g-2 align-items-center">
            <?php if (es_admin()): ?>
                <div class="col-md-3">
                    <select name="colegio_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Todos los Colegios --</option>
                        <?php foreach ($colegios_lista as $col): ?>
                            <option value="<?= $col['id'] ?>" <?= ($filtro_colegio == $col['id']) ? 'selected' : '' ?>>
                                RBD <?= htmlspecialchars($col['rbd']) ?> - <?= htmlspecialchars($col['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="<?= es_admin() ? 'col-md-3' : 'col-md-5' ?>">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" name="b" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar por código, serie, CPU, nombre...">
                </div>
            </div>

            <div class="col-md-3">
                <select name="categoria_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todas las Categorías</option>
                    <?php foreach ($categorias_lista as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($filtro_categoria == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos los Estados</option>
                    <option value="operativo" <?= ($filtro_estado === 'operativo') ? 'selected' : '' ?>>Operativo</option>
                    <option value="regular" <?= ($filtro_estado === 'regular') ? 'selected' : '' ?>>Regular</option>
                    <option value="con_falla" <?= ($filtro_estado === 'con_falla') ? 'selected' : '' ?>>Con Falla</option>
                    <option value="en_reparacion" <?= ($filtro_estado === 'en_reparacion') ? 'selected' : '' ?>>En Reparación</option>
                    <option value="dado_de_baja" <?= ($filtro_estado === 'dado_de_baja') ? 'selected' : '' ?>>Dado de Baja</option>
                </select>
            </div>

            <div class="col-md-1 text-end">
                <a href="activos.php" class="btn btn-outline-secondary btn-sm w-100" title="Limpiar"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Activos Tecnológicos -->
<div class="card card-gobcl">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código Único</th>
                        <th>Equipo / Marca</th>
                        <th>Categoría</th>
                        <th>Especificaciones Clave</th>
                        <th>Ubicación / Área</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lista_activos)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No se encontraron activos tecnológicos.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lista_activos as $act): ?>
                            <tr>
                                <td>
                                    <span class="codigo-unico"><?= htmlspecialchars($act['codigo_unico']) ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($act['nombre']) ?></span>
                                    <small class="text-muted d-block" style="font-size: 0.78rem;">
                                        <?= htmlspecialchars(($act['marca'] ?? '') . ' ' . ($act['modelo'] ?? '')) ?> 
                                        <?= !empty($act['numero_serie']) ? '| S/N: ' . htmlspecialchars($act['numero_serie']) : '' ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($act['nombre_categoria']) ?></span>
                                </td>
                                <td>
                                    <small class="d-block text-dark">
                                        <?php if (!empty($act['procesador'])): ?>
                                            <i class="bi bi-cpu text-primary me-1"></i><?= htmlspecialchars($act['procesador']) ?> (<?= htmlspecialchars($act['memoria_ram'] ?? '') ?>)
                                        <?php elseif (!empty($act['lumens'])): ?>
                                            <i class="bi bi-brightness-high text-warning me-1"></i><?= htmlspecialchars($act['lumens']) ?> | <?= htmlspecialchars($act['resolucion_nativa'] ?? '') ?>
                                        <?php else: ?>
                                            <span class="text-muted">Estándar</span>
                                        <?php endif; ?>
                                    </small>
                                    <?php if (!empty($act['sistema_operativo'])): ?>
                                        <small class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-windows me-1"></i><?= htmlspecialchars($act['sistema_operativo']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-semibold small d-block"><?= htmlspecialchars($act['nombre_colegio']) ?></span>
                                    <small class="text-muted"><i class="bi bi-door-open me-1"></i><?= htmlspecialchars($act['nombre_dependencia'] ?? 'Sin asignación') ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-estado badge-<?= $act['estado_activo'] ?>">
                                        <?= str_replace('_', ' ', ucfirst($act['estado_activo'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <!-- Botón Ver Ficha Completa -->
                                    <button class="btn btn-sm btn-link text-info p-0 me-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalFicha<?= $act['id'] ?>" 
                                            title="Ver Ficha Técnica Completa">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if ($puede_gestionar): ?>
                                        <a href="activos.php?editar=<?= $act['id'] ?>" class="btn btn-sm btn-link text-primary p-0 me-2" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="activos.php?eliminar=<?= $act['id'] ?>" 
                                           class="btn btn-sm btn-link text-danger p-0 btn-confirmar-eliminar" 
                                           data-nombre="<?= htmlspecialchars($act['nombre']) ?>"
                                           title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Modal Ficha Técnica Completa -->
                            <div class="modal fade" id="modalFicha<?= $act['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-dark text-white">
                                            <h5 class="modal-title fs-6">
                                                <i class="bi bi-card-checklist me-2"></i>Ficha Técnica Institucional
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                                <div>
                                                    <h5 class="fw-bold text-primary mb-0"><?= htmlspecialchars($act['nombre']) ?></h5>
                                                    <small class="text-muted"><?= htmlspecialchars($act['marca'] . ' ' . $act['modelo']) ?></small>
                                                </div>
                                                <span class="codigo-unico fs-6"><?= htmlspecialchars($act['codigo_unico']) ?></span>
                                            </div>

                                            <div class="row g-3">
                                                <!-- Identificación -->
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 h-100">
                                                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="bi bi-person-badge text-primary me-1"></i> Identificación del Equipo</h6>
                                                        <ul class="list-unstyled small mb-0">
                                                            <li><strong>Número de Serie:</strong> <code><?= htmlspecialchars($act['numero_serie'] ?? 'N/A') ?></code></li>
                                                            <li><strong>Nombre en SO:</strong> <?= htmlspecialchars($act['nombre_equipo_so'] ?? 'No configurado') ?></li>
                                                            <li><strong>Categoría:</strong> <?= htmlspecialchars($act['nombre_categoria']) ?></li>
                                                            <li><strong>Establecimiento:</strong> <?= htmlspecialchars($act['nombre_colegio']) ?></li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                <!-- Especificaciones Técnicas -->
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 h-100">
                                                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="bi bi-cpu text-success me-1"></i> Especificaciones Técnicas</h6>
                                                        <ul class="list-unstyled small mb-0">
                                                            <?php if (!empty($act['procesador'])): ?>
                                                                <li><strong>Procesador (CPU):</strong> <?= htmlspecialchars($act['procesador']) ?></li>
                                                                <li><strong>Memoria RAM:</strong> <?= htmlspecialchars($act['memoria_ram']) ?></li>
                                                                <li><strong>Almacenamiento:</strong> <?= htmlspecialchars($act['almacenamiento']) ?></li>
                                                                <li><strong>Sistema Operativo:</strong> <?= htmlspecialchars($act['sistema_operativo']) ?> (<?= htmlspecialchars($act['estado_licencia'] ?? 'Licencia Normal') ?>)</li>
                                                            <?php elseif (!empty($act['lumens'])): ?>
                                                                <li><strong>Resolución Nativa:</strong> <?= htmlspecialchars($act['resolucion_nativa']) ?></li>
                                                                <li><strong>Lúmenes (Luz):</strong> <?= htmlspecialchars($act['lumens']) ?></li>
                                                                <li><strong>Horas Lámpara:</strong> <?= $act['horas_lampara'] ?> hrs</li>
                                                                <li><strong>Conectividad:</strong> <?= htmlspecialchars($act['conectividad']) ?></li>
                                                                <li><strong>Accesorios:</strong> <?= htmlspecialchars($act['accesorios'] ?? 'Sin accesorios registrados') ?></li>
                                                            <?php else: ?>
                                                                <li class="text-muted">Especificaciones estándar</li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </div>

                                                <!-- Asignación y Ubicación -->
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 h-100">
                                                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="bi bi-geo-alt text-danger me-1"></i> Asignación y Ubicación</h6>
                                                        <ul class="list-unstyled small mb-0">
                                                            <li><strong>Usuario Responsable:</strong> <?= htmlspecialchars($act['nombre_responsable'] ?? 'Sin responsable asignado') ?></li>
                                                            <li><strong>Sala / Dependencia:</strong> <?= htmlspecialchars($act['nombre_dependencia'] ?? 'Sin asignación') ?></li>
                                                            <li><strong>Depto / Área:</strong> <?= htmlspecialchars($act['departamento_area'] ?? 'No especificado') ?></li>
                                                            <li><strong>Modalidad:</strong> <span class="text-capitalize"><?= htmlspecialchars($act['modalidad_ubicacion'] ?? 'presencial') ?></span></li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                <!-- Control Administrativo -->
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 h-100">
                                                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="bi bi-shield-check text-warning me-1"></i> Control y Ciclo de Vida</h6>
                                                        <ul class="list-unstyled small mb-0">
                                                            <li><strong>Estado Operativo:</strong> <span class="badge badge-estado badge-<?= $act['estado_activo'] ?>"><?= ucfirst($act['estado_activo']) ?></span></li>
                                                            <li><strong>Fecha Adquisición:</strong> <?= !empty($act['fecha_adquisicion']) ? date('d/m/Y', strtotime($act['fecha_adquisicion'])) : 'No registrada' ?></li>
                                                            <li><strong>Vencimiento Garantía:</strong> <?= !empty($act['fecha_vencimiento_garantia']) ? date('d/m/Y', strtotime($act['fecha_vencimiento_garantia'])) : 'No especificada' ?></li>
                                                            <li><strong>Proveedor / Documento:</strong> <?= htmlspecialchars($act['proveedor_compra'] ?? 'N/A') ?></li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                <?php if (!empty($act['observaciones'])): ?>
                                                    <div class="col-12">
                                                        <div class="p-3 bg-light rounded border">
                                                            <small class="fw-bold d-block text-dark mb-1">Observaciones / Historial de Mantenimiento:</small>
                                                            <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($act['observaciones'])) ?></p>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal ModalActivo (Crear / Editar) -->
<?php if ($puede_gestionar): ?>
<div class="modal fade" id="modalActivo" tabindex="-1" aria-labelledby="modalActivoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="activos.php" method="POST" autocomplete="off">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id" value="<?= $activo_editar['id'] ?? '' ?>">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6" id="modalActivoLabel">
                        <?= $activo_editar ? 'Editar Activo (' . htmlspecialchars($activo_editar['codigo_unico']) . ')' : 'Registrar Nuevo Activo Tecnológico' ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Nav Tabs para organizar las 4 secciones -->
                    <ul class="nav nav-tabs mb-3" id="activoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold small" id="ident-tab" data-bs-toggle="tab" data-bs-target="#tab-identificacion" type="button" role="tab">
                                1. Identificación
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold small" id="specs-tab" data-bs-toggle="tab" data-bs-target="#tab-especificaciones" type="button" role="tab">
                                2. Specs Técnicas
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold small" id="ubic-tab" data-bs-toggle="tab" data-bs-target="#tab-ubicacion" type="button" role="tab">
                                3. Asignación y Ubicación
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold small" id="admin-tab" data-bs-toggle="tab" data-bs-target="#tab-administracion" type="button" role="tab">
                                4. Control y Ciclo de Vida
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="activoTabsContent">
                        <!-- TAB 1: IDENTIFICACIÓN -->
                        <div class="tab-pane fade show active" id="tab-identificacion" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="nombre" class="form-label small fw-bold">Nombre del Equipo *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?= htmlspecialchars($activo_editar['nombre'] ?? '') ?>" placeholder="Ej: Notebook Lenovo ThinkPad / Proyector Epson" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="categoria_id_modal" class="form-label small fw-bold">Categoría *</label>
                                    <select name="categoria_id" id="categoria_id_modal" class="form-select" required>
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($categorias_lista as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= ($activo_editar['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="marca" class="form-label small fw-bold">Marca</label>
                                    <input type="text" class="form-control" id="marca" name="marca" 
                                           value="<?= htmlspecialchars($activo_editar['marca'] ?? '') ?>" placeholder="Lenovo, Epson, Samsung...">
                                </div>

                                <div class="col-md-4">
                                    <label for="modelo" class="form-label small fw-bold">Modelo</label>
                                    <input type="text" class="form-control" id="modelo" name="modelo" 
                                           value="<?= htmlspecialchars($activo_editar['modelo'] ?? '') ?>" placeholder="ThinkPad E14 / PowerLite X49...">
                                </div>

                                <div class="col-md-4">
                                    <label for="numero_serie" class="form-label small fw-bold">Número de Serie (S/N Fabricante)</label>
                                    <input type="text" class="form-control" id="numero_serie" name="numero_serie" 
                                           value="<?= htmlspecialchars($activo_editar['numero_serie'] ?? '') ?>" placeholder="SN-LN-998231">
                                </div>

                                <div class="col-md-6">
                                    <label for="nombre_equipo_so" class="form-label small fw-bold">Nombre del Equipo en SO</label>
                                    <input type="text" class="form-control" id="nombre_equipo_so" name="nombre_equipo_so" 
                                           value="<?= htmlspecialchars($activo_editar['nombre_equipo_so'] ?? '') ?>" placeholder="Ej: PC-LAB1-01 / TAB-CRA-05">
                                </div>

                                <div class="col-md-6">
                                    <label for="colegio_id_modal" class="form-label small fw-bold">Colegio *</label>
                                    <select name="colegio_id" id="colegio_id_modal" class="form-select" <?= !es_admin() ? 'disabled' : '' ?>>
                                        <?php foreach ($colegios_lista as $col): ?>
                                            <option value="<?= $col['id'] ?>" 
                                                <?= ($activo_editar['colegio_id'] ?? $usuario_logueado['colegio_id']) == $col['id'] ? 'selected' : '' ?>>
                                                RBD <?= htmlspecialchars($col['rbd']) ?> - <?= htmlspecialchars($col['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!es_admin()): ?>
                                        <input type="hidden" name="colegio_id" value="<?= $usuario_logueado['colegio_id'] ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: ESPECIFICACIONES TÉCNICAS -->
                        <div class="tab-pane fade" id="tab-especificaciones" role="tabpanel">
                            <h6 class="fw-bold text-primary mb-3">Para Computadores, Notebooks y Tablets:</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="procesador" class="form-label small fw-bold">Procesador (CPU)</label>
                                    <input type="text" class="form-control" id="procesador" name="procesador" 
                                           value="<?= htmlspecialchars($activo_editar['procesador'] ?? '') ?>" placeholder="Intel Core i5, Apple M1, Octa-Core...">
                                </div>
                                <div class="col-md-4">
                                    <label for="memoria_ram" class="form-label small fw-bold">Memoria RAM</label>
                                    <input type="text" class="form-control" id="memoria_ram" name="memoria_ram" 
                                           value="<?= htmlspecialchars($activo_editar['memoria_ram'] ?? '') ?>" placeholder="8 GB DDR4, 16 GB...">
                                </div>
                                <div class="col-md-4">
                                    <label for="almacenamiento" class="form-label small fw-bold">Almacenamiento (Tipo/Capacidad)</label>
                                    <input type="text" class="form-control" id="almacenamiento" name="almacenamiento" 
                                           value="<?= htmlspecialchars($activo_editar['almacenamiento'] ?? '') ?>" placeholder="256 GB SSD NVMe, 64 GB eMMC...">
                                </div>
                                <div class="col-md-6">
                                    <label for="sistema_operativo" class="form-label small fw-bold">Sistema Operativo</label>
                                    <input type="text" class="form-control" id="sistema_operativo" name="sistema_operativo" 
                                           value="<?= htmlspecialchars($activo_editar['sistema_operativo'] ?? '') ?>" placeholder="Windows 11 Pro, Android 13, macOS...">
                                </div>
                                <div class="col-md-6">
                                    <label for="estado_licencia" class="form-label small fw-bold">Estado de Licencia</label>
                                    <input type="text" class="form-control" id="estado_licencia" name="estado_licencia" 
                                           value="<?= htmlspecialchars($activo_editar['estado_licencia'] ?? '') ?>" placeholder="Original Activada, OEM, Gratuita...">
                                </div>
                            </div>

                            <h6 class="fw-bold text-primary mb-3 border-top pt-3">Para Proyectores:</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="resolucion_nativa" class="form-label small fw-bold">Resolución Nativa</label>
                                    <input type="text" class="form-control" id="resolucion_nativa" name="resolucion_nativa" 
                                           value="<?= htmlspecialchars($activo_editar['resolucion_nativa'] ?? '') ?>" placeholder="1920x1080 Full HD, 1024x768 XGA">
                                </div>
                                <div class="col-md-4">
                                    <label for="lumens" class="form-label small fw-bold">Intensidad Lumínica (Lúmenes)</label>
                                    <input type="text" class="form-control" id="lumens" name="lumens" 
                                           value="<?= htmlspecialchars($activo_editar['lumens'] ?? '') ?>" placeholder="3600 ANSI Lúmenes">
                                </div>
                                <div class="col-md-4">
                                    <label for="horas_lampara" class="form-label small fw-bold">Horas Uso Lámpara</label>
                                    <input type="number" class="form-control" id="horas_lampara" name="horas_lampara" 
                                           value="<?= htmlspecialchars($activo_editar['horas_lampara'] ?? '0') ?>" placeholder="450">
                                </div>
                                <div class="col-md-6">
                                    <label for="conectividad" class="form-label small fw-bold">Tipos de Conectividad</label>
                                    <input type="text" class="form-control" id="conectividad" name="conectividad" 
                                           value="<?= htmlspecialchars($activo_editar['conectividad'] ?? '') ?>" placeholder="HDMI, VGA, USB, Wi-Fi">
                                </div>
                                <div class="col-md-6">
                                    <label for="accesorios" class="form-label small fw-bold">Accesorios Asociados</label>
                                    <input type="text" class="form-control" id="accesorios" name="accesorios" 
                                           value="<?= htmlspecialchars($activo_editar['accesorios'] ?? '') ?>" placeholder="Control remoto, soporte de techo, cable HDMI 5m...">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: ASIGNACIÓN Y UBICACIÓN -->
                        <div class="tab-pane fade" id="tab-ubicacion" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="responsable_usuario_id" class="form-label small fw-bold">Usuario Responsable</label>
                                    <select name="responsable_usuario_id" id="responsable_usuario_id" class="form-select">
                                        <option value="">-- Sin Responsable Directo --</option>
                                        <?php foreach ($usuarios_lista as $usr): ?>
                                            <option value="<?= $usr['id'] ?>" <?= ($activo_editar['responsable_usuario_id'] ?? '') == $usr['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($usr['nombre'] . ' ' . $usr['apellido']) ?> (<?= ucfirst($usr['perfil']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="dependencia_id" class="form-label small fw-bold">Ubicación / Sala de Clases / Lab</label>
                                    <select name="dependencia_id" id="dependencia_id" class="form-select">
                                        <option value="">-- Sin Sala Asignada --</option>
                                        <?php foreach ($dependencias_lista as $dep): ?>
                                            <option value="<?= $dep['id'] ?>" <?= ($activo_editar['dependencia_id'] ?? '') == $dep['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dep['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="departamento_area" class="form-label small fw-bold">Departamento, Unidad o Área</label>
                                    <input type="text" class="form-control" id="departamento_area" name="departamento_area" 
                                           value="<?= htmlspecialchars($activo_editar['departamento_area'] ?? '') ?>" placeholder="Ej: Unidad Técnico Pedagógica, Informática...">
                                </div>

                                <div class="col-md-6">
                                    <label for="modalidad_ubicacion" class="form-label small fw-bold">Modalidad de Ubicación</label>
                                    <select name="modalidad_ubicacion" id="modalidad_ubicacion" class="form-select">
                                        <option value="presencial" <?= ($activo_editar['modalidad_ubicacion'] ?? 'presencial') === 'presencial' ? 'selected' : '' ?>>Presencial (Oficina / Sala / Lab)</option>
                                        <option value="teletrabajo" <?= ($activo_editar['modalidad_ubicacion'] ?? '') === 'teletrabajo' ? 'selected' : '' ?>>Teletrabajo / Asignación Docente domicilio</option>
                                        <option value="prestamo" <?= ($activo_editar['modalidad_ubicacion'] ?? '') === 'prestamo' ? 'selected' : '' ?>>Préstamo Temporal</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: CONTROL ADMINISTRATIVO Y CICLO DE VIDA -->
                        <div class="tab-pane fade" id="tab-administracion" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="estado_activo" class="form-label small fw-bold">Estado Operativo *</label>
                                    <select name="estado_activo" id="estado_activo" class="form-select" required>
                                        <option value="operativo" <?= ($activo_editar['estado_activo'] ?? 'operativo') === 'operativo' ? 'selected' : '' ?>>Operativo</option>
                                        <option value="regular" <?= ($activo_editar['estado_activo'] ?? '') === 'regular' ? 'selected' : '' ?>>Regular</option>
                                        <option value="con_falla" <?= ($activo_editar['estado_activo'] ?? '') === 'con_falla' ? 'selected' : '' ?>>Con Falla</option>
                                        <option value="en_reparacion" <?= ($activo_editar['estado_activo'] ?? '') === 'en_reparacion' ? 'selected' : '' ?>>En Reparación</option>
                                        <option value="dado_de_baja" <?= ($activo_editar['estado_editar'] ?? '') === 'dado_de_baja' ? 'selected' : '' ?>>Dado de Baja</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="fecha_adquisicion" class="form-label small fw-bold">Fecha de Adquisición</label>
                                    <input type="date" class="form-control" id="fecha_adquisicion" name="fecha_adquisicion" 
                                           value="<?= htmlspecialchars($activo_editar['fecha_adquisicion'] ?? '') ?>">
                                </div>

                                <div class="col-md-4">
                                    <label for="fecha_vencimiento_garantia" class="form-label small fw-bold">Vencimiento de Garantía</label>
                                    <input type="date" class="form-control" id="fecha_vencimiento_garantia" name="fecha_vencimiento_garantia" 
                                           value="<?= htmlspecialchars($activo_editar['fecha_vencimiento_garantia'] ?? '') ?>">
                                </div>

                                <div class="col-md-12">
                                    <label for="proveedor_compra" class="form-label small fw-bold">Proveedor / Documento de Compra</label>
                                    <input type="text" class="form-control" id="proveedor_compra" name="proveedor_compra" 
                                           value="<?= htmlspecialchars($activo_editar['proveedor_compra'] ?? '') ?>" placeholder="Ej: Mercado Público Factura F-12345 / Proveedor Chile SpA">
                                </div>

                                <div class="col-12">
                                    <label for="observaciones" class="form-label small fw-bold">Observaciones e Historial de Mantenimientos / Incidencias</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                              placeholder="Bitácora de fallas, cambios de pieza, calibraciones, mantenciones preventivas..."><?= htmlspecialchars($activo_editar['observaciones'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gob-primario btn-sm">
                        <i class="bi bi-save me-1"></i> Guardar Activo Tecnológico
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($activo_editar): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('modalActivo'));
        myModal.show();
    });
</script>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
