<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo veterinario puede eliminar historial clinico)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'veterinario') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_mascota_vacuna']) && isset($_POST['id_mascota'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre_veterinario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Veterinario';
    $id_mascota_vacuna = trim(urldecode(Crypto::decrypt($_POST['id_mascota_vacuna'])));
    $id_mascota = trim(urldecode(Crypto::decrypt($_POST['id_mascota'])));
    $errores = [];

    // Validacion y sanitizacion

    if (empty($id_mascota_vacuna)) {
        $errores[] = "El id de la mascota y vacuna es requerido";
    } elseif (!is_numeric($id_mascota_vacuna)) {
        $errores[] = "El id de la mascota y vacuna debe ser un número";
    } elseif ($id_mascota_vacuna <= 0) {
        $errores[] = "El id de la mascota y vacuna debe ser mayor a 0";
    }

    if (empty($id_mascota)) {
        $errores[] = "El id de la mascota es requerido";
    } elseif (!is_numeric($id_mascota)) {
        $errores[] = "El id de la mascota debe ser un número";
    } elseif ($id_mascota <= 0) {
        $errores[] = "El id de la mascota debe ser mayor a 0";
    }

    // Verificar si registro existe en la tabla mascota_vacunas que se quiere eliminar que le pertenezca a a esa mascota
    if (empty($errores)) {
        try {
            $sql_mascota_vacuna = $conexion->prepare("SELECT id,mascota_id FROM mascota_vacunas WHERE id = :id_mascota_vacuna AND mascota_id = :id_mascota");
            $sql_mascota_vacuna->bindParam(":id_mascota_vacuna", $id_mascota_vacuna, PDO::PARAM_INT);
            $sql_mascota_vacuna->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
            $sql_mascota_vacuna->execute();
            $mascota_vacuna = $sql_mascota_vacuna->fetch(PDO::FETCH_OBJ);
            if (!$mascota_vacuna) {
                $errores[] = "El registro de vacuna para la mascota no existe";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el registro de vacuna para la mascota: " . $e->getMessage());
            $errores[] = "Error al verificar el registro de vacuna para la mascota";
        }
    }



    // Si no hay errores, eliminar el registor de la vacuna para esa mascota
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // OBTENER NOMBRE DE LA MASCOTA PARA LA NOTIFICACIÓN ANTES DE ELIMINAR
            $sql_pet = $conexion->prepare("SELECT nombre FROM mascotas WHERE id = :id_m");
            $sql_pet->bindParam(":id_m", $id_mascota, PDO::PARAM_INT);
            $sql_pet->execute();
            $pet_name = $sql_pet->fetchColumn() ?: "Mascota desconocida";

            // ELIMINAR REGISTRO DE VACUNA PARA LA MASCOTA
            $sql = $conexion->prepare("DELETE FROM mascota_vacunas WHERE id = :id_mascota_vacuna");
            $sql->bindParam(":id_mascota_vacuna", $id_mascota_vacuna, PDO::PARAM_INT);
            $sql->execute();

            // NOTIFICAR A RECEPCIÓN SOBRE LA ELIMINACIÓN de esa vacuna para esa mascota
           
            $sql_recep = $conexion->query("SELECT id FROM usuarios WHERE rol = 'recepcionista' AND estado = 'activo'");
            $recepcionistas = $sql_recep->fetchAll(PDO::FETCH_OBJ);

            if ($recepcionistas) {
                $titulo_not = "Registro de Vacuna Eliminado: " . ucfirst($pet_name);
                $mensaje_not = "El veterinario " . ucfirst($nombre_veterinario) . " ha eliminado un registro de vacuna para " . ucfirst($pet_name) . ".";

                $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) VALUES (:uid, :tit, :msg, 'urgente')");

                foreach ($recepcionistas as $recep) {
                    $sql_notif->bindParam(":uid", $recep->id, PDO::PARAM_INT);
                    $sql_notif->bindParam(":tit", $titulo_not, PDO::PARAM_STR);
                    $sql_notif->bindParam(":msg", $mensaje_not, PDO::PARAM_STR);
                    $sql_notif->execute();
                }
            }

            $conexion->commit();

            $_SESSION['exito'] = "Registro de vacuna eliminado exitosamente";
            header("Location: ../../veterinario/gestion_vacunacion_mascotas.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)));
            exit;
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error al eliminar el registro de vacuna para la mascota: " . $e->getMessage());
            $_SESSION['errores'] = ["Error al eliminar el registro de vacuna para la mascota"];
            header("Location: ../../veterinario/gestion_vacunacion_mascotas.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../veterinario/gestion_vacunacion_mascotas.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Error al eliminar el registro de vacuna para la mascota"];
    header("Location: ../../veterinario/gestion_citas_confirmadas.php");
    exit;
}
