 // Funciones auxiliares para fechas
    function obtenerFechaActual() {
        const today = new Date();
        const dd = String(today.getDate()).padStart(2, '0');
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const yyyy = today.getFullYear();
        return `${dd}/${mm}/${yyyy}`;
    }

    function formatearFecha(fecha) {
        if (!fecha) return '';
        const [year, month, day] = fecha.split('-');
        return `${day}/${month}/${year}`;
    }

    // Vista previa en tiempo real
    function actualizarVistaPrevia() {
        const vacunaSelect = document.getElementById('vacuna');
        const vacuna = vacunaSelect.options[vacunaSelect.selectedIndex]?.text || '';
        const proximaDosis = document.getElementById('proximaDosis').value;

        const previewCard = document.getElementById('previewCard');
        const previewVacuna = document.getElementById('previewVacuna');
        const previewFecha = document.getElementById('previewFecha');
        const previewProxima = document.getElementById('previewProxima');

        if (vacuna && vacuna !== '-- Seleccione una vacuna --') {
            previewVacuna.textContent = vacuna;
        } else {
            previewVacuna.textContent = '—';
        }

        // La fecha de aplicación siempre se muestra como hoy
        previewFecha.textContent = obtenerFechaActual();

        if (proximaDosis) {
            previewProxima.textContent = formatearFecha(proximaDosis);
        } else {
            previewProxima.textContent = 'No programada';
        }

        previewCard.style.display = 'block';
    }

    // Eventos para vista previa
    document.getElementById('vacuna').addEventListener('change', actualizarVistaPrevia);
    document.getElementById('proximaDosis').addEventListener('change', actualizarVistaPrevia);

    // Limpiar formulario
    function limpiarFormulario() {
        document.getElementById('registroVacunaForm').reset();
        document.getElementById('proximaDosis').value = '';
        actualizarVistaPrevia();
        document.getElementById('vacuna').focus();
    }

    // Inicializar vista previa al cargar
    document.addEventListener('DOMContentLoaded', actualizarVistaPrevia);