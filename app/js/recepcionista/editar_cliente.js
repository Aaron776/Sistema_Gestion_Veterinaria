// Toggle sidebar en móvil
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });

    // Cerrar sidebar al hacer click en menú (mobile)
    document.querySelectorAll('.menu-item').forEach(function(item) {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                document.getElementById('sidebar').classList.remove('active');
            }
        });
    });

    // Vista previa en tiempo real
    function actualizarVistaPrevia() {
        const nombre = document.getElementById('nombre').value.trim();
        const cedula = document.getElementById('cedula').value.trim();
        const telefono = document.getElementById('telefono').value.trim();
        const email = document.getElementById('email').value.trim();
        const direccion = document.getElementById('direccion').value.trim();

        document.getElementById('previewNombre').textContent = nombre || '—';
        document.getElementById('previewCedula').textContent = cedula || '—';
        document.getElementById('previewTelefono').textContent = telefono || '—';
        document.getElementById('previewEmail').textContent = email || '—';
        document.getElementById('previewDireccion').textContent = direccion || '—';
    }

    // Eventos para vista previa
    document.getElementById('nombre').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('cedula').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('telefono').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('email').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('direccion').addEventListener('input', actualizarVistaPrevia);