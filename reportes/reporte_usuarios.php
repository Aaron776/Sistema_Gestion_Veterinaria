<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../vendor/autoload.php';
require_once 'estilos_reporte.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Verificar que tenga rol de admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit;
}

try {
    // Obtener los datos
    $sql = $conexion->prepare("SELECT id, nombre, cedula, email, telefono, rol, estado, fecha_creacion, ultimo_acceso 
                                FROM usuarios 
                                ORDER BY rol ASC, nombre ASC");
    $sql->execute();
    $usuarios = $sql->fetchAll(PDO::FETCH_OBJ);

    // Preparar el HTML
    date_default_timezone_set('America/Bogota');
    $fecha_actual = date('d/m/Y H:i A');

    $html = "
    <html>
    <head>
        <style>{$estilos_reporte}</style>
    </head>
    <body>
        <div class='header'>
            <h1 class='clinic-name'>SISTEMA VETERINARIO</h1>
            <p class='report-title'>Reporte General de Usuarios</p>
            <p class='date-info'>Generado el: {$fecha_actual}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Cédula/ID</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Último Acceso</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($usuarios as $u) {
        $rol_clase = '';
        $rol_nombre = '';
        switch($u->rol) {
            case 'admin': $rol_clase = 'badge-primary'; $rol_nombre = 'Admin'; break;
            case 'veterinario': $rol_clase = 'badge-success'; $rol_nombre = 'Veterinario'; break;
            case 'recepcionista': $rol_clase = 'badge-warning'; $rol_nombre = 'Recep'; break;
        }
        
        $estado_clase = ($u->estado === 'activo') ? 'badge-success' : 'badge-danger';
        $ultimo_acceso = ($u->ultimo_acceso) ? date('d/m/y H:i', strtotime($u->ultimo_acceso)) : 'Nunca';

        $html .= "
            <tr>
                <td>U-{$u->id}</td>
                <td><strong>" . htmlspecialchars($u->nombre) . "</strong></td>
                <td>" . htmlspecialchars($u->cedula) . "</td>
                <td>" . htmlspecialchars($u->email) . "</td>
                <td>" . htmlspecialchars($u->telefono) . "</td>
                <td class='align-center'><span class='badge {$rol_clase}'>{$rol_nombre}</span></td>
                <td class='align-center'><span class='badge {$estado_clase}'>" . ucfirst($u->estado) . "</span></td>
                <td>{$ultimo_acceso}</td>
            </tr>";
    }

    $html .= "
            </tbody>
        </table>

        <div class='footer'>
            Página <span class='page-number'></span> | Sistema de Gestión Veterinaria
        </div>
    </body>
    </html>";

    // Inicializar Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    // Salida del archivo
    $dompdf->stream("reporte_usuarios_" . date('Ymd') . ".pdf", ["Attachment" => false]);

} catch (Exception $e) {
    error_log("Error al generar reporte de usuarios: " . $e->getMessage());
    echo "Error al generar el reporte: " . $e->getMessage();
}
