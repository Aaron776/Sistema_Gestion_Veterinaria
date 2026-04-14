<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'veterinario') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$id_veterinario = $_SESSION['id_usuario']; // obtener id del veterinario logueado

// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);
$offset = ($pagina - 1) * $por_pagina;

// Obtener total de registros de citas
try {
    $sql_count = $conexion->prepare("SELECT COUNT(c.id) FROM citas c WHERE c.estado = 'confirmada' AND c.veterinario_id = :id_veterinario");
    $sql_count->bindParam(":id_veterinario", $id_veterinario, PDO::PARAM_INT);
    $sql_count->execute();
    $total_registros = $sql_count->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
} catch (PDOException $e) {
    $total_registros = 0;
    $total_paginas = 1;
}

// Obtener listado de citas confirmadas para este veterinario
try {
    $sql = $conexion->prepare("SELECT c.id as id_cita, m.nombre AS mascota, m.id as id_mascota, c.fecha_hora as fecha, c.motivo as motivo, r.nombre as recepcionista, cl.nombre as cliente, 
    (SELECT GROUP_CONCAT(s.nombre_servicio SEPARATOR ', ') FROM cita_servicios cs JOIN servicios s ON cs.servicio_id = s.id WHERE cs.cita_id = c.id) as servicios_nombres
    FROM citas c
    JOIN mascotas m ON c.mascota_id = m.id
    JOIN usuarios r ON c.recepcionista_id = r.id
    JOIN usuarios v ON c.veterinario_id = v.id
    JOIN clientes cl ON m.cliente_id = cl.id
    WHERE c.estado = 'confirmada' AND c.veterinario_id = :id_veterinario
    ORDER BY c.fecha_hora ASC 
    LIMIT :limit OFFSET :offset");
    $sql->bindParam(':id_veterinario', $id_veterinario, PDO::PARAM_INT);
    $sql->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $citas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener citas confirmadas: " . $e->getMessage());
    $citas = [];
}
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/veterinario/gestion_citas_confirmadas.css">
<div class="citas-container">
    <div class="page-header">
        <h2>
            <i class="fas fa-check-circle"></i>
            Citas Confirmadas
            <span class="badge-confirmadas" id="totalConfirmadas"><?= $total_registros; ?></span>
        </h2>
    </div>

    <div class="filters-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchCita" placeholder="Buscar por mascota o recepcionista...">
        </div>
    </div>

    <div class="table-container">
        <table class="citas-table" id="citasTable">
            <?php if (isset($_SESSION['errores'])) : ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($_SESSION['errores'] as $error) : ?>
                            <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errores']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['exito'])) : ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['exito']; ?>
                </div>
                <?php unset($_SESSION['exito']); ?>
            <?php endif; ?>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mascota</th>
                    <th>Recepcionista</th>
                    <th>Fecha</th>
                    <th>Motivo</th>
                    <th>Servicios</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($citas)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:50px 20px; color:#8fa3b3;">
                            <i class="fas fa-calendar-check" style="font-size: 48px; color: #cbd5e0; margin-bottom: 12px; display:block;"></i>
                            <p style="margin-top: 10px; font-size: 0.9rem;">No hay citas confirmadas registradas.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($citas as $item): ?>
                        <tr>
                            <td>C-<?= htmlspecialchars($item->id_cita); ?></td>
                            <td><strong><?= htmlspecialchars(ucfirst($item->mascota)); ?></strong><br><small style="color:#7f9aab;">Dueño: <?= htmlspecialchars(ucfirst($item->cliente)); ?></small></td>
                            <td><?= htmlspecialchars(ucfirst($item->recepcionista)); ?></td>
                            <td><?= date('d/m/Y H:i A', strtotime($item->fecha)); ?></td>
                            <td><?= htmlspecialchars(ucfirst($item->motivo)); ?></td>
                            <td><?= htmlspecialchars(ucfirst($item->servicios_nombres ?: 'Consulta General')); ?></td>
                            <td class="action-buttons">
                                <a href="historial_clinico_mascota.php?id_mascota=<?= htmlspecialchars(urlencode(Crypto::encrypt($item->id_mascota))); ?>&id_cita=<?= htmlspecialchars(urlencode(Crypto::encrypt($item->id_cita))); ?>" class="btn-icon btn-diagnostico"><i class="fas fa-file-medical"></i> Expediente Clínico</a>
                                <a href="cambiar_estado_cita.php?id_cita=<?= htmlspecialchars(urlencode(Crypto::encrypt($item->id_cita))); ?>" type="button" class="btn-icon btn-status"><i class="fas fa-exchange-alt"></i> Cambiar Estado</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="pagination-container" id="paginationContainer">
            <div class="pagination-info">
                Mostrando <?php echo $total_registros > 0 ? ($offset + 1) : 0; ?> - <?php echo min($offset + $por_pagina, $total_registros); ?> de <?php echo $total_registros; ?> citas
            </div>
            <div class="pagination-controls">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?php echo $pagina - 1; ?>" class="page-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <button class="page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?php echo $i; ?>" class="page-btn <?php echo $i === $pagina ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="?pagina=<?php echo $pagina + 1; ?>" class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <button class="page-btn" disabled><i class="fas fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="../app/js/veterinario/gestion_citas_confrimadas.js"></script>
<?php include_once '../templates/footer.php'; ?>