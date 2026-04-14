<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

 // 1. Verificación de Rol 
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_mascota']) && isset($_POST['id_cliente'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_mascota = Crypto::decrypt(urldecode($_POST['id_mascota']));
    $id_cliente = Crypto::decrypt(urldecode($_POST['id_cliente']));
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_mascota)){
        $errores[]="El id de la mascota es requerido";
    }elseif(!ctype_digit($id_mascota)){
        $errores[]="El id de la mascota debe ser un número";
    }elseif($id_mascota<=0){
        $errores[]="El id de la mascota debe ser mayor a 0";
    }

    if(empty($id_cliente)){
        $errores[]="El id del cliente es requerido";
    }elseif(!ctype_digit($id_cliente)){
        $errores[]="El id del cliente debe ser un número";
    }elseif($id_cliente<=0){
        $errores[]="El id del cliente debe ser mayor a 0";
    }

    // Verificar si la mascota que se quiere eliminar existe y le pertenece al cliente en la base de datos
    try{
        $sql=$conexion->prepare("SELECT id FROM mascotas WHERE id=:id_mascota AND cliente_id=:id_cliente LIMIT 1");
        $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
        $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
        $sql->execute();
        $mascota_existe=$sql->fetch(PDO::FETCH_OBJ);

        if(!$mascota_existe){
            $errores[]="La mascota que desea eliminar no existe o no pertenece al cliente";
        }
    }catch(PDOException $e){
        error_log("Error al verificar la mascota: " . $e->getMessage());
        $errores[]="Error al verificar la mascota";
    }

    // Si no hay errores, eliminar la mascota
    if(empty($errores)){
        try{
            // Iniciar transacción para proteger la base de datos
            $conexion->beginTransaction();

            // 1. Obtener la foto de la mascota ANTES de eliminar el registro
            $sql_foto=$conexion->prepare("SELECT foto FROM mascotas WHERE id=:id_mascota");
            $sql_foto->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
            $sql_foto->execute();
            $mascota_target_foto=$sql_foto->fetch(PDO::FETCH_OBJ);

            // 2. Eliminar el registro de la base de datos
            $sql=$conexion->prepare("DELETE FROM mascotas WHERE id=:id_mascota");
            $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
            $sql->execute();

            // 3. Confirmar la transacción
            $conexion->commit();

            // 4. Si la eliminación en BD fue exitosa, borrar la foto del servidor
            if($mascota_target_foto && !empty($mascota_target_foto->foto)){
                $ruta_foto = "../../app/img_mascotas/" . $mascota_target_foto->foto;
                // Verificar que el archivo realmente exista antes de intentar borrarlo
                if(file_exists($ruta_foto) && is_file($ruta_foto)){
                    unlink($ruta_foto);
                }
            }

            $_SESSION['exito']="Mascota eliminada exitosamente";
            header("Location: ../../recepcionista/gestion_mascotas_cliente.php?id_cliente=".urlencode(Crypto::encrypt($id_cliente)));
            exit;
        }catch(PDOException $e){
            // Si algo falla, revertir la base de datos
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error al eliminar la mascota: " . $e->getMessage());
            $errores[]="Error al eliminar la mascota";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../recepcionista/gestion_mascotas_cliente.php?id_cliente=".urlencode(Crypto::encrypt($id_cliente)));
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al eliminar la mascota"];
    header("Location: ../../recepcionista/gestion_clientes.php");
    exit;
}



?>