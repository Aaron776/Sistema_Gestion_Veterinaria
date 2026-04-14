<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_cita_enc = isset($_GET['id_cita']) ? $_GET['id_cita'] : '';
if(empty($id_cita_enc)){
    header("Location: gestion_citas.php");
    exit();
}
$id_cita = Crypto::decrypt(urldecode($id_cita_enc));

if(empty($id_cita) || !is_numeric($id_cita) || $id_cita < 1) {
    header("Location: gestion_citas.php");
    exit();
}

// 1. Validar si ya está facturada
try {
    $sql_check = $conexion->prepare("SELECT id FROM facturas WHERE cita_id = :id_cita AND estado = 'pagada' LIMIT 1");
    $sql_check->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
    $sql_check->execute();
    if($sql_check->fetch(PDO::FETCH_OBJ)){
        $_SESSION['errores'] = ["Esta cita ya cuenta con una factura cerrada y pagada."];
        header("Location: gestion_citas.php");
        exit();
    }
} catch (PDOException $e) {}

// 2. Obtener datos cabecera de la cita + mascota + cliente
try {
    $query = "SELECT c.id as cita_id, c.estado, m.nombre as nombre_mascota, cli.id as cliente_id, cli.nombre as nombre_cliente, cli.cedula 
              FROM citas c 
              INNER JOIN mascotas m ON c.mascota_id = m.id 
              INNER JOIN clientes cli ON m.cliente_id = cli.id 
              WHERE c.id = :id_cita";
    $sql = $conexion->prepare($query);
    $sql->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
    $sql->execute();
    $cita = $sql->fetch(PDO::FETCH_OBJ);
    
    if(!$cita || $cita->estado !== 'realizada') {
        $_SESSION['errores'] = ["La cita no existe o aún no ha sido 'Realizada' por el veterinario para poder facturarse."];
        header("Location: gestion_citas.php");
        exit();
    }
} catch (PDOException $e) {
    header("Location: gestion_citas.php");
    exit();
}

