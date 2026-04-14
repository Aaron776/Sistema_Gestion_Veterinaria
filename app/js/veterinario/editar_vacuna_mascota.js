 function actualizarVistaPrevia() {
        const vacunaSelect = document.getElementById('vacuna');
        const vacunaNombre = vacunaSelect.options[vacunaSelect.selectedIndex] ? vacunaSelect.options[vacunaSelect.selectedIndex].text : '—';
        const fecha = document.getElementById('fechaAplicacion').value;
        const proxima = document.getElementById('proximaDosis').value;

        document.getElementById('previewVacuna').innerText = vacunaNombre;
        document.getElementById('previewFecha').innerText = fecha ? fecha : '—';
        document.getElementById('previewProxima').innerText = proxima ? proxima : '—';
    }

    // Eventos para vista previa
    document.getElementById('vacuna').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('fechaAplicacion').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('proximaDosis').addEventListener('input', actualizarVistaPrevia);

    // Inicializar vista previa al cargar
    document.addEventListener('DOMContentLoaded', actualizarVistaPrevia);