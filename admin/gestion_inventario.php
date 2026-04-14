<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtener listado del inventario
// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);

try {
    // 1. Obtener el total de registros
    $sql_count = $conexion->prepare("SELECT COUNT(*) as total FROM inventario WHERE estado='activo'");
    $sql_count->execute();
    $total_registros = $sql_count->fetch(PDO::FETCH_OBJ)->total;

    $total_paginas = ceil($total_registros / $por_pagina);

    // Si la página solicitada excede el límite real, lo ajustamos
    if ($pagina > $total_paginas && $total_paginas > 0) {
        $pagina = $total_paginas;
    }

    $offset = ($pagina - 1) * $por_pagina;

    // 2. Obtener los registros de la página actual
    $sql = $conexion->prepare("SELECT id as id_producto, nombre_producto, stock, precio, fecha_creacion FROM inventario WHERE estado='activo' ORDER BY fecha_creacion DESC LIMIT :limit OFFSET :offset");
    $sql->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $inventario = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener el inventario: " . $e->getMessage());
    header("Location: gestion_inventario.php");
    exit();
}
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/gestion_inventario.css">
<div class="inventory-container">
    <div class="page-header">
        <h2>
            <i class="fas fa-boxes"></i>
            Gestión de Inventario
        </h2>
        <button class="btn-add" id="btnAgregarProducto" type="button" onclick="window.location.href='agregar_producto.php'">
            <i class="fas fa-plus-circle"></i> Nuevo Producto
        </button>
    </div>

    <div class="filters-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchProducto" placeholder="Buscar por nombre o ID...">
        </div>
        <div class="stock-filter">
            <label><i class="fas fa-filter"></i> Filtrar por stock:</label>
            <select id="stockFilter">
                <option value="all">Todos</option>
                <option value="normal">Stock normal (>10)</option>
                <option value="low">Stock bajo (1-10)</option>
                <option value="critical">Stock crítico (0)</option>
            </select>
        </div>
    </div>

    <div class="table-container">
        <table class="inventory-table" id="inventoryTable">
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
                    <th>Nombre Producto</th>
                    <th>Stock</th>
                    <th>Precio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($inventario)): ?>
                    <tr class="empty-state-row">
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fas fa-boxes-open"></i>
                                <h3>No hay productos registrados</h3>
                                <p>Comienza agregando un nuevo producto al inventario.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inventario as $item): ?>
                        <tr class="row-inventario">
                            <td><strong>#<?= htmlspecialchars($item->id_producto) ?></strong></td>
                            <td><strong><?= htmlspecialchars($item->nombre_producto) ?></strong></td>
                            <td>
                                <?php if ($item->stock > 10) { ?>
                                    <span class="stock-badge stock-normal"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($item->stock) ?> unidades</span>
                                <?php } elseif ($item->stock >= 1 && $item->stock <= 10) { ?>
                                    <span class="stock-badge stock-low"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($item->stock) ?> unidades</span>
                                <?php } else { ?>
                                    <span class="stock-badge stock-critical"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($item->stock) ?> unidades</span>
                                <?php } ?>
                            </td>
                            <td><strong>$<?= htmlspecialchars(number_format($item->precio, 2)) ?></strong></td>
                            <td class="action-buttons">
                                <a href="editar_producto.php?id_producto=<?= htmlspecialchars(urlencode(Crypto::encrypt($item->id_producto))) ?>" class="btn-icon btn-edit" title="Editar producto"><i class="fas fa-edit"></i> Editar</a>
                                <form action="../controladores/admin/eliminar_producto.php" method="POST" class="formEliminar">
                                    <input type="hidden" name="id_producto" value="<?= htmlspecialchars(urlencode(Crypto::encrypt($item->id_producto))) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="button" class="btn-icon btn-delete btn-eliminar" title="Eliminar producto"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Fila para cuando no hay resultados en la búsqueda (Controlado por JS) -->
                <tr id="noResultsRow" style="display: none;">
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No se encontraron productos</h3>
                            <p>Intenta con otros términos de búsqueda.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="pagination-container" id="paginationContainer">
            <?php if (isset($total_registros) && $total_registros > 0): ?>
                <div class="pagination-info">
                    <span>Página <strong><?= $pagina ?></strong> de <strong><?= $total_paginas ?></strong></span>
                    <span style="margin: 0 10px;">|</span>
                    Mostrando <strong><?= $offset + 1 ?></strong> - <strong><?= min($offset + $por_pagina, $total_registros) ?></strong> de <strong><?= $total_registros ?></strong> productos
                </div>
                <div class="pagination-controls">
                    <a href="?pagina=<?= max(1, $pagina - 1) ?>" class="page-btn" <?= $pagina <= 1 ? 'style="pointer-events: none; opacity: 0.5;"' : '' ?>>
                        <i class="fas fa-chevron-left"></i>
                    </a>

                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?pagina=<?= $i ?>" class="page-btn <?= $i === $pagina ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <a href="?pagina=<?= min($total_paginas, $pagina + 1) ?>" class="page-btn" <?= $pagina >= $total_paginas ? 'style="pointer-events: none; opacity: 0.5;"' : '' ?>>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../app/js/admin/gestion_inventario.js?v=<?= time() ?>"></script>
<?php include_once '../templates/footer.php'; ?>