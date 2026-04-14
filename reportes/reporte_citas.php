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

// Parámetros de filtros
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');

try {
    // Obtener las citas en el rango
    $sql_str = "SELECT c.id, c.fecha_hora, c.motivo, c.estado, 
                       m.nombre as mascota, cl.nombre as dueño,
                       v.nombre as veterinario
                FROM citas c
                INNER JOIN mascotas m ON c.mascota_id = m.id
                INNER JOIN clientes cl ON m.cliente_id = cl.id
                LEFT JOIN usuarios v ON c.veterinario_id = v.id
                WHERE DATE(c.fecha_hora) BETWEEN :desde AND :hasta
                ORDER BY c.fecha_hora ASC";
    
    $sql = $conexion->prepare($sql_str);
    $sql->bindParam(':desde', $fecha_desde);
    $sql->bindParam(':hasta', $fecha_hasta);
    $sql->execute();
    $citas = $sql->fetchAll(PDO::FETCH_OBJ);

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
            <p class='report-title'>Reporte de Citas Médicas</p>
            <p class='date-info'>Período: " . date('d/m/Y', strtotime($fecha_desde)) . " al " . date('d/m/Y', strtotime($fecha_hasta)) . "</p>
            <p class='date-info'>Generado el: {$fecha_actual}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Fecha / Hora</th>
                    <th>Paciente</th>
                    <th>Dueño</th>
                    <th>Veterinario</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($citas as $c) {
        $estado_clase = '';
        switch($c->estado) {
            case 'realizada': $estado_clase = 'badge-success'; break;
            case 'pendiente': $estado_clase = 'badge-warning'; break;
            case 'cancelada': $estado_clase = 'badge-danger'; break;
            case 'confirmada': $estado_clase = 'badge-primary'; break;
        }

        $html .= "
            <tr>
                <td style='white-space:nowrap;'>" . date('d/m/Y H:i', strtotime($c->fecha_hora)) . "</td>
                <td><strong>" . htmlspecialchars($c->mascota) . "</strong></td>
                <td>" . htmlspecialchars($c->dueño) . "</td>
                <td>" . htmlspecialchars($c->veterinario ?? 'No asignado') . "</td>
                <td style='font-size: 9px;'>" . htmlspecialchars(substr($c->motivo, 0, 50)) . "...</td>
                <td class='align-center'><span class='badge {$estado_clase}'>" . ucfirst($c->estado) . "</span></td>
            </tr>";
    }

    if (empty($citas)) {
        $html .= "<tr><td colspan='6' class='align-center'>No se encontraron citas en este período.</td></tr>";
    }

    $html .= "
            </tbody>
        </table>

        <div class='total-box'>
            <span class='total-label'>Total de Citas en el período:</span> 
            <span class='total-amount'>" . count($citas) . "</span>
        </div>

        <div class='footer'>
            Página <span class='page-number'></span> | Registro de Citas Veterinarias
        </div>
    </body>
    </html>";

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream("reporte_citas_" . date('Ymd') . ".pdf", ["Attachment" => false]);

} catch (Exception $e) {
    error_log("Error al generar reporte de citas: " . $e->getMessage());
    echo "Error al generar el reporte.";
}
