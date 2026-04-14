<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_recepcionista = $_SESSION['id_usuario']; // obtener id del recepcionista logueado

// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);
$offset = ($pagina - 1) * $por_pagina;

// Obtener total de registros de citas
try {
    $sql_count = $conexion->prepare("SELECT COUNT(*) FROM citas WHERE recepcionista_id = :id_recepcionista");
    $sql_count->bindParam(":id_recepcionista", $id_recepcionista, PDO::PARAM_INT);
    $sql_count->execute();
    $total_registros = $sql_count->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
} catch (PDOException $e) {
    $total_registros = 0;
    $total_paginas = 1;
}

// Obtener el listado de citas creadas por el recepcionista con paginación
try {
    $sql = $conexion->prepare("SELECT citas.id as id_cita, usuarios.nombre as nombre_veterinario, mascotas.nombre as nombre_mascota, citas.fecha_hora as fecha_cita, citas.motivo as motivo, citas.estado as estado, clientes.nombre as nombre_cliente FROM citas INNER JOIN usuarios ON citas.veterinario_id = usuarios.id INNER JOIN mascotas ON citas.mascota_id = mascotas.id INNER JOIN clientes ON mascotas.cliente_id = clientes.id WHERE citas.recepcionista_id = :id_recepcionista ORDER BY citas.fecha_hora DESC LIMIT :limit OFFSET :offset");
    $sql->bindParam(':id_recepcionista', $id_recepcionista, PDO::PARAM_INT);
    $sql->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $citas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener citas: " . $e->getMessage());
    exit();
}

$contador = 0;
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/gestion_citas.css">
<div class="citas-container">
    <div class="page-header">
        <h2>
            <i class="fas fa-calendar-check"></i>
            Gestión de Citas Médicas
        </h2>
        <button class="btn-add" id="btnAgregarCita" onclick="window.location.href='registrar_cita.php'">
            <i class="fas fa-plus-circle"></i> Nueva Cita
        </button>
    </div>

    <div class="filters-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchCita" placeholder="Buscar por mascota, veterinario...">
        </div>
        <div class="filter-group">
            <select id="estadoFilter">
                <option value="all">Todos los estados</option>
                <option value="Pendiente">Pendiente</option>
                <option value="Confirmada">Confirmada</option>
                <option value="Atendida">Atendida</option>
                <option value="Cancelada">Cancelada</option>
            </select>
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
                    <th>Veterinario</th>
                    <th>Fecha Cita</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($citas)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:50px 20px; color:#8fa3b3;">
                            <i class="fas fa-calendar-times" style="font-size: 48px; color: #cbd5e0; margin-bottom: 12px; display:block;"></i>
                            <p style="margin-top: 10px; font-size: 0.9rem;">No hay citas registradas.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($citas as $item): ?>
                        <tr>
                            <td><?php echo ++$contador; ?></td>
                            <td><strong><?php echo htmlspecialchars(ucfirst($item->nombre_mascota)); ?></strong><br><small style="color:#7f9aab;">Dueño: <?php echo htmlspecialchars(ucfirst($item->nombre_cliente)); ?></small></td>
                            <td>Dr(a). <?php echo htmlspecialchars(ucfirst($item->nombre_veterinario)); ?></td>
                            <td><?php echo date("d/m/Y h:i a", strtotime($item->fecha_cita)); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($item->motivo)); ?></td>
                            <td>
                                <?php
                                $estado = strtolower($item->estado);
                                $class = '';
                                switch ($estado) {
                                    case 'pendiente':
                                        $class = 'status-pendiente';
                                        break;
                                    case 'confirmada':
                                        $class = 'status-confirmada';
                                        break;
                                    case 'realizada':
                                        $class = 'status-atendida';
                                        break;
                                    case 'cancelada':
                                        $class = 'status-cancelada';
                                        break;
                                    default:
                                        $class = 'status-pendiente';
                                }
                                ?>
                                <span class="status-badge <?php echo $class; ?>"><?php echo htmlspecialchars(ucfirst($item->estado)); ?></span>
                            </td>
                            <td class="action-buttons">
                                <?php if ($item->estado == 'pendiente'): ?>
                                    <a href="editar_cita.php?id_cita=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_cita))); ?>" class="btn-icon btn-edit"><i class="fas fa-edit"></i> Editar</a>
                                    <form action="../controladores/recepcionista/eliminar_cita.php" method="POST">
                                        <input type="hidden" name="id_cita" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_cita))); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="btn-icon btn-delete"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                    </form>
                                    <a href="cambiar_estado_cita.php?id_cita=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_cita))); ?>" type="button" class="btn-icon btn-status"><i class="fas fa-exchange-alt"></i> Estado</a>
                                <?php endif; ?>
                                <?php if ($item->estado == 'realizada'): ?>
                                    <a href="facturar_cita.php?id_cita=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_cita))); ?>" class="btn-icon btn-payment"><i class="fas fa-file-invoice-dollar"></i> Facturar</a>
                                <?php endif; ?>
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

<script src="../app/js/recepcionista/gestion_citas.js"></script>
<?php include_once '../templates/footer.php'; ?>