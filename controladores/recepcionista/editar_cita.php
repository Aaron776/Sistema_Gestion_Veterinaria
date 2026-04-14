<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// Verificación de Rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cita'])) {
    
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    $id_cita_enc = $_POST['id_cita'];
    $id_cita = Crypto::decrypt(urldecode($id_cita_enc));
    
    if (empty($id_cita) || !is_numeric($id_cita)) {
        header("Location: ../../recepcionista/gestion_citas.php");
        exit;
    }

    // Recibir datos generales
    $id_veterinario = trim($_POST['id_veterinario']);
    $id_mascota = trim($_POST['id_mascota']);
    $fecha_hora = str_replace('T', ' ', trim($_POST['fecha_hora']));
    $motivo = trim($_POST['motivo']);

    // Recibir matrices de servicios
    $id_servicio_arr = isset($_POST['id_servicio']) && is_array($_POST['id_servicio']) ? $_POST['id_servicio'] : [];
    $cantidad_arr = isset($_POST['cantidad']) && is_array($_POST['cantidad']) ? $_POST['cantidad'] : [];
    $precio_arr = isset($_POST['precio']) && is_array($_POST['precio']) ? $_POST['precio'] : [];
    
    $errores = [];

    // Validacion General
    if(empty($id_veterinario)){
        $errores[]="El veterinario es requerido";
    }elseif(!ctype_digit($id_veterinario)){
        $errores[]="El id del veterinario debe ser número";
    }

    if(empty($id_mascota)){
        $errores[]="La mascota es requerida";
    }elseif(!ctype_digit($id_mascota)){
        $errores[]="El id de la mascota debe ser número";
    }

    if(empty($fecha_hora)){
        $errores[]="La fecha y hora son requeridas";
    }

    if(empty($motivo)){
        $errores[]="El motivo es requerido";
    }elseif(strlen($motivo)>255){
        $errores[]="El motivo no debe exceder los 255 caracteres";
    }

    // Validación de Servicios Dinámicos
    if(empty($id_servicio_arr)){
        $errores[]="Debe seleccionar al menos un servicio.";
    } else {
        foreach ($id_servicio_arr as $index => $id_srv) {
            $ctd = isset($cantidad_arr[$index]) ? trim($cantidad_arr[$index]) : '';
            $prc = isset($precio_arr[$index]) ? trim($precio_arr[$index]) : '';

            if(empty($id_srv)){
                $errores[]="El id del servicio en la fila ".($index+1)." es requerido";
            }elseif(!ctype_digit(strval($id_srv))){
                $errores[]="El id del servicio en fila ".($index+1)." es inválido";
            }

            if(empty($ctd)){
                $errores[]="La cantidad en la fila ".($index+1)." es requerida";
            }elseif(!ctype_digit(strval($ctd)) || $ctd <= 0){
                $errores[]="La cantidad en la fila ".($index+1)." debe ser un número entero mayor a cero";
            }

            if(empty($prc)){
                $errores[]="El precio en la fila ".($index+1)." es requerido";
            }elseif(!is_numeric($prc) || $prc <= 0){
                $errores[]="El precio en la fila ".($index+1)." debe ser válido y mayor a cero";
            }
        }
    }

    // Primero verificamos que la cita siga en un estado modificable
    try {
        $sql=$conexion->prepare("SELECT id FROM citas WHERE id=:id_cita AND estado='pendiente' LIMIT 1");
        $sql->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
        $sql->execute();
        $esta_pendiente=$sql->fetch(PDO::FETCH_OBJ);

        if(!$esta_pendiente){
            $errores[]="No se puede editar esta cita porque ya fue procesada, confirmada o borrada.";
        }
    } catch(PDOException $e) {
        $errores[]="Error al verificar la disponibilidad de la cita.";
    }

    // Ejecutar Transacción Si Todo Está Correcto
    if(empty($errores)){
        try{
            $conexion->beginTransaction();

            // 1. UPDATE DE TABLA PRINCIPAL `citas`
            $sql_cita = $conexion->prepare("UPDATE citas SET mascota_id = :id_mascota, veterinario_id = :id_veterinario, fecha_hora = :fecha_hora, motivo = :motivo WHERE id = :id_cita");
            $sql_cita->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
            $sql_cita->bindParam(":id_veterinario", $id_veterinario, PDO::PARAM_INT);
            $sql_cita->bindParam(":fecha_hora", $fecha_hora, PDO::PARAM_STR);
            $sql_cita->bindParam(":motivo", $motivo, PDO::PARAM_STR);
            $sql_cita->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
            $sql_cita->execute();

            // 2. DESTRUIR SERVICIOS ANTIGUOS
            $sql_delete = $conexion->prepare("DELETE FROM cita_servicios WHERE cita_id = :id_cita");
            $sql_delete->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
            $sql_delete->execute();

            // 3. RECONSTRUIR LOS NUEVOS SERVICIOS SELECCIONADOS
            $sql_insert = $conexion->prepare("INSERT INTO cita_servicios (cita_id, servicio_id, cantidad, precio) VALUES (:cita_id, :servicio_id, :cantidad, :precio)");
            
            foreach($id_servicio_arr as $index => $id_srv) {
                $ctd = trim($cantidad_arr[$index]);
                $prc = trim($precio_arr[$index]);
                
                $sql_insert->bindParam(":cita_id", $id_cita, PDO::PARAM_INT);
                $sql_insert->bindParam(":servicio_id", $id_srv, PDO::PARAM_INT);
                $sql_insert->bindParam(":cantidad", $ctd, PDO::PARAM_INT);
                $sql_insert->bindParam(":precio", $prc, PDO::PARAM_STR);
                $sql_insert->execute();
            }

            // 4. GENERAR NOTIFICACION DE ACTUALIZACIÓN
            $sql_mascota = $conexion->prepare("SELECT nombre FROM mascotas WHERE id=:id_mascota LIMIT 1");
            $sql_mascota->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
            $sql_mascota->execute();
            $mascota_db = $sql_mascota->fetchColumn();
            $nombre_mascota = $mascota_db ? ucfirst($mascota_db) : "Desconocida";

            $titulo_notif = "Actualización de Cita";
            $mensaje_notif = "Los detalles, fecha u hora de tu cita con el paciente '" . $nombre_mascota . "' han sido modificados por Recepción.";
            $tipo_notif = "cita";

            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) VALUES (:uid, :tit, :msg, :tipo)");
            $sql_notif->bindParam(":uid", $id_veterinario, PDO::PARAM_INT);
            $sql_notif->bindParam(":tit", $titulo_notif, PDO::PARAM_STR);
            $sql_notif->bindParam(":msg", $mensaje_notif, PDO::PARAM_STR);
            $sql_notif->bindParam(":tipo", $tipo_notif, PDO::PARAM_STR);
            $sql_notif->execute();

            $conexion->commit();

            $_SESSION['exito']="Cita actualizada exitosamente";
            header("Location: ../../recepcionista/gestion_citas.php");
            exit;

        } catch(PDOException $e) {
            $conexion->rollBack();
            error_log("Error al editar la cita: " . $e->getMessage());
            $errores[]="Ocurrió un error en la base de datos al intentar guardar. Contacte al administrador.";
            
            $_SESSION['errores'] = $errores;
            header("Location: ../../recepcionista/editar_cita.php?id_cita=".urlencode($id_cita_enc));
            exit;
        }
    } else {
        // Redirigir de regreso de manera manual con los errores persistidos
        $_SESSION['errores'] = $errores;
        header("Location: ../../recepcionista/editar_cita.php?id_cita=".urlencode($id_cita_enc));
        exit;
    }
} else {
    header("Location: ../../recepcionista/gestion_citas.php");
    exit;
}
