<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'veterinario') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_veterinario=$_SESSION['id_usuario']; // obtener el id del usuario logueado


// Obtener cantidad de citas para el dia de hoy que esten confirmadas
try {
    $sql=$conexion->prepare('SELECT COUNT(*) as cantidad FROM citas WHERE veterinario_id = :id_veterinario AND estado = "confirmada" AND fecha_hora = CURDATE()');
    $sql->bindParam(':id_veterinario', $id_veterinario, PDO::PARAM_INT);
    $sql->execute();
    $cantidad_citas_hoy_confirmadas = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener la cantidad de citas: " . $e->getMessage());
    $cantidad_citas_hoy_confirmadas = 0;
}


// Obtener cantidad de mascotas de las citas que tiene el estado de realizada (Pacientes Únicos)
try {
    $sql_pacientes = $conexion->prepare("SELECT COUNT(DISTINCT mascota_id) as cantidad FROM citas WHERE veterinario_id = :id_veterinario AND estado = 'realizada'");
    $sql_pacientes->bindParam(':id_veterinario', $id_veterinario, PDO::PARAM_INT);
    $sql_pacientes->execute();
    $cantidad_pacientes = $sql_pacientes->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener cantidad de mascotas: " . $e->getMessage());
    $cantidad_pacientes = (object)['cantidad' => 0];
}

// Obtener cantidad de consultas completadas (Total de citas realizadas)
try {
    $sql_consultas = $conexion->prepare("SELECT COUNT(*) as cantidad FROM citas WHERE veterinario_id = :id_veterinario AND estado = 'realizada'");
    $sql_consultas->bindParam(':id_veterinario', $id_veterinario, PDO::PARAM_INT);
    $sql_consultas->execute();
    $cantidad_consultas = $sql_consultas->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener consultas completadas: " . $e->getMessage());
    $cantidad_consultas = (object)['cantidad' => 0];
}

// Obtener cantidad de citas canceladas
try {
    $sql_canceladas = $conexion->prepare("SELECT COUNT(*) as cantidad FROM citas WHERE veterinario_id = :id_veterinario AND estado = 'cancelada'");
    $sql_canceladas->bindParam(':id_veterinario', $id_veterinario, PDO::PARAM_INT);
    $sql_canceladas->execute();
    $cantidad_canceladas = $sql_canceladas->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener citas canceladas: " . $e->getMessage());
    $cantidad_canceladas = (object)['cantidad' => 0];
}

// Lógica para el gráfico de los últimos 7 días
try {
    $sql_grafico = $conexion->prepare("
        SELECT DATE(fecha_hora) as fecha, COUNT(*) as cantidad 
        FROM citas 
        WHERE veterinario_id = :id_veterinario 
        AND estado = 'realizada' 
        AND fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(fecha_hora)
    ");
    $sql_grafico->bindParam(':id_veterinario', $id_veterinario, PDO::PARAM_INT);
    $sql_grafico->execute();
    $resultados_grafico = $sql_grafico->fetchAll(PDO::FETCH_KEY_PAIR);

    $dias_semana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    $grafico_labels = [];
    $grafico_data = [];

    for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-$i days"));
        $dia_idx = date('w', strtotime($fecha));
        $grafico_labels[] = $dias_semana[$dia_idx];
        $grafico_data[] = isset($resultados_grafico[$fecha]) ? (int)$resultados_grafico[$fecha] : 0;
    }
} catch (PDOException $e) {
    error_log("Error al obtener datos del gráfico: " . $e->getMessage());
    $grafico_labels = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    $grafico_data = [0, 0, 0, 0, 0, 0, 0];
}

