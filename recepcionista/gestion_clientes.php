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

// Paginación PHP
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rowsPerPage = 10;
$offset = ($page - 1) * $rowsPerPage;

// Contar total de clientes
$sqlCount = $conexion->prepare("SELECT COUNT(*) as total FROM clientes WHERE estado='activo'");
$sqlCount->execute();
$totalRow = $sqlCount->fetch(PDO::FETCH_OBJ);
$totalClients = $totalRow->total;
$totalPages = ceil($totalClients / $rowsPerPage);

// Obtener clientes con LIMIT y OFFSET
try {
    $sql = $conexion->prepare("SELECT id as id_cliente, nombre, cedula, telefono, email, direccion FROM clientes WHERE estado='activo' ORDER BY id ASC LIMIT :limit OFFSET :offset");
    $sql->bindValue(':limit', $rowsPerPage, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $clientes = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener clientes: " . $e->getMessage());
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/gestion_clientes.css">
<div class="clientes-container">
    <div class="page-header">
        <h2>
            <i class="fas fa-users"></i>
            Gestión de Clientes
        </h2>
        <button class="btn-add" id="btnAgregarCliente" onclick="window.location.href='agregar_cliente.php'">
            <i class="fas fa-user-plus"></i> Nuevo Cliente
        </button>
    </div>

    <div class="filters-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchCliente" placeholder="Buscar por nombre, cédula o email...">
        </div>
    </div>

    <div class="table-container">
        <table class="clientes-table" id="clientesTable">
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
                    <th>Cédula</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($clientes)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:60px;">
                            <i class="fas fa-users-slash" style="font-size: 48px; color: #cbd5e0;"></i>
                            <p style="margin-top: 15px; color: #7f8c8d;">No se encontraron clientes</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clientes as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars('C-' . $item->id_cliente) ?></td>
                            <td><span class="cliente-cedula"><?= htmlspecialchars($item->cedula ?? '—') ?></span></td>
                            <td><span class="cliente-nombre"><?= htmlspecialchars($item->nombre) ?></span></td>
                            <td><span class="cliente-email"><?= htmlspecialchars($item->email ?? '—') ?></span></td>
                            <td><span class="cliente-telefono"><?= htmlspecialchars($item->telefono ?? '—') ?></span></td>
                            <td><span class="cliente-direccion"><?= htmlspecialchars($item->direccion ?? '—') ?></span></td>
                            <td class="action-buttons">
                                <a href="editar_cliente.php?id_cliente=<?= htmlspecialchars(urlencode(Crypto::encrypt($item->id_cliente))) ?>" class="btn-icon btn-edit" title="Editar cliente">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <form action="../controladores/recepcionista/eliminar_cliente.php" method="POST">
                                    <input type="hidden" name="id_cliente" value="<?= htmlspecialchars(urlencode(Crypto::encrypt($item->id_cliente))) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <button type="submit" class="btn-icon btn-delete" title="Eliminar cliente">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </button>
                                </form>
                                <a href="gestion_mascotas_cliente.php?id_cliente=<?= htmlspecialchars(urlencode(Crypto::encrypt($item->id_cliente))) ?>" class="btn-icon btn-pets" title="Ver mascotas">
                                    <i class="fas fa-paw"></i> Mascotas
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando <?= ($offset + 1) ?> - <?= min($offset + $rowsPerPage, $totalClients) ?> de <?= $totalClients ?> clientes
                </div>
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="page-btn">Anterior</a>
                    <?php else: ?>
                        <button class="page-btn" disabled>Anterior</button>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="page-btn">Siguiente</a>
                    <?php else: ?>
                        <button class="page-btn" disabled>Siguiente</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="../app/js/recepcionista/gestion_clientes.js"></script>
<?php include_once '../templates/footer.php'; ?>