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
    // Obtener los datos del inventario
    $sql = $conexion->prepare("SELECT id, nombre_producto, stock, precio, estado 
                                FROM inventario 
                                ORDER BY stock ASC, nombre_producto ASC");
    $sql->execute();
    $productos = $sql->fetchAll(PDO::FETCH_OBJ);

    // Cálculos estadísticos
    $total_productos = count($productos);
    $valor_total = 0;
    foreach ($productos as $p) {
        if ($p->estado === 'activo') {
            $valor_total += ($p->precio * $p->stock);
        }
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
            <p class='report-title'>Reporte Detallado de Inventario</p>
            <p class='date-info'>Generado el: {$fecha_actual}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Estado</th>
                    <th>Stock</th>
                    <th>Precio Unit.</th>
                    <th>Valor en Stock</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($productos as $p) {
        $estado_clase = ($p->estado === 'activo') ? 'badge-success' : 'badge-danger';
        
        $stock_clase = '';
        if ($p->stock <= 0) {
            $stock_clase = 'badge-danger';
        } elseif ($p->stock <= 10) {
            $stock_clase = 'badge-warning';
        } else {
            $stock_clase = 'badge-success';
        }

        $subtotal = $p->precio * $p->stock;

        $html .= "
            <tr>
                <td>P-{$p->id}</td>
                <td><strong>" . htmlspecialchars($p->nombre_producto) . "</strong></td>
                <td class='align-center'><span class='badge {$estado_clase}'>" . ucfirst($p->estado) . "</span></td>
                <td class='align-center'><span class='badge {$stock_clase}'>{$p->stock} unidades</span></td>
                <td class='align-right'>$" . number_format($p->precio, 2) . "</td>
                <td class='align-right'><strong>$" . number_format($subtotal, 2) . "</strong></td>
            </tr>";
    }

    $html .= "
            </tbody>
        </table>

        <div class='total-box'>
            <span class='total-label'>Total Productos:</span> " . $total_productos . " | 
            <span class='total-label'>VALOR TOTAL INVENTARIO:</span> 
            <span class='total-amount'>$" . number_format($valor_total, 2) . "</span>
        </div>

        <div class='footer'>
            Página <span class='page-number'></span> | Control de Existencias Veterinaria
        </div>
    </body>
    </html>";

    // Inicializar Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Salida del archivo
    $dompdf->stream("reporte_inventario_" . date('Ymd') . ".pdf", ["Attachment" => false]);

} catch (Exception $e) {
    error_log("Error al generar reporte de inventario: " . $e->getMessage());
    echo "Error al generar el reporte.";
}
