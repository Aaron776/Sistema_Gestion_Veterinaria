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

$id_cliente = crypto::decrypt(urldecode($_GET['id_cliente']));
if (!$id_cliente || $id_cliente <= 0 || !is_numeric($id_cliente)) {
    header('Location: gestion_clientes.php');
    exit();
}

// Paginación
$por_pagina = 5;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);
$offset = ($pagina - 1) * $por_pagina;

// Obtener total de registros
try {
    $sql_count = $conexion->prepare("SELECT COUNT(*) FROM mascotas WHERE cliente_id = :id_cliente");
    $sql_count->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
    $sql_count->execute();
    $total_registros = $sql_count->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
} catch (PDOException $e) {
    $total_registros = 0;
    $total_paginas = 1;
}

// Obtener listado de mascotas del cliente con paginación
try {
    $sql = $conexion->prepare("SELECT id as id_mascota,nombre,especie,raza,sexo,fecha_nacimiento,foto FROM mascotas WHERE cliente_id = :id_cliente LIMIT :limit OFFSET :offset");
    $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
    $sql->bindValue(":limit", $por_pagina, PDO::PARAM_INT);
    $sql->bindValue(":offset", $offset, PDO::PARAM_INT);
    $sql->execute();
    $mascotas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener las mascotas: " . $e->getMessage());
    header('Location: gestion_clientes.php');
    exit();
}

// Obtener datos del cliente
try {
    $sql = $conexion->prepare("SELECT nombre,telefono,email,cedula FROM clientes WHERE id = :id_cliente");
    $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
    $sql->execute();
    $cliente = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener el cliente: " . $e->getMessage());
    header('Location: gestion_clientes.php');
    exit();
}

