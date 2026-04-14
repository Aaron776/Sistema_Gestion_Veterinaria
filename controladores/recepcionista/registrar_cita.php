<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo recepcionista puede agregar clientes)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_veterinario']) && isset($_POST['id_mascota']) && isset($_POST['fecha_hora']) && isset($_POST['motivo']) && isset($_POST['id_servicio']) && isset($_POST['cantidad']) && isset($_POST['precio']) && isset($_POST['id_recepcionista'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_veterinario = trim($_POST['id_veterinario']);
    $id_recepcionista = urldecode(Crypto::decrypt(trim($_POST['id_recepcionista'])));
    $id_mascota = trim($_POST['id_mascota']);
    $fecha_hora = str_replace('T', ' ', trim($_POST['fecha_hora']));
    $motivo = trim($_POST['motivo']);
    $id_servicio_arr = isset($_POST['id_servicio']) && is_array($_POST['id_servicio']) ? $_POST['id_servicio'] : [];
    $cantidad_arr = isset($_POST['cantidad']) && is_array($_POST['cantidad']) ? $_POST['cantidad'] : [];
    $precio_arr = isset($_POST['precio']) && is_array($_POST['precio']) ? $_POST['precio'] : [];
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_veterinario)){
        $errores[]="El id del veterinario es requerido";
    }elseif(!ctype_digit($id_veterinario)){
        $errores[]="El id del veterinario debe contener solo números";
    }elseif($id_veterinario<=0){
        $errores[]= "El id del veterinario debe ser mayor a 0";
    }

    if(empty($id_recepcionista)){
        $errores[]="El id del recepcionista es requerido";
    }elseif(!ctype_digit($id_recepcionista)){
        $errores[]="El id del recepcionista debe contener solo números";
    }elseif($id_recepcionista<=0){
        $errores[]= "El id del recepcionista debe ser mayor a 0";
    }

    if(empty($id_mascota)){
        $errores[]="El id de la mascota es requerido";
    }elseif(!ctype_digit($id_mascota)){
        $errores[]="El id de la mascota debe contener solo números";
    }elseif($id_mascota<=0){
        $errores[]= "El id de la mascota debe ser mayor a 0";
    }

    if(empty($id_servicio_arr)){
        $errores[]="Debe seleccionar al menos un servicio.";
    } else {
        foreach ($id_servicio_arr as $index => $id_srv) {
            $ctd = isset($cantidad_arr[$index]) ? trim($cantidad_arr[$index]) : '';
            $prc = isset($precio_arr[$index]) ? trim($precio_arr[$index]) : '';

            if(empty($id_srv)){
                $errores[]="El id del servicio en la fila ".($index+1)." es requerido";
            }elseif(!ctype_digit(strval($id_srv))){
                $errores[]="El id del servicio en la fila ".($index+1)." debe contener solo números";
            }elseif($id_srv<=0){
                $errores[]= "El id del servicio en la fila ".($index+1)." debe ser mayor a 0";
            }

            if(empty($ctd)){
                $errores[]="La cantidad en la fila ".($index+1)." es requerida";
            }elseif(!ctype_digit(strval($ctd))){
                $errores[]="La cantidad en la fila ".($index+1)." debe contener solo números";
            }elseif($ctd<=0){
                $errores[]= "La cantidad en la fila ".($index+1)." debe ser mayor a 0";
            }

            if(empty($prc)){
                $errores[]="El precio en la fila ".($index+1)." es requerido";
            }elseif(!is_numeric($prc)){
                $errores[]="El precio en la fila ".($index+1)." debe ser un formato de moneda válido";
            }elseif($prc<=0){
                $errores[]= "El precio en la fila ".($index+1)." debe ser mayor a 0";
            }
        }
    }

    if(empty($fecha_hora)){
        $errores[]="La fecha y hora es requerida";
    }

    if(empty($motivo)){
        $errores[]="El motivo es requerido";
    }elseif(strlen($motivo)<3){
        $errores[]="El motivo debe tener al menos 3 caracteres";
    }elseif(strlen($motivo)>255){
        $errores[]="El motivo no debe exceder los 255 caracteres";
    }



    // Si no hay errores, registrar la cita
    if(empty($errores)){
        try{
            $conexion->beginTransaction();

            // Insertar la cita
            $sql=$conexion->prepare("INSERT INTO citas (mascota_id, veterinario_id, recepcionista_id, fecha_hora, motivo) VALUES (:id_mascota, :id_veterinario, :id_recepcionista, :fecha_hora, :motivo)");
            $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
            $sql->bindParam(":id_veterinario", $id_veterinario, PDO::PARAM_INT);
            $sql->bindParam(":id_recepcionista", $id_recepcionista, PDO::PARAM_INT);
            $sql->bindParam(":fecha_hora", $fecha_hora, PDO::PARAM_STR);
            $sql->bindParam(":motivo", $motivo, PDO::PARAM_STR);
            $sql->execute();

            // Obtener id de la ultima cita registrada
            $id_cita = $conexion->lastInsertId();

            // Registrar los servicios de la cita iterativamente
            $sql_servicio = $conexion->prepare("INSERT INTO cita_servicios (cita_id, servicio_id, cantidad, precio) VALUES (:cita_id, :servicio_id, :cantidad, :precio)");
            foreach($id_servicio_arr as $index => $id_srv) {
                $ctd = trim($cantidad_arr[$index]);
                $prc = trim($precio_arr[$index]);
                
                $sql_servicio->bindParam(":cita_id", $id_cita, PDO::PARAM_INT);
                $sql_servicio->bindParam(":servicio_id", $id_srv, PDO::PARAM_INT);
                $sql_servicio->bindParam(":cantidad", $ctd, PDO::PARAM_INT);
                $sql_servicio->bindParam(":precio", $prc, PDO::PARAM_STR);
                $sql_servicio->execute();
            }

            // Insertar Notificacion
            // Extraer el nombre de la mascota para un mejor mensaje
            $sql_mascota = $conexion->prepare("SELECT nombre FROM mascotas WHERE id=:id_mascota");
            $sql_mascota->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
            $sql_mascota->execute();
            $datos_mascota = $sql_mascota->fetch(PDO::FETCH_OBJ);
            $nombre_mascota = $datos_mascota ? ucfirst($datos_mascota->nombre) : "Desconocida";

            $titulo="Nueva Cita";
            $mensaje="El recepcionista ".$_SESSION['nombre']." ha registrado una nueva cita para la mascota ".$nombre_mascota.".";
            $tipo="cita";
            $usuario_id=$id_veterinario;

            $sql=$conexion->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) VALUES (:usuario_id, :titulo, :mensaje, :tipo)");
            $sql->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
            $sql->bindParam(":titulo", $titulo, PDO::PARAM_STR);
            $sql->bindParam(":mensaje", $mensaje, PDO::PARAM_STR);
            $sql->bindParam(":tipo", $tipo, PDO::PARAM_STR);
            $sql->execute();

            $conexion->commit();

            $_SESSION['exito']="Cita registrada exitosamente";
            header("Location: ../../recepcionista/registrar_cita.php");
            exit;
        }catch(PDOException $e){
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error al registrar la cita: " . $e->getMessage());
            $errores[]="Error al registrar la cita";
        }

    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../recepcionista/registrar_cita.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al registrar la cita"];
    header("Location: ../../recepcionista/gestion_citas.php");
    exit;
}



?>