// Toggle sidebar en móvil
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });

    // Cerrar sidebar al hacer click en menú (mobile)
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('active');
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

        const previewCard = document.getElementById('previewCard');
        const previewNombre = document.getElementById('previewNombre');
        const previewCedula = document.getElementById('previewCedula');
        const previewTelefono = document.getElementById('previewTelefono');
        const previewEmail = document.getElementById('previewEmail');
        const previewDireccion = document.getElementById('previewDireccion');

        previewNombre.textContent = nombre || '—';
        previewCedula.textContent = cedula || '—';
        previewTelefono.textContent = telefono || '—';
        previewEmail.textContent = email || '—';
        previewDireccion.textContent = direccion || '—';
    }

    // Eventos para vista previa
    document.getElementById('nombre').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('cedula').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('telefono').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('email').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('direccion').addEventListener('input', actualizarVistaPrevia);

    // Inicializar vista previa
    actualizarVistaPrevia();

    // Función para limpiar formulario
    function limpiarFormulario() {
        document.getElementById('registroClienteForm').reset();
        actualizarVistaPrevia();
    }