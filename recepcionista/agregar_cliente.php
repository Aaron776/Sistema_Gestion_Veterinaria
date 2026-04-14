<?php
require_once '../autorizacion/auth.php';

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../acceso_denegado.php");
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/agregar_cliente.css">
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-user-plus"></i>
                Registrar Nuevo Cliente
            </h2>
            <p>Complete la información para agregar un nuevo cliente al sistema de la clínica veterinaria</p>
        </div>
        <div class="form-body">
            <form id="registroClienteForm" action="../controladores/recepcionista/agregar_cliente.php" method="POST">
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
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="required"><i class="fas fa-user"></i> Nombre Completo</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="nombre" name="nombre"
                                placeholder="Ej: María González Pérez"
                                required autofocus>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-id-card"></i> Cédula</label>
                        <div class="input-with-icon">
                            <i class="fas fa-id-card"></i>
                            <input type="text" id="cedula" name="cedula"
                                placeholder="Ej: 1234567890"
                                required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Teléfono</label>
                        <div class="input-with-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="telefono" name="telefono"
                                placeholder="Ej: 3001234567">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-envelope"></i> Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email"
                                placeholder="ejemplo@vetcare.com"
                                required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Dirección</label>
                    <div class="input-with-icon">
                        <i class="fas fa-map-marker-alt"></i>
                        <textarea id="direccion" name="direccion"
                            rows="3"
                            placeholder="Ej: Calle 123 #45-67, Bogotá"></textarea>
                    </div>
                </div>

                <!-- Vista previa en tiempo real -->
                <div class="preview-card" id="previewCard">
                    <h4><i class="fas fa-eye"></i> Vista Previa del Cliente</h4>
                    <div class="preview-content">
                        <div class="preview-item">
                            <div class="preview-label">Nombre Completo</div>
                            <div class="preview-value" id="previewNombre">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Cédula</div>
                            <div class="preview-value" id="previewCedula">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Teléfono</div>
                            <div class="preview-value" id="previewTelefono">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Email</div>
                            <div class="preview-value" id="previewEmail">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Dirección</div>
                            <div class="preview-value" id="previewDireccion">—</div>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Registrar Cliente
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='gestion_clientes.php'">
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
        <i class="fas fa-paw" style="color: #6bcb77;"></i>
        <span style="font-size: 0.8rem; color: #5f7f8f;"> Los clientes registrados aparecerán automáticamente en la <a href="gestion_clientes.php" style="color:#6bcb77;">lista de clientes</a></span>
    </div>
</div>

<script src="../app/js/recepcionista/agregar_cliente.js"></script>
<?php include_once '../templates/footer.php'; ?>