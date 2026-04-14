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

$id_mascota = Crypto::decrypt(urldecode($_GET['id_mascota']));
if (!$id_mascota || $id_mascota <= 0 || !is_numeric($id_mascota)) {
    header('Location: gestion_clientes.php');
    exit();
}

// Paginación
$por_pagina = 5;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);
$offset = ($pagina - 1) * $por_pagina;

// Obtener total de registros del historial
try {
    $sql_count = $conexion->prepare("SELECT COUNT(*) FROM historial_clinico WHERE mascota_id = :id_mascota");
    $sql_count->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql_count->execute();
    $total_registros = $sql_count->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
} catch (PDOException $e) {
    $total_registros = 0;
    $total_paginas = 1;
}

// Obtener historial clinico de la mascota con paginación
try {
    $sql = $conexion->prepare("SELECT historial_clinico.id as id_historial,usuarios.nombre as nombre_veterinario,historial_clinico.fecha as fecha,historial_clinico.peso as peso,historial_clinico.sintomas as sintomas,historial_clinico.diagnostico as diagnostico,historial_clinico.tratamiento as tratamiento,historial_clinico.observaciones as observaciones,citas.fecha_hora as fecha_cita FROM historial_clinico INNER JOIN usuarios ON historial_clinico.veterinario_id=usuarios.id INNER JOIN citas ON historial_clinico.cita_id=citas.id WHERE historial_clinico.mascota_id = :id_mascota ORDER BY historial_clinico.fecha DESC LIMIT :limit OFFSET :offset");
    $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql->bindValue(":limit", $por_pagina, PDO::PARAM_INT);
    $sql->bindValue(":offset", $offset, PDO::PARAM_INT);
    $sql->execute();
    $historial_clinico = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener el historial clinico: " . $e->getMessage());
    header('Location: gestion_clientes.php');
    exit();
}

// Obtener datos de la mascota
try {
    $sql = $conexion->prepare('SELECT mascotas.nombre as nombre,mascotas.especie as especie,mascotas.raza as raza,mascotas.sexo as sexo,mascotas.fecha_nacimiento as fecha_nacimiento,clientes.nombre as nombre_cliente,clientes.cedula as cedula,clientes.telefono as telefono FROM mascotas INNER JOIN clientes ON mascotas.cliente_id=clientes.id WHERE mascotas.id = :id_mascota');
    $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql->execute();
    $mascota = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los datos de la mascota: " . $e->getMessage());
    header('Location: gestion_clientes.php');
    exit();
}
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/historial_clinico_mascota.css">
<div class="historial-container">
    <!-- Header de mascota -->
    <div class="mascota-header">
        <div class="mascota-info">
            <div class="mascota-avatar">
                <?php if ($mascota->especie == 'perro'): ?>
                    <i class="fas fa-dog"></i>
                <?php elseif ($mascota->especie == 'gato'): ?>
                    <i class="fas fa-cat"></i>
                <?php elseif ($mascota->especie == 'ave'): ?>
                    <i class="fas fa-dove"></i>
                <?php elseif ($mascota->especie == 'roedor'): ?>
                    <i class="fas fa-rabbit"></i>
                <?php else: ?>
                    <i class="fas fa-paw"></i>
                <?php endif; ?>
            </div>
            <div class="mascota-datos">
                <h3><?= htmlspecialchars(ucfirst($mascota->nombre)) ?></h3>
                <p><i class="fas fa-paw"></i> <?= htmlspecialchars(ucfirst($mascota->especie)) ?> | <i class="fas fa-dna"></i> <?= htmlspecialchars(ucfirst($mascota->raza ?: 'No especificada')) ?> | <i class="fas fa-venus-mars"></i> <?= htmlspecialchars(ucfirst($mascota->sexo ?: 'No especificado')) ?> | <i class="fas fa-calendar"></i> <?= htmlspecialchars($mascota->fecha_nacimiento ?: 'No registrada') ?></p>
            </div>
            <div class="cliente-info">
                <p><i class="fas fa-user"></i> Propietario: <strong><?= htmlspecialchars(ucfirst($mascota->nombre_cliente)) ?></strong><br><i class="fas fa-id-card"></i> Cédula: <span><?= htmlspecialchars($mascota->cedula) ?></span> | <i class="fas fa-phone"></i> <span><?= htmlspecialchars($mascota->telefono ?: 'No registrado') ?></span></p>
            </div>
        </div>
    </div>

    <!-- Tabla de historial clínico -->
    <div class="table-container">
        <table class="historial-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre Veterinario</th>
                    <th>Fecha Cita Medica</th>
                    <th>Diagnóstico</th>
                    <th>Peso</th>
                    <th>Síntomas</th>
                    <th>Tratamientos</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historial_clinico)): ?>
                    <tr>
                        <td colspan="8" class="empty-message">
                            <i class="fas fa-notes-medical"></i>
                            <p>No hay registros clínicos para esta mascota</p>
                            <p style="font-size: 0.8rem; margin-top: 10px;">Las consultas médicas aparecerán aquí</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historial_clinico as $item): ?>
                        <tr>
                            <td><strong>HC-<?= htmlspecialchars($item->id_historial) ?></strong></td>
                            <td><span class="veterinario-nombre"><?= htmlspecialchars(ucfirst($item->nombre_veterinario)) ?></span></td>
                            <td><span class="fecha-texto"><?= htmlspecialchars($item->fecha) ?></span></td>
                            <td><span class="diagnostico-texto"><?= htmlspecialchars($item->diagnostico) ?></span></td>
                            <td><span class="peso-badge"><?= htmlspecialchars($item->peso) ?></span></td>
                            <td><?= htmlspecialchars($item->sintomas) ?></td>
                            <td><?= htmlspecialchars($item->tratamiento) ?></td>
                            <td><?= htmlspecialchars($item->observaciones) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="pagination-container" id="paginationContainer">
            <div class="pagination-info">
                Mostrando <?php echo $total_registros > 0 ? ($offset + 1) : 0; ?> - <?php echo min($offset + $por_pagina, $total_registros); ?> de <?php echo $total_registros; ?> registros
            </div>
            <div class="pagination-controls">
                <?php if ($pagina > 1): ?>
                    <a href="?id_mascota=<?php echo urlencode($_GET['id_mascota']); ?>&pagina=<?php echo $pagina - 1; ?>" class="page-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <button class="page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?id_mascota=<?php echo urlencode($_GET['id_mascota']); ?>&pagina=<?php echo $i; ?>" class="page-btn <?php echo $i === $pagina ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="?id_mascota=<?php echo urlencode($_GET['id_mascota']); ?>&pagina=<?php echo $pagina + 1; ?>" class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <button class="page-btn" disabled><i class="fas fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="../app/js/recepcionista/historial_clinico_mascota.js"></script>
<?php include_once '../templates/footer.php'; ?>