<?php
require_once '../../conexion/bd.php';
require_once '../../conexion/session.php';
require_once '../../helpers/Encriptar.php';
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$env = parse_ini_file(__DIR__ . '/../../.env'); // Carga las variables de entorno

// Verificar que tenga rol de admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_usuario'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    //Recibir datos
    $id_usuario = Crypto::decrypt(trim($_POST['id_usuario']));
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_usuario)) {
        $errores[] = "El id del usuario es obligatorio";
    } elseif (!is_numeric($id_usuario)) {
        $errores[] = "El id del usuario debe ser numerico";
    } elseif ($id_usuario <= 0) {
        $errores[] = "El id del usuario debe ser mayor a cero";
    }

    // Verificar que el usuario a editar l existe Y está activo en la base de datos
    // (No se deben poder editar la contraseña de usuarios que han sido desactivados/eliminados)
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id,email FROM usuarios WHERE id=:id AND estado='activo' LIMIT 1");
            $sql->bindParam(":id", $id_usuario, PDO::PARAM_INT);
            $sql->execute();
            $usuario_existe = $sql->fetch(PDO::FETCH_OBJ);
            if (!$usuario_existe) {
                $errores[] = "El usuario no existe o ha sido desactivado y no puede editar la contraseña.";
            }
        } catch (PDOException $e) {
            $errores[] = "Error de base de datos al verificar existencia: " . $e->getMessage();
        }
    }

    // Si no hay errores procedemos a editar la contraseña del usuario
    if (empty($errores)) {
        // Generar contraseña aleatoria segura de 8 caracteres
        $nuevaPassword = substr(bin2hex(random_bytes(4)), 0, 8);
        $hashPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);

        try {
            $conexion->beginTransaction();

            // 1. Actualizar contraseña en BD
            $sql = $conexion->prepare("UPDATE usuarios SET password=:password WHERE id=:id_usuario");
            $sql->bindParam(":password", $hashPassword, PDO::PARAM_STR);
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();

            // 2. Registrar Notificación al Usuario Afectado
            $titulo = "Actualización de Contraseña";
            $mensaje_audit = "El administrador " . $_SESSION['nombre'] . " ha restablecido tu contraseña del sistema.";
            $usuario_destino = $usuario_existe->id;
            $tipo = "info";

            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) 
                                           VALUES (:id_usuario, :titulo, :mensaje, :tipo)");
            $sql_notif->bindParam(":id_usuario", $usuario_destino, PDO::PARAM_INT);
            $sql_notif->bindParam(":titulo", $titulo, PDO::PARAM_STR);
            $sql_notif->bindParam(":mensaje", $mensaje_audit, PDO::PARAM_STR);
            $sql_notif->bindParam(":tipo", $tipo, PDO::PARAM_STR);
            $sql_notif->execute();

            // 3. Enviar correo con PHPMailer
            $mail = new PHPMailer(true);

            // Configuramos PHPMailer
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $env['MAIL_USER'];
            $mail->Password   = $env['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Opciones para evitar errores de certificado SSL en entorno local (XAMPP)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->setFrom($env['MAIL_USER'], 'Sistema de Gestion Veterinaria');
            $mail->addAddress($usuario_existe->email);

            $mail->isHTML(true);
            $mail->Subject = 'Nueva Contraseña - Sistema de Gestión Veterinaria';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; border: 1px solid #eef2f8; padding: 25px; border-radius: 12px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h2 style='color: #2c5a6e; font-size: 1.8rem; margin: 0;'>Sistema Veterinario</h2>
                    </div>
                    <h3 style='color: #1f3e4b; border-bottom: 2px solid #6bcb77; padding-bottom: 10px;'>Restablecimiento de Contraseña</h3>
                    <p style='color: #2c3e50; font-size: 1.05rem;'>Hola,</p>
                    <p style='color: #2c3e50; font-size: 1.05rem;'>Un administrador ha restablecido tu contraseña de acceso al sistema mediante solicitud.</p>
                    <div style='background: #f8fbfe; padding: 15px 20px; border-left: 5px solid #6bcb77; border-radius: 4px; margin: 25px 0;'>
                        <p style='margin: 0; font-size: 1.1rem; color: #2c5a6e;'>Tu nueva contraseña temporal es:</p>
                        <p style='margin: 10px 0 0 0; font-size: 1.5rem; font-weight: bold; color: #1f3e4b; letter-spacing: 2px;'>{$nuevaPassword}</p>
                    </div>
                    <p style='color: #e74c3c; font-size: 0.95rem; font-weight: 600;'><i style='color: #f39c12;'>⚠️</i> Te recomendamos iniciar sesión y cambiarla inmediatamente por razones de seguridad.</p>
                    <hr style='border: none; border-top: 1px solid #eef2f8; margin: 30px 0;'>
                    <p style='color: #8ba0b0; font-size: 0.85rem; text-align: center; margin: 0;'><small>Este es un correo automático, por favor no respondas.</small></p>
                </div>
            ";

            $mail->send();

            // Si llegamos aquí, todo salió bien
            $conexion->commit();

            $_SESSION['exito'] = "Contraseña actualizada y enviada correctamente a: " . $usuario_existe->email;
            header("Location: ../../admin/gestion_usuarios.php");
            exit();
        } catch (Exception $e) {
            // Si el correo falla o la BD falla, revertimos TODO
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error en Reset Password para {$usuario_existe->email}: " . $e->getMessage());

            // Detectar si el error fue de PHPMailer
            $msg_error = "Error al procesar la solicitud.";
            if (isset($mail) && !empty($mail->ErrorInfo)) {
                $msg_error = "Error al enviar el correo. Detalle técnico: " . $mail->ErrorInfo;
            } else {
                $msg_error = "Error: " . $e->getMessage();
            }

            $_SESSION['errores'] = [$msg_error];
            header("Location: ../../admin/gestion_usuarios.php");
            exit();
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/gestion_usuarios.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Error en el envio del formulario"];
    header("Location: ../../admin/gestion_usuarios.php");
    exit;
}
