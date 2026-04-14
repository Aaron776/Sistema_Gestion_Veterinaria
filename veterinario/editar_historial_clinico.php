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


$id_historial_clinico = urldecode(Crypto::decrypt($_GET['id_historial_clinico'])); // obtener el id de la historial clinico de la cita medica para el registro del hisotial clinico
$id_mascota = urldecode(Crypto::decrypt($_GET['id_mascota'])); // obtener el id de la mascota de la cita medica para el registro del hisotial clinico
$id_cita = urldecode(Crypto::decrypt($_GET['id_cita'])); // obtener el id de la cita medica para el registro del hisotial clinico
if (empty($id_historial_clinico) || !is_numeric($id_historial_clinico) || $id_historial_clinico < 1) {
    header("Location: historial_clinico_mascota.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)));
    exit();
}
if (empty($id_mascota) || !is_numeric($id_mascota) || $id_mascota < 1) {
    header("Location: historial_clinico_mascota.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)));
    exit();
}
if (empty($id_cita) || !is_numeric($id_cita) || $id_cita < 1) {
    header("Location: historial_clinico_mascota.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)));
    exit();
}

// Obtener datos de la mascota
try {
    $sql_mascota = $conexion->prepare("SELECT m.nombre as nombre_mascota, m.raza, m.especie, m.sexo, m.fecha_nacimiento, c.nombre as nombre_cliente,c.cedula as cedula, c.telefono as telefono
    FROM mascotas m 
    JOIN clientes c ON m.cliente_id = c.id 
    WHERE m.id = :id_mascota");
    $sql_mascota->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql_mascota->execute();
    $mascota = $sql_mascota->fetch(PDO::FETCH_OBJ);
    if (!$mascota) {
        header("Location: gestion_citas_confirmadas.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener información de la mascota: " . $e->getMessage());
    header("Location: gestion_citas_confirmadas.php");
    exit();
}

// Obtener datos del historial clinico
try {
    $sql_historial_clinico = $conexion->prepare("SELECT peso,sintomas,diagnostico,tratamiento,observaciones FROM historial_clinico WHERE id = :id_historial_clinico");
    $sql_historial_clinico->bindParam(":id_historial_clinico", $id_historial_clinico, PDO::PARAM_INT);
    $sql_historial_clinico->execute();
    $historial_clinico = $sql_historial_clinico->fetch(PDO::FETCH_OBJ);
    if (!$historial_clinico) {
        header("Location: historial_clinico_mascota.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)));
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener información del historial clinico: " . $e->getMessage());
    header("Location: historial_clinico_mascota.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)));
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/veterinario/editar_historial_clinico.css">

<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-edit"></i>
                Editar Historial Clínico
            </h2>
            <p>Modifique la información del registro clínico de la mascota</p>
        </div>
        <div class="form-body">
            <!-- Alerta informativa -->
            <div class="info-alert">
                <i class="fas fa-info-circle"></i>
                <span>Los campos marcados con <strong style="color:#e74c3c;">*</strong> son obligatorios.</span>
            </div>

            <!-- Información de la mascota -->
            <div class="mascota-info" id="mascotaInfo">
                <div class="mascota-avatar" id="mascotaAvatar">
                    <i class="fas fa-dog"></i>
                </div>
                <div class="mascota-datos">
                    <h3 id="mascotaNombre"><?php echo htmlspecialchars(ucfirst($mascota->nombre_mascota)) ?></h3>
                    <p><i class="fas fa-paw"></i> <span id="mascotaEspecie"><?php echo htmlspecialchars(ucfirst($mascota->especie)) ?></span> | <i class="fas fa-dna"></i> <span id="mascotaRaza"><?php echo htmlspecialchars(ucfirst($mascota->raza)) ?></span> | <i class="fas fa-venus-mars"></i> <span id="mascotaSexo"><?php echo htmlspecialchars(ucfirst($mascota->sexo)) ?></span></p>
                    <p><i class="fas fa-user"></i> Propietario: <strong id="clienteNombre"><?php echo htmlspecialchars(ucfirst($mascota->nombre_cliente)) ?></strong> | <i class="fas fa-id-card"></i> <span id="clienteCedula"><?php echo htmlspecialchars($mascota->cedula) ?></span></p>
                </div>
            </div>

            <form id="editarHistorialForm">
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
                <input type="hidden" name="id_cita" value="<?= urlencode(Crypto::encrypt($id_cita)) ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_historial_clinico" value="<?= urlencode(Crypto::encrypt($id_historial_clinico)) ?>">
                <div class="form-group">
                    <label><i class="fas fa-weight-scale"></i> Peso (kg)</label>
                    <div class="input-with-icon">
                        <i class="fas fa-weight-scale"></i>
                        <input type="number" id="peso" name="peso" value="<?php echo htmlspecialchars($historial_clinico->peso); ?>" min="0" required step="0.1" placeholder="Ej: 24.5">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-head-side-medical"></i> Síntomas</label>
                    <textarea id="sintomas" name="sintomas" rows="3" placeholder="Describa los síntomas presentados por el paciente..."><?php echo htmlspecialchars($historial_clinico->sintomas); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="required"><i class="fas fa-stethoscope"></i> Diagnóstico</label>
                    <textarea id="diagnostico" name="diagnostico" rows="3" placeholder="Diagnóstico principal de la consulta..." required><?php echo htmlspecialchars($historial_clinico->diagnostico); ?></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-prescription-bottle"></i> Tratamiento</label>
                    <textarea id="tratamiento" name="tratamiento" rows="3" placeholder="Tratamiento indicado (medicamentos, dosis, duración)..."><?php echo htmlspecialchars($historial_clinico->tratamiento); ?></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-comment-dots"></i> Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="2" placeholder="Observaciones adicionales, recomendaciones, seguimiento..."><?php echo htmlspecialchars($historial_clinico->observaciones); ?></textarea>
                </div>

                <!-- Vista previa -->
                <div class="preview-card" id="previewCard">
                    <h4><i class="fas fa-eye"></i> Vista Previa del Registro</h4>
                    <div class="preview-content">
                        <div class="preview-item">
                            <div class="preview-label">Fecha</div>
                            <div class="preview-value" id="previewFecha">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Peso</div>
                            <div class="preview-value" id="previewPeso">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Diagnóstico</div>
                            <div class="preview-value" id="previewDiagnostico">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Tratamiento</div>
                            <div class="preview-value" id="previewTratamiento">—</div>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='historial-clinico.html'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Información adicional -->
    <div style="margin-top: 20px; text-align: center; background: white; border-radius: 20px; padding: 15px; border: 1px solid #eef2f8;">
        <i class="fas fa-history" style="color: #6bcb77;"></i>
        <span style="font-size: 0.8rem; color: #5f7f8f;"> Los cambios se guardarán en el historial clínico de la mascota</span>
    </div>
</div>
<script src="../app/js/veterinario/editar_historial_clinico.js"></script>
<?php include_once '../templates/footer.php'; ?>