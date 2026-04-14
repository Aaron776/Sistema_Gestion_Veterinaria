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

    // Función para calcular el total dinámico
    function calcularTotal() {
        let granTotal = 0;
        const rows = document.querySelectorAll('.servicio-row');
        rows.forEach(row => {
            const precio = parseFloat(row.querySelector('.precio-input').value) || 0;
            const cantidad = parseInt(row.querySelector('.cantidad-input').value) || 1;
            granTotal += (precio * cantidad);
        });

        document.getElementById('subtotal').textContent = '$' + granTotal.toFixed(2);
        document.getElementById('total').textContent = '$' + granTotal.toFixed(2);
    }

    // Función para formatear fecha y hora
    function formatearFechaHora(fechaHora) {
        if (!fechaHora) return 'No seleccionada';
        const fecha = new Date(fechaHora);
        return fecha.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) + ' - ' +
            fecha.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });
    }

    // Vista previa en tiempo real
    function actualizarVistaPrevia() {
        const mascotaSelect = document.getElementById('mascota');
        const veterinarioSelect = document.getElementById('veterinario');
        const fecha = document.getElementById('fecha').value;

        const mascota = mascotaSelect.options[mascotaSelect.selectedIndex]?.text || '';
        const veterinario = veterinarioSelect.options[veterinarioSelect.selectedIndex]?.text || '';
        
        let arrServicios = [];
        let totalCantidad = 0;
        document.querySelectorAll('.servicio-row').forEach(row => {
            const sel = row.querySelector('.servicio-select');
            const txt = sel.options[sel.selectedIndex]?.text || '';
            const ctd = parseInt(row.querySelector('.cantidad-input').value) || 0;
            if(txt && txt !== '-- Seleccione un servicio --') {
                arrServicios.push(txt);
            }
            totalCantidad += ctd;
        });
        
        const servicio = arrServicios.length > 0 ? arrServicios.join(', ') : '—';

        const previewCard = document.getElementById('previewCard');
        const previewMascota = document.getElementById('previewMascota');
        const previewVeterinario = document.getElementById('previewVeterinario');
        const previewServicio = document.getElementById('previewServicio');
        const previewFecha = document.getElementById('previewFecha');
        const previewCantidad = document.getElementById('previewCantidad');
        const previewTotal = document.getElementById('previewTotal');

        let tieneDatos = false;

        if (mascota && mascota !== '-- Seleccione una mascota --') {
            previewMascota.textContent = mascota;
            tieneDatos = true;
        } else {
            previewMascota.textContent = '—';
        }

        if (veterinario && veterinario !== '-- Seleccione un veterinario --') {
            previewVeterinario.textContent = veterinario;
            tieneDatos = true;
        } else {
            previewVeterinario.textContent = '—';
        }

        if (servicio !== '—') {
            previewServicio.textContent = servicio;
            tieneDatos = true;
        } else {
            previewServicio.textContent = '—';
        }

        if (fecha) {
            previewFecha.textContent = formatearFechaHora(fecha);
            tieneDatos = true;
        } else {
            previewFecha.textContent = '—';
        }

        if (totalCantidad > 0) {
            previewCantidad.textContent = totalCantidad;
            tieneDatos = true;
        } else {
            previewCantidad.textContent = '—';
        }

        const total = document.getElementById('total').textContent;
        if (total !== '$0.00' && total !== '$0') {
            previewTotal.textContent = total;
            tieneDatos = true;
        } else {
            previewTotal.textContent = '—';
        }

        if (tieneDatos) {
            previewCard.style.display = 'block';
        }
    }

    // Manejo de eventos delegados para inputs dinámicos de servicios
    document.addEventListener('change', function(e) {
        if(e.target && e.target.classList.contains('servicio-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const precio = selectedOption.getAttribute('data-precio');
            const row = e.target.closest('.servicio-row');
            row.querySelector('.precio-input').value = precio || '';
            calcularTotal();
            actualizarVistaPrevia();
        }
    });

    document.addEventListener('input', function(e) {
        if(e.target && e.target.classList.contains('cantidad-input')) {
            calcularTotal();
            actualizarVistaPrevia();
        }
    });

    // Agregar nuevo servicio (clonar fila)
    document.getElementById('btnAgregarServicio').addEventListener('click', function() {
        const container = document.getElementById('serviciosContainer');
        const firstRow = container.querySelector('.servicio-row');
        const newRow = firstRow.cloneNode(true);
        
        // Limpiar
        newRow.querySelector('.servicio-select').selectedIndex = 0;
        newRow.querySelector('.precio-input').value = '';
        newRow.querySelector('.cantidad-input').value = '1';
        
        // Mostrar botón basurero
        newRow.querySelector('.btn-remove-servicio').style.display = 'block';
        
        container.appendChild(newRow);
        actualizarVistaPrevia();
    });

    // Remover Servicio
    document.addEventListener('click', function(e) {
        // Find closest button just in case user clicks the icon itself
        const btn = e.target.closest('.btn-remove-servicio');
        if(btn) {
             const row = btn.closest('.servicio-row');
             row.remove();
             calcularTotal();
             actualizarVistaPrevia();
        }
    });

    // Eventos primarios
    document.getElementById('mascota').addEventListener('change', actualizarVistaPrevia);
    document.getElementById('veterinario').addEventListener('change', actualizarVistaPrevia);
    document.getElementById('fecha').addEventListener('change', actualizarVistaPrevia);

    // Limpiar formulario completo
    function limpiarFormulario() {
        document.getElementById('registroCitaForm').reset();
        
        // Eliminar filas de servicios extra (mantener la primera)
        const container = document.getElementById('serviciosContainer');
        const rows = container.querySelectorAll('.servicio-row');
        rows.forEach((row, index) => {
            if(index > 0) row.remove();
        });
        
        // Reiniciar totales
        document.getElementById('subtotal').textContent = '$0.00';
        document.getElementById('total').textContent = '$0.00';
        actualizarVistaPrevia();
    }


    // Establecer fecha mínima (hoy)
    const fechaInput = document.getElementById('fecha');
    const ahora = new Date();
    ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
    fechaInput.min = ahora.toISOString().slice(0, 16);

    // Inicializar cálculos y vista previa
    calcularTotal();
    actualizarVistaPrevia();