<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

 // 1. Verificación de Rol 
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cliente'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_cliente = Crypto::decrypt(urldecode($_POST['id_cliente']));
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_cliente)){
        $errores[]="El id del cliente es requerido";
    }elseif(!ctype_digit($id_cliente)){
        $errores[]="El id del cliente debe ser un número";
    }elseif($id_cliente<=0){
        $errores[]="El id del cliente debe ser mayor a 0";
    }

    // Verificar si el cliente que se quiere eliminar existe en la base de datos
    try{
        $sql=$conexion->prepare("SELECT id FROM clientes WHERE id=:id_cliente AND estado='activo' LIMIT 1");
        $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
        $sql->execute();
        $cliente_target=$sql->fetch(PDO::FETCH_OBJ);

        if(!$cliente_target){
            $errores[]="El cliente que desea eliminar no existe o ya está inhabilitado";
        }
    }catch(PDOException $e){
        error_log("Error al verificar el cliente: " . $e->getMessage());
        $errores[]="Error al verificar el cliente";
    }

    // Si no hay errores, inhabilitar al cliente
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("UPDATE clientes SET estado='inactivo' WHERE id=:id_cliente");
            $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
            $sql->execute();

            $_SESSION['exito']="Cliente inhabilitado exitosamente";
            header("Location: ../../recepcionista/gestion_clientes.php");
            exit;
        }catch(PDOException $e){
            error_log("Error al inhabilitar el cliente: " . $e->getMessage());
            $errores[]="Error al inhabilitar el cliente";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../recepcionista/gestion_clientes.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al eliminar el cliente"];
    header("Location: ../../recepcionista/gestion_clientes.php");
    exit;
}



?>