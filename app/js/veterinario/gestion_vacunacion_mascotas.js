// Manejo de la confirmación de eliminación con SweetAlert2
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.form-eliminar');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción eliminará el registro de la vacuna permanentemente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c', // Rojo para peligro
                cancelButtonColor: '#7f9aab',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});

// Filtrar vacunas
    function filtrarVacunas() {
        let filtradas = [...vacunasData];

        if (currentSearch) {
            const searchLower = currentSearch.toLowerCase();
            filtradas = filtradas.filter(v =>
                v.nombre.toLowerCase().includes(searchLower)
            );
        }

        return filtradas;
    }

    // Eventos
    document.getElementById('menuToggle').addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('active');
    });

    document.getElementById('btnAgregarVacuna').addEventListener('click', abrirModalNuevaVacuna);
    document.getElementById('vacunaForm').addEventListener('submit', guardarVacuna);

    document.getElementById('searchVacuna').addEventListener('input', (e) => {
        currentSearch = e.target.value;
        currentPage = 1;
        renderTable();
    });