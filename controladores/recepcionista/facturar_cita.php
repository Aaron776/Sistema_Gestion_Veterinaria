<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cita'])) {
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    $id_cita_enc = $_POST['id_cita'];
    $id_cita = Crypto::decrypt(urldecode($id_cita_enc));
    $id_cliente = trim($_POST['id_cliente']);
    
    if (empty($id_cita) || !is_numeric($id_cita) || empty($id_cliente) || !is_numeric($id_cliente)) {
        header("Location: ../../recepcionista/gestion_citas.php");
        exit;
    }

    $metodo_pago = trim($_POST['metodo_pago']); // efectivo, tarjeta, transferencia
    $monto_cobrado = trim($_POST['monto_cobrado']);

    // Arrays de Servicios
    $servicios_id = isset($_POST['servicios_id']) ? $_POST['servicios_id'] : [];
    $servicios_qty = isset($_POST['servicios_qty']) ? $_POST['servicios_qty'] : [];
    $servicios_prc = isset($_POST['servicios_prc']) ? $_POST['servicios_prc'] : [];

    // Arrays de Productos Físicos (Opcional)
    $productos_id = isset($_POST['productos_id']) ? $_POST['productos_id'] : [];
    $productos_qty = isset($_POST['productos_qty']) ? $_POST['productos_qty'] : [];
    $productos_prc = isset($_POST['productos_prc']) ? $_POST['productos_prc'] : [];

    $errores = [];

    if(!in_array($metodo_pago, ['efectivo', 'tarjeta', 'transferencia'])) {
        $errores[] = "Método de pago inválido.";
    }

    // 1er Bloque: Evitar Hackeo JS - Re-calculo matemático en el Backend
    $gran_total_backend = 0;
    
    // Validar Servicios
    foreach ($servicios_id as $index => $s_id) {
        if(!is_numeric($s_id) || empty($s_id)) { $errores[] = "Error en el ID del servicio."; break; }
        $qty = $servicios_qty[$index];
        $prc = $servicios_prc[$index];
        if(!is_numeric($qty) || $qty <= 0) { $errores[] = "Cantidad de servicio inyectada inválida."; break; }
        if(!is_numeric($prc) || $prc < 0) { $errores[] = "Precio de servicio inválido."; break; }
        $gran_total_backend += ($qty * $prc);
    }

    // Validar Productos
    foreach ($productos_id as $index => $p_id) {
        if(!is_numeric($p_id) || empty($p_id)) { $errores[] = "Error en el ID del producto ext."; break; }
        $qty = $productos_qty[$index];
        $prc = $productos_prc[$index];
        if(!is_numeric($qty) || $qty <= 0) { $errores[] = "Cantidad de producto inyectada inválida."; break; }
        if(!is_numeric($prc) || $prc < 0) { $errores[] = "Precio de producto inválido."; break; }
        $gran_total_backend += ($qty * $prc);
    }

    // Validación lógica de Efectivo
    if ($metodo_pago == 'efectivo' && (!is_numeric($monto_cobrado) || $monto_cobrado < $gran_total_backend)) {
        $errores[] = "El monto en efectivo entregado es menor al total real calculado ($".number_format($gran_total_backend, 2).").";
    }

    if(empty($errores)) {
        try {
            $conexion->beginTransaction();

            // 1. Inyectar FACTURA MASTER
            $sql_fac = $conexion->prepare("INSERT INTO facturas (cliente_id, cita_id, total, estado) VALUES (:cli, :cit, :tot, 'pagada')");
            $sql_fac->bindParam(":cli", $id_cliente, PDO::PARAM_INT);
            $sql_fac->bindParam(":cit", $id_cita, PDO::PARAM_INT);
            $sql_fac->bindParam(":tot", $gran_total_backend, PDO::PARAM_STR);
            $sql_fac->execute();
            
            $factura_id = $conexion->lastInsertId();

            // 2. Inyectar DETALLES DE SERVICIOS
            if(!empty($servicios_id)) {
                $sql_det_s = $conexion->prepare("INSERT INTO detalle_factura (factura_id, servicio_id, descripcion, cantidad, precio, subtotal) VALUES (:fid, :sid, 'Servicio Médico de Cita', :qty, :prc, :sub)");
                foreach ($servicios_id as $index => $s_id) {
                    $qty = $servicios_qty[$index];
                    $prc = $servicios_prc[$index];
                    $sub = $qty * $prc;
                    
                    $sql_det_s->bindParam(":fid", $factura_id, PDO::PARAM_INT);
                    $sql_det_s->bindParam(":sid", $s_id, PDO::PARAM_INT);
                    $sql_det_s->bindParam(":qty", $qty, PDO::PARAM_INT);
                    $sql_det_s->bindParam(":prc", $prc, PDO::PARAM_STR);
                    $sql_det_s->bindParam(":sub", $sub, PDO::PARAM_STR);
                    $sql_det_s->execute();
                }
            }

            // 3. Inyectar DETALLES DE PRODUCTOS Y REDUCIR INVENTARIO
            if(!empty($productos_id)) {
                $sql_det_p = $conexion->prepare("INSERT INTO detalle_factura (factura_id, producto_id, descripcion, cantidad, precio, subtotal) VALUES (:fid, :pid, 'Compra en Mostrador', :qty, :prc, :sub)");
                $sql_resta_inv = $conexion->prepare("UPDATE inventario SET stock = stock - :qty WHERE id = :pid AND stock >= :qty");

                foreach ($productos_id as $index => $p_id) {
                    $qty = $productos_qty[$index];
                    $prc = $productos_prc[$index];
                    $sub = $qty * $prc;
                    
                    // Comprobar y Restar Stock primero (Lock lógico)
                    $sql_resta_inv->bindParam(":qty", $qty, PDO::PARAM_INT);
                    $sql_resta_inv->bindParam(":pid", $p_id, PDO::PARAM_INT);
                    $sql_resta_inv->execute();
                    
                    if($sql_resta_inv->rowCount() === 0) {
                        // Significa que alguien vendió esto antes o ya no hay inventario
                        throw new PDOException("El producto con ID #$p_id ya no cuenta con stock suficiente. Operación Interrumpida.");
                    }

                    // Si pasó la barrera, registrar en detalles
                    $sql_det_p->bindParam(":fid", $factura_id, PDO::PARAM_INT);
                    $sql_det_p->bindParam(":pid", $p_id, PDO::PARAM_INT);
                    $sql_det_p->bindParam(":qty", $qty, PDO::PARAM_INT);
                    $sql_det_p->bindParam(":prc", $prc, PDO::PARAM_STR);
                    $sql_det_p->bindParam(":sub", $sub, PDO::PARAM_STR);
                    $sql_det_p->execute();
                }
            }

            // 4. Registrar en la tabla de PAGOS Contables
            $monto_final_pago = ($metodo_pago === 'efectivo') ? $gran_total_backend : $monto_cobrado; // en tarjeta o transf el monto suele ser exacto
            $sql_pago = $conexion->prepare("INSERT INTO pagos (factura_id, monto, metodo_pago) VALUES (:fid, :mon, :met)");
            $sql_pago->bindParam(":fid", $factura_id, PDO::PARAM_INT);
            $sql_pago->bindParam(":mon", $monto_final_pago, PDO::PARAM_STR);
            $sql_pago->bindParam(":met", $metodo_pago, PDO::PARAM_STR);
            $sql_pago->execute();

            // 5. REGISTRAR NOTIFICACIÓN EN EL SISTEMA (Para Administradores)
            $sql_admins = $conexion->query("SELECT id FROM usuarios WHERE rol = 'admin' AND estado = 'activo'");
            $admins = $sql_admins->fetchAll(PDO::FETCH_OBJ);
            
            if ($admins) {
                $titulo_not = "Nuevo Ingreso Registrado";
                $mensaje_not = "Se ha cobrado la Factura #$factura_id vinculada a la Cita #$id_cita por un total de $" . number_format($gran_total_backend, 2) . " ($metodo_pago).";
                
                $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) VALUES (:uid, :tit, :msg, 'pago')");
                
                foreach ($admins as $admin) {
                    $sql_notif->bindParam(":uid", $admin->id, PDO::PARAM_INT);
                    $sql_notif->bindParam(":tit", $titulo_not, PDO::PARAM_STR);
                    $sql_notif->bindParam(":msg", $mensaje_not, PDO::PARAM_STR);
                    $sql_notif->execute();
                }
            }

            $conexion->commit();

            $_SESSION['exito'] = "Factura #$factura_id cobrada exitosamente bajo $metodo_pago. Inventario reajustado.";
            header("Location: ../../recepcionista/gestion_citas.php");
            exit;

        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al procesar el pago: " . $e->getMessage());
            $errores[] = "Error Transaccional de BD: " . $e->getMessage();
            $_SESSION['errores'] = $errores;
            header("Location: ../../recepcionista/facturar_cita.php?id_cita=".urlencode($id_cita_enc));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../recepcionista/facturar_cita.php?id_cita=".urlencode($id_cita_enc));
        exit;
    }
} else {
    header("Location: ../../recepcionista/gestion_citas.php");
    exit;
}
