<footer>
            <i class="fas fa-paw"></i> VetCare Dashboard — Gestión clínica veterinaria moderna | © 2025 Todos los derechos reservados
        </footer>
    </div>
</div>

<script>
    // Toggle menu para responsive
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
    // Cerrar sidebar al hacer click en un enlace (mejora mobile)
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            if(window.innerWidth <= 992) {
                sidebar.classList.remove('active');
            }
            // activar clase visual (simulación)
            menuItems.forEach(m => m.classList.remove('active'));
            item.classList.add('active');
        });
    });

    // Gráfico con Chart.js (Consultas por semana)
    const chartLabels = window.chartLabels || ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    const chartData = window.chartData || [12, 18, 14, 22, 27, 16, 9];

    const ctx = document.getElementById('visitsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Consultas atendidas',
                data: chartData,
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
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { backgroundColor: '#1f3e4b', titleColor: '#eef2f8' }
            },
            scales: {
                y: { grid: { color: '#e6edf4' }, ticks: { stepSize: 5 } },
                x: { grid: { display: false } }
            }
        }
    });

    // Gráfico de Ingresos Diarios
    const incomeCtx = document.getElementById('incomeChart');
    if (incomeCtx) {
        new Chart(incomeCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Ingresos ($)',
                    data: window.incomeData || [0, 0, 0, 0, 0, 0, 0],
                    backgroundColor: '#6bcb77',
                    borderRadius: 8,
                    maxBarThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#1f3e4b' }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#e6edf4' },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // pequeña simulación de datos dinámicos (solo para estilo, números estáticos pero interactivos)
    // Opcional: hover cards etc. Se mantiene diseño moderno
</script>
</body>
</html>