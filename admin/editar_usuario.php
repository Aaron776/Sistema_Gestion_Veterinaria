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

$id_usuario = Crypto::decrypt(urldecode($_GET['id_usuario']));
if (empty($id_usuario) || !is_numeric($id_usuario) || $id_usuario <= 0) {
    header("Location: gestion_usuarios.php");
    exit();
}

// Obtener datos del usaurio que se va a editar
try {
    $sql = $conexion->prepare("SELECT nombre, email, rol, telefono,cedula, ultimo_acceso FROM usuarios WHERE id=:id_usuario AND estado='activo' limit 1");
    $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
    $sql->execute();
    $usuario = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener el usuario a editar: " . $e->getMessage());
    header("Location: gestion_usuarios.php");
    exit();
}

if (empty($usuario)) {
    header("Location: gestion_usuarios.php");
    exit();
}
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/editar_usuario.css">
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-user-edit"></i>
                Editar Usuario
            </h2>
            <p>Modifique la información del usuario seleccionado</p>
        </div>
        <div class="form-body">
            <form id="editarUsuarioForm" action="../controladores/admin/editar_usuario.php" method="post">
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
                <input type="hidden" id="userId" name="id_usuario" value="<?php echo htmlspecialchars(urldecode(Crypto::encrypt($id_usuario))); ?>">
                <input type="hidden" id="csrfToken" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="required"><i class="fas fa-user"></i> Nombre Completo</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario->nombre); ?>" placeholder="Ej: María González Pérez" required>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-id-card"></i> Cédula</label>
                        <input type="text" id="cedula" name="cedula" value="<?php echo htmlspecialchars($usuario->cedula); ?>" placeholder="Ej: 1234567890" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario->telefono); ?>" placeholder="Ej: 3001234567">
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario->email); ?>" placeholder="ejemplo@vetcare.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required"><i class="fas fa-user-tag"></i> Rol</label>
                    <select id="rol" name="rol" required>
                        <option value="" disabled>-- Seleccione un rol --</option>
                        <option value="admin" <?php echo $usuario->rol === 'admin' ? 'selected' : ''; ?>>👑 Administrador</option>
                        <option value="veterinario" <?php echo $usuario->rol === 'veterinario' ? 'selected' : ''; ?>>🩺 Veterinario</option>
                        <option value="recepcionista" <?php echo $usuario->rol === 'recepcionista' ? 'selected' : ''; ?>>📋 Recepcionista</option>
                    </select>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='gestion-usuarios.php'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Información adicional -->
    <div style="margin-top: 20px; text-align: center; background: white; border-radius: 20px; padding: 15px; border: 1px solid #eef2f8;">
        <i class="fas fa-lock" style="color: #f39c12;"></i>
        <span style="font-size: 0.8rem; color: #5f7f8f;"> Para cambiar la contraseña, diríjase a la <a href="gestion-usuarios.html" style="color:#6bcb77;">lista de usuarios</a> y use el botón de "Cambiar contraseña"</span>
    </div>
</div>

<script src="../app/js/admin/editar_usuario.js"></script>
<?php include_once '../templates/footer.php'; ?>