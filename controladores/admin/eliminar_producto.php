<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

 // 1. Verificación de Rol (Solo admin puede eliminar)
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_producto'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_producto = Crypto::decrypt(urldecode($_POST['id_producto']));
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_producto)){
        $errores[]="El id del producto es requerido";
    }elseif(!ctype_digit($id_producto)){
        $errores[]="El id del producto debe ser un número";
    }elseif($id_producto<=0){
        $errores[]="El id del producto debe ser mayor a 0";
    }

    // Verificar si el producto existe
    try{
        $sql=$conexion->prepare("SELECT id FROM inventario WHERE id=:id_producto LIMIT 1");
        $sql->bindParam(":id_producto", $id_producto, PDO::PARAM_INT);
        $sql->execute();
        $producto_target=$sql->fetch(PDO::FETCH_OBJ);

        if(!$producto_target){
            $errores[]="El producto que desea eliminar no existe";
        }
    }catch(PDOException $e){
        error_log("Error al verificar el producto: " . $e->getMessage());
        $errores[]="Error al verificar el producto";
    }

    // Si no hay errores, inhabilitar el producto
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("UPDATE inventario SET estado='inactivo' WHERE id=:id_producto");
            $sql->bindParam(":id_producto", $id_producto, PDO::PARAM_INT);
            $sql->execute();

            $_SESSION['exito']="Producto inhabilitado exitosamente";
            header("Location: ../../admin/gestion_inventario.php");
            exit;
        }catch(PDOException $e){
            error_log("Error al eliminar el producto: " . $e->getMessage());
            $errores[]="Error al eliminar el producto";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/gestion_inventario.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al eliminar el producto"];
    header("Location: ../../admin/gestion_inventario.php");
    exit;
}



?>