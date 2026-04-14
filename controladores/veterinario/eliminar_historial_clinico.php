<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo veterinario puede eliminar historial clinico)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'veterinario') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_historial_clinico'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_historial_clinico = trim(urldecode(Crypto::decrypt($_POST['id_historial_clinico'])));
    $errores = [];

    // Validacion y sanitizacion

    if (empty($id_historial_clinico)) {
        $errores[] = "El id del historial clinico es requerido";
    } elseif (!is_numeric($id_historial_clinico)) {
        $errores[] = "El id del historial clinico debe ser un número";
    } elseif ($id_historial_clinico <= 0) {
        $errores[] = "El id del historial clinico debe ser mayor a 0";
    }

    // Verificar si el historial clinico existe 
    if (empty($errores)) {
        try {
            $sql_historial_clinico = $conexion->prepare("SELECT id,mascota_id FROM historial_clinico WHERE id = :id_historial_clinico");
            $sql_historial_clinico->bindParam(":id_historial_clinico", $id_historial_clinico, PDO::PARAM_INT);
            $sql_historial_clinico->execute();
            $historial_clinico = $sql_historial_clinico->fetch(PDO::FETCH_OBJ);
            if (!$historial_clinico) {
                $errores[] = "El historial clinico no existe";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el historial clinico: " . $e->getMessage());
            $errores[] = "Error al verificar el historial clinico";
        }
    }



    // Si no hay errores, eliminar historial clinico
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // OBTENER NOMBRE DE LA MASCOTA PARA LA NOTIFICACIÓN ANTES DE ELIMINAR
            $sql_pet = $conexion->prepare("SELECT nombre FROM mascotas WHERE id = :id_m");
            $sql_pet->bindParam(":id_m", $historial_clinico->mascota_id, PDO::PARAM_INT);
            $sql_pet->execute();
            $pet_name = $sql_pet->fetchColumn() ?: "Mascota desconocida";

            // ELIMINAR HISTORIAL CLINICO
            $sql = $conexion->prepare("DELETE FROM historial_clinico WHERE id = :id_historial_clinico");
            $sql->bindParam(":id_historial_clinico", $id_historial_clinico, PDO::PARAM_INT);
            $sql->execute();

            // NOTIFICAR A RECEPCIÓN SOBRE LA ELIMINACIÓN (IMPORTANTE PARA INTEGRIDAD DE COBROS)
            $sql_recep = $conexion->query("SELECT id FROM usuarios WHERE rol = 'recepcionista' AND estado = 'activo'");
            $recepcionistas = $sql_recep->fetchAll(PDO::FETCH_OBJ);

            if ($recepcionistas) {
                $titulo_not = "Registro Clínico Eliminado: " . ucfirst($pet_name);
                $mensaje_not = "El veterinario ha eliminado un registro de historial clínico para " . ucfirst($pet_name) . ". Por favor, verifique si esto afecta el cobro de la cita correspondiente.";

                $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) VALUES (:uid, :tit, :msg, 'urgente')");

                foreach ($recepcionistas as $recep) {
                    $sql_notif->bindParam(":uid", $recep->id, PDO::PARAM_INT);
                    $sql_notif->bindParam(":tit", $titulo_not, PDO::PARAM_STR);
                    $sql_notif->bindParam(":msg", $mensaje_not, PDO::PARAM_STR);
                    $sql_notif->execute();
                }
            }

            $conexion->commit();

            $_SESSION['exito'] = "Historial clinico eliminado exitosamente";
            header("Location: ../../veterinario/historial_clinico_mascota.php?id_mascota=" . urlencode(Crypto::encrypt($historial_clinico->mascota_id)));
            exit;
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error al eliminar el historial clinico: " . $e->getMessage());
            $_SESSION['errores'] = ["Error al eliminar el historial clinico"];
            header("Location: ../../veterinario/historial_clinico_mascota.php?id_mascota=" . urlencode(Crypto::encrypt($historial_clinico->mascota_id)));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        // Si falló antes de obtener el mascota_id, redirigimos a la gestión general
        $redirect = isset($historial_clinico->mascota_id) 
            ? "../../veterinario/historial_clinico_mascota.php?id_mascota=" . urlencode(Crypto::encrypt($historial_clinico->mascota_id))
            : "../../veterinario/gestion_citas_confirmadas.php";
        header("Location: $redirect");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Error al eliminar el historial clinico"];
    header("Location: ../../veterinario/gestion_citas_confirmadas.php");
    exit;
}
