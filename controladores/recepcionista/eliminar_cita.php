<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

 // 1. Verificación de Rol 
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cita'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_cita = Crypto::decrypt(urldecode($_POST['id_cita']));
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_cita)){
        $errores[]="El id de la cita es requerido";
    }elseif(!ctype_digit($id_cita)){
        $errores[]="El id de la cita debe ser un número";
    }elseif($id_cita<=0){
        $errores[]="El id de la cita debe ser mayor a 0";
    }

    // Verificar si la cita que se quiere eliminar existe en la base de datos y esta en estado pendiente
    try{
        $sql=$conexion->prepare("SELECT id FROM citas WHERE id=:id_cita AND estado='pendiente' LIMIT 1");
        $sql->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
        $sql->execute();
        $cita_existe=$sql->fetch(PDO::FETCH_OBJ);

        if(!$cita_existe){
            $errores[]="La cita que desea eliminar no existe";
        }
    }catch(PDOException $e){
        error_log("Error al verificar la cita: " . $e->getMessage());
        $errores[]="Error al verificar la cita";
    }

    // Si no hay errores, eliminar la cita
    if(empty($errores)){
        try{
            $conexion->beginTransaction();
            //Elimiar registros de Citas Servicios
            $sql=$conexion->prepare("DELETE FROM cita_servicios WHERE cita_id=:id_cita");
            $sql->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
            $sql->execute();

            // Eliminar la cita
            $sql=$conexion->prepare("DELETE FROM citas WHERE id=:id_cita");
            $sql->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
            $sql->execute();

            $conexion->commit();

            $_SESSION['exito']="Cita eliminada exitosamente";
            header("Location: ../../recepcionista/gestion_citas.php");
            exit;
        }catch(PDOException $e){
            $conexion->rollBack();
            error_log("Error al eliminar la cita: " . $e->getMessage());
            $errores[]="Error al eliminar la cita";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../recepcionista/gestion_citas.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al eliminar la cita"];
    header("Location: ../../recepcionista/gestion_citas.php");
    exit;
}



?>