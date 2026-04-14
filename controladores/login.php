<?php
require_once '../conexion/session.php';
require_once '../conexion/bd.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $errores=[];

    // Validacion y sanitizacion
    if(empty($email)){
        $errores[]="El email es requerido";
    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errores[]="El email no es valido";
    }elseif(strlen($email)>100){
        $errores[]="El email es muy largo";
    }

    if(empty($password)){
        $errores[]="La contraseña es requerida";
    }elseif(strlen($password)<5){
        $errores[]="La contraseña debe tener al menos 5 caracteres";
    }

    // Verificar si el usuario que quiere ingresar esta en estado inactivo
    if(empty($errores)){
        $sql=$conexion->prepare("SELECT estado FROM usuarios WHERE email=:email");
        $sql->bindParam(":email", $email, PDO::PARAM_STR);
        $sql->execute();
        $usuario_estado=$sql->fetch(PDO::FETCH_OBJ);
        if($usuario_estado && $usuario_estado->estado === 'inactivo'){
            $errores[]="Tu cuenta esta desactivada. Por favor, contacta al administrador.";
        }
    }
    
    // Si no hay errores, iniciar sesion
    if(empty($errores)){

        // Actualizar el Ultimo acceso
        try {
            $fecha_acceso = date('Y-m-d H:i:s');
            $sqlAcceso = $conexion->prepare("UPDATE usuarios SET ultimo_acceso = :acceso WHERE email = :email");
            $sqlAcceso->bindParam(":acceso", $fecha_acceso, PDO::PARAM_STR);
            $sqlAcceso->bindParam(":email", $email, PDO::PARAM_STR);
            $sqlAcceso->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar el ultimo acceso: " . $e->getMessage());
        }

        // Iniciar sesion
        try{
            $sql=$conexion->prepare("SELECT id,nombre,rol,telefono,password FROM usuarios WHERE email=:email");
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->execute();
            $usuario=$sql->fetch(PDO::FETCH_OBJ);
            if($usuario && password_verify($password, $usuario->password)){
                $_SESSION['id_usuario']=$usuario->id;
                $_SESSION['nombre']=$usuario->nombre;
                $_SESSION['rol']=$usuario->rol;
                $_SESSION['telefono']=$usuario->telefono;
                $_SESSION['logueado']=true;
                
                switch($usuario->rol){
                    case 'admin':
                        header("Location: ../admin/dash_admin.php");
                        break;
                    case 'veterinario':
                        header("Location: ../veterinario/dash_veterinario.php");
                        break;
                    case 'recepcionista':
                        header("Location: ../recepcionista/dash_recepcionista.php");
                        break;
                    default:
                        header("Location: ../login.php");
                        break;
                }
                exit;
            }else{
                $_SESSION['errores'] = ["Credenciales incorrectas"]; 
                header("Location: ../login.php");
                exit;
            }
        }catch(PDOException $e){
            error_log("Error al iniciar sesión: " . $e->getMessage());
            $errores[]="Error al iniciar sesión";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../login.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al iniciar sesión"];
    header("Location: ../login.php");
    exit;
}



?>