// 3. Obtener los servicios obligatorios de la cita (lo que hizo el doc)
try {
    $sql_servs = $conexion->prepare("SELECT cs.servicio_id, s.nombre_servicio, cs.cantidad, cs.precio, (cs.cantidad * cs.precio) as subtotal 
                                     FROM cita_servicios cs 
                                     INNER JOIN servicios s ON cs.servicio_id = s.id 
                                     WHERE cs.cita_id = :id_cita");
    $sql_servs->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
    $sql_servs->execute();
    $servicios_medicos = $sql_servs->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $servicios_medicos = [];
}

// 4. Obtener Inventario Físico para compras extra de mostrador
try {
    $sql_inv = $conexion->prepare("SELECT id as producto_id, nombre_producto, precio, stock FROM inventario WHERE estado = 'activo' AND stock > 0");
    $sql_inv->execute();
    $productos = $sql_inv->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $productos = [];
}

include_once '../templates/header.php';
?>

<link rel="stylesheet" href="../app/css/recepcionista/factura.css">

<form id="posForm" action="../controladores/recepcionista/facturar_cita.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <input type="hidden" name="id_cita" value="<?php echo htmlspecialchars($id_cita_enc); ?>">
    <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($cita->cliente_id); ?>">
    
    <!-- Para almacenar dinámicamente arrays para el post -->
    <div id="hiddenInputsArea"></div>
    <input type="hidden" name="metodo_pago" id="metodoPagoInput" value="efectivo">
    <input type="hidden" name="monto_cobrado" id="montoCobradoInput" value="0">
    <input type="hidden" name="total_calculado" id="totalCalculadoInput" value="0">

    <?php if (isset($_SESSION['errores'])) : ?>
        <div class="alert alert-danger" style="margin: 20px 32px 0;">
            <ul>
                <?php foreach ($_SESSION['errores'] as $error) : ?>
                    <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errores']); ?>
    <?php endif; ?>

    <div class="pos-wrapper">
        <!-- IZQUIERDA: FACTURA DETALLE -->
        <div class="pos-left">
            <div class="pos-header">
                <h2><i class="fas fa-receipt"></i> Módulo de Facturación POS</h2>
                <p>Cierre Fiscal de Cita Médica #<?php echo htmlspecialchars($cita->cita_id); ?></p>
            </div>
            <div class="client-data">
                <div><strong>Cliente:</strong> <?php echo htmlspecialchars($cita->nombre_cliente); ?></div>
                <div><strong>CI/RUC:</strong> <?php echo htmlspecialchars($cita->cedula); ?></div>
                <div><strong>Paciente:</strong> <?php echo htmlspecialchars($cita->nombre_mascota); ?></div>
            </div>

            <div class="product-list" id="ticketItems">
                <!-- Servicios Médicos (Cargados de Base - Bloqueados) -->
                <?php foreach($servicios_medicos as $sm): ?>
                    <div class="ticket-row locked locked-item" data-type="servicio" data-id="<?php echo $sm->servicio_id; ?>" data-price="<?php echo $sm->precio; ?>">
                        <div>
                            <div class="item-name"><i class="fas fa-stethoscope" style="color:#3498db;"></i> <?php echo htmlspecialchars($sm->nombre_servicio); ?></div>
                            <span class="item-meta">Servicio Clínico Obligatorio</span>
                        </div>
                        <div class="item-qty">
                            <input type="number" value="<?php echo htmlspecialchars($sm->cantidad); ?>" readonly style="background:#e2e8f0; border-color:#cbd5e1; color:#64748b;">
                        </div>
                        <div class="item-price">$<?php echo htmlspecialchars($sm->precio); ?></div>
                        <div class="item-subtotal">$<?php echo htmlspecialchars($sm->subtotal); ?></div>
                    </div>
                <?php endforeach; ?>

                <!-- Artículos dinámicos irán apareciendo aquí -> #ticketItems -->
            </div>

            <!-- Adicionales Mostrador -->
            <div class="addon-section">
                <span style="font-size:0.8rem; font-weight:600; color:#1f3e4b; display:block; margin-bottom:8px;">
                    <i class="fas fa-shopping-basket"></i> Compra de Mostrador (Opcional):
                </span>
                <div class="addon-box">
                    <select id="productoSelect">
                        <option value="" disabled selected>Vender producto físico del inventario...</option>
                        <?php foreach($productos as $p): ?>
                            <option value="<?php echo htmlspecialchars($p->producto_id); ?>" 
                                    data-nombre="<?php echo htmlspecialchars($p->nombre_producto); ?>"
                                    data-precio="<?php echo htmlspecialchars($p->precio); ?>"
                                    data-stock="<?php echo htmlspecialchars($p->stock); ?>">
                                <?php echo htmlspecialchars($p->nombre_producto); ?> - $<?php echo htmlspecialchars($p->precio); ?> (Disp: <?php echo htmlspecialchars($p->stock); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn-add" id="btnAddProduct"><i class="fas fa-plus"></i> Añadir</button>
                </div>
            </div>
        </div>

        <!-- DERECHA: COBRO -->
        <div class="pos-right">
            <div class="total-card">
                <span>Total a Cobrar</span>
                <h1 id="granTotalView">$0.00</h1>
            </div>

            <div class="payment-card">
                <h3>Método de Pago</h3>
                <div class="method-grid">
                    <div class="method-option selected" data-method="efectivo">
                        <i class="fas fa-money-bill-alt"></i>
                        <span>Efectivo</span>
                    </div>
                    <div class="method-option" data-method="tarjeta">
                        <i class="fas fa-credit-card"></i>
                        <span>Tarjeta</span>
                    </div>
                    <div class="method-option" data-method="transferencia">
                        <i class="fas fa-mobile-alt"></i>
                        <span>Digital</span>
                    </div>
                </div>

                <div class="input-group" id="recibidoGroup">
                    <label>Monto Recibido ($)</label>
                    <input type="number" id="inputRecibido" step="0.01" min="0" placeholder="0.00">
                </div>

                <div class="change-box" id="cambioBox">
                    <span>Cambio a devolver:</span>
                    <strong id="cambioView">$0.00</strong>
                </div>

                <button type="button" class="btn-pay" id="btnProcesarPago">
                    <i class="fas fa-check-circle"></i> Confirmar Pago
                </button>
            </div>
            
            <div style="text-align:center;">
                <a href="gestion_citas.php" style="color:#64748b; text-decoration:none; font-size:0.9rem; font-weight:600;"><i class="fas fa-arrow-left"></i> Volver sin cerrar caja</a>
            </div>
        </div>
    </div>
</form>

<script src="../app/js/recepcionista/factura.js"></script>
<?php include_once '../templates/footer.php'; ?>
