<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'veterinario') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_cita = Crypto::decrypt(urldecode($_GET['id_cita'] ?? ''));
if (empty($id_cita) || !is_numeric($id_cita) || $id_cita <= 0) {
    header("Location: gestion_citas_confirmadas.php");
    exit();
}

// Obtener datos de la cita
try {
    $sql = $conexion->prepare("SELECT mascotas.nombre as nombre_mascota,clientes.nombre as nombre_cliente,usuarios.nombre as nombre_veterinario,citas.fecha_hora as fecha_cita,citas.motivo as motivo FROM citas INNER JOIN mascotas ON citas.mascota_id = mascotas.id INNER JOIN clientes ON mascotas.cliente_id = clientes.id INNER JOIN usuarios ON citas.veterinario_id = usuarios.id WHERE citas.id=:id_cita AND citas.estado='confirmada' limit 1");
    $sql->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
    $sql->execute();
    $cita = $sql->fetch(PDO::FETCH_OBJ);
    if (empty($cita)) {
        header("Location: gestion_citas_confirmadas.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener cita: " . $e->getMessage());
    header("Location: gestion_citas_confirmadas.php");
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/veterinario/cambiar_estado_cita.css">
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
                <span>Al cambiar el estado de la cita a <strong>"Realizada"</strong>, se habilitará la opción de agregar el registro al historial clínico.</span>
            </div>

            <!-- Información de la cita -->
            <div class="cita-info" id="citaInfo">
                <h3><i class="fas fa-calendar-alt"></i> Información de la Cita</h3>
                <div class="info-row">
                    <div class="info-label">ID Cita:</div>
                    <div class="info-value" id="citaId">#<?php echo htmlspecialchars($id_cita); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Mascota:</div>
                    <div class="info-value" id="citaMascota"><?php echo htmlspecialchars(ucfirst($cita->nombre_mascota)); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Dueño:</div>
                    <div class="info-value" id="citaDueno"><?php echo htmlspecialchars(ucfirst($cita->nombre_cliente)); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Veterinario:</div>
                    <div class="info-value" id="citaVeterinario">Dr/ra. <?php echo htmlspecialchars(ucfirst($cita->nombre_veterinario)); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha:</div>
                    <div class="info-value" id="citaFecha"><?php echo htmlspecialchars(date('d/m/Y H:i A', strtotime($cita->fecha_cita))); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Motivo:</div>
                    <div class="info-value" id="citaMotivo"><?php echo htmlspecialchars(ucfirst($cita->motivo)); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Estado Actual:</div>
                    <div class="info-value" id="citaEstado">
                        <span class="status-badge status-confirmada">Confirmada</span>
                    </div>
                </div>
            </div>

            <form id="cambiarEstadoForm" action="../controladores/veterinario/cambiar_estado_cita.php" method="POST">
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
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label class="required"><i class="fas fa-tag"></i> Nuevo Estado</label>
                    <div class="input-with-icon">
                        <i class="fas fa-exchange-alt"></i>
                        <select id="nuevoEstado" name="estado" required>
                            <option value="" disabled selected>-- Seleccione un estado --</option>
                            <option value="realizada">✅ Realizada</option>
                        </select>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Cambiar Estado
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='gestion-citas-veterinario.html'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Información adicional -->
    <div style="margin-top: 20px; text-align: center; background: white; border-radius: 20px; padding: 15px; border: 1px solid #eef2f8;">
        <i class="fas fa-stethoscope" style="color: #6bcb77;"></i>
        <span style="font-size: 0.8rem; color: #5f7f8f;"> Una vez marcada como "Realizada", podrá registrar el historial clínico de la mascota</span>
    </div>
</div>

<script src="../app/js/veterinario/cambiar_estado_cita.js"></script>
<?php include_once '../templates/footer.php'; ?>