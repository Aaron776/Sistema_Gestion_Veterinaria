<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$usuario_id = $_SESSION['id_usuario'];

try {
    $sql = $conexion->prepare("UPDATE notificaciones SET leida = 'si' WHERE usuario_id = :uid AND leida = 'no'");
    $sql->execute([':uid' => $usuario_id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Notificaciones marcadas como leídas'
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
