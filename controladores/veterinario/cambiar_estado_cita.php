<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

 // 1. Verificación de Rol 
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'veterinario') {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cita']) && isset($_POST['estado'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_cita = Crypto::decrypt($_POST['id_cita'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_cita)){
        $errores[]="El id de la cita es requerido";
    }elseif(!is_numeric($id_cita)){
        $errores[]="El id de la cita no es válido";
    }elseif($id_cita<=0){
        $errores[]="El id de la cita debe ser mayor a 0";
    }

    if(empty($estado)){
        $errores[]="El estado de la cita es requerido";
    }elseif(!in_array($estado, ['realizada'])){
        $errores[]="El estado de la cita no es valido";
    }

    // Verificar si la cita que se quiere cambiar el estado existe en la base de datos y esta en estado confirmada
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("SELECT id FROM citas WHERE id=:id_cita AND estado='confirmada' LIMIT 1");
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
    }

    // Si no hay errores, cambiar el estado de la cita
    if(empty($errores)){
        try{
            $conexion->beginTransaction();

            $sql=$conexion->prepare("UPDATE citas SET estado=:estado WHERE id=:id_cita AND estado='confirmada'");
            $sql->bindParam(":estado", $estado, PDO::PARAM_STR);
            $sql->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
            $sql->execute();

            // Notificar a Recepcionistas y Administradores que ya pueden facturar
            if ($estado === 'realizada') {
                // Extraer nombre del paciente
                $sqlMascota = $conexion->prepare("SELECT m.nombre FROM mascotas m JOIN citas c ON c.mascota_id = m.id WHERE c.id = :id_cita LIMIT 1");
                $sqlMascota->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
                $sqlMascota->execute();
                $mascota = $sqlMascota->fetchColumn();
                $nombre_mascota = $mascota ? ucfirst($mascota) : "Desconocido";

                // Buscar personal de recepción y admin
                $sqlPersonal = $conexion->query("SELECT id FROM usuarios WHERE (rol='recepcionista' OR rol='admin') AND estado='activo'");
                $personal = $sqlPersonal->fetchAll(PDO::FETCH_OBJ);

                $titulo = "Consulta Finalizada";
                $mensaje = "El veterinario " . $_SESSION['nombre'] . " finalizó la cita del paciente '" . $nombre_mascota . "'. Listo para facturar en caja.";
                $tipo = "info";

                $sqlNotif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) VALUES (:id_usuario, :titulo, :mensaje, :tipo)");
                
                foreach($personal as $usuario_dest) {
                     $sqlNotif->bindValue(":id_usuario", $usuario_dest->id, PDO::PARAM_INT);
                     $sqlNotif->bindValue(":titulo", $titulo, PDO::PARAM_STR);
                     $sqlNotif->bindValue(":mensaje", $mensaje, PDO::PARAM_STR);
                     $sqlNotif->bindValue(":tipo", $tipo, PDO::PARAM_STR);
                     $sqlNotif->execute();
                }
            }

            $conexion->commit();

            $_SESSION['exito']="Estado de la cita cambiado a ".ucfirst($estado) ." exitosamente";
            header("Location: ../../veterinario/gestion_citas_confirmadas.php");
            exit;
        }catch(PDOException $e){
            $conexion->rollBack();
            error_log("Error al cambiar el estado de la cita: " . $e->getMessage());
            $errores[]="Error al cambiar el estado de la cita";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../veterinario/cambiar_estado_cita.php?id_cita=".urlencode(Crypto::encrypt($id_cita)));
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al cambiar el estado de la cita"];
    header("Location: ../../veterinario/gestion_citas_confirmadas.php");
    exit;
}



?>