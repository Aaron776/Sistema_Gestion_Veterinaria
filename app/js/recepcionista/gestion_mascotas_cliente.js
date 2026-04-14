 // Toggle menu para responsive
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // Obtener icono según especie
    function getIconoEspecie(especie) {
        const iconos = {
            'Perro': 'fa-dog',
            'Gato': 'fa-cat',
            'Ave': 'fa-dove',
            'Roedor': 'fa-rabbit',
            'Otro': 'fa-paw'
        };
        return iconos[especie] || 'fa-paw';
    }

    // SweetAlert para confirmar eliminación
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');

            Swal.fire({
                title: '¿Eliminar mascota?',
                text: '¿Estás seguro de eliminar esta mascota? Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });