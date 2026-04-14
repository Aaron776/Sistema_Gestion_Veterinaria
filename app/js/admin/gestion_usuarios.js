// Sidebar toggle para móvil
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function toggleSidebar() {
    sidebar.classList.toggle('active');
    sidebarOverlay.classList.toggle('active');
}

if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', toggleSidebar);
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', toggleSidebar);
}

const menuItems = document.querySelectorAll('.menu-item');
menuItems.forEach(item => {
    item.addEventListener('click', () => {
        if (window.innerWidth <= 992) {
            toggleSidebar();
        }
    });
});

// Tiempo relativo para último acceso
function formatRelativeTime() {
    document.querySelectorAll('td[data-ultimo-acceso]').forEach(td => {
        const dateString = td.getAttribute('data-ultimo-acceso');
        const span = td.querySelector('.tiempo-relativo');
        if (!span || !dateString) return;
        
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);

        let text = '';
        if (diff < 60) {
            text = 'Hace un momento';
        } else if (diff < 3600) {
            const mins = Math.floor(diff / 60);
            text = `Hace ${mins} minuto${mins > 1 ? 's' : ''}`;
        } else if (diff < 86400) {
            const hours = Math.floor(diff / 3600);
            text = `Hace ${hours} hora${hours > 1 ? 's' : ''}`;
        } else if (diff < 604800) {
            const days = Math.floor(diff / 86400);
            text = `Hace ${days} día${days > 1 ? 's' : ''}`;
        } else {
            const weeks = Math.floor(diff / 604800);
            text = `Hace ${weeks} semana${weeks > 1 ? 's' : ''}`;
        }
        
        span.innerHTML = `<i class="fas fa-clock"></i> ${text}`;
    });
}

// Paginador
let currentPage = 1;
let rowsPerPage = 10;

function getDataRows() {
    return document.querySelectorAll('#tableBody tr:not(:has(.empty-state))');
}

function updateTable() {
    const rows = getDataRows();
    const totalRows = rows.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);

    if (currentPage > totalPages && totalPages > 0) {
        currentPage = totalPages;
    }
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    rows.forEach((row, index) => {
        row.style.display = (index >= start && index < end) ? '' : 'none';
    });

    renderPagination(totalPages, totalRows);
}

function renderPagination(totalPages, totalRows) {
    const container = document.getElementById('paginationContainer');
    
    if (totalRows === 0) {
        container.innerHTML = `
            <div class="pagination-info">
                No hay usuarios registrados
            </div>
            <div class="pagination-controls"></div>
        `;
        return;
    }

    const start = (currentPage - 1) * rowsPerPage + 1;
    const end = Math.min(currentPage * rowsPerPage, totalRows);

    let html = `
        <div class="pagination-info">
            <span>Página <strong>${currentPage}</strong> de <strong>${totalPages}</strong></span>
            <span style="margin: 0 10px;">|</span>
            Mostrando <strong>${start}</strong> - <strong>${end}</strong> de <strong>${totalRows}</strong> usuarios
        </div>
        <div class="pagination-controls">
            <button class="page-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
    `;

    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
    }

    html += `
            <button class="page-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    `;

    container.innerHTML = html;
}

function goToPage(page) {
    const rows = getDataRows();
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    updateTable();
}

// SweetAlert para eliminar
document.addEventListener('DOMContentLoaded', function() {
    formatRelativeTime();
    
    document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            const form = document.getElementById('formEliminar_' + id);
            
            Swal.fire({
                title: '¿Eliminar usuario?',
                html: `<p style="color: #2c3e50;">¿Estás seguro de eliminar al usuario <strong>${nombre}</strong>?</p><p style="color: #e74c3c; font-size: 0.9rem; margin-top: 10px;"><i class="fas fa-exclamation-triangle"></i> Esta acción no se puede deshacer.</p>`,
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

    // SweetAlert para cambiar contraseña
    document.querySelectorAll('.formPassword').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Cambiar contraseña?',
                html: '<p style="color: #2c3e50;">Se generará una <strong>nueva contraseña automática</strong> y se enviará por correo electrónico a este usuario.</p><p style="color: #6bcb77; font-size: 0.9rem; margin-top: 10px;"><i class="fas fa-envelope"></i> Asegúrate de que el usuario tenga un correo válido.</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3498db',
                cancelButtonColor: '#6c8d9e',
                confirmButtonText: '<i class="fas fa-key"></i> Sí, cambiar',
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });

    updateTable();
});
