<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

// 1. Verificación de Rol (Solo admin puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['password']) && isset($_POST['email']) && isset($_POST['telefono']) && isset($_POST['rol']) && isset($_POST['cedula'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $cedula = trim($_POST['cedula']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $rol = trim($_POST['rol']);
    $errores=[];

    // Validacion y sanitizacion
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

    if(empty($password)){
        $errores[]="La contraseña es requerida";
    }elseif(strlen($password)<5){
        $errores[]="La contraseña debe tener al menos 5 caracteres";
    }

    if(empty($rol)){
        $errores[]="El rol es requerido";
    }elseif($rol !== 'recepcionista' && $rol !== 'veterinario' && $rol !== 'admin'){
        $errores[]="El rol no es valido";
    }

    // Verificar si no existe otro usuario con el mismo email o telefono o cedula
    try{
        $sql=$conexion->prepare("SELECT email, telefono, cedula FROM usuarios WHERE email=:email OR telefono=:telefono OR cedula=:cedula LIMIT 1");
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

    // Si no hay errores, registrar al usuario
    if(empty($errores)){
        try{
            $password_hash=password_hash($password, PASSWORD_DEFAULT);
            $sql=$conexion->prepare("INSERT INTO usuarios (nombre, cedula, email, telefono, password,rol) VALUES (:nombre, :cedula, :email, :telefono, :password, :rol)");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            $sql->bindParam(":password", $password_hash, PDO::PARAM_STR);
            $sql->bindParam(":rol", $rol, PDO::PARAM_STR);
            $sql->execute();

            $_SESSION['exito']="Usuario registrado exitosamente";
            header("Location: ../../admin/agregar_usuario.php");
            exit;
        }catch(PDOException $e){
            error_log("Error al registrar el usuario: " . $e->getMessage());
            $errores[]="Error al registrar el usuario";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/agregar_usuario.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al iniciar sesión"];
    header("Location: ../../admin/gestion_usuarios.php");
    exit;
}



?>