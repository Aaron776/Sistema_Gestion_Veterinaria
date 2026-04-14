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

// Obtener listado de usuarios
try {
    $sql = $conexion->prepare("SELECT id as id_usuario, nombre, email, rol, telefono,cedula, ultimo_acceso FROM usuarios WHERE estado='activo' ORDER BY fecha_creacion DESC");
    $sql->execute();
    $usuarios = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los usuarios: " . $e->getMessage());
    header("Location: gestion_usuarios.php");
    exit();
}
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/gestion_usuarios.css">
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="users-container">
    <div class="page-header">
        <h2>
            <i class="fas fa-users"></i>
            Gestión de Usuarios
        </h2>
        <button class="btn-add" id="btnAgregarUsuario" onclick="window.location.href='agregar_usuario.php'">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </button>
    </div>

    <div class="table-container">
        <table class="users-table" id="usersTable">
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
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Cédula</th>
                    <th>Rol</th>
                    <th>Último Acceso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <h3>No hay usuarios registrados</h3>
                                <p>Comienza agregando un nuevo usuario al sistema.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $item): ?>
                        <tr data-id="1">
                            <td>U-<?php echo htmlspecialchars($item->id_usuario); ?></td>
                            <td><strong><?php echo htmlspecialchars($item->nombre); ?></strong></td>
                            <td><?php echo htmlspecialchars($item->email); ?></td>
                            <td><?php echo htmlspecialchars($item->telefono); ?></td>
                            <td><?php echo htmlspecialchars($item->cedula); ?></td>
                            <td>
                                <?php if ($item->rol == 'admin'): ?>
                                    <span class="role-badge role-admin">Administrador</span>
                                <?php elseif ($item->rol == 'veterinario'): ?>
                                    <span class="role-badge role-vet">Veterinario</span>
                                <?php elseif ($item->rol == 'recepcionista'): ?>
                                    <span class="role-badge role-reception">Recepcionista</span>
                                <?php endif; ?>
                            </td>
                            <td data-ultimo-acceso="<?php echo $item->ultimo_acceso ? htmlspecialchars($item->ultimo_acceso) : ''; ?>">
                                <?php if ($item->ultimo_acceso == null): ?>
                                    <span class="tiempo-relativo"><i class="fas fa-clock"></i> Nunca ha accedido</span>
                                <?php else: ?>
                                    <span class="tiempo-relativo"></span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <a type="button" class="btn-icon btn-edit" href="editar_usuario.php?id_usuario=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_usuario))); ?>" title="Editar usuario"><i class="fas fa-edit"></i></a>
                                <form action="../controladores/admin/editar_password_usuario.php" method="post" class="formPassword" id="formPassword_<?php echo $item->id_usuario; ?>">
                                    <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_usuario))); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="btn-icon btn-password"  title="Cambiar contraseña"><i class="fas fa-key"></i></button>
                                </form>
                                <?php if ($_SESSION['id_usuario'] != $item->id_usuario): ?>
                                    <form action="../controladores/admin/eliminar_usuario.php" method="post" class="formEliminar" id="formEliminar_<?php echo $item->id_usuario; ?>">
                                        <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_usuario))); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="button" class="btn-icon btn-delete btn-eliminar" data-id="<?php echo $item->id_usuario; ?>" data-nombre="<?php echo htmlspecialchars($item->nombre); ?>" title="Eliminar usuario"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="pagination-container" id="paginationContainer">
        </div>
    </div>
</div>
<script src="../app/js/admin/gestion_usuarios.js"></script>
<?php include_once '../templates/footer.php'; ?>