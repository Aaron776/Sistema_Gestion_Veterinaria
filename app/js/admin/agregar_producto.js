// Formatear precio a moneda local
function formatPrecio(num) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(num);
}

// Vista previa en tiempo real
function actualizarVistaPrevia() {
    const nombre = document.getElementById('nombreProducto').value.trim();
    const stock = document.getElementById('stock').value;
    const precio = document.getElementById('precio').value;

    const previewCard = document.getElementById('previewCard');
    const previewNombre = document.getElementById('previewNombre');
    const previewStock = document.getElementById('previewStock');
    const previewPrecio = document.getElementById('previewPrecio');

    let tieneDatos = false;

    if (nombre) {
        previewNombre.textContent = nombre;
        tieneDatos = true;
    } else {
        previewNombre.textContent = '—';
    }

    if (stock !== '' && stock !== null) {
        const stockNum = parseInt(stock);
        previewStock.textContent = stockNum + ' unidades';
        if (stockNum === 0) {
            previewStock.style.color = '#e74c3c';
        } else if (stockNum <= 10) {
            previewStock.style.color = '#f39c12';
        } else {
            previewStock.style.color = '#1f3e4b';
        }
        tieneDatos = true;
    } else {
        previewStock.textContent = '—';
    }

    if (precio !== '' && precio !== null && parseFloat(precio) > 0) {
        previewPrecio.textContent = formatPrecio(parseFloat(precio));
        tieneDatos = true;
    } else {
        previewPrecio.textContent = '—';
    }

    previewCard.style.display = tieneDatos ? 'block' : 'none';
}

// Eventos para vista previa
document.getElementById('nombreProducto').addEventListener('input', actualizarVistaPrevia);
document.getElementById('stock').addEventListener('input', actualizarVistaPrevia);
document.getElementById('precio').addEventListener('input', actualizarVistaPrevia);

// Limpiar formulario
function limpiarFormulario() {
    document.getElementById('registroProductoForm').reset();
    document.getElementById('stock').value = '0';
    actualizarVistaPrevia();
    document.getElementById('nombreProducto').focus();
}

// Inicializar vista previa
actualizarVistaPrevia();
