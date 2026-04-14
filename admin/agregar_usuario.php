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
<link rel="stylesheet" href="../app/css/admin/agregar_usuario.css">
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-user-plus"></i>
                Registrar Nuevo Usuario
            </h2>
            <p>Complete la información para agregar un nuevo usuario al sistema de la clínica veterinaria</p>
        </div>
        <div class="form-body">
            <form id="registroUsuarioForm" method="post" action="../controladores/admin/agregar_usuario.php">
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
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="required"><i class="fas fa-user"></i> Nombre Completo</label>
                        <input type="text" id="nombre" name="nombre" placeholder="Ej: María González Pérez" required>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-id-card"></i> Cédula</label>
                        <input type="text" id="cedula" name="cedula" placeholder="Ej: 1234567890" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" placeholder="Ej: 3001234567">
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email" name="email" placeholder="ejemplo@vetcare.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required"><i class="fas fa-user-tag"></i> Rol</label>
                    <select id="rol" name="rol" required>
                        <option value="" disabled selected>-- Seleccione un rol --</option>
                        <option value="admin">👑 Administrador</option>
                        <option value="veterinario">🩺 Veterinario</option>
                        <option value="recepcionista">📋 Recepcionista</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="required"><i class="fas fa-lock"></i> Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Registrar Usuario
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='gestion-usuarios.html'">
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
        <i class="fas fa-info-circle" style="color: #6bcb77;"></i>
        <span style="font-size: 0.8rem; color: #5f7f8f;">Los campos marcados con <span style="color:#e74c3c;">*</span> son obligatorios</span>
    </div>
</div>

<script src="../app/js/admin/agregar_usuario.js"></script>
<?php include_once '../templates/footer.php'; ?>