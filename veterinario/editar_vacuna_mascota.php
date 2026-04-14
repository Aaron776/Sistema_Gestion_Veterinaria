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
$id_mascota_vacuna = crypto::decrypt(urldecode($_GET['id_mascota_vacuna']));
if (!$id_mascota || $id_mascota <= 0 || !is_numeric($id_mascota)) {
    header('Location: gestion_clientes.php');
    exit();
}

if (!$id_mascota_vacuna || $id_mascota_vacuna <= 0 || !is_numeric($id_mascota_vacuna)) {
    header('Location: gestion_vacunacion_mascotas.php?id_mascota=' . urlencode(Crypto::encrypt($id_mascota)));
    exit();
}

// Obtener datos de la mascota y del cliente al que le pertenece
try {
    $sql = $conexion->prepare("SELECT m.nombre as nombre_mascota,m.especie as especie,m.raza as raza,m.sexo as sexo,m.fecha_nacimiento as fecha_nacimiento,c.nombre as cliente_nombre,c.cedula as cliente_cedula,c.telefono as cliente_telefono FROM mascotas m JOIN clientes c ON m.cliente_id = c.id WHERE m.id = :id_mascota");
    $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql->execute();
    $mascota = $sql->fetch(PDO::FETCH_OBJ);
    if (!$mascota) {
        header('Location: gestion_vacunacion_mascotas.php?id_mascota=' . urlencode(Crypto::encrypt($id_mascota)));
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener la mascota: " . $e->getMessage());
    header('Location: gestion_citas_confirmadas.php');
    exit();
}

// Obtener registro de la tabla mascota_vacunas
try {
    $sql = $conexion->prepare("SELECT id as id_mascota_vacuna,proxima_dosis,vacuna_id,fecha_aplicacion FROM mascota_vacunas WHERE id = :id_mascota_vacuna");
    $sql->bindParam(":id_mascota_vacuna", $id_mascota_vacuna, PDO::PARAM_INT);
    $sql->execute();
    $mascota_vacuna = $sql->fetch(PDO::FETCH_OBJ);
    if (!$mascota_vacuna) {
        header('Location: gestion_vacunacion_mascotas.php?id_mascota=' . urlencode(Crypto::encrypt($id_mascota)));
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener la mascota_vacuna: " . $e->getMessage());
    header('Location: gestion_vacunacion_mascotas.php?id_mascota=' . urlencode(Crypto::encrypt($id_mascota)));
    exit();
}

// Obtener listado de vacunas
try {
    $sql = $conexion->prepare("SELECT id as id_vacuna, nombre FROM vacunas");
    $sql->execute();
    $listado_vacunas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener las vacunas: " . $e->getMessage());
    header('Location: gestion_vacunacion_mascotas.php?id_mascota=' . urlencode(Crypto::encrypt($id_mascota)));
    exit();
}


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/veterinario/editar_vacuna_mascota.css">
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-edit"></i>
                Editar Vacuna
            </h2>
            <p>Modifique la información de la vacuna registrada</p>
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
                    <h3 id="mascotaNombre"><?php echo htmlspecialchars(ucfirst($mascota->nombre_mascota)); ?></h3>
                    <p><i class="fas fa-paw"></i> <span id="mascotaEspecie"><?php echo htmlspecialchars(ucfirst($mascota->especie)); ?></span> | <i class="fas fa-dna"></i> <span id="mascotaRaza"><?php echo htmlspecialchars(ucfirst($mascota->raza)); ?></span> | <i class="fas fa-venus-mars"></i> <span id="mascotaSexo"><?php echo htmlspecialchars(ucfirst($mascota->sexo)); ?></span></p>
                    <p><i class="fas fa-user"></i> Propietario: <strong id="clienteNombre"><?php echo htmlspecialchars(ucfirst($mascota->cliente_nombre)); ?></strong> | <i class="fas fa-id-card"></i> <span id="clienteCedula"><?php echo htmlspecialchars($mascota->cliente_cedula); ?></span></p>
                </div>
            </div>

            <form id="editarVacunaForm" action="../controladores/veterinario/editar_vacuna_mascota.php" method="POST">
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
                <input type="hidden" id="id_mascota" name="id_mascota" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_mascota))); ?>">
                <input type="hidden" id="id_mascota_vacuna" name="id_mascota_vacuna" value="<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_mascota_vacuna))); ?>">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <!-- Formulario -->
                <div class="form-group">
                    <label class="required"><i class="fas fa-medicine"></i> Vacuna</label>
                    <div class="input-with-icon">
                        <i class="fas fa-syringe"></i>
                        <select id="vacuna" name="id_vacuna" required>
                            <option value="" disabled>-- Seleccione una vacuna --</option>
                            <?php foreach ($listado_vacunas as $item) : ?>
                                <option value="<?php echo htmlspecialchars($item->id_vacuna); ?>" <?php if ($item->id_vacuna == $mascota_vacuna->vacuna_id) {
                                                                                                        echo 'selected';
                                                                                                    } ?>><?php echo htmlspecialchars(ucfirst($item->nombre)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required"><i class="fas fa-calendar-check"></i> Fecha de Aplicación</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar"></i>
                        <input type="date" id="fechaAplicacion" name="fecha_aplicacion" value="<?php echo htmlspecialchars($mascota_vacuna->fecha_aplicacion); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Próxima Dosis</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-plus"></i>
                        <input type="date" id="proximaDosis" name="proxima_dosis" value="<?php echo htmlspecialchars($mascota_vacuna->proxima_dosis); ?>">
                    </div>
                    <small style="color: #7f8c8d; font-size: 0.7rem;">Dejar en blanco si es la última dosis o no aplica</small>
                </div>

                <!-- Vista previa -->
                <div class="preview-card" id="previewCard">
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
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='gestion_vacunacion_mascotas.php?id_mascota=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_mascota))); ?>'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Información adicional -->
    <div style="margin-top: 20px; text-align: center; background: white; border-radius: 20px; padding: 15px; border: 1px solid #eef2f8;">
        <i class="fas fa-clock" style="color: #f39c12;"></i>
        <span style="font-size: 0.8rem; color: #5f7f8f;"> Los cambios se guardarán en el historial de vacunas de la mascota</span>
    </div>
</div>

<script src="../app/js/veterinario/editar_vacuna_mascota.js"></script>
<?php include_once '../templates/footer.php'; ?>