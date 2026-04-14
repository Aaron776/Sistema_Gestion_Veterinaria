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

$id_mascota = crypto::decrypt(urldecode($_GET['id_mascota']));
if (!$id_mascota || $id_mascota <= 0 || !is_numeric($id_mascota)) {
    header('Location: gestion_clientes.php');
    exit();
}

// Obtener datos de la mascota y del cliente al que le pertenece
try {
    $sql = $conexion->prepare("SELECT m.nombre as nombre_mascota,m.especie as especie,m.raza as raza,m.sexo as sexo,m.fecha_nacimiento as fecha_nacimiento,c.nombre as cliente_nombre,c.cedula as cliente_cedula,c.telefono as cliente_telefono FROM mascotas m JOIN clientes c ON m.cliente_id = c.id WHERE m.id = :id_mascota");
    $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql->execute();
    $mascota = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener la mascota: " . $e->getMessage());
    header('Location: gestion_citas_confirmadas.php');
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
    $sql = $conexion->prepare("SELECT mascota_vacunas.id as id_mascota_vacuna, mascota_vacunas.fecha_aplicacion as fecha_aplicacion, mascota_vacunas.proxima_dosis as proxima_dosis, vacunas.nombre as nombre_vacuna,vacunas.id as id_vacuna 
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
    header('Location: gestion_citas_confirmadas.php');
    exit();
}


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/veterinario/gestion_vacunacion_mascotas.css">
<div class="vacunas-container">
    <!-- Header de mascota -->
    <div class="mascota-header" id="mascotaHeader">
        <div class="mascota-info">
            <div class="mascota-avatar" id="mascotaAvatar">
                <i class="fas fa-dog"></i>
            </div>
            <div class="mascota-datos">
                <h3 id="mascotaNombre"><?php echo htmlspecialchars(ucfirst($mascota->nombre_mascota)); ?></h3>
                <p><i class="fas fa-paw"></i> <span id="mascotaEspecie"><?php echo htmlspecialchars(ucfirst($mascota->especie)); ?></span> | <i class="fas fa-dna"></i> <span id="mascotaRaza"><?php echo htmlspecialchars(ucfirst($mascota->raza)); ?></span> | <i class="fas fa-venus-mars"></i> <span id="mascotaSexo"><?php echo htmlspecialchars(ucfirst($mascota->sexo)); ?></span> | <i class="fas fa-calendar"></i> <span id="mascotaEdad"><?php echo htmlspecialchars($mascota->fecha_nacimiento); ?></span></p>
            </div>
            <div class="cliente-info">
                <p><i class="fas fa-user"></i> Propietario: <strong id="clienteNombre"><?php echo htmlspecialchars(ucfirst($mascota->cliente_nombre)); ?></strong><br><i class="fas fa-id-card"></i> Cédula: <span id="clienteCedula"><?php echo htmlspecialchars($mascota->cliente_cedula); ?></span> | <i class="fas fa-phone"></i> <span id="clienteTelefono"><?php echo htmlspecialchars($mascota->cliente_telefono); ?></span></p>
            </div>
        </div>
        <a href="agregar_vacuna_mascota.php?id_mascota=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_mascota))); ?>" type="button" class="btn-add" id="btnAgregarVacuna">
            <i class="fas fa-plus-circle"></i> Nueva Vacuna
        </a>
    </div>

    <!-- Filtros -->
    <div class="filters-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchVacuna" placeholder="Buscar por nombre de vacuna...">
        </div>
    </div>

    <!-- Tabla de vacunas -->
    <div class="table-container">
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
        <table class="vacunas-table" id="vacunasTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre de la Vacuna</th>
                    <th>Fecha de Aplicación</th>
                    <th>Próxima Dosis</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($listado_vacunas as $item) : ?>
                    <tr>
                        <td>V-<?php echo htmlspecialchars($item->id_mascota_vacuna); ?></td>
                        <td><strong><?php echo htmlspecialchars(ucfirst($item->nombre_vacuna)); ?></strong></td>
                        <td><?php echo htmlspecialchars($item->fecha_aplicacion); ?></td>
                        <td><span class="proxima-badge proxima-normal"><?php echo htmlspecialchars($item->proxima_dosis); ?></span></td>
                        <td class="action-buttons">
                            <a href="editar_vacuna_mascota.php?id_mascota_vacuna=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_mascota_vacuna))); ?>&id_mascota=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_mascota))); ?>" type="button" class="btn-icon btn-edit"><i class="fas fa-edit"></i> Editar</a>
                            <form action="../controladores/veterinario/eliminar_vacuna_mascota.php" method="post" class="form-eliminar">
                                <input type="hidden" name="id_mascota_vacuna" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_mascota_vacuna))); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="id_mascota" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_mascota))); ?>">
                                <button type="submit" class="btn-icon btn-delete"><i class="fas fa-trash-alt"></i> Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="pagination-container" id="paginationContainer">
            <div class="pagination-info">
                Mostrando <?php echo $total_registros > 0 ? ($offset + 1) : 0; ?> - <?php echo min($offset + $por_pagina, $total_registros); ?> de <?php echo $total_registros; ?> vacunas
            </div>
            <div class="pagination-controls">
                <?php
                // Mantener el id_mascota encriptado en las URLs de paginación
                $url_params = "id_mascota=" . urlencode($_GET['id_mascota']);
                ?>

                <?php if ($pagina > 1): ?>
                    <a href="?<?php echo $url_params; ?>&pagina=<?php echo $pagina - 1; ?>" class="page-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <button class="page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?<?php echo $url_params; ?>&pagina=<?php echo $i; ?>" class="page-btn <?php echo $i === $pagina ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="?<?php echo $url_params; ?>&pagina=<?php echo $pagina + 1; ?>" class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <button class="page-btn" disabled><i class="fas fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="../app/js/veterinario/gestion_vacunacion_mascotas.js"></script>
<?php include_once '../templates/footer.php'; ?>