<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre_producto']) && isset($_POST['precio']) && isset($_POST['stock']) && isset($_POST['id_producto'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    $id_producto = Crypto::decrypt(urldecode($_POST['id_producto']));
    $nombre_producto = preg_replace('/\s+/', ' ', trim($_POST['nombre_producto']));
    $precio = trim($_POST['precio']);
    $stock = trim($_POST['stock']);
    $errores = [];

    // Validaciones y Sanitizacion
    if (!$id_producto || !is_numeric($id_producto) || $id_producto <= 0) {
        $errores[] = "ID de producto inválido";
    }

    if (empty($nombre_producto)) {
        $errores[] = "El nombre es requerido";
    } elseif (strlen($nombre_producto) < 3) {
        $errores[] = "El nombre debe tener al menos 3 caracteres";
    } elseif (strlen($nombre_producto) > 100) {
        $errores[] = "El nombre no debe exceder los 100 caracteres";
    }

    if ($precio === '') {
        $errores[] = "El precio es requerido";
    } elseif (!is_numeric($precio) || floatval($precio) <= 0) {
        $errores[] = "El precio debe ser un número mayor a 0";
    }

    if ($stock === '') {
        $errores[] = "El stock es requerido";
    } elseif (!ctype_digit($stock)) {
        $errores[] = "El stock debe ser un número entero";
    }

    // Verificar si el producto que se va a editar existe y esta activo
    try {
        $sql = $conexion->prepare("SELECT nombre_producto FROM inventario WHERE id = :id_producto AND estado = 'activo'");
        $sql->bindParam(":id_producto", $id_producto, PDO::PARAM_INT);
        $sql->execute();
        $producto_existe = $sql->fetch(PDO::FETCH_OBJ);
        if (!$producto_existe) {
            $errores[] = "El producto que intenta editar no existe o ha sido eliminado.";
        }
    } catch (PDOException $e) {
        error_log("Error al verificar el producto: " . $e->getMessage());
        $errores[] = "Error técnico al verificar disponibilidad.";
    }

    // Verificar si no existe otro producto con el mismo nombre y que este en estado activo
    try {
        $sql = $conexion->prepare("SELECT nombre_producto FROM inventario WHERE LOWER(nombre_producto) = LOWER(:nombre_producto) AND estado = 'activo' AND id != :id_producto LIMIT 1");
        $sql->bindParam(":nombre_producto", $nombre_producto, PDO::PARAM_STR);
        $sql->bindParam(":id_producto", $id_producto, PDO::PARAM_INT);
        $sql->execute();
        $producto_repetido = $sql->fetch(PDO::FETCH_OBJ);
        if ($producto_repetido) {
            $errores[] = "Este producto ya existe en el inventario.";
        }
    } catch (PDOException $e) {
        error_log("Error al verificar el producto: " . $e->getMessage());
        $errores[] = "Error técnico al verificar disponibilidad.";
    }

    // Si no hay errores, actualizar el producto
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            $sql = $conexion->prepare("UPDATE inventario SET nombre_producto = :nombre_producto, stock = :stock, precio = :precio WHERE id = :id_producto AND estado = 'activo'");
            $sql->bindParam(":nombre_producto", $nombre_producto, PDO::PARAM_STR);
            $sql->bindParam(":stock", $stock, PDO::PARAM_INT);
            $sql->bindParam(":precio", $precio, PDO::PARAM_STR);
            $sql->bindParam(":id_producto", $id_producto, PDO::PARAM_INT);
            $sql->execute();

            // 2. Si el stock cae a un nivel bajo (<= 10), notificar a los admins
            if (intval($stock) <= 10) {
                // Obtener todos los administradores activos
                $sqlAdmins = $conexion->query("SELECT id FROM usuarios WHERE rol='admin' AND estado='activo'");
                $admins = $sqlAdmins->fetchAll(PDO::FETCH_OBJ);
                
                $titulo = "Alerta de Stock Bajo";
                $mensaje = "El producto '" . htmlspecialchars($nombre_producto) . "' ha bajado a un nivel crítico (" . $stock . " unidades). Te recomendamos reabastecer pronto.";
                $tipo = "inventario";
                
                $sqlNotif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) VALUES (:id_usuario, :titulo, :mensaje, :tipo)");
                
                foreach($admins as $admin) {
                     $sqlNotif->bindValue(":id_usuario", $admin->id, PDO::PARAM_INT);
                     $sqlNotif->bindValue(":titulo", $titulo, PDO::PARAM_STR);
                     $sqlNotif->bindValue(":mensaje", $mensaje, PDO::PARAM_STR);
                     $sqlNotif->bindValue(":tipo", $tipo, PDO::PARAM_STR);
                     $sqlNotif->execute();
                }
            }

            $conexion->commit();

            $_SESSION['exito'] = "Producto actualizado exitosamente";
            header("Location: ../../admin/gestion_inventario.php");
            exit;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al actualizar el producto: " . $e->getMessage());
            $errores[] = "Error al actualizar el producto";
        }
    }

    $_SESSION['errores'] = $errores;
    header("Location: ../../admin/editar_producto.php?id_producto=" . urlencode($_POST['id_producto']));
    exit;
} else {
    header("Location: ../../admin/gestion_inventario.php");
    exit;
}