// Obtener las últimas 5 citas de hoy
try {
    $sql_citas_hoy = $conexion->prepare("
        SELECT c.id, c.fecha_hora, c.motivo, c.estado, m.nombre as mascota, u.nombre as dueno
        FROM citas c
        JOIN mascotas m ON c.mascota_id = m.id
        JOIN usuarios u ON m.cliente_id = u.id
        WHERE c.veterinario_id = :id_veterinario 
        AND DATE(c.fecha_hora) = CURDATE()
        ORDER BY c.fecha_hora ASC
        LIMIT 5
    ");
    $sql_citas_hoy->bindParam(':id_veterinario', $id_veterinario, PDO::PARAM_INT);
    $sql_citas_hoy->execute();
    $citas_hoy_resultados = $sql_citas_hoy->fetchAll(PDO::FETCH_ASSOC);

    // Formatear para que el JS lo entienda fácilmente
    $citas_hoy_json = array_map(function($c) {
        return [
            'hora' => date('H:i A', strtotime($c['fecha_hora'])),
            'mascota' => $c['mascota'],
            'dueño' => $c['dueno'],
            'motivo' => $c['motivo'],
            'estado' => ucfirst($c['estado'])
        ];
    }, $citas_hoy_resultados);
} catch (PDOException $e) {
    error_log("Error al obtener citas de hoy: " . $e->getMessage());
    $citas_hoy_json = [];
}

// Obtener pacientes recientes (últimas 5 mascotas atendidas)
try {
    $sql_pacientes_list = $conexion->prepare("
        SELECT DISTINCT m.nombre, m.especie, u.nombre as dueno, MAX(c.fecha_hora) as ultima_fecha
        FROM citas c
        JOIN mascotas m ON c.mascota_id = m.id
        JOIN usuarios u ON m.cliente_id = u.id
        WHERE c.veterinario_id = :id_veterinario AND c.estado = 'realizada'
        GROUP BY m.id
        ORDER BY ultima_fecha DESC
        LIMIT 5
    ");
    $sql_pacientes_list->bindParam(':id_veterinario', $id_veterinario, PDO::PARAM_INT);
    $sql_pacientes_list->execute();
    $pacientes_recientes_resultados = $sql_pacientes_list->fetchAll(PDO::FETCH_ASSOC);

    $pacientes_json = array_map(function($p) {
        return [
            'nombre' => $p['nombre'],
            'especie' => ucfirst($p['especie']),
            'dueño' => $p['dueno'],
            'fecha' => date('d/m/Y', strtotime($p['ultima_fecha']))
        ];
    }, $pacientes_recientes_resultados);
} catch (PDOException $e) {
    error_log("Error al obtener pacientes recientes: " . $e->getMessage());
    $pacientes_json = [];
}

// Obtener recordatorios (usaremos una lista vacía por ahora para que muestre el mensaje de "No hay")
$recordatorios_json = [];



include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/veterinario/dash_veterinario.css">

<div class="dashboard-container">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3>Citas Hoy</h3>
                <div class="stat-number" id="citasHoy"><?php echo htmlspecialchars($cantidad_citas_hoy_confirmadas->cantidad); ?></div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Pacientes Atendidos</h3>
                <div class="stat-number" id="pacientesAtendidos"><?php echo htmlspecialchars($cantidad_pacientes->cantidad); ?></div>
                <small>Historial total</small>
            </div>
            <div class="stat-icon">
                <i class="fas fa-paw"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Citas Canceladas</h3>
                <div class="stat-number" id="enEspera"><?php echo htmlspecialchars($cantidad_canceladas->cantidad); ?></div>
                <small>Total histórico</small>
            </div>
            <div class="stat-icon" style="background: #fee2e2; color: #ef4444;">
                <i class="fas fa-times-circle"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Consultas Completadas</h3>
                <div class="stat-number" id="consultasCompletadas"><?php echo htmlspecialchars($cantidad_consultas->cantidad); ?></div>
                <small>Total realizadas</small>
            </div>
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>

    <!-- Row 1: Citas del día y Acciones rápidas -->
    <div class="row-grid">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-clock"></i> Mis Citas de Hoy</h4>
                <button class="btn-small" onclick="window.location.href='gestion-citas.html'">Ver todas</button>
            </div>
            <ul class="appointments-list" id="citasList">
                <!-- Citas dinámicas -->
            </ul>
        </div>

        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-bolt"></i> Acciones Rápidas</h4>
                <i class="fas fa-ellipsis-h"></i>
            </div>
            <div class="quick-actions">
                <div class="action-btn" onclick="atenderPaciente()">
                    <i class="fas fa-stethoscope"></i>
                    <span>Atender Paciente</span>
                </div>
                <div class="action-btn" onclick="verHistorial()">
                    <i class="fas fa-history"></i>
                    <span>Ver Historial</span>
                </div>
                <div class="action-btn" onclick="recetarMedicamento()">
                    <i class="fas fa-prescription-bottle"></i>
                    <span>Recetar</span>
                </div>
                <div class="action-btn" onclick="programarRecordatorio()">
                    <i class="fas fa-bell"></i>
                    <span>Recordatorio</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Gráfico y Pacientes recientes -->
    <div class="row-grid">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-chart-line"></i> Consultas por Semana</h4>
                <span style="font-size:12px; background:#f0f3f9; padding:4px 12px; border-radius:20px;">Últimos 7 días</span>
            </div>
            <canvas id="consultasChart" height="200" style="max-height:220px; width:100%"></canvas>
        </div>
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-user-plus"></i> Mis Pacientes Recientes</h4>
                <button class="btn-small" onclick="verTodosPacientes()">Ver todos</button>
            </div>
            <ul class="patient-list" id="pacientesRecientes">
                <!-- Pacientes dinámicos -->
            </ul>
        </div>
    </div>

    <!-- Row 3: Próximos recordatorios -->
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-bell"></i> Recordatorios y Alertas Médicas</h4>
            <i class="fas fa-bell" style="color:#f4b942;"></i>
        </div>
        <div id="recordatoriosList">
            <!-- Recordatorios dinámicos -->
        </div>
    </div>
</div>

<script>
    // Variables dinámicas desde PHP
    let citasHoy = <?php echo json_encode($citas_hoy_json); ?>;
    let pacientesRecientes = <?php echo json_encode($pacientes_json); ?>;
    let recordatorios = <?php echo json_encode($recordatorios_json); ?>;


    // Renderizar citas del día
    function renderizarCitas() {
        const container = document.getElementById('citasList');
        
        if (!citasHoy || citasHoy.length === 0) {
            container.innerHTML = `
                <li style="text-align: center; padding: 30px; color: #94a3b8;">
                    <i class="fas fa-calendar-day" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.3; display: block;"></i>
                    No hay citas programadas para hoy
                </li>
            `;
            return;
        }

        container.innerHTML = citasHoy.map(c => `
            <li class="appointment-item" onclick="atenderPacienteDesdeCita('${c.mascota}')">
                <div class="appointment-info">
                    <h5>${c.mascota}</h5>
                    <p>${c.motivo} • ${c.dueño}</p>
                </div>
                <div class="appointment-time">
                    ${c.hora}
                    <span class="status-badge status-${c.estado.toLowerCase()}">${c.estado}</span>
                </div>
            </li>
        `).join('');
    }

    // Renderizar pacientes recientes
    function renderizarPacientes() {
        const container = document.getElementById('pacientesRecientes');

        if (!pacientesRecientes || pacientesRecientes.length === 0) {
            container.innerHTML = `
                <li style="text-align: center; padding: 20px; color: #94a3b8; border: none;">
                    No hay pacientes recientes
                </li>
            `;
            return;
        }

        container.innerHTML = pacientesRecientes.map(p => `
            <li onclick="verHistorialPaciente('${p.nombre}')">
                <div class="patient-avatar">
                    <i class="fas ${p.especie === 'Perro' ? 'fa-dog' : 'fa-cat'}"></i>
                </div>
                <div>
                    <strong>${p.nombre}</strong> <span style="font-size:0.7rem; color:#7f8c8d;">• ${p.especie}</span><br>
                    <small>Dueño: ${p.dueño} | ${p.fecha}</small>
                </div>
            </li>
        `).join('');
    }

    // Renderizar recordatorios
    function renderizarRecordatorios() {
        const container = document.getElementById('recordatoriosList');

        if (!recordatorios || recordatorios.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 20px; color: #94a3b8; background: #f8fafc; border-radius: 20px;">
                    No hay recordatorios pendientes
                </div>
            `;
            return;
        }

        container.innerHTML = recordatorios.map(r => `
            <div style="background: ${r.tipo === 'seguimiento' ? '#fef9e6' : r.tipo === 'vacuna' ? '#eef3fc' : '#eaf8ea'}; border-radius: 20px; padding: 14px; margin-bottom: 12px;">
                <div><i class="fas ${r.icono}" style="color:#6bcb77;"></i> <strong>${r.tipo === 'seguimiento' ? 'Seguimiento' : r.tipo === 'vacuna' ? 'Vacunación' : 'Cita'}</strong></div>
                <small style="color: #5f7f8f;">${r.mensaje}</small>
            </div>
        `).join('');
    }

    // Gráfico de consultas
    const ctx = document.getElementById('consultasChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($grafico_labels); ?>,
            datasets: [{
                label: 'Consultas atendidas',
                data: <?php echo json_encode($grafico_data); ?>,
                backgroundColor: 'rgba(107, 203, 119, 0.08)',
                borderColor: '#6bcb77',
                borderWidth: 3,
                pointBackgroundColor: '#3faa5e',
                pointBorderColor: '#ffffff',
                pointRadius: 5,
                pointHoverRadius: 7,
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        font: {
                            size: 11
                        }
                    }
                }
            },
            scales: {
                y: {
                    grid: {
                        color: '#e6edf4'
                    },
                    ticks: {
                        stepSize: 5
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });


    // Funciones de acciones rápidas
    function atenderPaciente() {
        document.getElementById('atenderForm').reset();
        document.getElementById('atenderModal').style.display = 'flex';
    }

    function atenderPacienteDesdeCita(mascota) {
        document.getElementById('atenderForm').reset();
        const select = document.getElementById('pacienteAtender');
        if (select) {
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value === mascota) {
                    select.selectedIndex = i;
                    break;
                }
            }
        }
        document.getElementById('atenderModal').style.display = 'flex';
    }

    function cerrarAtenderModal() {
        document.getElementById('atenderModal').style.display = 'none';
    }

    const atenderForm = document.getElementById('atenderForm');
    if (atenderForm) {
        atenderForm.addEventListener('submit', (e) => {
            e.preventDefault();
            Swal.fire({
                title: 'Atención registrada',
                text: 'El paciente ha sido atendido correctamente',
                icon: 'success',
                confirmButtonColor: '#6bcb77'
            });
            cerrarAtenderModal();
        });
    }

    function verHistorial() {
        Swal.fire({
            title: 'Buscar historial clínico',
            input: 'text',
            inputPlaceholder: 'Nombre de la mascota',
            showCancelButton: true,
            confirmButtonText: 'Buscar',
            confirmButtonColor: '#6bcb77',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire('Historial clínico', `Mostrando historial de ${result.value}\n\n- No se encontraron registros recientes para esta mascota.`, 'info');
            }
        });
    }

    function verHistorialPaciente(nombre) {
        Swal.fire('Historial clínico', `Mostrando historial de ${nombre}\n\n- Última consulta: Hoy\n- Estado: En progreso`, 'info');
    }

    function recetarMedicamento() {
        Swal.fire({
            title: 'Recetar medicamento',
            html: `
                <input id="medicamento" class="swal2-input" placeholder="Nombre del medicamento">
                <input id="dosis" class="swal2-input" placeholder="Dosis/Instrucciones">
            `,
            showCancelButton: true,
            confirmButtonText: 'Guardar receta',
            confirmButtonColor: '#6bcb77',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Receta generada', 'La receta ha sido registrada en el sistema', 'success');
            }
        });
    }

    function programarRecordatorio() {
        Swal.fire({
            title: 'Programar recordatorio',
            html: `
                <input id="recordatorioTitulo" class="swal2-input" placeholder="Título del recordatorio">
                <input id="recordatorioFecha" class="swal2-input" type="date">
                <textarea id="recordatorioDesc" class="swal2-textarea" placeholder="Descripción"></textarea>
            `,
            showCancelButton: true,
            confirmButtonText: 'Programar',
            confirmButtonColor: '#6bcb77',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Recordatorio programado', 'Se enviará una alerta en la fecha indicada', 'success');
            }
        });
    }

    function verTodosPacientes() {
        window.location.href = 'gestion_citas_realizadas.php';
    }

    window.onclick = (e) => {
        const modal = document.getElementById('atenderModal');
        if (e.target === modal) cerrarAtenderModal();
    };

    // Inicializar
    renderizarCitas();
    renderizarPacientes();
    renderizarRecordatorios();
</script>
<?php include_once '../templates/footer.php'; ?>