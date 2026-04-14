<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';

if(!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin'){
    header("Location: ../acceso_denegado.php");
    exit();
}

// Obtener la cantidad de citas para hoy agendadas
try {
    $hoy = date('Y-m-d');
    $sql = $conexion->prepare("SELECT COUNT(*) as total FROM citas WHERE DATE(fecha_hora) = :fecha AND estado = 'confirmada'");
    $sql->bindParam("fecha", $hoy, PDO::PARAM_STR);
    $sql->execute();
    $totalCitasHoy = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener las citas: " . $e->getMessage());
    exit();
}

// Obtener total de mascotas
try {
    $sql = $conexion->prepare("SELECT COUNT(*) as total FROM mascotas");
    $sql->execute();
    $totalMascotas = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener las mascotas: " . $e->getMessage());
    exit();
}

// Obtener cantidad de usaurios con rol veterinario
try {
    $sql = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'veterinario'");
    $sql->execute();
    $totalVeterinarios = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los veterinarios: " . $e->getMessage());
    exit();
}

// Obtener ingresos del mes actual
try {
    $primerDiaMes = date('Y-m-01');
    $ultimoDiaMes = date('Y-m-t');
    $sql = $conexion->prepare("SELECT SUM(total) as total FROM facturas WHERE estado = 'pagada' AND DATE(fecha) BETWEEN :inicio AND :fin");
    $sql->bindParam(":inicio", $primerDiaMes);
    $sql->bindParam(":fin", $ultimoDiaMes);
    $sql->execute();
    $ingresosMes = $sql->fetch(PDO::FETCH_OBJ);
    $totalIngresos = $ingresosMes->total ?? 0;
} catch (PDOException $e) {
    error_log("Error al obtener los ingresos: " . $e->getMessage());
    $totalIngresos = 0;
}

