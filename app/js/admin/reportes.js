/**
 * Lógica mínima para la vista de reportes
 * La generación de reportes se maneja ahora mediante enlaces directos (PHP)
 */
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle para móvil (funcionalidad general del sistema)
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 992 && sidebar) {
                sidebar.classList.remove('active');
            }
        });
    });
});