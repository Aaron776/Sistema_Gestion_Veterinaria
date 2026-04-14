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

$id_cliente = crypto::decrypt(urldecode($_GET['id_cliente']));
$id_mascota = crypto::decrypt(urldecode($_GET['id_mascota']));
if (!$id_cliente || $id_cliente <= 0 || !is_numeric($id_cliente)) {
    header('Location: gestion_clientes.php');
    exit();
}

if (!$id_mascota || $id_mascota <= 0 || !is_numeric($id_mascota)) {
    header('Location: gestion_clientes.php');
    exit();
}



// Obtener datos de la mascota para editar que pertenezca al cliente
try {
    $sql = $conexion->prepare("SELECT nombre,especie,raza,sexo,fecha_nacimiento,foto FROM mascotas WHERE cliente_id = :id_cliente AND id = :id_mascota");
    $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
    $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
    $sql->execute();
    $mascota = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener el cliente: " . $e->getMessage());
    header('Location: gestion_clientes.php');
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/editar_mascota.css">
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-edit"></i>
                Editar Mascota
            </h2>
            <p>Actualice la información general de la mascota seleccionada</p>
        </div>
        <div class="form-body">
            <div class="info-alert">
                <i class="fas fa-info-circle"></i>
                <span>Los campos marcados con <strong style="color:#e74c3c;">*</strong> son obligatorios.</span>
            </div>

            <form id="editarMascotaForm" method="POST" action="../controladores/recepcionista/editar_mascota_cliente.php" enctype="multipart/form-data">
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

                <div class="form-group">
                    <label class="required"><i class="fas fa-tag"></i> Nombre de la Mascota</label>
                    <div class="input-with-icon">
                        <i class="fas fa-paw"></i>
                        <input type="text" id="nombreMascota" name="nombre" 
                               value="<?= htmlspecialchars($mascota->nombre) ?>" required autofocus>
                    </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_cliente" value="<?= htmlspecialchars(urlencode(Crypto::encrypt($id_cliente))) ?>">
                <input type="hidden" name="id_mascota" value="<?= htmlspecialchars(urlencode(Crypto::encrypt($id_mascota))) ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="required"><i class="fas fa-dog"></i> Especie</label>
                        <div class="input-with-icon">
                            <i class="fas fa-paw"></i>
                            <select id="especieMascota" name="especie" required>
                                <option value="" disabled>-- Seleccione una especie --</option>
                                <option value="perro" <?= $mascota->especie === 'perro' ? 'selected' : '' ?>>🐕 Perro</option>
                                <option value="gato" <?= $mascota->especie === 'gato' ? 'selected' : '' ?>>🐈 Gato</option>
                                <option value="ave" <?= $mascota->especie === 'ave' ? 'selected' : '' ?>>🦜 Ave</option>
                                <option value="roedor" <?= $mascota->especie === 'roedor' ? 'selected' : '' ?>>🐹 Roedor</option>
                                <option value="otro" <?= $mascota->especie === 'otro' ? 'selected' : '' ?>>🐾 Otro</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-dna"></i> Raza</label>
                        <div class="input-with-icon">
                            <i class="fas fa-tag"></i>
                            <input type="text" id="razaMascota" name="raza" value="<?= htmlspecialchars($mascota->raza) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Fecha de Nacimiento</label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar"></i>
                            <input type="date" id="fechaNacimiento" name="fecha_nacimiento" value="<?= htmlspecialchars($mascota->fecha_nacimiento) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-venus-mars"></i> Sexo</label>
                        <div class="sexo-group">
                            <label class="sexo-option <?= $mascota->sexo === 'M' ? 'selected' : '' ?>" id="sexoMachoLabel">
                                <input type="radio" name="sexo" value="M" <?= $mascota->sexo === 'M' ? 'checked' : '' ?> required>
                                <i class="fas fa-mars"></i> Macho
                            </label>
                            <label class="sexo-option <?= $mascota->sexo === 'F' ? 'selected' : '' ?>" id="sexoHembraLabel">
                                <input type="radio" name="sexo" value="F" <?= $mascota->sexo === 'F' ? 'checked' : '' ?>>
                                <i class="fas fa-venus"></i> Hembra
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-camera"></i> Actualizar Foto (Opcional)</label>
                    <div class="input-with-icon">
                        <input type="file" id="fotoMascota" name="foto" accept="image/jpeg, image/png, image/jpg" onchange="previewImage(this)">
                    </div>
                    <small style="color: #7f8c8d; font-size: 0.7rem;">Solo si desea cambiar la foto actual. (Máx. 2MB)</small>
                    
                    <div id="imagePreview" style="margin-top: 15px; <?= empty($mascota->foto) ? 'display: none;' : 'display: block;' ?>">
                        <img id="previewImg" src="<?= !empty($mascota->foto) ? '../app/img_mascotas/' . htmlspecialchars($mascota->foto) : '' ?>" style="width: 100px; height: 100px; border-radius: 20px; object-fit: cover; border: 2px solid #3498db;">
                    </div>
                </div>

                <!-- Vista previa en tiempo real -->
                <div class="preview-card" id="previewCard">
                    <h4><i class="fas fa-eye"></i> Vista Previa de Cambios</h4>
                    <div class="preview-content">
                        <div class="preview-item" style="text-align: center;">
                            <div class="preview-label">Foto</div>
                            <div id="previewFotoContainer" style="margin-top: 10px;">
                                <?php if (!empty($mascota->foto)): ?>
                                    <img src="../app/img_mascotas/<?= htmlspecialchars($mascota->foto) ?>" class="preview-foto" alt="Foto actual">
                                <?php else: ?>
                                    <div class="preview-foto-placeholder"><i class="fas fa-camera"></i></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Nombre</div>
                            <div class="preview-value" id="previewNombre">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Especie</div>
                            <div class="preview-value" id="previewEspecie">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Raza</div>
                            <div class="preview-value" id="previewRaza">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Sexo</div>
                            <div class="preview-value" id="previewSexo">—</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Nacimiento</div>
                            <div class="preview-value" id="previewEdad">—</div>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="gestion_mascotas_cliente.php?id_cliente=<?= urlencode(Crypto::encrypt($id_cliente)) ?>" class="btn-secondary" style="text-decoration:none;">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../app/js/recepcionista/editar_mascota.js"></script>
<?php include_once '../templates/footer.php'; ?>