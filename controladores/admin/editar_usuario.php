<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo admin puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['id_usuario']) && isset($_POST['email']) && isset($_POST['telefono']) && isset($_POST['rol']) && isset($_POST['cedula'])) {
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
    $rol = trim($_POST['rol']);
    $id_usuario = trim(Crypto::decrypt(urldecode($_POST['id_usuario'])));
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

    if(empty($id_usuario)){
        $errores[]="El id del usuario es requerido";
    }elseif(!is_numeric($id_usuario)){
        $errores[]="El id del usuario no es valido";
    }else if($id_usuario <= 0){
        $errores[]="El id del usuario no es valido";
    }

    if(empty($rol)){
        $errores[]="El rol es requerido";
    }elseif($rol !== 'recepcionista' && $rol !== 'veterinario' && $rol !== 'admin'){
        $errores[]="El rol no es valido";
    }

    // Verificar que el usuario exista
    try{
        $sql=$conexion->prepare("SELECT id FROM usuarios WHERE id=:id_usuario AND estado='activo' LIMIT 1");
        $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $sql->execute();
        $usuario=$sql->fetch(PDO::FETCH_OBJ);
        if(!$usuario){
            $errores[]="El usuario que desea editar no existe";
        }
    }catch(PDOException $e){
        error_log("Error al verificar el usuario: " . $e->getMessage());
        $errores[]="Error al verificar el usuario";
    }

    // Verificar si no existe otro usuario con el mismo email o telefono o cedula que sea diferente al que se va a editar
    try{
        $sql=$conexion->prepare("SELECT email, telefono, cedula FROM usuarios WHERE (email=:email OR telefono=:telefono OR cedula=:cedula) AND id!=:id_usuario LIMIT 1");
        $sql->bindParam(":email", $email, PDO::PARAM_STR);
        $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
        $sql->bindParam(":cedula", $cedula, PDO::PARAM_STR);
        $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
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

    // Protección contra "Orfandad del Sistema" por cambio de rol
    if(empty($errores) && $rol !== 'admin') {
        try{
            // Primero, vemos si el usuario que estamos editando actualmente ES un admin
            $sqlCheck = $conexion->prepare("SELECT rol FROM usuarios WHERE id=:id_usuario LIMIT 1");
            $sqlCheck->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sqlCheck->execute();
            $rolActual = $sqlCheck->fetchColumn();

            if($rolActual === 'admin'){
                // Si le estamos quitando el rol de admin, verificamos cuántos admins activos quedan
                $sqlCount = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol='admin' AND estado='activo'");
                $totalAdmins = $sqlCount->fetchColumn();
                
                if($totalAdmins <= 1){
                    $errores[]="No puedes borrar el único administrador activo del sistema cambiando su rol";
                }
            }
        }catch(PDOException $e){
            error_log("Error al verificar roles de administrador: " . $e->getMessage());
            $errores[]="Error de seguridad al verificar el acceso administrativo";
        }
    }

    // Si no hay errores, editar al usuario
    if(empty($errores)){
        try{
            $conexion->beginTransaction();

            $sql=$conexion->prepare("UPDATE usuarios SET nombre=:nombre, cedula=:cedula, email=:email, telefono=:telefono, rol=:rol WHERE id=:id_usuario");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            $sql->bindParam(":rol", $rol, PDO::PARAM_STR);
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();

            // 2. Insertar notificación para el usuario afectado
            $titulo_notif = "Perfil Actualizado";
            $mensaje_notif = "Un administrador ha modificado la información de tu cuenta o tus permisos en el sistema.";
            $tipo_notif = "info";
            
            $sqlNotif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) VALUES (:id_usuario, :titulo, :mensaje, :tipo)");
            $sqlNotif->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sqlNotif->bindParam(":titulo", $titulo_notif, PDO::PARAM_STR);
            $sqlNotif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
            $sqlNotif->bindParam(":tipo", $tipo_notif, PDO::PARAM_STR);
            $sqlNotif->execute();

            $conexion->commit();

            $_SESSION['exito']="Usuario editado exitosamente";
            header("Location: ../../admin/editar_usuario.php?id_usuario=" . urlencode(Crypto::encrypt($id_usuario)));
            exit;
        }catch(PDOException $e){
            $conexion->rollBack();
            error_log("Error al editar el usuario: " . $e->getMessage());
            $errores[]="Error al editar el usuario";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/editar_usuario.php?id_usuario=" . urlencode(Crypto::encrypt($id_usuario)));
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al iniciar sesión"];
    header("Location: ../../admin/gestion_usuarios.php");
    exit;
}



?>