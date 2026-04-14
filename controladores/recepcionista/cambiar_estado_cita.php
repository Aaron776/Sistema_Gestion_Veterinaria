<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

 // 1. Verificación de Rol 
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cita']) && isset($_POST['estado'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_cita = Crypto::decrypt(urldecode($_POST['id_cita']));
    $estado = trim($_POST['estado']);
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_cita)){
        $errores[]="El id de la cita es requerido";
    }elseif(!ctype_digit($id_cita)){
        $errores[]="El id de la cita debe ser un número";
    }elseif($id_cita<=0){
        $errores[]="El id de la cita debe ser mayor a 0";
    }

    if(empty($estado)){
        $errores[]="El estado de la cita es requerido";
    }elseif(!in_array($estado, ['confirmada', 'cancelada'])){
        $errores[]="El estado de la cita no es valido";
    }

    // Verificar si la cita que se quiere cambiar el estado existe en la base de datos y esta en estado pendiente
    try{
        $sql=$conexion->prepare("SELECT id, veterinario_id, mascota_id FROM citas WHERE id=:id_cita AND estado='pendiente' LIMIT 1");
        $sql->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
        $sql->execute();
        $cita_existe=$sql->fetch(PDO::FETCH_OBJ);

        if(!$cita_existe){
            $errores[]="La cita que desea cambiar el estado no existe";
        }
    }catch(PDOException $e){
        error_log("Error al verificar la cita: " . $e->getMessage());
        $errores[]="Error al verificar la cita";
    }

    // Si no hay errores, cambiar el estado de la cita
    if(empty($errores)){
        try{
            $conexion->beginTransaction();

            $sql=$conexion->prepare("UPDATE citas SET estado=:estado WHERE id=:id_cita AND estado='pendiente'");
            $sql->bindParam(":estado", $estado, PDO::PARAM_STR);
            $sql->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
            $sql->execute();

            // Notificar al Veterinario Asignado
            if (!empty($cita_existe->veterinario_id)) {
                // Opcional: Obtener nombre de la mascota
                $sql_mascota = $conexion->prepare("SELECT nombre FROM mascotas WHERE id=:id_mascota LIMIT 1");
                $sql_mascota->bindParam(":id_mascota", $cita_existe->mascota_id, PDO::PARAM_INT);
                $sql_mascota->execute();
                $mascota = $sql_mascota->fetchColumn();
                $nombre_mascota = $mascota ? ucfirst($mascota) : "Desconocida";

                $titulo_notif = $estado === 'confirmada' ? 'Cita Confirmada' : 'Cita Cancelada';
                $mensaje_notif = "El estado de tu cita con el paciente '" . $nombre_mascota . "' ha cambiado a " . strtoupper($estado) . ".";
                $tipo_notif = "cita";

                $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) VALUES (:uid, :tit, :msg, :tipo)");
                $sql_notif->bindParam(":uid", $cita_existe->veterinario_id, PDO::PARAM_INT);
                $sql_notif->bindParam(":tit", $titulo_notif, PDO::PARAM_STR);
                $sql_notif->bindParam(":msg", $mensaje_notif, PDO::PARAM_STR);
                $sql_notif->bindParam(":tipo", $tipo_notif, PDO::PARAM_STR);
                $sql_notif->execute();
            }

            $conexion->commit();

            $_SESSION['exito']="Estado de la cita cambiado a ".ucfirst($estado) ." exitosamente";
            header("Location: ../../recepcionista/gestion_citas.php");
            exit;
        }catch(PDOException $e){
            $conexion->rollBack();
            error_log("Error al cambiar el estado de la cita: " . $e->getMessage());
            $errores[]="Error al cambiar el estado de la cita";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../recepcionista/cambiar_estado_cita.php?id_cita=".urlencode(Crypto::encrypt($id_cita)));
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al cambiar el estado de la cita"];
    header("Location: ../../recepcionista/gestion_citas.php");
    exit;
}



?>