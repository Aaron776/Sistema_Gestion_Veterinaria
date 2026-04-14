// Función para obtener clase del badge según estado
    function getBadgeClass(estado) {
        switch (estado) {
            case 'Pendiente':
                return 'badge-pendiente';
            case 'Confirmada':
                return 'badge-confirmada';
            case 'Atendida':
                return 'badge-atendida';
            case 'Cancelada':
                return 'badge-cancelada';
            default:
                return 'badge-pendiente';
        }
    }

    // Función para obtener texto del badge según estado
    function getBadgeText(estado) {
        switch (estado) {
            case 'Pendiente':
                return '⏳ Pendiente';
            case 'Confirmada':
                return '✅ Confirmada';
            case 'Atendida':
                return '📋 Atendida';
            case 'Cancelada':
                return '❌ Cancelada';
            default:
                return estado;
        }
    }