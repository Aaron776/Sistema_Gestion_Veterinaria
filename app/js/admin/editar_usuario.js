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
            if(window.innerWidth <= 992) {
                sidebar.classList.remove('active');
            }
        });
    });