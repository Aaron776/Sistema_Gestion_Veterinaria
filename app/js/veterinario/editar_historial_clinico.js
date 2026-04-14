// Obtener fecha actual
    function obtenerFechaActual() {
        const ahora = new Date();
        const opciones = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        return ahora.toLocaleDateString('es-ES', opciones);
    }

    // Vista previa en tiempo real
    function actualizarVistaPrevia() {
        const peso = document.getElementById('peso').value;
        const diagnostico = document.getElementById('diagnostico').value.trim();
        const tratamiento = document.getElementById('tratamiento').value.trim();

        const previewCard = document.getElementById('previewCard');
        const previewFecha = document.getElementById('previewFecha');
        const previewPeso = document.getElementById('previewPeso');
        const previewDiagnostico = document.getElementById('previewDiagnostico');
        const previewTratamiento = document.getElementById('previewTratamiento');

        // Siempre mostrar la fecha actual
        previewFecha.textContent = obtenerFechaActual();

        if (peso) {
            previewPeso.textContent = peso + ' kg';
        } else {
            previewPeso.textContent = '—';
        }

        if (diagnostico) {
            previewDiagnostico.textContent = diagnostico.length > 50 ? diagnostico.substring(0, 50) + '...' : diagnostico;
        } else {
            previewDiagnostico.textContent = '—';
        }

        if (tratamiento) {
            previewTratamiento.textContent = tratamiento.length > 50 ? tratamiento.substring(0, 50) + '...' : tratamiento;
        } else {
            previewTratamiento.textContent = '—';
        }
    }

    // Eventos para vista previa
    document.getElementById('peso').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('diagnostico').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('tratamiento').addEventListener('input', actualizarVistaPrevia);

    // Mostrar vista previa al cargar
    actualizarVistaPrevia();

    // Toggle sidebar
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });

    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('active');
            }
        });
    });