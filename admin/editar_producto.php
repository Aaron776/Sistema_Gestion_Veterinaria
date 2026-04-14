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

$id_producto = Crypto::decrypt(urldecode($_GET['id_producto']));
if (!$id_producto || !is_numeric($id_producto) || $id_producto <= 0) {
    header("Location: gestion_inventario.php");
    exit();
}

// Obtener los datos del producto para editar
try {
    $sql = $conexion->prepare("SELECT nombre_producto,precio,stock FROM inventario WHERE id = :id_producto AND estado = 'activo'");
    $sql->bindParam(':id_producto', $id_producto);
    $sql->execute();
    $producto = $sql->fetch(PDO::FETCH_OBJ);

    if (!$producto) {
        header("Location: gestion_inventario.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener el producto: " . $e->getMessage());
    header("Location: gestion_inventario.php");
    exit();
}
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/editar_producto.css">
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-edit"></i>
                Editar Producto
            </h2>
            <p>Modifique la información del producto en el inventario de la clínica veterinaria</p>
        </div>
        <div class="form-body">
            <form id="registroProductoForm" method="POST" action="../controladores/admin/editar_producto.php">
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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="id_producto" value="<?= htmlspecialchars(urlencode(Crypto::encrypt($id_producto))) ?>">
                <div class="form-group">
                    <label class="required"><i class="fas fa-tag"></i> Nombre del Producto</label>
                    <input type="text" id="nombreProducto" name="nombre_producto"
                        placeholder="Ej: Vacuna Antirrábica, Antibiótico, Alimento Premium..."
                        required autofocus value="<?= htmlspecialchars($producto->nombre_producto) ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="required"><i class="fas fa-cubes"></i> Stock</label>
                        <div class="input-with-icon">
                            <i class="fas fa-box"></i>
                            <input type="number" id="stock" name="stock"
                                placeholder="Cantidad disponible"
                                min="0" value="<?= htmlspecialchars($producto->stock) ?>" required>
                        </div>
                        <small style="color: #7f8c8d; font-size: 0.7rem; display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Ingrese la cantidad en inventario
                        </small>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-dollar-sign"></i> Precio Unitario</label>
                        <div class="input-with-icon">
                            <i class="fas fa-money-bill-wave"></i>
                            <input type="number" id="precio" name="precio"
                                placeholder="Precio por unidad"
                                min="0" step="0.01" value="<?= htmlspecialchars($producto->precio) ?>" required>
                        </div>
                        <small style="color: #7f8c8d; font-size: 0.7rem; display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Precio de venta al público
                        </small>
                    </div>
                </div>

                <!-- Vista previa en tiempo real -->
                <div class="preview-card" id="previewCard">
                    <h4><i class="fas fa-eye"></i> Vista Previa del Producto</h4>
                    <div class="preview-content">
                        <div class="preview-item">
                            <div class="preview-label">Producto</div>
                            <div class="preview-value" id="previewNombre">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Stock</div>
                            <div class="preview-value" id="previewStock">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Precio</div>
                            <div class="preview-value" id="previewPrecio">—</div>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Actualizar Producto
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='gestion_inventario.php'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Información adicional -->
    <div style="margin-top: 20px; text-align: center; background: white; border-radius: 20px; padding: 15px; border: 1px solid #eef2f8;">
        <i class="fas fa-chart-line" style="color: #6bcb77;"></i>
        <span style="font-size: 0.8rem; color: #5f7f8f;"> Los cambios se guardarán en la <a href="gestion_inventario.php" style="color:#6bcb77;">lista de inventario</a></span>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>
<script src="../app/js/admin/agregar_producto.js"></script>