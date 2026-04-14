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
    // 1. Obtener ingresos por servicios (desde detalle_factura)
    $sql_serv = $conexion->prepare("SELECT SUM(subtotal) as total 
                                   FROM detalle_factura df
                                   INNER JOIN facturas f ON df.factura_id = f.id
                                   WHERE f.estado = 'pagada' AND df.servicio_id IS NOT NULL
                                   AND DATE(f.fecha) BETWEEN :desde AND :hasta");
    $sql_serv->bindParam(':desde', $fecha_desde);
    $sql_serv->bindParam(':hasta', $fecha_hasta);
    $sql_serv->execute();
    $ingresos_servicios = $sql_serv->fetch(PDO::FETCH_OBJ)->total ?? 0;

    // 2. Obtener ingresos por productos (desde detalle_factura)
    $sql_prod = $conexion->prepare("SELECT SUM(subtotal) as total 
                                   FROM detalle_factura df
                                   INNER JOIN facturas f ON df.factura_id = f.id
                                   WHERE f.estado = 'pagada' AND df.producto_id IS NOT NULL
                                   AND DATE(f.fecha) BETWEEN :desde AND :hasta");
    $sql_prod->bindParam(':desde', $fecha_desde);
    $sql_prod->bindParam(':hasta', $fecha_hasta);
    $sql_prod->execute();
    $ingresos_productos = $sql_prod->fetch(PDO::FETCH_OBJ)->total ?? 0;

    // 3. Obtener listado de últimas facturas pagadas
    $sql_fact = $conexion->prepare("SELECT f.id, f.fecha, f.total, c.nombre as cliente, 
                                          (SELECT GROUP_CONCAT(metodo_pago) FROM pagos WHERE factura_id = f.id) as metodos
                                   FROM facturas f
                                   INNER JOIN clientes c ON f.cliente_id = c.id
                                   WHERE f.estado = 'pagada'
                                   AND DATE(f.fecha) BETWEEN :desde AND :hasta
                                   ORDER BY f.fecha DESC");
    $sql_fact->bindParam(':desde', $fecha_desde);
    $sql_fact->bindParam(':hasta', $fecha_hasta);
    $sql_fact->execute();
    $facturas = $sql_fact->fetchAll(PDO::FETCH_OBJ);

    $total_general = $ingresos_servicios + $ingresos_productos;

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
            <p class='report-title'>Reporte Financiero y de Ventas</p>
            <p class='date-info'>Período: " . date('d/m/Y', strtotime($fecha_desde)) . " al " . date('d/m/Y', strtotime($fecha_hasta)) . "</p>
            <p class='date-info'>Generado el: {$fecha_actual}</p>
        </div>

        <div style='margin-bottom: 30px;'>
            <h3 style='color: #2c5a6e; border-bottom: 1px solid #eef2f8; padding-bottom: 5px;'>Resumen de Ingresos</h3>
            <table style='width: 50%;'>
                <tr>
                    <td>Ingresos por Servicios:</td>
                    <td class='align-right'>$" . number_format($ingresos_servicios, 2) . "</td>
                </tr>
                <tr>
                    <td>Ingresos por Productos:</td>
                    <td class='align-right'>$" . number_format($ingresos_productos, 2) . "</td>
                </tr>
                <tr style='background: #f8fbfe;'>
                    <td><strong>TOTAL INGRESOS:</strong></td>
                    <td class='align-right'><strong style='color: #1f3e4b; font-size: 14px;'>$" . number_format($total_general, 2) . "</strong></td>
                </tr>
            </table>
        </div>

        <h3 style='color: #2c5a6e; border-bottom: 1px solid #eef2f8; padding-bottom: 5px;'>Detalle de Transacciones (Facturas Pagadas)</h3>
        <table>
            <thead>
                <tr>
                    <th>ID Factura</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Métodos de Pago</th>
                    <th>Monto Total</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($facturas as $f) {
        $html .= "
            <tr>
                <td>FAC-{$f->id}</td>
                <td>" . date('d/m/Y H:i', strtotime($f->fecha)) . "</td>
                <td>" . htmlspecialchars($f->cliente) . "</td>
                <td>" . htmlspecialchars(ucfirst($f->metodos ?? 'efectivo')) . "</td>
                <td class='align-right'><strong>$" . number_format($f->total, 2) . "</strong></td>
            </tr>";
    }

    if (empty($facturas)) {
        $html .= "<tr><td colspan='5' class='align-center'>No se registraron transacciones pagadas en este período.</td></tr>";
    }

    $html .= "
            </tbody>
        </table>

        <div class='total-box'>
            <span class='total-label'>RESULTADO NETO DEL PERÍODO:</span> 
            <span class='total-amount'>$" . number_format($total_general, 2) . "</span>
        </div>

        <div class='footer'>
            Página <span class='page-number'></span> | Reporte de Ingresos Veterinarios
        </div>
    </body>
    </html>";

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("reporte_financiero_" . date('Ymd') . ".pdf", ["Attachment" => false]);

} catch (Exception $e) {
    error_log("Error al generar reporte financiero: " . $e->getMessage());
    echo "Error al generar el reporte.";
}
