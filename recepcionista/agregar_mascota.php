<?php
require_once '../autorizacion/auth.php';
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
if (!$id_cliente || $id_cliente <= 0 || !is_numeric($id_cliente)) {
    header('Location: gestion_clientes.php');
    exit();
}


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/agregar_mascota.css">
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-paw"></i>
                Registrar Nueva Mascota
            </h2>
            <p>Complete la información para agregar una nueva mascota al sistema de la clínica veterinaria</p>
        </div>
        <div class="form-body">
            <!-- Alerta informativa -->
            <div class="info-alert">
                <i class="fas fa-info-circle"></i>
                <span>Los campos marcados con <strong style="color:#e74c3c;">*</strong> son obligatorios. El ID se generará automáticamente.</span>
            </div>

            <form id="registroMascotaForm" method="POST" action="../controladores/recepcionista/agregar_mascota.php" enctype="multipart/form-data">
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
                            placeholder="Ej: Max, Luna, Rocky"
                            required autofocus>
                    </div>
                </div>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_cliente" value="<?= htmlspecialchars(urlencode(Crypto::encrypt($id_cliente))) ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="required"><i class="fas fa-dog"></i> Especie</label>
                        <div class="input-with-icon">
                            <i class="fas fa-paw"></i>
                            <select id="especieMascota" name="especie" required>
                                <option value="" disabled selected>-- Seleccione una especie --</option>
                                <option value="perro">🐕 Perro</option>
                                <option value="gato">🐈 Gato</option>
                                <option value="ave">🦜 Ave</option>
                                <option value="roedor">🐹 Roedor</option>
                                <option value="otro">🐾 Otro</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-dna"></i> Raza</label>
                        <div class="input-with-icon">
                            <i class="fas fa-tag"></i>
                            <input type="text" id="razaMascota" name="raza"
                                placeholder="Ej: Golden Retriever, Siamés">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Fecha de Nacimiento</label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar"></i>
                            <input type="date" id="fechaNacimiento" name="fecha_nacimiento">
                        </div>
                        <small style="color: #7f8c8d; font-size: 0.7rem;">La edad se calculará automáticamente</small>
                    </div>
                    <div class="form-group">
                        <label class="required"><i class="fas fa-venus-mars"></i> Sexo</label>
                        <div class="sexo-group">
                            <label class="sexo-option" id="sexoMachoLabel">
                                <input type="radio" name="sexo" value="M" required>
                                <i class="fas fa-mars"></i> Macho
                            </label>
                            <label class="sexo-option" id="sexoHembraLabel">
                                <input type="radio" name="sexo" value="F">
                                <i class="fas fa-venus"></i> Hembra
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-camera"></i> Foto de la Mascota</label>
                    <div class="input-with-icon">
                        <input type="file" id="fotoMascota" name="foto" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <small style="color: #7f8c8d; font-size: 0.7rem;">Formats: JPG, PNG, GIF (max 2MB)</small>
                    <div id="imagePreview" style="margin-top: 15px; display: none;">
                        <img id="previewImg" src="" alt="Preview" style="max-width: 150px; max-height: 150px; border-radius: 20px; object-fit: cover;">
                    </div>
                </div>

                <!-- Vista previa en tiempo real -->
                <div class="preview-card" id="previewCard">
                    <h4><i class="fas fa-eye"></i> Vista Previa de la Mascota</h4>
                    <div class="preview-content">
                        <div class="preview-item" style="text-align: center;">
                            <div class="preview-label">Foto</div>
                            <div id="previewFotoContainer" style="margin-top: 10px;">
                                <div class="preview-foto-placeholder"><i class="fas fa-camera"></i></div>
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
                            <div class="preview-label">Fecha de Nacimiento</div>
                            <div class="preview-value" id="previewEdad">—</div>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Registrar Mascota
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='gestion-mascotas.html?clienteId=' + obtenerIdCliente()">
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
        <i class="fas fa-heart" style="color: #e74c3c;"></i>
        <span style="font-size: 0.8rem; color: #5f7f8f;"> La mascota quedará asociada automáticamente al cliente seleccionado</span>
    </div>
</div>

<script src="../app/js/recepcionista/agregar_mascota.js"></script>
<?php include_once '../templates/footer.php'; ?>