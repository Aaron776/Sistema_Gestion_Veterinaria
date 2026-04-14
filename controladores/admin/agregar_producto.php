<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

// 1. Verificación de Rol (Solo admin puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre_producto']) && isset($_POST['precio']) && isset($_POST['stock'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre_producto = trim($_POST['nombre_producto']);
    $precio = trim($_POST['precio']);
    $stock = trim($_POST['stock']);
    $errores=[];

    // Validacion y sanitizacion
    if(empty($nombre_producto)){
        $errores[]="El nombre es requerido";
    }elseif(strlen($nombre_producto)<3){
        $errores[]="El nombre debe tener al menos 3 caracteres";
    }elseif(strlen($nombre_producto)>100){
        $errores[]="El nombre no debe exceder los 100 caracteres";
    }

    if($precio === ''){ 
        $errores[]="El precio es requerido";
    }elseif(!is_numeric($precio) || floatval($precio) <= 0){ 
        $errores[]="El precio debe ser un número mayor a 0";
    }

    if($stock === ''){ 
        $errores[]="El stock es requerido";
    }elseif(!ctype_digit($stock)){ 
        $errores[]="El stock debe ser un número entero";
    }


    // Verificar si no existe otro producto con el mismo nombre y que este en estado activo
    try{
        $sql=$conexion->prepare("SELECT nombre_producto FROM inventario WHERE LOWER(nombre_producto) = LOWER(:nombre_producto) AND estado = 'activo' LIMIT 1");
        $sql->bindParam(":nombre_producto", $nombre_producto, PDO::PARAM_STR);
        $sql->execute();
        $producto_repetido=$sql->fetch(PDO::FETCH_OBJ);
        if($producto_repetido){
            $errores[]="Este producto ya existe en el inventario (el nombre coincide con uno registrado).";
        }
    }catch(PDOException $e){
        error_log("Error al verificar el producto: " . $e->getMessage());
        $errores[]="Error técnico al verificar disponibilidad.";
    }

    // Si no hay errores, registrar al usuario
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("INSERT INTO inventario (nombre_producto,stock,precio) VALUES (:nombre_producto, :stock, :precio)");
            $sql->bindParam(":nombre_producto", $nombre_producto, PDO::PARAM_STR);
            $sql->bindParam(":stock", $stock, PDO::PARAM_INT);
            $sql->bindParam(":precio", $precio, PDO::PARAM_STR);
            $sql->execute();

            $_SESSION['exito']="Producto registrado exitosamente";
            header("Location: ../../admin/agregar_producto.php");
            exit;
        }catch(PDOException $e){
            error_log("Error al registrar el producto: " . $e->getMessage());
            $errores[]="Error al registrar el producto";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/agregar_producto.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al iniciar sesión"];
    header("Location: ../../admin/gestionar_productos.php");
    exit;
}



?>