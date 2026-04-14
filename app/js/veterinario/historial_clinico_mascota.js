// Filtrar registros
    function filtrarRegistros() {
        let filtrados = [...registrosData];

        if (currentSearch) {
            const searchLower = currentSearch.toLowerCase();
            filtrados = filtrados.filter(r =>
                r.diagnostico.toLowerCase().includes(searchLower) ||
                r.tratamiento.toLowerCase().includes(searchLower) ||
                r.sintomas.toLowerCase().includes(searchLower)
            );
        }

        return filtrados;
    }

    // Eventos
    document.getElementById('menuToggle').addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('active');
    });

    document.getElementById('searchRegistro').addEventListener('input', (e) => {
        currentSearch = e.target.value;
        currentPage = 1;
        renderTable();
    });

    // Confirmación para eliminar registro
    document.querySelectorAll('.btn-delete-history').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');

            Swal.fire({
                title: '¿Eliminar registro?',
                text: '¿Estás seguro de eliminar este registro del historial clínico? Esta acción no se puede deshacer.',
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