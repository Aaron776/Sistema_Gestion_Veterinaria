<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo veterinario puede agregar historial clinico)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'veterinario') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_mascota']) && isset($_POST['id_vacuna']) && isset($_POST['id_mascota_vacuna']) && isset($_POST['fecha_aplicacion']) && isset($_POST['proxima_dosis'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre_veterinario = $_SESSION['nombre'];
    $id_mascota = trim(urldecode(Crypto::decrypt($_POST['id_mascota'])));
    $id_vacuna = trim($_POST['id_vacuna']);
    $id_mascota_vacuna = trim(urldecode(Crypto::decrypt($_POST['id_mascota_vacuna'])));
    $fecha_aplicacion = trim($_POST['fecha_aplicacion']);
    $proxima_dosis = isset($_POST['proxima_dosis']) ? trim($_POST['proxima_dosis']) : '';
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_vacuna)) {
        $errores[] = "El id de la vacuna es requerido";
    } elseif (!is_numeric($id_vacuna)) {
        $errores[] = "El id de la vacuna debe ser un número";
    } elseif ($id_vacuna <= 0) {
        $errores[] = "El id de la vacuna debe ser mayor a 0";
    }

    if (empty($id_mascota_vacuna)) {
        $errores[] = "El id de la mascota_vacuna es requerido";
    } elseif (!is_numeric($id_mascota_vacuna)) {
        $errores[] = "El id de la mascota_vacuna debe ser un número";
    } elseif ($id_mascota_vacuna <= 0) {
        $errores[] = "El id de la mascota_vacuna debe ser mayor a 0";
    }

    if (empty($id_mascota)) {
        $errores[] = "El id de la mascota es requerido";
    } elseif (!is_numeric($id_mascota)) {
        $errores[] = "El id de la mascota debe ser un número";
    } elseif ($id_mascota <= 0) {
        $errores[] = "El id de la mascota debe ser mayor a 0";
    }

    if ($proxima_dosis !== '') {
        $fecha_actual = date('Y-m-d');
        if ($proxima_dosis <= $fecha_actual) {
            $errores[] = "La próxima dosis debe ser una fecha en el futuro (después de hoy).";
        }
    } else {
        $proxima_dosis = null; // Si dejó en blanco, se envía null a la DB
    }

    if (empty($fecha_aplicacion)) {
        $errores[] = "La fecha de aplicación es requerida";
    }

    // Verificar si el registro de la macota_vacunas existe y le pertenece a esa mascota
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT * FROM mascota_vacunas WHERE id = :id_mascota_vacuna AND mascota_id = :id_mascota");
            $sql->bindParam(":id_mascota_vacuna", $id_mascota_vacuna, PDO::PARAM_INT);
            $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
            $sql->execute();
            $mascota_vacuna = $sql->fetch(PDO::FETCH_OBJ);
            if (!$mascota_vacuna) {
                $errores[] = "El registro de la mascota_vacuna no existe o no le pertenece a esa mascota";
            }
        } catch (PDOException $e) {
            error_log("Error al obtener la mascota_vacuna: " . $e->getMessage());
            $errores[] = "Error al obtener la mascota_vacuna";
        }
    }


    // Si no hay errores, editar la vacuna de la mascota
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // OBTENER NOMBRE DE LA MASCOTA PARA LA NOTIFICACIÓN
            $sql_pet = $conexion->prepare("SELECT nombre FROM mascotas WHERE id = :id_m");
            $sql_pet->bindParam(":id_m", $id_mascota, PDO::PARAM_INT);
            $sql_pet->execute();
            $pet_name = $sql_pet->fetchColumn() ?: "Mascota desconocida";

            // EDITAR VACUNA de la mascota
            $sql = $conexion->prepare("UPDATE mascota_vacunas SET vacuna_id = :id_vacuna, fecha_aplicacion = :fecha_aplicacion, proxima_dosis = :proxima_dosis WHERE id = :id_mascota_vacuna AND mascota_id = :id_mascota");
            $sql->bindValue(":id_mascota", $id_mascota, PDO::PARAM_INT);
            $sql->bindValue(":id_vacuna", $id_vacuna, PDO::PARAM_INT);
            $sql->bindValue(":fecha_aplicacion", $fecha_aplicacion, PDO::PARAM_STR);
            $sql->bindValue(":proxima_dosis", $proxima_dosis, $proxima_dosis === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $sql->bindValue(":id_mascota_vacuna", $id_mascota_vacuna, PDO::PARAM_INT);
            $sql->execute();

            // REGISTRAR NOTIFICACIÓN PARA RECEPCIONISTAS
            $sql_recep = $conexion->query("SELECT id, nombre FROM usuarios WHERE rol = 'recepcionista' AND estado = 'activo'");
            $recepcionistas = $sql_recep->fetchAll(PDO::FETCH_OBJ);

            if ($recepcionistas) {
                $titulo_not = "Vacuna Editada: " . ucfirst($pet_name);
                $mensaje_not = "El veterinario " . ucfirst($nombre_veterinario) . " ha editado una vacuna para " . ucfirst($pet_name) . ".";

                $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) VALUES (:uid, :tit, :msg, 'info')");

                foreach ($recepcionistas as $recep) {
                    $sql_notif->bindParam(":uid", $recep->id, PDO::PARAM_INT);
                    $sql_notif->bindParam(":tit", $titulo_not, PDO::PARAM_STR);
                    $sql_notif->bindParam(":msg", $mensaje_not, PDO::PARAM_STR);
                    $sql_notif->execute();
                }
            }

            $conexion->commit();

            $_SESSION['exito'] = "Registro de vacuna actualizado exitosamente";
            header("Location: ../../veterinario/editar_vacuna_mascota.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)) . "&id_mascota_vacuna=" . urlencode(Crypto::encrypt($id_mascota_vacuna)));
            exit;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al editar la vacuna: " . $e->getMessage());
            $errores[] = "Error al editar la vacuna";
            $_SESSION['errores'] = $errores;
            header("Location: ../../veterinario/editar_vacuna_mascota.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)) . "&id_mascota_vacuna=" . urlencode(Crypto::encrypt($id_mascota_vacuna)));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../veterinario/editar_vacuna_mascota.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)) . "&id_mascota_vacuna=" . urlencode(Crypto::encrypt($id_mascota_vacuna)));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Error al editar la vacuna"];
    header("Location: ../../veterinario/gestion_vacunacion_mascotas.php");
    exit;
}
