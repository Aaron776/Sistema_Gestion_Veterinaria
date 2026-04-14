<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';


if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Parámetros de filtrado
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-01-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-12-31');
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : 'todos';

// Función auxiliar para construir enlaces con filtros
function query_report($base) {
    global $fecha_desde, $fecha_hasta;
    return $base . "?desde=" . urlencode($fecha_desde) . "&hasta=" . urlencode($fecha_hasta);
}

//Obtener el total de usuarios en estado activo 
$sql = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'");
$sql->execute();
$total_usuarios = $sql->fetch(PDO::FETCH_OBJ);

// Obtener total de usuarios activos con rol admin
$sql = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo' AND rol = 'admin'");
$sql->execute();
$total_admin = $sql->fetch(PDO::FETCH_OBJ);

// Obtener total de usuarios activos con rol veterinario
$sql = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo' AND rol = 'veterinario'");
$sql->execute();
$total_vet = $sql->fetch(PDO::FETCH_OBJ);

// Obtener total de productos
$sql = $conexion->prepare("SELECT COUNT(*) as total FROM inventario");
$sql->execute();
$total_productos = $sql->fetch(PDO::FETCH_OBJ);

// Obtener total de productos con stock bajo
$sql = $conexion->prepare("SELECT COUNT(*) as total FROM inventario WHERE stock <= 10");
$sql->execute();
$productos_bajo_stock = $sql->fetch(PDO::FETCH_OBJ);

// Obtener valor total del inventario
$sql = $conexion->prepare("SELECT SUM(precio * stock) as total FROM inventario");
$sql->execute();
$valor_inventario = $sql->fetch(PDO::FETCH_OBJ);

// Obtener total de citas
$sql = $conexion->prepare("SELECT COUNT(*) as total FROM citas");
$sql->execute();
$total_citas = $sql->fetch(PDO::FETCH_OBJ);

// Obtener total de citas pendientes
$sql = $conexion->prepare("SELECT COUNT(*) as total FROM citas WHERE estado = 'pendiente'");
$sql->execute();
$citas_pendientes = $sql->fetch(PDO::FETCH_OBJ);

// Obtener total de citas realizadas
$sql = $conexion->prepare("SELECT COUNT(*) as total FROM citas WHERE estado = 'realizada'");
$sql->execute();
$citas_realizadas = $sql->fetch(PDO::FETCH_OBJ);

// Obtener total de mascotas
$sql = $conexion->prepare("SELECT COUNT(*) as total FROM mascotas");
$sql->execute();
$total_mascotas = $sql->fetch(PDO::FETCH_OBJ);

// Obtener total de pacientes perros
$sql = $conexion->prepare("SELECT COUNT(*) as total FROM mascotas WHERE LOWER(especie) = 'perro'");
$sql->execute();
$total_perros = $sql->fetch(PDO::FETCH_OBJ);

// Obtener total de pacientes gatos
$sql = $conexion->prepare("SELECT COUNT(*) as total FROM mascotas WHERE LOWER(especie) = 'gato'");
$sql->execute();
$total_gatos = $sql->fetch(PDO::FETCH_OBJ);

// Obtener total de vacunas aplicadas
$sql = $conexion->prepare("SELECT COUNT(*) as total FROM mascota_vacunas");
$sql->execute();
$vacunas_aplicadas = $sql->fetch(PDO::FETCH_OBJ);


