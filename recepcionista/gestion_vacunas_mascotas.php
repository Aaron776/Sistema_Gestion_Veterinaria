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

$id_mascota = crypto::decrypt(urldecode($_GET['id_mascota']));
if (!$id_mascota || $id_mascota <= 0 || !is_numeric($id_mascota)) {
    header('Location: gestion_clientes.php');
    exit();
}

// Obtener datos de la mascota y del cliente al que le pertenece
try {
    $sql = $conexion->prepare("SELECT m.nombre as nombre,m.especie as especie,m.raza as raza,m.sexo as sexo,m.fecha_nacimiento as fecha_nacimiento,c.nombre as cliente_nombre,c.cedula as cliente_cedula FROM mascotas m JOIN clientes c ON m.cliente_id = c.id WHERE m.id = :id_mascota");
    $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql->execute();
    $mascota = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener la mascota: " . $e->getMessage());
    header('Location: gestion_clientes.php');
    exit();
}

// Paginación
$por_pagina = 5;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);
$offset = ($pagina - 1) * $por_pagina;

// Obtener total de registros de vacunas
try {
    $sql_count = $conexion->prepare("SELECT COUNT(*) FROM mascota_vacunas WHERE mascota_id = :id_mascota");
    $sql_count->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql_count->execute();
    $total_registros = $sql_count->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
} catch (PDOException $e) {
    $total_registros = 0;
    $total_paginas = 1;
}

// Obtener listado de vacunas de esa mascota con paginación
try {
    $sql = $conexion->prepare("SELECT mascota_vacunas.id as id_mascota_vacuna, mascota_vacunas.fecha_aplicacion as fecha_aplicacion, mascota_vacunas.proxima_dosis as proxima_dosis, vacunas.nombre as nombre_vacuna 
                              FROM mascota_vacunas 
                              INNER JOIN vacunas ON mascota_vacunas.vacuna_id = vacunas.id 
                              WHERE mascota_vacunas.mascota_id = :id_mascota 
                              ORDER BY mascota_vacunas.fecha_aplicacion DESC
                              LIMIT :limit OFFSET :offset");
    $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql->bindValue(":limit", $por_pagina, PDO::PARAM_INT);
    $sql->bindValue(":offset", $offset, PDO::PARAM_INT);
    $sql->execute();
    $listado_vacunas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener las vacunas: " . $e->getMessage());
    header('Location: gestion_clientes.php');
    exit();
}


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/gestion_vacunas_mascota.css">
<div class="vacunas-container">
    <!-- Header de mascota -->
    <div class="mascota-header" id="mascotaHeader">
        <div class="mascota-info">
            <div class="mascota-avatar" id="mascotaAvatar">
                <i class="fas fa-dog"></i>
            </div>
            <div class="mascota-datos">
                <h3><?php echo htmlspecialchars(ucfirst($mascota->nombre)); ?></h3>
                <p><i class="fas fa-paw"></i> <span><?php echo htmlspecialchars(ucfirst($mascota->especie)); ?></span> | <i class="fas fa-dna"></i> <span><?php echo htmlspecialchars(ucfirst($mascota->raza)); ?></span> | <i class="fas fa-venus-mars"></i> <span><?php echo htmlspecialchars(ucfirst($mascota->sexo)); ?></span> | <i class="fas fa-calendar"></i> <span><?php echo htmlspecialchars($mascota->fecha_nacimiento); ?></span></p>
            </div>
            <div class="cliente-info">
                <p><i class="fas fa-user"></i> Propietario: <strong><?php echo htmlspecialchars(ucfirst($mascota->cliente_nombre)); ?></strong><br><i class="fas fa-id-card"></i> Cédula: <span><?php echo htmlspecialchars($mascota->cliente_cedula); ?></span></p>
            </div>
        </div>
    </div>

    <!-- Tabla de vacunas -->
    <div class="table-container">
        <table class="vacunas-table" id="vacunasTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre Vacuna</th>
                    <th>Fecha Aplicación</th>
                    <th>Próxima Dosis</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php $contador = $offset; ?>
                <?php if (empty($listado_vacunas)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding:50px 20px; color:#8fa3b3;">
                            <i class="fas fa-syringe" style="font-size: 48px; color: #cbd5e0; margin-bottom: 12px; display:block;"></i>
                            <p style="margin-top: 10px; font-size: 0.9rem;">No hay vacunas registradas para esta mascota.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($listado_vacunas as $item): ?>
                        <tr>
                            <td><?php $contador++;
                                echo $contador; ?></td>
                            <td>
                                <span class="vacuna-nombre"><?php echo htmlspecialchars(ucfirst($item->nombre_vacuna)); ?></span>
                            </td>
                            <td>
                                <span class="fecha-texto"><?php echo htmlspecialchars($item->fecha_aplicacion); ?></span>
                            </td>
                            <td>
                                <div class="proxima-wrapper">
                                    <span class="proxima-badge proxima-normal">
                                        <?php echo htmlspecialchars($item->proxima_dosis); ?>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="pagination-container" id="paginationContainer">
            <div class="pagination-info">
                Mostrando <?php echo $total_registros > 0 ? ($offset + 1) : 0; ?> - <?php echo min($offset + $por_pagina, $total_registros); ?> de <?php echo $total_registros; ?> vacunas
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

<script src="../app/js/recepcionista/gestion_vacunas_mascota.js"></script>
<?php include_once '../templates/footer.php'; ?>