// Función para calcular el total de la cita
    function calcularTotal() {
        let granTotal = 0;
        const rows = document.querySelectorAll('.servicio-row');
        rows.forEach(row => {
            const precio = parseFloat(row.querySelector('.precio-input').value) || 0;
            const cantidad = parseInt(row.querySelector('.cantidad-input').value) || 1;
            granTotal += (precio * cantidad);
        });

        const subtotalEl = document.getElementById('subtotal');
        const totalEl = document.getElementById('total');
        if (subtotalEl && totalEl) {
            subtotalEl.textContent = '$' + granTotal.toFixed(2);
            totalEl.textContent = '$' + granTotal.toFixed(2);
        }
        verificarBotonesEliminar();
    }

    // Actualizar precio al cambiar servicio
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('servicio-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const precio = selectedOption.getAttribute('data-precio');
            const row = e.target.closest('.servicio-row');
            row.querySelector('.precio-input').value = precio || '';
            calcularTotal();
        }
    });

    // Actualizar total al cambiar cantidad
    document.addEventListener('input', function(e) {
        if (e.target && e.target.classList.contains('cantidad-input')) {
            calcularTotal();
        }
    });

    // Agregar nuevo servicio
    document.getElementById('btnAgregarServicio').addEventListener('click', function() {
        const container = document.getElementById('serviciosContainer');
        const firstRow = container.querySelector('.servicio-row');
        const newRow = firstRow.cloneNode(true);

        newRow.querySelector('.servicio-select').selectedIndex = 0;
        newRow.querySelector('.precio-input').value = '';
        newRow.querySelector('.cantidad-input').value = '1';

        container.appendChild(newRow);
        calcularTotal();
    });

    // Eliminar servicio
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-servicio');
        if (btn) {
            const rowsLength = document.querySelectorAll('.servicio-row').length;
            // No permitir borrar si solo queda 1
            if (rowsLength > 1) {
                const row = btn.closest('.servicio-row');
                row.remove();
                calcularTotal();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Debe existir al menos un servicio asignado a esta cita.'
                });
            }
        }
    });

    // Mostrar u ocultar botones de eliminar
    function verificarBotonesEliminar() {
        const rows = document.querySelectorAll('.servicio-row');
        const botones = document.querySelectorAll('.btn-remove-servicio');
        if (rows.length === 1) {
            botones.forEach(b => b.style.display = 'none');
        } else {
            botones.forEach(b => b.style.display = 'block');
        }
    }

    // Inicializar cálculos al cargar la página
    document.addEventListener('DOMContentLoaded', calcularTotal);