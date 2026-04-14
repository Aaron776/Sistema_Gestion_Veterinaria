<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

 // 1. Verificación de Rol 
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_mascota']) && isset($_POST['id_cliente']) && isset($_POST['nombre']) && isset($_POST['especie']) && isset($_POST['raza']) && isset($_POST['sexo']) && isset($_POST['fecha_nacimiento'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_mascota = Crypto::decrypt(urldecode($_POST['id_mascota']));
    $id_cliente = Crypto::decrypt(urldecode($_POST['id_cliente']));
    $nombre = preg_replace('/\s+/', ' ', trim($_POST['nombre']));
    $especie = trim($_POST['especie']);
    $raza = preg_replace('/\s+/', ' ', trim($_POST['raza']));
    $sexo = trim($_POST['sexo']);
    $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
    $foto_actual = isset($_POST['foto_actual']) ? trim($_POST['foto_actual']) : '';
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

    // Verificar si la mascota existe y obtener su foto actual de forma segura
    $foto_db_actual = null;
    try{
        $sql = $conexion->prepare("SELECT id, foto FROM mascotas WHERE id=:id_mascota AND cliente_id=:id_cliente LIMIT 1");
        $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
        $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
        $sql->execute();
        $mascota_existe = $sql->fetch(PDO::FETCH_OBJ);

        if(!$mascota_existe){
            $errores[]="La mascota que intenta editar no existe o no pertenece al cliente";
        } else {
            $foto_db_actual = $mascota_existe->foto;
        }
    }catch(PDOException $e){
        error_log("Error al verificar la mascota: " . $e->getMessage());
        $errores[]="Error al verificar la mascota";
    }

    // Si no hay errores, actualizar la mascota
    if(empty($errores)){
        try{
            // Iniciar transacción
            $conexion->beginTransaction();

            $foto_final = null;

            if ($foto) {
                // Se subió una nueva foto correctamente, asignamos la nueva para la BD
                $foto_final = $foto;
            } else {
                // No se subió foto, mantenemos el nombre del archivo que la base de datos ya tenía
                $foto_final = $foto_db_actual;
            }

            // Actualizar el registro
            $sql = $conexion->prepare("UPDATE mascotas SET nombre = :nombre, especie = :especie, raza = :raza, sexo = :sexo, fecha_nacimiento = :fecha_nacimiento, foto = :foto WHERE id = :id_mascota AND cliente_id = :id_cliente");
            
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":especie", $especie, PDO::PARAM_STR);
            $sql->bindParam(":raza", $raza, PDO::PARAM_STR);
            $sql->bindParam(":sexo", $sexo, PDO::PARAM_STR);
            $sql->bindParam(":fecha_nacimiento", $fecha_nacimiento, PDO::PARAM_STR);
            $sql->bindParam(":foto", $foto_final, PDO::PARAM_STR);
            $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
            $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
            
            $sql->execute();

            // Confirmar transacción (La BD se actualizó con éxito)
            $conexion->commit();

            // SEGURIDAD DE ARCHIVOS: 
            // Solo borramos la foto vieja si el commit fue exitoso y si de verdad se subió una nueva
            if ($foto && !empty($foto_db_actual)) {
                $ruta_foto_anterior = __DIR__ . "/../../app/img_mascotas/" . $foto_db_actual;
                if(file_exists($ruta_foto_anterior) && is_file($ruta_foto_anterior)){
                    unlink($ruta_foto_anterior);
                }
            }

            $_SESSION['exito'] = "Mascota actualizada exitosamente";
            header("Location: ../../recepcionista/editar_mascota_cliente.php?id_cliente=".urlencode(Crypto::encrypt($id_cliente))."&id_mascota=".urlencode(Crypto::encrypt($id_mascota)));
            exit;

        } catch(PDOException $e) {
            // Si falla la BD, revertir
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            
            // Seguridad: Si se subió imagen pero falló el UPDATE, borrar la imagen recién subida para no dejar basura
            if($foto){
                $ruta_nueva = __DIR__ . "/../../app/img_mascotas/" . $foto;
                if(file_exists($ruta_nueva) && is_file($ruta_nueva)){
                    unlink($ruta_nueva);
                }
            }
            
            error_log("Error al actualizar la mascota: " . $e->getMessage());
            $errores[]="Error al guardar los cambios en la base de datos.";
        }
    }
    
    // Falló la validación o el guardado final
    if(!empty($errores)){
        $_SESSION['errores'] = $errores;
        header("Location: ../../recepcionista/editar_mascota_cliente.php?id_cliente=".urlencode(Crypto::encrypt($id_cliente))."&id_mascota=".urlencode(Crypto::encrypt($id_mascota)));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Acceso no válido al formulario"];
    header("Location: ../../recepcionista/gestion_clientes.php");
    exit;
}
?>