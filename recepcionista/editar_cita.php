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

// Validar y obtener ID
$id_cita_enc = isset($_GET['id_cita']) ? $_GET['id_cita'] : '';
if (empty($id_cita_enc)) {
    header("Location: gestion_citas.php");
    exit();
}
$id_cita = Crypto::decrypt(urldecode($id_cita_enc));
if (empty($id_cita) || !is_numeric($id_cita) || $id_cita < 1) {
    header("Location: gestion_citas.php");
    exit();
}

// Obtener datos de la cita y verificar que está pendiente
try {
    $sql = $conexion->prepare("SELECT * FROM citas WHERE id = :id AND estado = 'pendiente'");
    $sql->bindParam(":id", $id_cita, PDO::PARAM_INT);
    $sql->execute();
    $cita = $sql->fetch(PDO::FETCH_OBJ);

    if (!$cita) {
        $_SESSION['errores'] = ["La cita solicitada no existe o ya no se permite editarla porque ha sido procesada."];
        header("Location: gestion_citas.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener la cita:" . $e->getMessage());
    header("Location: gestion_citas.php");
    exit();
}

// Obtener servicios actuales vinculados a esta cita
try {
    $sql_servicios_actuales = $conexion->prepare("SELECT * FROM cita_servicios WHERE cita_id = :cita_id");
    $sql_servicios_actuales->bindParam(":cita_id", $id_cita, PDO::PARAM_INT);
    $sql_servicios_actuales->execute();
    $servicios_actuales = $sql_servicios_actuales->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los servicios de la cita:" . $e->getMessage());
    $servicios_actuales = [];
}

// Obtener listados para los selectores
try {
    $sql = $conexion->prepare("SELECT clientes.cedula as cedula,mascotas.id as id_mascota, mascotas.nombre as nombre_mascota,mascotas.raza as raza ,clientes.nombre as nombre_cliente FROM mascotas INNER JOIN clientes ON mascotas.cliente_id = clientes.id");
    $sql->execute();
    $mascotas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
}

try {
    $sql = $conexion->prepare("SELECT id as id_veterinario,nombre as nombre_veterinario FROM usuarios WHERE rol = 'veterinario'");
    $sql->execute();
    $veterinarios = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
}

try {
    $sql = $conexion->prepare("SELECT id as id_servicio,nombre_servicio,precio FROM servicios");
    $sql->execute();
    $servicios = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/editar_cita.css">

<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-edit"></i>
                Editar Cita #<?php echo htmlspecialchars($cita->id); ?>
            </h2>
            <p>Modifique la información y los servicios asignados a esta cita.</p>
        </div>
        <div class="form-body">
            <div class="info-alert">
                <i class="fas fa-info-circle" style="color: #f39c12;"></i>
                <span>Edite cuidadosamente los valores. Los servicios antiguos serán reemplazados por los declarados en esta forma.</span>
            </div>

            <form id="editarCitaForm" action="../controladores/recepcionista/editar_cita.php" method="POST">
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

                <input type="hidden" name="id_cita" value="<?php echo htmlspecialchars($id_cita_enc); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label class="required"><i class="fas fa-paw"></i> Mascota</label>
                        <div class="input-with-icon">
                            <i class="fas fa-dog"></i>
                            <select id="mascota" name="id_mascota" required>
                                <option value="" disabled>-- Seleccione una mascota --</option>
                                <?php foreach ($mascotas as $item): ?>
                                    <option value="<?php echo htmlspecialchars($item->id_mascota); ?>" <?php echo ($item->id_mascota == $cita->mascota_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($item->nombre_mascota); ?> - Dueño: <?php echo htmlspecialchars($item->nombre_cliente); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-user-md"></i> Veterinario</label>
                        <div class="input-with-icon">
                            <i class="fas fa-stethoscope"></i>
                            <select id="veterinario" name="id_veterinario" required>
                                <option value="" disabled>-- Seleccione un veterinario --</option>
                                <?php foreach ($veterinarios as $item): ?>
                                    <option value="<?php echo htmlspecialchars($item->id_veterinario); ?>" <?php echo ($item->id_veterinario == $cita->veterinario_id) ? 'selected' : ''; ?>>
                                        Dr(a). <?php echo htmlspecialchars($item->nombre_veterinario); ?>
                                    </option>
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
                            <input type="datetime-local" id="fecha" name="fecha_hora" value="<?php echo date('Y-m-d\TH:i', strtotime($cita->fecha_hora)); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <!-- Spacer -->
                    </div>
                </div>

                <!-- CARGA DINÁMICA DE SERVICIOS PREVIOS -->
                <div id="serviciosContainer">
                    <?php if (empty($servicios_actuales)): ?>
                        <!-- Fallback visual si cita no tenía servicios por algún error del pasado -->
                        <div class="servicio-row">
                            <div class="form-row" style="grid-template-columns: 2fr 1fr 1fr auto;">
                                <div class="form-group">
                                    <label class="required"><i class="fas fa-concierge-bell"></i> Servicio</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-briefcase-medical"></i>
                                        <select class="servicio-select" name="id_servicio[]" required>
                                            <option value="" disabled selected>-- Seleccione --</option>
                                            <?php foreach ($servicios as $item): ?>
                                                <option value="<?php echo htmlspecialchars($item->id_servicio); ?>" data-precio="<?php echo htmlspecialchars($item->precio); ?>">
                                                    <?php echo htmlspecialchars($item->nombre_servicio); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="required"><i class="fas fa-hashtag"></i> Ctd</label>
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
                                    <button type="button" class="btn-remove-servicio" style="display:none; background:#fee9e7; color:#e74c3c; border:none; border-radius:12px; height: 48px; width: 48px; cursor:pointer;"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Loop de servicios existentes -->
                        <?php foreach ($servicios_actuales as $index => $srv_act): ?>
                            <div class="servicio-row">
                                <div class="form-row" style="grid-template-columns: 2fr 1fr 1fr auto;">
                                    <div class="form-group">
                                        <label class="required"><i class="fas fa-concierge-bell"></i> Servicio</label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-briefcase-medical"></i>
                                            <select class="servicio-select" name="id_servicio[]" required>
                                                <option value="" disabled>-- Seleccione --</option>
                                                <?php foreach ($servicios as $item): ?>
                                                    <option value="<?php echo htmlspecialchars($item->id_servicio); ?>"
                                                        data-precio="<?php echo htmlspecialchars($item->precio); ?>"
                                                        <?php echo ($item->id_servicio == $srv_act->servicio_id) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($item->nombre_servicio); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="required"><i class="fas fa-hashtag"></i> Cantidad</label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-boxes"></i>
                                            <input type="number" class="cantidad-input" name="cantidad[]" value="<?php echo htmlspecialchars($srv_act->cantidad); ?>" min="1" step="1" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="required"><i class="fas fa-dollar-sign"></i> Precio</label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <input type="number" class="precio-input" name="precio[]" value="<?php echo htmlspecialchars($srv_act->precio); ?>" placeholder="0.00" step="0.01" required readonly>
                                        </div>
                                    </div>
                                    <div class="form-group" style="display:flex; align-items:flex-end;">
                                        <!-- En modo de edición, permitimos borrar incluso la primera fila si tiene más de 1 servicio -->
                                        <button type="button" class="btn-remove-servicio" style="<?php echo (count($servicios_actuales) == 1 && $index == 0) ? 'display:none;' : 'display:block;'; ?> background:#fee9e7; color:#e74c3c; border:none; border-radius:12px; height: 48px; width: 48px; cursor:pointer;" title="Eliminar servicio">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="margin-bottom: 24px;">
                    <button type="button" id="btnAgregarServicio" style="background:#fdfaf5; border:1px solid #f39c12; color:#f39c12; padding:10px 20px; border-radius:40px; font-weight:600; cursor:pointer; font-size: 0.85rem;">
                        <i class="fas fa-plus"></i> Agregar Otro Servicio
                    </button>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-comment-medical"></i> Motivo de la Consulta</label>
                    <textarea id="motivo" name="motivo" rows="3" placeholder="Describa el motivo..."><?php echo htmlspecialchars($cita->motivo); ?></textarea>
                </div>

                <div class="total-preview">
                    <h4><i class="fas fa-receipt"></i> Evaluación del Costo</h4>
                    <div class="form-row" style="margin-top: 15px;">
                        <div style="text-align: center;">
                            <p style="color: #7f9aab; font-size: 0.75rem;">Subtotal Dinámico</p>
                            <p style="font-weight: 700; font-size: 1.2rem;" id="subtotal">$0</p>
                        </div>
                        <div style="text-align: center;">
                            <p style="color: #f39c12; font-size: 0.75rem;">NUEVO TOTAL</p>
                            <p class="total-amount" id="total">$0</p>
                        </div>
                    </div>
                </div>

                <!-- Oculto para que no abulte, misma lógica JS que antes -->
                <div class="preview-card" id="previewCard" style="display: none;"></div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='gestion_citas.php'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../app/js/recepcionista/editar_cita.js"></script>
<?php include_once '../templates/footer.php'; ?>