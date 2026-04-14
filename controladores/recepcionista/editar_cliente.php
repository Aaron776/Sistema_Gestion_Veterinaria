<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo recepcionista puede agregar clientes)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cliente']) && isset($_POST['nombre']) && isset($_POST['email']) && isset($_POST['telefono']) && isset($_POST['direccion']) && isset($_POST['cedula'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_cliente=Crypto::decrypt(urldecode($_POST['id_cliente']));
    $nombre = preg_replace('/\s+/', ' ', trim($_POST['nombre']));
    $cedula = trim($_POST['cedula']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $errores=[];

    // Validacion y sanitizacion

    if(empty($id_cliente)){
        $errores[]="El id del cliente es requerido";
    }elseif(!ctype_digit($id_cliente)){
        $errores[]="El id del cliente debe contener solo números";
    }elseif($id_cliente<=0){
        $errores[]= "El id del cliente debe ser mayor a 0";
    }


    if(empty($nombre)){
        $errores[]="El nombre es requerido";
    }elseif(strlen($nombre)<3){
        $errores[]="El nombre debe tener al menos 3 caracteres";
    }elseif(strlen($nombre)>100){
        $errores[]="El nombre no debe exceder los 100 caracteres";
    }

    if(empty($email)){
        $errores[]="El email es requerido";
    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errores[]="El email no es valido";
    }elseif(strlen($email)>100){
        $errores[]="El email no debe exceder los 100 caracteres";
    }

    if(empty($cedula)){
        $errores[]="La cedula es requerida";
    }elseif(!ctype_digit($cedula)){
        $errores[]="La cedula debe contener solo números";
    }elseif(strlen($cedula)<10){
        $errores[]="La cedula debe tener al menos 10 caracteres";
    }elseif(strlen($cedula)>10){
        $errores[]="La cedula no debe exceder los 10 caracteres";
    }

    if(empty($telefono)){
        $errores[]="El telefono es requerido";
    }elseif(!ctype_digit($telefono)){
        $errores[]="El telefono debe contener solo números";
    }elseif(strlen($telefono)<10){
        $errores[]="El telefono debe tener al menos 10 caracteres";
    }elseif(strlen($telefono)>10){
        $errores[]="El telefono no debe exceder los 10 caracteres";
    }

    if(empty($direccion)){
        $errores[]="La direccion es requerida";
    }elseif(strlen($direccion)<5){
        $errores[]="La direccion debe tener al menos 5 caracteres";
    }elseif(strlen($direccion)>255){
        $errores[]="La direccion no debe exceder los 255 caracteres";
    }

    // Verificar si el cliente que se quiere editar existe en la base de datos
    try{
        $sql=$conexion->prepare("SELECT id FROM clientes WHERE id=:id_cliente AND estado='activo' LIMIT 1");
        $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
        $sql->execute();
        $cliente_existe=$sql->fetch(PDO::FETCH_OBJ);
        if(!$cliente_existe){
            $errores[]="El cliente no existe";
        }
    }catch(PDOException $e){
        error_log("Error al verificar el cliente: " . $e->getMessage());
        $errores[]="Error al verificar el cliente";
    }


    // Verificar si no existe otro usuario con el mismo email o telefono o cedula 
    try{
        $sql=$conexion->prepare("SELECT email, telefono, cedula FROM usuarios WHERE (LOWER(email)=LOWER(:email) OR telefono=:telefono OR cedula=:cedula) AND estado='activo' LIMIT 1");
        $sql->bindParam(":email", $email, PDO::PARAM_STR);
        $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
        $sql->bindParam(":cedula", $cedula, PDO::PARAM_STR);
        $sql->execute();
        $usuario_repetido=$sql->fetch(PDO::FETCH_OBJ);
        if($usuario_repetido){
            if($usuario_repetido->email == $email) $errores[]="Este correo ya está registrado";
            if($usuario_repetido->telefono == $telefono) $errores[]="Este teléfono ya está registrado";
            if($usuario_repetido->cedula == $cedula) $errores[]="Esta cédula ya está registrada";
        }
    }catch(PDOException $e){
        error_log("Error al verificar el usuario: " . $e->getMessage());
        $errores[]="Error al verificar el usuario";
    }

    // Verificar si no existe otro cliente con el mismo email o telefono o cedula diferente al que se quiere editar
    try{
        $sql=$conexion->prepare("SELECT email, telefono, cedula FROM clientes WHERE (LOWER(email)=LOWER(:email) OR telefono=:telefono OR cedula=:cedula) AND id!=:id_cliente AND estado='activo' LIMIT 1");
        $sql->bindParam(":email", $email, PDO::PARAM_STR);
        $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
        $sql->bindParam(":cedula", $cedula, PDO::PARAM_STR);
        $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
        $sql->execute();
        $cliente_repetido=$sql->fetch(PDO::FETCH_OBJ);
        if($cliente_repetido){
            if($cliente_repetido->email == $email) $errores[]="Este correo ya está registrado";
            if($cliente_repetido->telefono == $telefono) $errores[]="Este teléfono ya está registrado";
            if($cliente_repetido->cedula == $cedula) $errores[]="Esta cédula ya está registrada";
        }
    }catch(PDOException $e){
        error_log("Error al verificar el cliente: " . $e->getMessage());
        $errores[]="Error al verificar el cliente";
    }

    // Si no hay errores, editar al cliente
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("UPDATE clientes SET nombre=:nombre, cedula=:cedula, telefono=:telefono, email=:email, direccion=:direccion WHERE id=:id_cliente AND estado='activo'");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":direccion", $direccion, PDO::PARAM_STR);
            $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
            $sql->execute();

            $_SESSION['exito']="Cliente editado exitosamente";
            header("Location: ../../recepcionista/editar_cliente.php?id_cliente=".urlencode(Crypto::encrypt($id_cliente)));
            exit;
        }catch(PDOException $e){
            error_log("Error al editar el cliente: " . $e->getMessage());
            $errores[]="Error al editar el cliente";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../recepcionista/editar_cliente.php?id_cliente=".urlencode(Crypto::encrypt($id_cliente)));
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al editar el cliente"];
    header("Location: ../../recepcionista/gestion_clientes.php");
    exit;
}



?>