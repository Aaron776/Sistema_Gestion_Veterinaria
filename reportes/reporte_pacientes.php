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

try {
    // Obtener los pacientes
    $sql_str = "SELECT m.id, m.nombre as mascota, m.especie, m.raza, m.sexo, 
                       c.nombre as dueño, c.telefono, m.fecha_creacion
                FROM mascotas m
                INNER JOIN clientes c ON m.cliente_id = c.id
                ORDER BY m.especie ASC, m.nombre ASC";
    
    $sql = $conexion->prepare($sql_str);
    $sql->execute();
    $pacientes = $sql->fetchAll(PDO::FETCH_OBJ);

    // Conteo por especie
    $conteo = [];
    foreach ($pacientes as $p) {
        $esp = ucfirst(strtolower($p->especie));
        $conteo[$esp] = ($conteo[$esp] ?? 0) + 1;
    }

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
            <p class='report-title'>Reporte General de Pacientes (Mascotas)</p>
            <p class='date-info'>Generado el: {$fecha_actual}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Paciente</th>
                    <th>Especie</th>
                    <th>Raza</th>
                    <th>Sexo</th>
                    <th>Dueño / Contacto</th>
                    <th>Desde</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($pacientes as $p) {
        $sexo = ($p->sexo === 'M') ? 'Macho' : 'Hembra';
        $html .= "
            <tr>
                <td>M-{$p->id}</td>
                <td><strong>" . htmlspecialchars($p->mascota) . "</strong></td>
                <td>" . htmlspecialchars(ucfirst($p->especie)) . "</td>
                <td>" . htmlspecialchars($p->raza) . "</td>
                <td class='align-center'>{$sexo}</td>
                <td>" . htmlspecialchars($p->dueño) . "<br><small style='color:#7f8c8d'>" . htmlspecialchars($p->telefono) . "</small></td>
                <td>" . date('d/m/y', strtotime($p->fecha_creacion)) . "</td>
            </tr>";
    }

    $resumen_html = "";
    foreach ($conteo as $esp => $cant) {
        $resumen_html .= " | <span class='total-label'>{$esp}s:</span> {$cant}";
    }

    $html .= "
            </tbody>
        </table>

        <div class='total-box' style='text-align: left;'>
            <span class='total-label'>Total Pacientes:</span> " . count($pacientes) . "
            {$resumen_html}
        </div>

        <div class='footer'>
            Página <span class='page-number'></span> | Registro de Pacientes Veterinaria
        </div>
    </body>
    </html>";

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("reporte_pacientes_" . date('Ymd') . ".pdf", ["Attachment" => false]);

} catch (Exception $e) {
    error_log("Error al generar reporte de pacientes: " . $e->getMessage());
    echo "Error al generar el reporte.";
}
