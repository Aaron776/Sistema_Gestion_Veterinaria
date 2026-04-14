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

$id_recepcionista = $_SESSION['id_usuario']; // obtener id del recepcionista logueado


// Obtener listado de mascotas
try {
    $sql = $conexion->prepare("SELECT clientes.cedula as cedula,mascotas.id as id_mascota, mascotas.nombre as nombre_mascota,mascotas.raza as raza ,clientes.nombre as nombre_cliente FROM mascotas INNER JOIN clientes ON mascotas.cliente_id = clientes.id");
    $sql->execute();
    $mascotas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener las mascotas:" . $e->getMessage());
    exit();
}

// Obtener listado de usuarios con rol veterinario
try {
    $sql = $conexion->prepare("SELECT id as id_veterinario,nombre as nombre_veterinario FROM usuarios WHERE rol = 'veterinario'");
    $sql->execute();
    $veterinarios = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los veterinarios:" . $e->getMessage());
    exit();
}

// Obtener listado de servicios
try {
    $sql = $conexion->prepare("SELECT id as id_servicio,nombre_servicio,precio FROM servicios");
    $sql->execute();
    $servicios = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los servicios:" . $e->getMessage());
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/registrar_cita.css">
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-calendar-plus"></i>
                Registrar Nueva Cita
            </h2>
            <p>Complete la información para agendar una nueva cita médica</p>
        </div>
        <div class="form-body">
            <!-- Alerta informativa -->
            <div class="info-alert">
                <i class="fas fa-info-circle"></i>
                <span>Los campos marcados con <strong style="color:#e74c3c;">*</strong> son obligatorios.</span>
            </div>

            <form id="registroCitaForm" action="../controladores/recepcionista/registrar_cita.php" method="POST">
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
                <input type="hidden" name="id_recepcionista" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_recepcionista))); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="required"><i class="fas fa-paw"></i> Mascota</label>
                        <div class="input-with-icon">
                            <i class="fas fa-dog"></i>
                            <select id="mascota" name="id_mascota" required>
                                <option value="" disabled selected>-- Seleccione una mascota --</option>
                                <?php foreach ($mascotas as $item): ?>
                                    <option value="<?php echo htmlspecialchars($item->id_mascota); ?>"><?php echo htmlspecialchars($item->nombre_mascota); ?> - Dueño: <?php echo htmlspecialchars($item->nombre_cliente); ?> - Cedula: <?php echo htmlspecialchars($item->cedula); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-user-md"></i> Veterinario</label>
                        <div class="input-with-icon">
                            <i class="fas fa-stethoscope"></i>
                            <select id="veterinario" name="id_veterinario" required>
                                <option value="" disabled selected>-- Seleccione un veterinario --</option>
                                <?php foreach ($veterinarios as $item): ?>
                                    <option value="<?php echo htmlspecialchars($item->id_veterinario); ?>"><?php echo htmlspecialchars($item->nombre_veterinario); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="required"><i class="fas fa-calendar-alt"></i> Fecha y Hora</label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar"></i>
                            <input type="datetime-local" id="fecha" name="fecha_hora" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <!-- Relleno para alinear -->
                    </div>
                </div>

                <div id="serviciosContainer">
                    <div class="servicio-row">
                        <div class="form-row" style="grid-template-columns: 2fr 1fr 1fr auto;">
                            <div class="form-group">
                                <label class="required"><i class="fas fa-concierge-bell"></i> Servicio</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-briefcase-medical"></i>
                                    <select class="servicio-select" name="id_servicio[]" required>
                                        <option value="" disabled selected>-- Seleccione un servicio --</option>
                                        <?php foreach ($servicios as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item->id_servicio); ?>" data-precio="<?php echo htmlspecialchars($item->precio); ?>"><?php echo htmlspecialchars($item->nombre_servicio); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="required"><i class="fas fa-hashtag"></i> Cantidad</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-boxes"></i>
                                    <input type="number" class="cantidad-input" name="cantidad[]" value="1" min="1" step="1" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="required"><i class="fas fa-dollar-sign"></i> Precio</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <input type="number" class="precio-input" name="precio[]" placeholder="0.00" step="0.01" required readonly>
                                </div>
                            </div>
                            <div class="form-group" style="display:flex; align-items:flex-end;">
                                <button type="button" class="btn-remove-servicio" style="display:none; background:#fee9e7; color:#e74c3c; border:none; border-radius:12px; height: 48px; width: 48px; cursor:pointer;" title="Eliminar servicio"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <button type="button" id="btnAgregarServicio" style="background:#e8f0fe; color:#3498db; border:none; padding:10px 20px; border-radius:40px; font-weight:600; cursor:pointer; font-size: 0.85rem;">
                        <i class="fas fa-plus"></i> Agregar Otro Servicio
                    </button>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-stethoscope"></i> Motivo de la Consulta</label>
                    <textarea id="motivo" name="motivo" rows="3" placeholder="Describa el motivo de la consulta, síntomas o razón de la cita..."></textarea>
                </div>

                <!-- Resumen de la cita -->
                <div class="total-preview">
                    <h4><i class="fas fa-receipt"></i> Resumen de la Cita</h4>
                    <div class="form-row" style="margin-top: 15px;">
                        <div style="text-align: center;">
                            <p style="color: #7f9aab; font-size: 0.75rem;">Subtotal</p>
                            <p style="font-weight: 700; font-size: 1.2rem;" id="subtotal">$0</p>
                        </div>
                        <div style="text-align: center;">
                            <p style="color: #6bcb77; font-size: 0.75rem;">TOTAL</p>
                            <p class="total-amount" id="total">$0</p>
                        </div>
                    </div>
                </div>

                <!-- Vista previa de la cita -->
                <div class="preview-card" id="previewCard" style="display: none;">
                    <h4><i class="fas fa-eye"></i> Vista Previa de la Cita</h4>
                    <div class="preview-content">
                        <div class="preview-item">
                            <div class="preview-label">Mascota</div>
                            <div class="preview-value" id="previewMascota">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Veterinario</div>
                            <div class="preview-value" id="previewVeterinario">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Servicio</div>
                            <div class="preview-value" id="previewServicio">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Fecha y Hora</div>
                            <div class="preview-value" id="previewFecha">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Cantidad</div>
                            <div class="preview-value" id="previewCantidad">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Total</div>
                            <div class="preview-value" id="previewTotal">—</div>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Registrar Cita
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='gestion_citas.php'">
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
        <i class="fas fa-clock" style="color: #f39c12;"></i>
        <span style="font-size: 0.8rem; color: #5f7f8f;"> Horario de atención: Lunes a Viernes 8:00 AM - 6:00 PM | Sábados 8:00 AM - 1:00 PM</span>
    </div>
</div>

<script src="../app/js/recepcionista/registrar_cita.js"></script>
<?php include_once '../templates/footer.php'; ?>