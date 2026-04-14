<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';


if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../acceso_denegado.php");
    exit();
}

$id_recepcionista = $_SESSION["id_usuario"];

// Obtener la cantidad de citas para hoy agendadas por este recepcionista
try {
    $hoy = date('Y-m-d');
    $sql = $conexion->prepare("SELECT COUNT(*) as total FROM citas WHERE DATE(fecha_hora) = :fecha AND recepcionista_id = :id_recepcionista AND estado = 'confirmada'");
    $sql->bindParam("fecha", $hoy, PDO::PARAM_STR);
    $sql->bindParam("id_recepcionista", $id_recepcionista, PDO::PARAM_INT);
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

// Obtener cantida de citas pendientes que registro este recepcionista
try {
    $sql = $conexion->prepare("SELECT COUNT(*) as total FROM citas WHERE recepcionista_id = :id_recepcionista AND estado = 'pendiente'");
    $sql->bindParam("id_recepcionista", $id_recepcionista, PDO::PARAM_INT);
    $sql->execute();
    $totalCitasPendientes = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener las citas pendientes: " . $e->getMessage());
    exit();
}

// Obtener total de usuarios con rol de veterinario
try {
    $sql = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'veterinario'");
    $sql->execute();
    $totalVeterinarios = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los veterinarios: " . $e->getMessage());
    exit();
}

// Obtener las ultimas 5 citas confirmadas generadas por el recepcionista
try {
    $sqlCitas = $conexion->prepare("
        SELECT c.fecha_hora, m.nombre as mascota, cl.nombre as dueno, u.nombre as veterinario, c.estado
        FROM citas c
        INNER JOIN mascotas m ON c.mascota_id = m.id
        INNER JOIN clientes cl ON m.cliente_id = cl.id
        LEFT JOIN usuarios u ON c.veterinario_id = u.id
        WHERE c.recepcionista_id = :id_recepcionista AND c.estado = 'confirmada'
        ORDER BY c.id DESC
        LIMIT 5
    ");
    $sqlCitas->bindParam("id_recepcionista", $id_recepcionista, PDO::PARAM_INT);
    $sqlCitas->execute();
    $ultimasCitas = $sqlCitas->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener las últimas citas: " . $e->getMessage());
    $ultimasCitas = [];
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

// Obtener estadisticas por especie para el ChartJS
try {
    $sqlEspecies = $conexion->prepare("SELECT especie, COUNT(*) as cantidad FROM mascotas GROUP BY especie");
    $sqlEspecies->execute();
    $datosEspecies = $sqlEspecies->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $datosEspecies = [];
}

// Obtener veterinarios activos
try {
    $sqlVets = $conexion->prepare("SELECT nombre, especialidad FROM usuarios WHERE rol = 'veterinario' AND estado = 'activo' LIMIT 4");
    $sqlVets->execute();
    $vetsActivos = $sqlVets->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $vetsActivos = [];
}
include_once '../templates/header.php';
?>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: #f4f7fc;
        overflow-x: hidden;
        color: #1e2a3e;
    }

    /* Layout principal */
    .wrapper {
        display: flex;
        width: 100%;
        min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
        width: 280px;
        background: linear-gradient(180deg, #0f2b3d 0%, #1a3a4f 100%);
        color: #e2f0f7;
        position: fixed;
        height: 100vh;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .sidebar-header {
        padding: 28px 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sidebar-header i {
        font-size: 32px;
        color: #6bcb77;
    }

    .sidebar-header h2 {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 1.6rem;
        color: white;
    }

    .sidebar-header span {
        font-size: 0.75rem;
        background: #6bcb77;
        padding: 2px 8px;
        border-radius: 30px;
        color: #0f2b3d;
        font-weight: 600;
    }

    .sidebar-menu {
        padding: 20px 0 30px;
    }

    .menu-item {
        padding: 12px 24px;
        margin: 6px 12px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        color: #cbdbe2;
        transition: 0.2s;
        cursor: pointer;
    }

    .menu-item i {
        width: 26px;
        font-size: 1.25rem;
    }

    .menu-item span {
        font-weight: 500;
        font-size: 0.95rem;
    }

    .menu-item.active,
    .menu-item:hover {
        background: rgba(107, 203, 119, 0.2);
        color: white;
    }

    .menu-item.active {
        background: #6bcb77;
        color: #0f2b3d;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    /* Main Content */
    .main-content {
        flex: 1;
        margin-left: 280px;
        transition: margin-left 0.3s;
        background: #f4f7fc;
    }

    /* Top Navbar */
    .top-navbar {
        background: white;
        padding: 16px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        border-bottom: 1px solid #e9edf2;
        position: sticky;
        top: 0;
        z-index: 999;
    }

    .menu-toggle {
        display: none;
        font-size: 1.6rem;
        cursor: pointer;
        color: #2c5a6e;
    }

    .search-bar {
        background: #f1f5f9;
        padding: 8px 18px;
        border-radius: 40px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .search-bar i {
        color: #8ba0b0;
    }

    .search-bar input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 0.9rem;
        width: 220px;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .notification-bell {
        position: relative;
        font-size: 1.3rem;
        color: #3b6f8a;
        cursor: pointer;
    }

    .avatar {
        width: 44px;
        height: 44px;
        background: #6bcb77;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        font-size: 1rem;
    }

    /* Dashboard content */
    .dashboard-container {
        padding: 28px 32px;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: white;
        border-radius: 28px;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.02);
        border: 1px solid #eef2f6;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 30px -12px rgba(0, 0, 0, 0.1);
    }

    .stat-info h3 {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c8d9e;
        margin-bottom: 8px;
    }

    .stat-number {
        font-size: 2.2rem;
        font-weight: 800;
        color: #1e2f3c;
    }

    .stat-icon {
        width: 54px;
        height: 54px;
        background: #eef7f0;
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: #6bcb77;
    }

    /* Grid de dos columnas */
    .row-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 28px;
        margin-bottom: 32px;
    }

    /* Tarjetas */
    .card {
        background: white;
        border-radius: 28px;
        padding: 20px 24px;
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.02);
        border: 1px solid #eef2f8;
        transition: all 0.2s;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        border-bottom: 1px solid #eff3f8;
        padding-bottom: 12px;
    }

    .card-header h4 {
        font-weight: 700;
        font-size: 1.1rem;
        color: #1f3e4b;
    }

    .card-header i {
        color: #6bcb77;
    }

    /* Tabla de citas */
    .appointments-table {
        width: 100%;
        border-collapse: collapse;
    }

    .appointments-table th {
        text-align: left;
        padding: 12px 4px;
        font-weight: 600;
        font-size: 0.75rem;
        color: #6f91a2;
        text-transform: uppercase;
    }

    .appointments-table td {
        padding: 12px 4px;
        border-bottom: 1px solid #f0f3f8;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .status-badge {
        background: #e0f7e8;
        color: #2b7e3a;
        padding: 4px 10px;
        border-radius: 40px;
        font-size: 0.7rem;
        font-weight: 700;
        display: inline-block;
    }

    .status-badge.pending {
        background: #fff0db;
        color: #c97e00;
    }

    .status-badge.cancelled {
        background: #fee9e7;
        color: #e74c3c;
    }

    /* Lista de pacientes */
    .patient-list {
        list-style: none;
    }

    .patient-list li {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f4f9;
    }

    .patient-avatar {
        width: 42px;
        height: 42px;
        background: #d9eef5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #2f6b7c;
    }

    /* Botones de acción */
    .btn-small {
        background: #eef2fa;
        border: none;
        padding: 6px 12px;
        border-radius: 40px;
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-small:hover {
        background: #6bcb77;
        color: white;
    }

    .btn-primary-small {
        background: #6bcb77;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 40px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-primary-small:hover {
        background: #54b561;
        transform: translateY(-2px);
    }

    /* Quick Actions */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-top: 15px;
    }

    .action-btn {
        background: #f8fbfe;
        border: 1px solid #eef2f8;
        border-radius: 20px;
        padding: 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .action-btn:hover {
        background: #6bcb77;
        border-color: #6bcb77;
        transform: translateY(-3px);
    }

    .action-btn:hover i,
    .action-btn:hover span {
        color: white;
    }

    .action-btn i {
        font-size: 1.5rem;
        color: #6bcb77;
        margin-bottom: 8px;
        display: block;
    }

    .action-btn span {
        font-size: 0.75rem;
        font-weight: 600;
        color: #1f3e4b;
    }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
    }

    .modal-content {
        background: white;
        border-radius: 32px;
        width: 90%;
        max-width: 500px;
        max-height: 85vh;
        overflow-y: auto;
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #1f3e4b, #2c5a6e);
        padding: 20px 25px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        font-family: 'Poppins', sans-serif;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        transition: all 0.2s;
    }

    .modal-body {
        padding: 25px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #1f3e4b;
        font-size: 0.85rem;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        font-size: 0.9rem;
        font-family: 'Inter', sans-serif;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #6bcb77;
    }

    .modal-buttons {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }

    .modal-btn-save {
        background: #6bcb77;
        color: white;
        border: none;
        padding: 12px;
        border-radius: 40px;
        cursor: pointer;
        font-weight: 600;
        flex: 1;
    }

    .modal-btn-cancel {
        background: #eef2f5;
        color: #2c5a6e;
        border: none;
        padding: 12px;
        border-radius: 40px;
        cursor: pointer;
        font-weight: 600;
        flex: 1;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .sidebar {
            left: -280px;
        }

        .sidebar.active {
            left: 0;
        }

        .main-content {
            margin-left: 0;
        }

        .menu-toggle {
            display: block;
        }

        .row-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .dashboard-container {
            padding: 20px;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .quick-actions {
            grid-template-columns: 1fr;
        }
    }

    footer {
        text-align: center;
        padding: 20px 32px;
        font-size: 0.75rem;
        color: #7f9aab;
        border-top: 1px solid #e2e8f0;
        background: #f8fbfe;
        margin-top: 20px;
    }
</style>

<div class="dashboard-container">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3>Citas Hoy</h3>
                <div class="stat-number" id="citasHoy"><?php echo htmlspecialchars($totalCitasHoy->total); ?></div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Pacientes Registrados</h3>
                <div class="stat-number" id="totalPacientes"><?php echo htmlspecialchars($totalMascotas->total); ?></div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-paw"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Espera Pendiente</h3>
                <div class="stat-number" id="esperaPendiente"><?php echo htmlspecialchars($totalCitasPendientes->total); ?></div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Veterinarios</h3>
                <div class="stat-number" id="veterinariosHoy"><?php echo htmlspecialchars($totalVeterinarios->total); ?></div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-user-md"></i>
            </div>
        </div>
    </div>

    <!-- Row 1: Próximas citas y Acciones rápidas -->
    <div class="row-grid">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-clock"></i> Próximas Citas</h4>
                <button class="btn-small" onclick="abrirModalCita()"><i class="fas fa-plus"></i> Nueva</button>
            </div>
            <table class="appointments-table" id="citasTable">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Mascota</th>
                        <th>Dueño</th>
                        <th>Veterinario</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody id="citasBody">
                    <?php if (empty($ultimasCitas)) : ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 20px; color:#7f8c8d;">
                                No hay citas confirmadas registradas por usted.
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($ultimasCitas as $ita) : ?>
                            <tr>
                                <td><?php echo date('d/m h:i A', strtotime($ita->fecha_hora)); ?></td>
                                <td><strong><?php echo htmlspecialchars(ucfirst($ita->mascota)); ?></strong></td>
                                <td><?php echo htmlspecialchars(ucfirst($ita->dueno)); ?></td>
                                <td><?php echo $ita->veterinario ? htmlspecialchars(ucfirst($ita->veterinario)) : 'Sin asignar'; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $ita->estado === 'pendiente' ? 'pending' : ''; ?>">
                                        <?php echo $ita->estado === 'confirmada' ? 'Confirmada' : 'Pendiente'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-bolt"></i> Acciones Rápidas</h4>
                <i class="fas fa-ellipsis-h"></i>
            </div>
            <div class="quick-actions">
                <div class="action-btn" onclick="abrirModalCita()">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Agendar Cita</span>
                </div>
                <div class="action-btn" onclick="abrirModalPaciente()">
                    <i class="fas fa-dog"></i>
                    <span>Registrar Paciente</span>
                </div>
                <div class="action-btn" onclick="registrarLlegada()">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Registrar Llegada</span>
                </div>
                <div class="action-btn" onclick="consultarHistorial()">
                    <i class="fas fa-history"></i>
                    <span>Historial Clínico</span>
                </div>
                <div class="action-btn" onclick="facturar()">
                    <i class="fas fa-receipt"></i>
                    <span>Facturar</span>
                </div>
                <div class="action-btn" onclick="verInventario()">
                    <i class="fas fa-boxes"></i>
                    <span>Consultar Stock</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Pacientes recientes y Recordatorios -->
    <div class="row-grid">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-user-plus"></i> Pacientes Recientes</h4>
                <button class="btn-small" onclick="abrirModalPaciente()"><i class="fas fa-plus"></i> Nuevo</button>
            </div>
            <ul class="patient-list" id="pacientesRecientes">
                <?php if (empty($ultimasMascotas)) : ?>
                    <li style="justify-content:center; padding: 20px; color:#7f8c8d;">
                        No hay pacientes registrados recientemente.
                    </li>
                <?php else : ?>
                    <?php foreach ($ultimasMascotas as $pac) : ?>
                        <li>
                            <div class="patient-avatar">
                                <i class="fas <?php 
                                    $esp = strtolower($pac->especie);
                                    echo ($esp === 'perro') ? 'fa-dog' : (($esp === 'gato') ? 'fa-cat' : 'fa-paw'); 
                                ?>"></i>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars(ucfirst($pac->nombre)); ?></strong> 
                                <span style="font-size:0.7rem; color:#7f8c8d;">• <?php echo htmlspecialchars(ucfirst($pac->especie)); ?></span>
                                <br>
                                <small>Dueño: <?php echo htmlspecialchars(ucfirst($pac->dueno)); ?> | Reciente</small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-chart-pie"></i> Distribución de Pacientes</h4>
                <i class="fas fa-paw" style="color:#6bcb77;"></i>
            </div>
            <div style="position: relative; height: 250px; display: flex; justify-content: center;">
                <canvas id="pacientesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Row 3: Veterinarios disponibles -->
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-stethoscope"></i> Veterinarios en Turno</h4>
            <button class="btn-small" onclick="verHorarios()">Ver horarios</button>
        </div>
        <div style="display: flex; gap: 20px; flex-wrap: wrap;" id="veterinariosList">
            <?php if (empty($vetsActivos)) : ?>
                <div style="padding: 15px; color:#7f8c8d; font-size: 0.85rem;">No hay veterinarios activos en este momento.</div>
            <?php else : ?>
                <?php foreach ($vetsActivos as $v) : ?>
                    <div style="background: #f8fbfe; border-radius: 20px; padding: 15px; min-width: 180px; border: 1px solid #eef2f8;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <i class="fas fa-user-md" style="color: #6bcb77;"></i>
                            <strong><?php echo htmlspecialchars(ucfirst($v->nombre)); ?></strong>
                        </div>
                        <div style="font-size: 0.7rem; color: #7f8c8d;"><?php echo htmlspecialchars(ucfirst($v->especialidad) ?: 'Medicina General'); ?></div>
                        <div style="font-size: 0.7rem; color: #6bcb77;">Horario: 08:00 - 18:00</div>
                        <span class="status-badge" style="font-size: 0.6rem; margin-top: 5px; display: inline-block;">En turno</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Inyectar datos dinámicos desde PHP
    let citas = <?php echo json_encode(array_map(function($c) {
        return [
            'hora' => date('d/m h:i A', strtotime($c->fecha_hora)),
            'mascota' => ucfirst($c->mascota),
            'dueño' => ucfirst($c->dueno),
            'veterinario' => $c->veterinario ? ucfirst($c->veterinario) : 'Sin asignar',
            'estado' => $c->estado
        ];
    }, $ultimasCitas)); ?>;

    let pacientesRecientes = <?php echo json_encode(array_map(function($p) {
        return [
            'nombre' => ucfirst($p->nombre),
            'especie' => ucfirst($p->especie),
            'dueño' => ucfirst($p->dueno),
            'fecha' => 'Reciente'
        ];
    }, $ultimasMascotas)); ?>;

    let chartLabels = <?php echo json_encode(array_map(function($e) { return ucfirst($e->especie); }, $datosEspecies)); ?>;
    let chartData = <?php echo json_encode(array_map(function($e) { return (int)$e->cantidad; }, $datosEspecies)); ?>;

    // Generar grafico de ChartJS
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('pacientesChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: chartLabels.length > 0 ? chartLabels : ['Sin registros'],
                    datasets: [{
                        data: chartData.length > 0 ? chartData : [1],
                        backgroundColor: ['#6bcb77', '#3498db', '#f39c12', '#9b59b6', '#e74c3c'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { font: { family: 'Inter', size: 12 } } },
                        tooltip: { backgroundColor: '#1e2f3c', padding: 12, cornerRadius: 8 }
                    },
                    cutout: '70%'
                }
            });
        }
    });

    // Las secciones de Citas, Pacientes y Veterinarios ahora se renderizan directamente con PHP para mayor fiabilidad.



    // Buscar en tiempo real
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const search = e.target.value.toLowerCase();
            const filtered = citas.filter(c =>
                c.mascota.toLowerCase().includes(search) ||
                c.dueño.toLowerCase().includes(search)
            );
            const tbody = document.getElementById('citasBody');
            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No se encontraron citas</td></tr>';
            } else {
                tbody.innerHTML = filtered.map(c => `
                    <tr>
                        <td>${c.hora}</td>
                        <td><strong>${c.mascota}</strong></td>
                        <td>${c.dueño}</td>
                        <td>${c.veterinario}</td>
                        <td><span class="status-badge ${c.estado === 'pendiente' ? 'pending' : ''}">${c.estado === 'confirmada' ? 'Confirmada' : 'Pendiente'}</span></td>
                    </tr>
                `).join('');
            }
        });
    }

    // Funciones de modales
    function abrirModalCita() {
        document.getElementById('modalCita').style.display = 'flex';
        document.getElementById('fechaCita').valueAsDate = new Date();
    }

    function cerrarModalCita() {
        document.getElementById('modalCita').style.display = 'none';
        document.getElementById('formCita').reset();
    }

    function abrirModalPaciente() {
        document.getElementById('modalPaciente').style.display = 'flex';
    }

    function cerrarModalPaciente() {
        document.getElementById('modalPaciente').style.display = 'none';
        document.getElementById('formPaciente').reset();
    }

    // Acciones rápidas
    function registrarLlegada() {
        Swal.fire({
            title: 'Registrar llegada',
            html: `
                <input id="swal-input" class="swal2-input" placeholder="Nombre de la mascota">
                <select id="swal-vet" class="swal2-select" style="margin-top:10px;">
                    <option value="">Seleccionar veterinario</option>
                    <option>Dra. Laura Méndez</option>
                    <option>Dr. Carlos Rojas</option>
                    <option>Dra. Sofía Ramírez</option>
                </select>
            `,
            showCancelButton: true,
            confirmButtonText: 'Registrar',
            preConfirm: () => {
                const mascota = document.getElementById('swal-input').value;
                if (!mascota) {
                    Swal.showValidationMessage('Ingrese el nombre de la mascota');
                }
                return mascota;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Llegada registrada', `Paciente ${result.value} ha sido registrado en sala de espera`, 'success');
                const espera = parseInt(document.getElementById('esperaPendiente').textContent);
                document.getElementById('esperaPendiente').textContent = espera + 1;
            }
        });
    }

    function consultarHistorial() {
        Swal.fire({
            title: 'Consultar historial',
            input: 'text',
            inputPlaceholder: 'Nombre de la mascota o ID',
            showCancelButton: true,
            confirmButtonText: 'Buscar'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire('Historial clínico', `Mostrando historial de ${result.value}\n\n- Vacunas al día\n- Última consulta: 10/03/2024\n- Próxima cita: Pendiente`, 'info');
            }
        });
    }

    function facturar() {
        Swal.fire({
            title: 'Facturación',
            html: `
                <input class="swal2-input" placeholder="Nombre del cliente">
                <input class="swal2-input" placeholder="Monto total" type="number">
            `,
            showCancelButton: true,
            confirmButtonText: 'Generar factura'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Factura generada', 'La factura ha sido generada correctamente', 'success');
            }
        });
    }

    function verInventario() {
        Swal.fire({
            title: 'Consultar inventario',
            html: `
                <div style="text-align: left;">
                    <p><strong>Stock bajo:</strong> Antibiótico (5 unidades)</p>
                    <p><strong>Próximo a vencer:</strong> Vacuna Antirrábica</p>
                    <p><strong>Total productos:</strong> 45</p>
                </div>
            `,
            icon: 'info'
        });
    }

    function verHorarios() {
        Swal.fire({
            title: 'Horarios de veterinarios',
            html: `
                <div style="text-align: left;">
                    <p>👩‍⚕️ Dra. Laura Méndez: 8:00 - 12:00</p>
                    <p>👨‍⚕️ Dr. Carlos Rojas: 8:00 - 14:00</p>
                    <p>👩‍⚕️ Dra. Sofía Ramírez: 10:00 - 16:00</p>
                    <p>👨‍⚕️ Dr. Andrés Sánchez: 12:00 - 18:00</p>
                </div>
            `,
            icon: 'info'
        });
    }

    function mostrarNotificaciones() {
        Swal.fire({
            title: 'Notificaciones',
            html: `
                <div style="text-align: left;">
                    <p>🔔 Cita pendiente: Rocky - 11:45 AM</p>
                    <p>💉 Vacunas por vencer: 3 mascotas</p>
                    <p>📦 Stock bajo: Antibiótico</p>
                </div>
            `,
            icon: 'info'
        });
    }

    // Guardar nueva cita
    document.getElementById('formCita').addEventListener('submit', (e) => {
        e.preventDefault();
        const nuevaCita = {
            hora: document.getElementById('horaCita').value,
            mascota: document.getElementById('mascotaNombre').value,
            dueño: document.getElementById('duenoNombre').value,
            veterinario: document.getElementById('veterinarioCita').value,
            estado: 'pendiente'
        };
        citas.push(nuevaCita);
        renderizarCitas();
        cerrarModalCita();
        Swal.fire('Cita agendada', 'La cita ha sido registrada exitosamente', 'success');
    });

    // Guardar nuevo paciente
    document.getElementById('formPaciente').addEventListener('submit', (e) => {
        e.preventDefault();
        const nuevoPaciente = {
            nombre: document.getElementById('pacienteNombre').value,
            especie: document.getElementById('especie').value,
            dueño: document.getElementById('duenoPaciente').value,
            fecha: new Date().toLocaleDateString()
        };
        pacientesRecientes.unshift(nuevoPaciente);
        renderizarPacientes();
        cerrarModalPaciente();
        Swal.fire('Paciente registrado', `${nuevoPaciente.nombre} ha sido registrado exitosamente`, 'success');
    });

    // Toggle sidebar
    document.getElementById('menuToggle').addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('active');
    });

    // Cerrar modales al hacer clic fuera
    window.onclick = (e) => {
        if (e.target === document.getElementById('modalCita')) cerrarModalCita();
        if (e.target === document.getElementById('modalPaciente')) cerrarModalPaciente();
    };

    // Inicializar
    // Las tablas y listas se renderizan ahora directamente con PHP
    // Los contadores top se calculan automáticamente en PHP
</script>
<?php include_once '../templates/footer.php'; ?>