// Obtener las ultimas 5 citas confirmadas 
try {
    $sqlCitas = $conexion->prepare("
        SELECT c.fecha_hora, m.nombre as mascota, cl.nombre as dueno, u.nombre as veterinario, c.estado
        FROM citas c
        INNER JOIN mascotas m ON c.mascota_id = m.id
        INNER JOIN clientes cl ON m.cliente_id = cl.id
        LEFT JOIN usuarios u ON c.veterinario_id = u.id
        WHERE c.estado = 'confirmada'
        ORDER BY c.id DESC
        LIMIT 5
    ");
    $sqlCitas->execute();
    $ultimasCitas = $sqlCitas->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener las últimas citas: " . $e->getMessage());
    $ultimasCitas = [];
}

// Obtener datos para el gráfico de consultas por semana (últimos 7 días)
try {
    $sqlGrafico = $conexion->prepare("
        SELECT DATE(fecha_hora) as fecha, COUNT(*) as total 
        FROM citas 
        WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
        GROUP BY DATE(fecha_hora)
    ");
    $sqlGrafico->execute();
    $resultadosGrafico = $sqlGrafico->fetchAll(PDO::FETCH_ASSOC);

    $temp_datos = [];
    foreach($resultadosGrafico as $row) {
        $temp_datos[$row['fecha']] = $row['total'];
    }

    $labelsSemana = [];
    $datosSemana = [];
    $diasEsp = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-$i days"));
        $diaSemana = date('w', strtotime($fecha));
        $labelsSemana[] = $diasEsp[$diaSemana]; 
        $datosSemana[] = isset($temp_datos[$fecha]) ? (int)$temp_datos[$fecha] : 0;
    }
} catch (PDOException $e) {
    $labelsSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    $datosSemana = [0, 0, 0, 0, 0, 0, 0];
}

// Obtener mascotas recientes
try {
    $sqlMascotas = $conexion->prepare("
        SELECT m.nombre, m.especie, cl.nombre as dueno
        FROM mascotas m
        JOIN clientes cl ON m.cliente_id = cl.id
        ORDER BY m.id DESC
        LIMIT 5
    ");
    $sqlMascotas->execute();
    $ultimasMascotas = $sqlMascotas->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener mascotas recientes: " . $e->getMessage());
    $ultimasMascotas = [];
}

// Obtener datos para el gráfico de ingresos diarios (últimos 7 días)
try {
    $sqlIngresosGrafico = $conexion->prepare("
        SELECT DATE(fecha) as fecha, SUM(total) as total 
        FROM facturas 
        WHERE estado = 'pagada' AND fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
        GROUP BY DATE(fecha)
    ");
    $sqlIngresosGrafico->execute();
    $resultadosIngresos = $sqlIngresosGrafico->fetchAll(PDO::FETCH_ASSOC);

    $temp_ingresos = [];
    foreach($resultadosIngresos as $row) {
        $temp_ingresos[$row['fecha']] = $row['total'];
    }

    $datosIngresosSemana = [];
    for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-$i days"));
        $datosIngresosSemana[] = isset($temp_ingresos[$fecha]) ? (float)$temp_ingresos[$fecha] : 0;
    }
} catch (PDOException $e) {
    $datosIngresosSemana = [0, 0, 0, 0, 0, 0, 0];
}

include_once '../templates/header.php';
?>

<script>
    // Pasar datos al gráfico del footer
    window.chartLabels = <?php echo json_encode($labelsSemana); ?>;
    window.chartData = <?php echo json_encode($datosSemana); ?>;
    // Datos para el segundo gráfico (Ingresos)
    window.incomeData = <?php echo json_encode($datosIngresosSemana); ?>;
</script>

<div class="dashboard-container">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3>Pacientes Totales</h3>
                <div class="stat-number"><?php echo htmlspecialchars($totalMascotas->total); ?></div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-paw"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Citas Hoy</h3>
                <div class="stat-number"><?php echo htmlspecialchars($totalCitasHoy->total); ?></div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Ingresos (Mes)</h3>
                <div class="stat-number">$<?php echo htmlspecialchars(number_format($totalIngresos, 2)); ?></div>
                <small>Efectivo, Tarjeta, Transf.</small>
            </div>
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Veterinarios Activos</h3>
                <div class="stat-number"><?php echo htmlspecialchars($totalVeterinarios->total); ?></div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-stethoscope"></i>
            </div>
        </div>
    </div>

    <!-- Charts + Lista moderna -->
    <div class="row-grid">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-chart-simple" style="margin-right: 8px; color:#6bcb77;"></i> Consultas por semana</h4>
                <span style="font-size:12px; background:#f0f3f9; padding:4px 12px; border-radius:20px;">Últimos 7 días</span>
            </div>
            <canvas id="visitsChart" height="180" style="max-height:220px; width:100%"></canvas>
        </div>
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-clinic-medical" style="margin-right: 8px; color:#6bcb77;"></i> Próximas citas</h4>
                <i class="fas fa-ellipsis-h" style="color:#8aaec0;"></i>
            </div>
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>Mascota</th>
                        <th>Dueño</th>
                        <th>Hora</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimasCitas)) : ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding: 20px; color:#7f8c8d;">
                                No hay citas confirmadas pendientes.
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($ultimasCitas as $cita) : ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars(ucfirst($cita->mascota)); ?></strong></td>
                                <td><?php echo htmlspecialchars(ucfirst($cita->dueno)); ?></td>
                                <td><?php echo date('h:i A', strtotime($cita->fecha_hora)); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $cita->estado === 'pendiente' ? 'warning' : ''; ?>">
                                        <?php echo ucfirst($cita->estado); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>

    <!-- Segunda fila: pacientes recientes y recordatorios -->
    <div class="row-grid">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-user-plus"></i> Últimos pacientes registrados</h4>
                <i class="fas fa-plus-circle" style="color:#6bcb77; cursor:pointer;"></i>
            </div>
            <ul class="patient-list">
                <?php if (empty($ultimasMascotas)) : ?>
                    <li style="justify-content: center; color: #7f8c8d; padding: 20px;">
                        No hay pacientes registrados recientemente.
                    </li>
                <?php else : ?>
                    <?php foreach ($ultimasMascotas as $mascota) : ?>
                        <?php 
                            $especie = strtolower($mascota->especie);
                            $icono = 'fa-paw';
                            if ($especie === 'perro') $icono = 'fa-dog';
                            elseif ($especie === 'gato') $icono = 'fa-cat';
                            elseif ($especie === 'ave' || $especie === 'pajaro') $icono = 'fa-dove';
                            elseif ($especie === 'conejo') $icono = 'fa-rabbit';
                        ?>
                        <li>
                            <div class="patient-avatar"><i class="fas <?php echo $icono; ?>"></i></div>
                            <div>
                                <strong><?php echo htmlspecialchars(ucfirst($mascota->nombre)); ?></strong> 
                                <span style="font-size:0.7rem; color:#7f8c8d;">• <?php echo htmlspecialchars(ucfirst($mascota->especie)); ?></span>
                                <br>
                                <small>Dueño: <?php echo htmlspecialchars(ucfirst($mascota->dueno)); ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-hand-holding-dollar" style="color:#6bcb77;"></i> Ingresos diarios</h4>
                <span style="font-size:12px; color:#7f8c8d;">Última semana</span>
            </div>
            <div style="height: 220px; width: 100%;">
                <canvas id="incomeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabla de últimas atenciones o historial -->
    <div class="card" style="margin-top: 0px;">
        <div class="card-header">
            <h4><i class="fas fa-history"></i> Últimas atenciones médicas</h4>
            <span class="btn-small">Ver historial completo</span>
        </div>
        <div style="overflow-x: auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                <thead>
                    <tr style="border-bottom:1px solid #eef2f8;">
                        <th style="padding:12px 4px; text-align:left;">Paciente</th>
                        <th style="padding:12px 4px; text-align:left;">Servicio</th>
                        <th style="padding:12px 4px; text-align:left;">Veterinario</th>
                        <th style="padding:12px 4px; text-align:left;">Fecha</th>
                        <th style="padding:12px 4px; text-align:left;">Costo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding:12px 4px;">Luna (Felina)</td>
                        <td>Consulta general</td>
                        <td>Dra. Méndez</td>
                        <td>10/03/2025</td>
                        <td>$35</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 4px;">Max (Golden)</td>
                        <td>Vacunación múltiple</td>
                        <td>Dr. Rojas</td>
                        <td>09/03/2025</td>
                        <td>$48</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 4px;">Coco (Loro)</td>
                        <td>Revisión de plumaje</td>
                        <td>Dra. Méndez</td>
                        <td>08/03/2025</td>
                        <td>$42</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 4px;">Toby (Beagle)</td>
                        <td>Urgencias</td>
                        <td>Dr. Sánchez</td>
                        <td>07/03/2025</td>
                        <td>$120</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include_once '../templates/footer.php';
?>