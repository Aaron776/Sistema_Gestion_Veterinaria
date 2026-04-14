<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cliente']) && isset($_POST['nombre']) && isset($_POST['especie']) && isset($_POST['raza']) && isset($_POST['sexo']) && isset($_POST['fecha_nacimiento'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_cliente = Crypto::decrypt(urldecode($_POST['id_cliente']));
    $nombre = preg_replace('/\s+/', ' ', trim($_POST['nombre']));
    $especie = trim($_POST['especie']);
    $raza = preg_replace('/\s+/', ' ', trim($_POST['raza']));
    $sexo = trim($_POST['sexo']);
    $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
    
    $errores=[];

    // Validacion y sanitizacion
    if(empty($nombre)){
        $errores[]="El nombre es requerido";
    }elseif(strlen($nombre)<3){
        $errores[]="El nombre debe tener al menos 3 caracteres";
    }elseif(strlen($nombre)>100){
        $errores[]="El nombre no debe exceder los 100 caracteres";
    }

    $especies=['perro','gato','ave','roedor','otro'];
    if(empty($especie)){
        $errores[]="La especie es requerida";
    }elseif(!in_array($especie, $especies)){
        $errores[]="La especie no es valida";
    }

    if(empty($raza)){
        $errores[]="La raza es requerida";
    }elseif(strlen($raza)< 2){
        $errores[]="La raza debe tener al menos 2 caracteres";
    }elseif(strlen($raza)>50){
        $errores[]="La raza no debe exceder los 50 caracteres";
    }

    $sexos=['M','F'];
    if(empty($sexo)){
        $errores[]="El sexo es requerido";
    }elseif(!in_array($sexo, $sexos)){
        $errores[]="El sexo no es valido";
    }

    if(empty($fecha_nacimiento)){
        $errores[]="La fecha de nacimiento es requerida";
    } else {
        // Validar formato de fecha (YYYY-MM-DD) y crear objeto DateTime
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
        $hoy = new DateTime();
        
        if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fecha_nacimiento) {
            $errores[]="El formato de la fecha de nacimiento no es válido";
        } elseif ($fecha_obj > $hoy) {
            // La mascota no puede nacer en el futuro
            $errores[]="La fecha de nacimiento no puede ser en el futuro";
        } else {
            // Validar que la mascota no tenga una edad inverosímil (ej: más de 30 años)
            $diferencia_anios = $hoy->diff($fecha_obj)->y;
            if ($diferencia_anios > 30) {
                $errores[]="Revisa la fecha, la mascota tendría más de 30 años";
            }
        }
    }

    // Manejo del archivo (si se subió una foto)
    $foto = null;
    // 1. Verificar si el campo 'foto' existe en $_FILES y si no hubo errores críticos de subida (como archivo muy pesado para PHP)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        // 2. Definir los tipos de imágen permitidos (MIME types)
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['foto']['type']; // obtener el tipo de archivo de la imagen que se subio
        
        // 3. Validar que el tipo de archivo esté en la lista blanca
        if (!in_array($file_type, $allowed_types)) {
            $errores[] = "Tipo de archivo no permitido. Solo JPG, JPEG y PNG.";
        // 4. Validar que el tamaño no supere los 2MB (2 * 1024 * 1024 bytes)
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $errores[] = "La foto es demasiado grande (máx. 2MB).";
        } else {
            // 5. Obtener la extensión original del archivo (ej: jpg, png)
            $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            // 6. Generar un nombre único e irrepetible para evitar choques (ej: pet_65f1a23b4c5d6.jpg)
            $nombreArchivo = uniqid('pet_', true) . '.' . $extension;
            // 7. Definir la ruta donde se guardará (subiendo 2 niveles para llegar a la carpeta raíz 'app')
            $directorioDestino = '../../app/img_mascotas/';
            
            // 8. Verificar si la carpeta existe; si no, crearla automáticamente con permisos de escritura
            if (!is_dir($directorioDestino)) {
                mkdir($directorioDestino, 0777, true);
            }
            
            // 9. Combinar el directorio con el nombre único para tener la ruta completa
            $rutaDestino = $directorioDestino . $nombreArchivo;
            // 10. Mover el archivo desde la carpeta temporal de PHP a su ubicación final en el servidor
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaDestino)) {
                // 11. Si todo salió bien, guardamos el nombre en la variable que irá a la base de datos
                $foto = $nombreArchivo;
            } else {
                $errores[] = "Error al guardar la imagen en el servidor.";
            }
        }
    }


    // Si no hay errores, registrar la mascota
    if(empty($errores)){
        try{
            $sql=$conexion->prepare("INSERT INTO mascotas (cliente_id, nombre, especie, raza, sexo, fecha_nacimiento, foto) VALUES (:cliente_id, :nombre, :especie, :raza, :sexo, :fecha_nacimiento, :foto)");
            $sql->bindParam(":cliente_id", $id_cliente, PDO::PARAM_INT);
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":especie", $especie, PDO::PARAM_STR);
            $sql->bindParam(":raza", $raza, PDO::PARAM_STR);
            $sql->bindParam(":sexo", $sexo, PDO::PARAM_STR);
            $sql->bindParam(":fecha_nacimiento", $fecha_nacimiento, PDO::PARAM_STR);
            $sql->bindParam(":foto", $foto, PDO::PARAM_STR);
            $sql->execute();

            $_SESSION['exito']="Mascota registrada exitosamente";
            header("Location: ../../recepcionista/agregar_mascota.php?id_cliente=" . urlencode(Crypto::encrypt($id_cliente)));
            exit;
        }catch(PDOException $e){
            error_log("Error al registrar la mascota: " . $e->getMessage());
            $errores[]="Error al registrar la mascota";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../recepcionista/agregar_mascota.php?id_cliente=" . urlencode(Crypto::encrypt($id_cliente)));
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al procesar la solicitud"];
    header("Location: ../../recepcionista/gestion_clientes.php");
    exit;
}



?>