$contador = 0; // contador para la tabla de registros
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/gestion_mascotas_cliente.css">
<div class="mascotas-container">
    <!-- Header del cliente -->
    <div class="cliente-header" id="clienteHeader">
        <div class="cliente-info">
            <h3><i class="fas fa-user"></i> <span id="clienteNombre"><?php echo htmlspecialchars($cliente->nombre); ?></span></h3>
            <p><i class="fas fa-id-card"></i> Cédula: <span id="clienteCedula"><?php echo htmlspecialchars($cliente->cedula); ?></span> | <i class="fas fa-phone"></i> <span id="clienteTelefono"><?php echo htmlspecialchars($cliente->telefono); ?></span> | <i class="fas fa-envelope"></i> <span id="clienteEmail"><?php echo htmlspecialchars($cliente->email); ?></span></p>
        </div>
        <a href="agregar_mascota.php?id_cliente=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_cliente))); ?>" type="button" class="btn-add" id="btnAgregarMascota">
            <i class="fas fa-plus-circle"></i> Nueva Mascota
        </a>
    </div>

    <!-- Filtros -->
    <div class="filters-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchMascota" placeholder="Buscar por nombre, raza...">
        </div>
        <div class="especie-filter">
            <select id="especieFilter">
                <option value="all">Todas las especies</option>
                <option value="Perro">Perros</option>
                <option value="Gato">Gatos</option>
                <option value="Ave">Aves</option>
                <option value="Roedor">Roedores</option>
                <option value="Otro">Otros</option>
            </select>
        </div>
    </div>

    <!-- Tabla de mascotas -->
    <div class="table-container">
        <table class="mascotas-table" id="mascotasTable">
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
                    <th>#</th>
                    <th>Foto</th>
                    <th>Nombre</th>
                    <th>Especie</th>
                    <th>Raza</th>
                    <th>Fecha Nac.</th>
                    <th>Sexo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($mascotas)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:30px; color:#7f9aab;">
                            <i class="fas fa-info-circle" style="margin-right:8px;"></i>
                            No hay mascotas registradas para este cliente.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mascotas as $item): ?>
                        <tr>
                            <td><?php $contador++;
                                echo $contador; ?></td>
                            <td>
                                <div class="foto-mascota">
                                    <?php if (!empty($item->foto)): ?>
                                        <img src="../app/img_mascotas/<?php echo htmlspecialchars($item->foto); ?>" alt="">
                                    <?php else: ?>
                                        <span class="foto-placeholder">Sin<br>foto</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="mascota-nombre"><?php echo htmlspecialchars(ucfirst($item->nombre)); ?></span>
                            </td>
                            <td>
                                <?php if ($item->especie == 'perro'): ?>
                                    <span class="especie-badge especie-perro"><i class="fas fa-dog"></i></span>
                                <?php elseif ($item->especie == 'gato'): ?>
                                    <span class="especie-badge especie-gato"><i class="fas fa-cat"></i></span>
                                <?php elseif ($item->especie == 'ave'): ?>
                                    <span class="especie-badge especie-ave"><i class="fas fa-dove"></i></span>
                                <?php elseif ($item->especie == 'roedor'): ?>
                                    <span class="especie-badge especie-roedor"><i class="fas fa-rabbit"></i></span>
                                <?php elseif ($item->especie == 'otro'): ?>
                                    <span class="especie-badge especie-otro"><i class="fas fa-paw"></i></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(ucfirst($item->raza)); ?></td>
                            <td>
                                <span class="fecha-texto"><?php
                                                            if (!empty($item->fecha_nacimiento)) {
                                                                $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                                                                $fecha = strtotime($item->fecha_nacimiento);
                                                                echo date('d', $fecha) . ' ' . $meses[date('n', $fecha)] . ' ' . date('Y', $fecha);
                                                            } else {
                                                                echo '-';
                                                            }
                                                            ?></span>
                            </td>
                            <td>
                                <?php if ($item->sexo == 'M'): ?>
                                    <span class="sexo-badge sexo-macho">Macho</span>
                                <?php elseif ($item->sexo == 'F'): ?>
                                    <span class="sexo-badge sexo-hembra">Hembra</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="editar_mascota_cliente.php?id_mascota=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_mascota))); ?>&id_cliente=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_cliente))); ?>" type="button" class="btn-icon btn-edit" title="Editar"><i class="fas fa-edit"></i> Editar</a>
                                        <form method="POST" action="../controladores/recepcionista/eliminar_mascota.php" style="display:inline;">
                                            <input type="hidden" name="id_mascota" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_mascota))); ?>">
                                            <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_cliente))); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <button type="submit" class="btn-icon btn-delete" title="Eliminar"><i class="fas fa-trash"></i> Eliminar</button>
                                        </form>
                                        <a href="historial_clinico_mascota.php?id_mascota=<?php echo urlencode(Crypto::encrypt($item->id_mascota)); ?>" class="btn-icon btn-history" title="Historial"><i class="fas fa-file-medical"></i> Historial</a>
                                        <a href="gestion_vacunas_mascotas.php?id_mascota=<?php echo urlencode(Crypto::encrypt($item->id_mascota)); ?>" class="btn-icon btn-vaccine" title="Vacunas"><i class="fas fa-syringe"></i> Vacunas</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="pagination-container">
            <div class="pagination-info">
                Mostrando <?php echo ($offset + 1); ?> - <?php echo min($offset + $por_pagina, $total_registros); ?> de <?php echo $total_registros; ?> mascotas
            </div>
            <div class="pagination-controls">
                <?php if ($pagina > 1): ?>
                    <a href="?id_cliente=<?php echo urlencode($_GET['id_cliente']); ?>&pagina=<?php echo $pagina - 1; ?>" class="page-btn" title="Anterior">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <button class="page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?id_cliente=<?php echo urlencode($_GET['id_cliente']); ?>&pagina=<?php echo $i; ?>" class="page-btn <?php echo $i === $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="?id_cliente=<?php echo urlencode($_GET['id_cliente']); ?>&pagina=<?php echo $pagina + 1; ?>" class="page-btn" title="Siguiente">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <button class="page-btn" disabled><i class="fas fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<script src="../app/js/recepcionista/gestion_mascotas_cliente.js"></script>
<?php include_once '../templates/footer.php'; ?>