<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

 // 1. Verificación de Rol (Solo admin puede eliminar)
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_usuario'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_usuario = Crypto::decrypt(urldecode($_POST['id_usuario']));
    $errores=[];

    // Validacion y sanitizacion
    if(empty($id_usuario)){
        $errores[]="El id del usuario es requerido";
    }elseif(!ctype_digit($id_usuario)){
        $errores[]="El id del usuario debe ser un número";
    }elseif($id_usuario<=0){
        $errores[]="El id del usuario debe ser mayor a 0";
    }elseif($id_usuario == $_SESSION['id_usuario']){
        $errores[]="No puedes inhabilitarte a ti mismo";
    }

    // Verificar si el usuario existe y su rol actual
    try{
        $sql=$conexion->prepare("SELECT id, rol, estado FROM usuarios WHERE id=:id_usuario LIMIT 1");
        $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $sql->execute();
        $usuario_target=$sql->fetch(PDO::FETCH_OBJ);

        if(!$usuario_target){
            $errores[]="El usuario que desea eliminar no existe";
        }else{
            // 2. Protección contra "Orfandad del Sistema" 
            // Si el usuario a inhabilitar es admin, verificar que no sea el último activo
            if($usuario_target->rol === 'admin' && $usuario_target->estado === 'activo'){
                $sqlCount = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol='admin' AND estado='activo'");
                $totalAdmins = $sqlCount->fetchColumn();
                
                if($totalAdmins <= 1){
                    $errores[]="No puedes inhabilitar al último administrador activo del sistema";
                }
            }
        }
    }catch(PDOException $e){
        error_log("Error al verificar el usuario: " . $e->getMessage());
        $errores[]="Error al verificar el usuario";
    }

    // Si no hay errores, eliminar al usuario
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("UPDATE usuarios SET estado='inactivo' WHERE id=:id_usuario");
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();

            $_SESSION['exito']="Usuario inhabilitado exitosamente";
            header("Location: ../../admin/gestion_usuarios.php");
            exit;
        }catch(PDOException $e){
            error_log("Error al inhabilitar el usuario: " . $e->getMessage());
            $errores[]="Error al inhabilitar el usuario";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/gestion_usuarios.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al eliminar el usuario"];
    header("Location: ../../admin/gestion_usuarios.php");
    exit;
}



?>