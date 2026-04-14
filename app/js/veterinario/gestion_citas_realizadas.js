 // Cerrar menú lateral en modo responsivo
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menuToggle');

        if (window.innerWidth <= 992 && sidebar && sidebar.classList.contains('active')) {
            // Cerrar sidebar si se hace clic fuera de él y no en el botón de menú
            if (!sidebar.contains(e.target) && (menuToggle && !menuToggle.contains(e.target))) {
                sidebar.classList.remove('active');
            }
        }
    });

    // Cerrar sidebar al hacer clic en un item del menú (móvil)
    document.querySelectorAll('.sidebar-menu a, .sidebar-menu .menu-item').forEach(function(item) {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                const sidebar = document.getElementById('sidebar');
                if (sidebar) {
                    sidebar.classList.remove('active');
                }
            }
        });
    });