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

$id_cita = urldecode(Crypto::decrypt($_GET['id_cita']));
if (empty($id_cita) || !is_numeric($id_cita) || $id_cita < 1) {
    header("Location: gestion_citas.php");
    exit();
}

// Obtener datos de la cita y que este en estado pendiente
try {
    $sql = $conexion->prepare("SELECT citas.id as id_cita, usuarios.nombre as nombre_veterinario, mascotas.nombre as nombre_mascota, citas.fecha_hora as fecha_cita, citas.motivo as motivo, citas.estado as estado, clientes.nombre as nombre_cliente FROM citas INNER JOIN usuarios ON citas.veterinario_id = usuarios.id INNER JOIN mascotas ON citas.mascota_id = mascotas.id INNER JOIN clientes ON mascotas.cliente_id = clientes.id WHERE citas.id = :id_cita AND citas.estado = 'pendiente'");
    $sql->bindParam(':id_cita', $id_cita, PDO::PARAM_INT);
    $sql->execute();
    $cita = $sql->fetch(PDO::FETCH_OBJ);
    if (!$cita) {
        $_SESSION['errores'] = ["La cita solicitada no existe o ya ha sido procesada."];
        header("Location: gestion_citas.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener cita: " . $e->getMessage());
    header("Location: gestion_citas.php");
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/cambiar_estado_cita.css">
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-exchange-alt"></i>
                Cambiar Estado de Cita
            </h2>
            <p>Actualice el estado de la cita médica</p>
        </div>
        <div class="form-body">
            <!-- Alerta informativa -->
            <div class="info-alert">
                <i class="fas fa-info-circle"></i>
                <span>Seleccione el nuevo estado para la cita. Esta acción se registrará en el historial.</span>
            </div>

            <!-- Información de la cita -->
            <div class="cita-info" id="citaInfo">
                <h3><i class="fas fa-calendar-check"></i> Información de la Cita</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">ID de Cita</span>
                        <span class="info-value" id="infoId"><?php echo htmlspecialchars($cita->id_cita); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Mascota</span>
                        <span class="info-value" id="infoMascota"><?php echo htmlspecialchars(ucfirst($cita->nombre_mascota)); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Veterinario</span>
                        <span class="info-value" id="infoVeterinario"><?php echo htmlspecialchars(ucfirst($cita->nombre_veterinario)); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Fecha y Hora</span>
                        <span class="info-value" id="infoFecha"><?php echo htmlspecialchars(date('d/m/Y H:i A', strtotime($cita->fecha_cita))); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Motivo</span>
                        <span class="info-value" id="infoMotivo"><?php echo htmlspecialchars(ucfirst($cita->motivo)); ?></span>
                    </div>
                </div>
                <div class="estado-actual">
                    <span class="info-label">Estado actual:</span>
                    <span class="badge" id="estadoActualBadge"><?php echo htmlspecialchars(ucfirst($cita->estado)); ?></span>
                </div>
            </div>

            <form id="cambiarEstadoForm" action="../controladores/recepcionista/cambiar_estado_cita.php" method="POST">
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
                <input type="hidden" name="id_cita" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_cita))); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label class="required"><i class="fas fa-tag"></i> Nuevo Estado</label>
                    <div class="select-wrapper">
                        <i class="fas fa-chevron-down"></i>
                        <select id="estado" name="estado" required>
                            <option value="" disabled selected>-- Seleccione un estado --</option>
                            <option value="confirmada">✅ Confirmada</option>
                            <option value="cancelada">❌ Cancelada</option>
                        </select>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Actualizar Estado
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='gestion_citas.php'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Información adicional -->
    <div style="margin-top: 20px; text-align: center; background: white; border-radius: 20px; padding: 15px; border: 1px solid #eef2f8;">
        <i class="fas fa-clock" style="color: #f39c12;"></i>
        <span style="font-size: 0.8rem; color: #5f7f8f;"> Las citas confirmadas aparecerán en la agenda del veterinario. Las citas canceladas liberan el horario.</span>
    </div>
</div>

<script src="../app/js/recepcionista/cambiar_estado.js"></script>
<?php include_once '../templates/footer.php'; ?>