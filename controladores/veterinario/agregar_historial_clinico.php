<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo veterinario puede agregar historial clinico)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'veterinario') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_mascota']) && isset($_POST['id_cita']) && isset($_POST['diagnostico']) && isset($_POST['tratamiento']) && isset($_POST['observaciones']) && isset($_POST['peso']) && isset($_POST['sintomas'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_veterinario = $_SESSION['id_usuario'];
    $id_mascota = trim(urldecode(Crypto::decrypt($_POST['id_mascota'])));
    $id_cita = trim(urldecode(Crypto::decrypt($_POST['id_cita'])));
    $diagnostico = trim($_POST['diagnostico']);
    $tratamiento = trim($_POST['tratamiento']);
    $observaciones = trim($_POST['observaciones']);
    $peso = trim($_POST['peso']);
    $sintomas = trim($_POST['sintomas']);
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_cita)) {
        $errores[] = "El id de la cita es requerido";
    } elseif (!is_numeric($id_cita)) {
        $errores[] = "El id de la cita debe ser un número";
    } elseif ($id_cita <= 0) {
        $errores[] = "El id de la cita debe ser mayor a 0";
    }

    if (empty($id_veterinario)) {
        $errores[] = "El id del veterinario es requerido";
    } elseif (!is_numeric($id_veterinario)) {
        $errores[] = "El id del veterinario debe ser un número";
    } elseif ($id_veterinario <= 0) {
        $errores[] = "El id del veterinario debe ser mayor a 0";
    }

    if (empty($id_mascota)) {
        $errores[] = "El id de la mascota es requerido";
    } elseif (!is_numeric($id_mascota)) {
        $errores[] = "El id de la mascota debe ser un número";
    } elseif ($id_mascota <= 0) {
        $errores[] = "El id de la mascota debe ser mayor a 0";
    }

    if (empty($diagnostico)) {
        $errores[] = "El diagnostico es requerido";
    } elseif (strlen($diagnostico) < 1) {
        $errores[] = "El diagnostico debe tener al menos 1 caracter";
    } elseif (strlen($diagnostico) > 10000) {
        $errores[] = "El diagnostico no debe exceder los 10,000 caracteres";
    }

    if (empty($tratamiento)) {
        $errores[] = "El tratamiento es requerido";
    } elseif (strlen($tratamiento) < 1) {
        $errores[] = "El tratamiento debe tener al menos 1 caracter";
    } elseif (strlen($tratamiento) > 10000) {
        $errores[] = "El tratamiento no debe exceder los 10,000 caracteres";
    }

    if (empty($observaciones)) {
        $errores[] = "Las observaciones son requeridas";
    } elseif (strlen($observaciones) < 1) {
        $errores[] = "Las observaciones deben tener al menos 1 caracter";
    } elseif (strlen($observaciones) > 10000) {
        $errores[] = "Las observaciones no deben exceder los 10,000 caracteres";
    }

    if ($peso == "") {
        $errores[] = "El peso es requerido";
    } elseif (!is_numeric($peso)) {
        $errores[] = "El peso debe ser un formato válido (ej. 24.5)";
    } elseif ($peso <= 0 || $peso > 999.99) {
        $errores[] = "El peso debe ser mayor a 0 y menor a 1,000 kg";
    }

    if (empty($sintomas)) {
        $errores[] = "Los sintomas son requeridos";
    } elseif (strlen($sintomas) < 1) {
        $errores[] = "Los sintomas deben tener al menos 1 caracter";
    } elseif (strlen($sintomas) > 10000) {
        $errores[] = "Los sintomas no deben exceder los 10,000 caracteres";
    }


    // Si no hay errores, registrar historial clinico
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // OBTENER NOMBRE DE LA MASCOTA PARA LA NOTIFICACIÓN
            $sql_pet = $conexion->prepare("SELECT nombre FROM mascotas WHERE id = :id_m");
            $sql_pet->bindParam(":id_m", $id_mascota, PDO::PARAM_INT);
            $sql_pet->execute();
            $pet_name = $sql_pet->fetchColumn() ?: "Mascota desconocida";

            // REGISTRAR HISTORIAL CLINICO
            $sql = $conexion->prepare("INSERT INTO historial_clinico (mascota_id, veterinario_id, cita_id, peso, sintomas, diagnostico, tratamiento, observaciones) VALUES (:id_mascota, :id_veterinario, :id_cita, :peso, :sintomas, :diagnostico, :tratamiento, :observaciones)");
            $sql->bindParam(":id_mascota", $id_mascota, PDO::PARAM_INT);
            $sql->bindParam(":id_veterinario", $id_veterinario, PDO::PARAM_INT);
            $sql->bindParam(":id_cita", $id_cita, PDO::PARAM_INT);
            $sql->bindParam(":peso", $peso, PDO::PARAM_STR);
            $sql->bindParam(":sintomas", $sintomas, PDO::PARAM_STR);
            $sql->bindParam(":diagnostico", $diagnostico, PDO::PARAM_STR);
            $sql->bindParam(":tratamiento", $tratamiento, PDO::PARAM_STR);
            $sql->bindParam(":observaciones", $observaciones, PDO::PARAM_STR);
            $sql->execute();

            // REGISTRAR NOTIFICACIÓN PARA RECEPCIONISTAS
            $sql_recep = $conexion->query("SELECT id FROM usuarios WHERE rol = 'recepcionista' AND estado = 'activo'");
            $recepcionistas = $sql_recep->fetchAll(PDO::FETCH_OBJ);

            if ($recepcionistas) {
                $titulo_not = "Nuevo Registro Clínico: " . ucfirst($pet_name);
                $mensaje_not = "El veterinario ha registrado un nuevo historial para " . ucfirst($pet_name) . " (Cita #$id_cita). Ya puede proceder con el flujo de cierre.";

                $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) VALUES (:uid, :tit, :msg, 'info')");

                foreach ($recepcionistas as $recep) {
                    $sql_notif->bindParam(":uid", $recep->id, PDO::PARAM_INT);
                    $sql_notif->bindParam(":tit", $titulo_not, PDO::PARAM_STR);
                    $sql_notif->bindParam(":msg", $mensaje_not, PDO::PARAM_STR);
                    $sql_notif->execute();
                }
            }

            $conexion->commit();

            $_SESSION['exito'] = "Historial clinico registrado exitosamente";
            header("Location: ../../veterinario/agregar_historial_clinico.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)) . "&id_cita=" . urlencode(Crypto::encrypt($id_cita)));
            exit;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al registrar el historial clinico: " . $e->getMessage());
            $errores[] = "Error al registrar el historial clinico";
            $_SESSION['errores'] = $errores;
            header("Location: ../../veterinario/agregar_historial_clinico.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)) . "&id_cita=" . urlencode(Crypto::encrypt($id_cita)));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../veterinario/agregar_historial_clinico.php?id_mascota=" . urlencode(Crypto::encrypt($id_mascota)) . "&id_cita=" . urlencode(Crypto::encrypt($id_cita)));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Error al registrar el historial clinico"];
    header("Location: ../../veterinario/gestion_citas_confirmadas.php");
    exit;
}
