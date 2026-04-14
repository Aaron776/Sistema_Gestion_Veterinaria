<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../vendor/autoload.php';
require_once 'estilos_reporte.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit;
}

$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');

try {
    // Obtener las vacunas aplicadas
    $sql_str = "SELECT mv.fecha_aplicacion, mv.proxima_dosis, 
                       m.nombre as mascota, v.nombre as vacuna,
                       cl.nombre as dueño
                FROM mascota_vacunas mv
                INNER JOIN mascotas m ON mv.mascota_id = m.id
                INNER JOIN vacunas v ON mv.vacuna_id = v.id
                INNER JOIN clientes cl ON m.cliente_id = cl.id
                WHERE mv.fecha_aplicacion BETWEEN :desde AND :hasta
                ORDER BY mv.fecha_aplicacion DESC";
    
    $sql = $conexion->prepare($sql_str);
    $sql->bindParam(':desde', $fecha_desde);
    $sql->bindParam(':hasta', $fecha_hasta);
    $sql->execute();
    $vacunas = $sql->fetchAll(PDO::FETCH_OBJ);

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
            <p class='report-title'>Reporte de Vacunación</p>
            <p class='date-info'>Período: " . date('d/m/Y', strtotime($fecha_desde)) . " al " . date('d/m/Y', strtotime($fecha_hasta)) . "</p>
            <p class='date-info'>Generado el: {$fecha_actual}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Fecha Aplicación</th>
                    <th>Paciente</th>
                    <th>Vacuna</th>
                    <th>Dueño</th>
                    <th>Próxima Dosis</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($vacunas as $v) {
        $proxima = ($v->proxima_dosis) ? date('d/m/Y', strtotime($v->proxima_dosis)) : 'N/A';
        $dosis_clase = ($v->proxima_dosis && strtotime($v->proxima_dosis) < time()) ? 'color: #e74c3c; font-weight:bold;' : '';

        $html .= "
            <tr>
                <td>" . date('d/m/Y', strtotime($v->fecha_aplicacion)) . "</td>
                <td><strong>" . htmlspecialchars($v->mascota) . "</strong></td>
                <td>" . htmlspecialchars($v->vacuna) . "</td>
                <td>" . htmlspecialchars($v->dueño) . "</td>
                <td class='align-center' style='{$dosis_clase}'>{$proxima}</td>
            </tr>";
    }

    if (empty($vacunas)) {
        $html .= "<tr><td colspan='5' class='align-center'>No se registraron vacunas en este período.</td></tr>";
    }

    $html .= "
            </tbody>
        </table>

        <div class='total-box'>
            <span class='total-label'>Total Vacunas Aplicadas:</span> 
            <span class='total-amount'>" . count($vacunas) . "</span>
        </div>

        <div class='footer'>
            Página <span class='page-number'></span> | Registro de Vacunación Preventiva
        </div>
    </body>
    </html>";

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("reporte_vacunacion_" . date('Ymd') . ".pdf", ["Attachment" => false]);

} catch (Exception $e) {
    error_log("Error al generar reporte de vacunas: " . $e->getMessage());
    echo "Error al generar el reporte.";
}
