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

$id_mascota = urldecode(Crypto::decrypt($_GET['id_mascota']));
if (empty($id_mascota) || !is_numeric($id_mascota) || $id_mascota < 1) {
    header("Location: gestion_citas_confirmadas.php");
    exit();
}

$id_cita = urldecode(Crypto::decrypt($_GET['id_cita']));
if (empty($id_cita) || !is_numeric($id_cita) || $id_cita < 1) {
    header("Location: gestion_citas_confirmadas.php");
    exit();
}

// Obtener información de la mascota
try {
    $sql_mascota = $conexion->prepare("SELECT m.nombre as nombre_mascota, m.raza, m.especie, m.sexo, m.fecha_nacimiento, c.nombre as nombre_cliente,c.cedula as cedula, c.telefono as telefono
    FROM mascotas m 
    JOIN clientes c ON m.cliente_id = c.id 
    WHERE m.id = :id_mascota");
    $sql_mascota->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql_mascota->execute();
    $mascota = $sql_mascota->fetch(PDO::FETCH_OBJ);
    if (!$mascota) {
        header("Location: gestion_citas_confirmadas.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener información de la mascota: " . $e->getMessage());
    header("Location: gestion_citas_confirmadas.php");
    exit();
}

// Obtener estado de la cita actual para determinar si mostrar botón de agregar
$estado_cita = '';
try {
    $sql_cita = $conexion->prepare("SELECT estado FROM citas WHERE id = :id_cita");
    $sql_cita->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
    $sql_cita->execute();
    $estado_cita = $sql_cita->fetchColumn();
} catch (PDOException $e) {
    error_log("Error al obtener estado de la cita: " . $e->getMessage());
}

// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);
$offset = ($pagina - 1) * $por_pagina;

try {
    $sql_count = $conexion->prepare("SELECT COUNT(id) FROM historial_clinico WHERE mascota_id = :id_mascota");
    $sql_count->execute([':id_mascota' => $id_mascota]);
    $total_registros = $sql_count->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
} catch (PDOException $e) {
    $total_registros = 0;
    $total_paginas = 1;
}

// Obtener historial clínico de la mascota
try {
    $sql_historial = $conexion->prepare("SELECT c.motivo as motivo_consulta, c.id as id_cita, hc.id as id_historial_clinico, hc.fecha as fecha, hc.peso as peso, hc.sintomas as sintomas, hc.diagnostico as diagnostico, hc.tratamiento as tratamiento, hc.observaciones as observaciones 
    FROM historial_clinico hc 
    INNER JOIN citas c ON hc.cita_id = c.id 
    WHERE hc.mascota_id = :id_mascota 
    ORDER BY hc.fecha DESC 
    LIMIT :limit OFFSET :offset");
    $sql_historial->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql_historial->bindValue(":limit", $por_pagina, PDO::PARAM_INT);
    $sql_historial->bindValue(":offset", $offset, PDO::PARAM_INT);
    $sql_historial->execute();
    $historial = $sql_historial->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener historial clínico: " . $e->getMessage());
    $historial = [];
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/veterinario/historial_clinico_mascota.css">
<div class="historial-container">
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
                <p><i class="fas fa-user"></i> Propietario: <strong id="clienteNombre"><?php echo htmlspecialchars(ucfirst($mascota->nombre_cliente)); ?></strong><br><i class="fas fa-id-card"></i> Cédula: <span id="clienteCedula"><?php echo htmlspecialchars($mascota->cedula); ?></span> | <i class="fas fa-phone"></i> <span id="clienteTelefono"><?php echo htmlspecialchars($mascota->telefono); ?></span></p>
            </div>
        </div>

        <?php if ($estado_cita !== 'realizada'): ?>
            <a href="agregar_historial_clinico.php?id_mascota=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_mascota))); ?>&id_cita=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_cita))); ?>" type="button" class="btn-add">
                <i class="fas fa-plus-circle"></i> Nuevo Registro
            </a>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <div class="filters-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchRegistro" placeholder="Buscar por diagnóstico o tratamiento...">
        </div>
    </div>

    <!-- Tabla de historial clínico -->
    <div class="table-container">
        <table class="historial-table" id="historialTable">
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
                    <th>Fecha</th>
                    <th>Peso</th>
                    <th>Síntomas</th>
                    <th>Motivo Cita</th>
                    <th>Diagnóstico</th>
                    <th>Tratamiento</th>
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($historial as $item) { ?>
                    <tr>
                        <td>HC-<?php echo htmlspecialchars($item->id_historial_clinico); ?></td>
                        <td><?php echo htmlspecialchars(date('d/m/Y H:i A', strtotime($item->fecha))); ?></td>
                        <td><span class="peso-badge"><?php echo htmlspecialchars($item->peso); ?> kg</span></td>
                        <td><?php echo htmlspecialchars(ucfirst($item->sintomas)); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($item->motivo_consulta)); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($item->diagnostico)); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($item->tratamiento)); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($item->observaciones)); ?></td>
                        <td class="action-buttons">
                            <a href="gestion_vacunacion_mascotas.php?id_mascota=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_mascota))); ?>" class="btn-icon btn-vaccines"><i class="fas fa-syringe"></i> Vacunas</a>
                            <?php if ($estado_cita !== 'realizada'): ?>
                                <a href="editar_historial_clinico.php?id_historial_clinico=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_historial_clinico))); ?>&id_mascota=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_mascota))); ?>&id_cita=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_cita))); ?>" type="button" class="btn-icon btn-edit"><i class="fas fa-edit"></i> Editar</a>
                                <form action="../controladores/veterinario/eliminar_historial_clinico.php" method="post" style="display: inline;">
                                    <input type="hidden" name="id_historial_clinico" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_historial_clinico))); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="btn-icon btn-delete btn-delete-history"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #8ba0b0; font-size: 0.8rem;"><i class="fas fa-lock"></i> Finalizado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php } ?>
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

<script src="../app/js/veterinario/historial_clinico_mascota.js"></script>
<?php include_once '../templates/footer.php'; ?>