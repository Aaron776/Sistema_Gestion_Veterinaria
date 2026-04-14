document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('tableBody');
    const searchInput = document.getElementById('searchProducto');
    const stockFilter = document.getElementById('stockFilter');
    const noResultsRow = document.getElementById('noResultsRow');

    function filterTable() {
        const searchText = searchInput.value.toLowerCase();
        const filterValue = stockFilter.value;
        const rows = document.querySelectorAll('.row-inventario');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const badge = row.querySelector('.stock-badge');
            
            let matchesSearch = rowText.includes(searchText);
            let matchesFilter = true;

            if (filterValue !== 'all' && badge) {
                if (filterValue === 'normal' && !badge.classList.contains('stock-normal')) matchesFilter = false;
                if (filterValue === 'low' && !badge.classList.contains('stock-low')) matchesFilter = false;
                if (filterValue === 'critical' && !badge.classList.contains('stock-critical')) matchesFilter = false;
            }

            if (matchesSearch && matchesFilter) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Mostrar u ocultar mensaje de "No se encontraron resultados"
        if (noResultsRow) {
            noResultsRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
    }

    // Eventos de filtrado
    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
    }
    
    if (stockFilter) {
        stockFilter.addEventListener('change', filterTable);
    }

    // Sidebar toggle para móvil
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

    // SweetAlert para eliminar producto
    document.querySelectorAll('.btn-eliminar').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.closest('form');

            Swal.fire({
                title: '¿Eliminar producto?',
                html: `<p style="color: #2c3e50;">¿Estás seguro de eliminar este producto del inventario?</p><p style="color: #e74c3c; font-size: 0.9rem; margin-top: 10px;"><i class="fas fa-exclamation-triangle"></i> Esta acción no se puede deshacer.</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c8d9e',
                confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar',
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});