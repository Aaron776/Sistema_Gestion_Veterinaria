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

$id_mascota = crypto::decrypt(urldecode($_GET['id_mascota']));
if (!$id_mascota || $id_mascota <= 0 || !is_numeric($id_mascota)) {
    header('Location: gestion_vacunacion_mascotas.php?id_mascota=' . urlencode(Crypto::encrypt($id_mascota)));
    exit();
}

// Obtener información de la mascota para el encabezado
try {
    $sql_mascota = $conexion->prepare("SELECT m.nombre as nombre_mascota, m.raza, m.especie, m.sexo, m.fecha_nacimiento, c.nombre as nombre_cliente, c.cedula as cedula
    FROM mascotas m 
    JOIN clientes c ON m.cliente_id = c.id 
    WHERE m.id = :id_mascota");
    $sql_mascota->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql_mascota->execute();
    $mascota = $sql_mascota->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener información de la mascota: " . $e->getMessage());
}


// Obtener listado de vacunas
try {
    $sql = $conexion->prepare("SELECT id as id_vacuna, nombre FROM vacunas");
    $sql->execute();
    $listado_vacunas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener las vacunas: " . $e->getMessage());
    exit();
}
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/veterinario/agregar_vacuna_mascota.css">
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-syringe"></i>
                Registrar Vacuna
            </h2>
            <p>Complete la información para registrar una nueva vacuna en el historial de la mascota</p>
        </div>
        <div class="form-body">
            <div class="info-alert">
                <i class="fas fa-info-circle"></i>
                <span>Los campos marcados con <strong style="color:#e74c3c;">*</strong> son obligatorios. La fecha de aplicación será la actual.</span>
            </div>

            <!-- Información de la mascota -->
            <?php if ($mascota): ?>
                <div class="mascota-info" id="mascotaInfo">
                    <div class="mascota-avatar" id="mascotaAvatar">
                        <i class="fas fa-dog"></i>
                    </div>
                    <div class="mascota-datos">
                        <h3 id="mascotaNombre"><?= htmlspecialchars(ucfirst($mascota->nombre_mascota)) ?></h3>
                        <p><i class="fas fa-paw"></i> <span id="mascotaEspecie"><?= htmlspecialchars(ucfirst($mascota->especie)) ?></span> | <i class="fas fa-dna"></i> <span id="mascotaRaza"><?= htmlspecialchars(ucfirst($mascota->raza)) ?></span> | <i class="fas fa-venus-mars"></i> <span id="mascotaSexo"><?= htmlspecialchars(ucfirst($mascota->sexo)) ?></span></p>
                        <p><i class="fas fa-user"></i> Propietario: <strong id="clienteNombre"><?= htmlspecialchars(ucfirst($mascota->nombre_cliente)) ?></strong></p>
                    </div>
                </div>
            <?php endif; ?>

            <form id="registroVacunaForm" method="POST" action="../controladores/veterinario/agregar_vacuna_mascota.php">
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
                <input type="hidden" name="id_mascota" value="<?= urlencode(Crypto::encrypt($id_mascota)) ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="form-group">
                    <label class="required"><i class="fas fa-briefcase-medical"></i> Vacuna</label>
                    <div class="input-with-icon">
                        <i class="fas fa-syringe"></i>
                        <select id="vacuna" name="id_vacuna" required>
                            <option value="" disabled selected>-- Seleccione una vacuna --</option>
                            <?php foreach ($listado_vacunas as $item) : ?>
                                <option value="<?php echo htmlspecialchars($item->id_vacuna); ?>"><?php echo htmlspecialchars(ucfirst($item->nombre)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Próxima Dosis</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar"></i>
                        <input type="date" id="proximaDosis" name="proxima_dosis">
                    </div>
                    <small style="color: #7f8c8d; font-size: 0.7rem;">Dejar en blanco si es la última dosis o no aplica</small>
                </div>

                <!-- Vista previa -->
                <div class="preview-card" id="previewCard" style="display: none;">
                    <h4><i class="fas fa-eye"></i> Vista Previa de la Vacuna</h4>
                    <div class="preview-content">
                        <div class="preview-item">
                            <div class="preview-label">Vacuna</div>
                            <div class="preview-value" id="previewVacuna">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Fecha de Aplicación</div>
                            <div class="preview-value" id="previewFecha">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Próxima Dosis</div>
                            <div class="preview-value" id="previewProxima">—</div>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Registrar Vacuna
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='gestion_vacunacion_mascotas.php?id_mascota=<?= urlencode(Crypto::encrypt($id_mascota)) ?>'">
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
        <span style="font-size: 0.8rem; color: #5f7f8f;"> La fecha de aplicación se registrará automáticamente con la fecha actual del sistema</span>
    </div>
</div>

<script src="../app/js/veterinario/agregar_vacuna_mascota.js"></script>
<?php include_once '../templates/footer.php'; ?>