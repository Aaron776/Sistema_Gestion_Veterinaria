<?php
require_once '../autorizacion/auth.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/agregar_producto.css">
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-plus-circle"></i>
                Registrar Nuevo Producto
            </h2>
            <p>Complete la información para agregar un nuevo producto al inventario de la clínica veterinaria</p>
        </div>
        <div class="form-body">
            <!-- Alerta informativa -->
            <div class="info-alert">
                <i class="fas fa-info-circle"></i>
                <span>El ID del producto se generará automáticamente al momento de registrar.</span>
            </div>

            <form id="registroProductoForm" method="POST" action="../controladores/admin/agregar_producto.php">
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
                <div class="form-group">
                    <label class="required"><i class="fas fa-tag"></i> Nombre del Producto</label>
                    <input type="text" id="nombreProducto" name="nombre_producto"
                        placeholder="Ej: Vacuna Antirrábica, Antibiótico, Alimento Premium..."
                        required autofocus>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="required"><i class="fas fa-cubes"></i> Stock Inicial</label>
                        <div class="input-with-icon">
                            <i class="fas fa-box"></i>
                            <input type="number" id="stock" name="stock"
                                placeholder="Cantidad disponible"
                                min="0" value="0" required>
                        </div>
                        <small style="color: #7f8c8d; font-size: 0.7rem; display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Ingrese la cantidad inicial en inventario
                        </small>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-dollar-sign"></i> Precio Unitario</label>
                        <div class="input-with-icon">
                            <i class="fas fa-money-bill-wave"></i>
                            <input type="number" id="precio" name="precio"
                                placeholder="Precio por unidad"
                                min="0" step="0.01" required>
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
                        <i class="fas fa-save"></i> Registrar Producto
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='inventario.html'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn-secondary" onclick="limpiarFormulario()">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Información adicional -->
    <div style="margin-top: 20px; text-align: center; background: white; border-radius: 20px; padding: 15px; border: 1px solid #eef2f8;">
        <i class="fas fa-chart-line" style="color: #6bcb77;"></i>
        <span style="font-size: 0.8rem; color: #5f7f8f;"> Los productos registrados aparecerán automáticamente en la <a href="inventario.html" style="color:#6bcb77;">lista de inventario</a></span>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>
<script src="../app/js/admin/agregar_producto.js"></script>