<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['id_usuario'];

try {
    // 1. Contar no leídas
    $sql_count = $conexion->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = :uid AND leida = 'no'");
    $sql_count->execute([':uid' => $usuario_id]);
    $no_leidas = $sql_count->fetchColumn();

    // 2. Obtener las últimas 5 notificaciones
    $sql_list = $conexion->prepare("SELECT id, titulo, mensaje, tipo, leida, fecha_creacion 
                                    FROM notificaciones 
                                    WHERE usuario_id = :uid 
                                    ORDER BY fecha_creacion DESC 
                                    LIMIT 5");
    $sql_list->execute([':uid' => $usuario_id]);
    $notificaciones = $sql_list->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'unleidas' => (int)$no_leidas,
        'listado' => $notificaciones
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