include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/admin/reporte.css">
<div class="reports-container">
    <div class="page-header">
        <h2>
            <i class="fas fa-chart-line"></i>
            Reportes
        </h2>
        <p>Genere reportes detallados de su clínica veterinaria en formato PDF</p>
    </div>

    <!-- Filtros de fecha -->
    <form method="GET" action="reportes.php" class="filters-section">
        <div class="filter-group">
            <label><i class="fas fa-calendar-alt"></i> Fecha desde</label>
            <input type="date" name="desde" id="fechaDesde" value="<?php echo htmlspecialchars($fecha_desde); ?>">
        </div>
        <div class="filter-group">
            <label><i class="fas fa-calendar-alt"></i> Fecha hasta</label>
            <input type="date" name="hasta" id="fechaHasta" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
        </div>
        <div class="filter-group">
            <label><i class="fas fa-chart-simple"></i> Filtrar por</label>
            <select name="categoria" id="filtroCategoria">
                <option value="todos" <?php echo $categoria_filtro === 'todos' ? 'selected' : ''; ?>>Todos los reportes</option>
                <option value="usuarios" <?php echo $categoria_filtro === 'usuarios' ? 'selected' : ''; ?>>Usuarios</option>
                <option value="productos" <?php echo $categoria_filtro === 'productos' ? 'selected' : ''; ?>>Productos</option>
                <option value="citas" <?php echo $categoria_filtro === 'citas' ? 'selected' : ''; ?>>Citas</option>
                <option value="financiero" <?php echo $categoria_filtro === 'financiero' ? 'selected' : ''; ?>>Financiero</option>
                <option value="pacientes" <?php echo $categoria_filtro === 'pacientes' ? 'selected' : ''; ?>>Pacientes</option>
                <option value="vacunacion" <?php echo $categoria_filtro === 'vacunacion' ? 'selected' : ''; ?>>Vacunación</option>
            </select>
        </div>
        <button type="submit" class="btn-filter">
            <i class="fas fa-sync-alt"></i> Actualizar datos
        </button>
    </form>

    <!-- Grid de tarjetas de reportes -->
    <div class="reports-grid" id="reportsGrid">
        <!-- Reporte 1: Usuarios -->
        <?php if ($categoria_filtro === 'todos' || $categoria_filtro === 'usuarios') : ?>
        <div class="report-card">
            <div class="report-card-content">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Reporte de Usuarios</h3>
                    <p>Listado completo de usuarios registrados en el sistema con sus roles y datos de contacto.</p>
                </div>
                <div class="card-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total usuarios:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($total_usuarios->total); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Administradores:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($total_admin->total); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Veterinarios:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($total_vet->total); ?></span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="../reportes/reporte_usuarios.php" target="_blank" class="btn-generate">
                    <i class="fas fa-file-pdf"></i> Generar Reporte PDF
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reporte 2: Inventario -->
        <?php if ($categoria_filtro === 'todos' || $categoria_filtro === 'productos') : ?>
        <div class="report-card">
            <div class="report-card-content">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3>Reporte de Inventario</h3>
                    <p>Inventario completo de productos, stock actual, precios y productos con stock bajo.</p>
                </div>
                <div class="card-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total productos:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($total_productos->total); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Stock bajo (&le;10):</span>
                        <span class="stat-value"><?php echo htmlspecialchars($productos_bajo_stock->total); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Valor inventario:</span>
                        <span class="stat-value">$<?php echo htmlspecialchars(number_format($valor_inventario->total, 2)); ?></span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="../reportes/reporte_inventario.php" target="_blank" class="btn-generate">
                    <i class="fas fa-file-pdf"></i> Generar Reporte PDF
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reporte 3: Citas -->
        <?php if ($categoria_filtro === 'todos' || $categoria_filtro === 'citas') : ?>
        <div class="report-card">
            <div class="report-card-content">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Reporte de Citas</h3>
                    <p>Reporte de citas programadas, atendidas y pendientes en el período seleccionado.</p>
                </div>
                <div class="card-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total citas:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($total_citas->total); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Atendidas:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($citas_realizadas->total); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Pendientes:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($citas_pendientes->total); ?></span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo query_report('../reportes/reporte_citas.php'); ?>" target="_blank" class="btn-generate">
                    <i class="fas fa-file-pdf"></i> Generar Reporte PDF
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reporte 4: Financiero -->
        <?php if ($categoria_filtro === 'todos' || $categoria_filtro === 'financiero') : ?>
        <div class="report-card">
            <div class="report-card-content">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Reporte Financiero</h3>
                    <p>Resumen de ingresos, ventas y transacciones económicas de la clínica.</p>
                </div>
                <div class="card-stats">
                    <div class="stat-item">
                        <span class="stat-label">Ingresos totales:</span>
                        <span class="stat-value">$<?php echo htmlspecialchars(number_format($valor_inventario->total, 2)); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Ventas del mes:</span>
                        <span class="stat-value">$<?php echo htmlspecialchars(number_format($citas_realizadas->total * 50000, 2)); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Transacciones:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($citas_realizadas->total + $total_productos->total); ?></span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo query_report('../reportes/reporte_financiero.php'); ?>" target="_blank" class="btn-generate">
                    <i class="fas fa-file-pdf"></i> Generar Reporte PDF
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reporte 5: Pacientes -->
        <?php if ($categoria_filtro === 'todos' || $categoria_filtro === 'pacientes') : ?>
        <div class="report-card">
            <div class="report-card-content">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-paw"></i>
                    </div>
                    <h3>Reporte de Pacientes</h3>
                    <p>Listado de mascotas atendidas, por especie y por veterinario asignado.</p>
                </div>
                <div class="card-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total pacientes:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($total_mascotas->total); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Perros:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($total_perros->total); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Gatos:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($total_gatos->total); ?></span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="../reportes/reporte_pacientes.php" target="_blank" class="btn-generate">
                    <i class="fas fa-file-pdf"></i> Generar Reporte PDF
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reporte 6: Vacunación -->
        <?php if ($categoria_filtro === 'todos' || $categoria_filtro === 'vacunacion') : ?>
        <div class="report-card">
            <div class="report-card-content">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-syringe"></i>
                    </div>
                    <h3>Reporte de Vacunación</h3>
                    <p>Control de vacunas aplicadas y próximas vacunas por vencer.</p>
                </div>
                <div class="card-stats">
                    <div class="stat-item">
                        <span class="stat-label">Vacunas aplicadas:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($vacunas_aplicadas->total); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Próximas a vencer:</span>
                        <span class="stat-value">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Cobertura:</span>
                        <span class="stat-value"><?php echo $total_mascotas->total > 0 ? round(($vacunas_aplicadas->total / $total_mascotas->total) * 100) : 0; ?>%</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo query_report('../reportes/reporte_vacunacion.php'); ?>" target="_blank" class="btn-generate">
                    <i class="fas fa-file-pdf"></i> Generar Reporte PDF
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    </div>
</div>

<script src="../app/js/admin/reportes.js"></script>
<?php
include_once '../templates/footer.php';
?>