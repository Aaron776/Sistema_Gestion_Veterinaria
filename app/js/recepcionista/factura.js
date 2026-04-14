 let globalTotal = 0;

    function recalcularTodo() {
        let total = 0;
        
        // Sumar bloqueados (Servicios Médicos)
        document.querySelectorAll('.locked-item').forEach(item => {
            const price = parseFloat(item.getAttribute('data-price'));
            const qty = parseInt(item.querySelector('input').value);
            total += (price * qty);
        });

        // Sumar Dinámicos (Productos Extra)
        document.querySelectorAll('.dynamic-item').forEach(item => {
            const price = parseFloat(item.getAttribute('data-price'));
            const qty = parseInt(item.querySelector('.dyn-qty').value) || 0;
            
            // Re-render subtotal UI for this row
            const subt = price * qty;
            item.querySelector('.item-subtotal').textContent = '$' + subt.toFixed(2);
            
            total += subt;
        });

        globalTotal = total;
        document.getElementById('granTotalView').textContent = '$' + globalTotal.toFixed(2);
        document.getElementById('totalCalculadoInput').value = globalTotal;
        
        // Disparar recálculo de cambio
        calcularCambio();
        crearInputsOcultos();
    }

    // Agregar Producto Extra
    document.getElementById('btnAddProduct').addEventListener('click', function() {
        const select = document.getElementById('productoSelect');
        const selected = select.options[select.selectedIndex];
        
        if(!selected.value) return;

        const id = selected.value;
        const nombre = selected.getAttribute('data-nombre');
        const precio = parseFloat(selected.getAttribute('data-precio'));
        const stockMax = parseInt(selected.getAttribute('data-stock'));

        // Prevenir duplicados (aumentar cant)
        const existe = document.querySelector(`.dynamic-item[data-id="${id}"]`);
        if(existe) {
            const inp = existe.querySelector('.dyn-qty');
            if(parseInt(inp.value) < stockMax) {
                inp.value = parseInt(inp.value) + 1;
                recalcularTodo();
            } else {
                alert("Stock máximo alcanzado para este producto.");
            }
            return;
        }

        const tr = document.createElement('div');
        tr.className = 'ticket-row dynamic-item';
        tr.setAttribute('data-id', id);
        tr.setAttribute('data-price', precio);

        tr.innerHTML = `
            <div>
                <div class="item-name"><i class="fas fa-box-open" style="color:#f39c12;"></i> ${nombre}</div>
                <span class="item-meta">Mostrador (Inventario)</span>
            </div>
            <div class="item-qty">
                <input type="number" class="dyn-qty" value="1" min="1" max="${stockMax}" step="1">
            </div>
            <div class="item-price">$${precio.toFixed(2)}</div>
            <div class="item-subtotal">$${precio.toFixed(2)}</div>
            <button type="button" class="btn-trash" onclick="this.closest('.ticket-row').remove(); recalcularTodo();"><i class="fas fa-trash"></i></button>
        `;
        
        document.getElementById('ticketItems').appendChild(tr);
        recalcularTodo();
        
        // Event listeners locales para la nueva fila
        tr.querySelector('.dyn-qty').addEventListener('input', function() {
            let val = parseInt(this.value);
            if(val > stockMax) this.value = stockMax;
            if(val < 1 || isNaN(val)) this.value = 1;
            recalcularTodo();
        });
    });

    // Selector de Método de pago
    const methodBtns = document.querySelectorAll('.method-option');
    methodBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            methodBtns.forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            
            const method = this.getAttribute('data-method');
            document.getElementById('metodoPagoInput').value = method;

            if(method === 'efectivo') {
                document.getElementById('recibidoGroup').style.display = 'block';
                document.getElementById('cambioBox').style.display = 'flex';
            } else {
                document.getElementById('recibidoGroup').style.display = 'none';
                document.getElementById('cambioBox').style.display = 'none';
                document.getElementById('inputRecibido').value = globalTotal.toFixed(2);
            }
            calcularCambio();
        });
    });

    // Matemáticas de Cambio
    document.getElementById('inputRecibido').addEventListener('input', calcularCambio);

    function calcularCambio() {
        const recibido = parseFloat(document.getElementById('inputRecibido').value) || 0;
        document.getElementById('montoCobradoInput').value = recibido;
        const metodo = document.getElementById('metodoPagoInput').value;

        if (metodo === 'efectivo') {
            const cambio = recibido - globalTotal;
            if(cambio > 0) {
                document.getElementById('cambioView').textContent = '$' + cambio.toFixed(2);
                document.getElementById('cambioView').style.color = '#6bcb77';
            } else {
                document.getElementById('cambioView').textContent = '$0.00';
                document.getElementById('cambioView').style.color = '#e74c3c';
            }
        }
    }

    // Preparar el motor para Enviar Arrays limpios al POST
    function crearInputsOcultos() {
        const area = document.getElementById('hiddenInputsArea');
        area.innerHTML = ''; // wipe
        
        // 1. Array de Servicios Médicos obligatorios
        document.querySelectorAll('.locked-item').forEach(item => {
            const id = item.getAttribute('data-id');
            const pr = item.getAttribute('data-price');
            const qt = item.querySelector('input').value;
            area.innerHTML += `<input type="hidden" name="servicios_id[]" value="${id}">`;
            area.innerHTML += `<input type="hidden" name="servicios_qty[]" value="${qt}">`;
            area.innerHTML += `<input type="hidden" name="servicios_prc[]" value="${pr}">`;
        });

        // 2. Array de Productos Inventario
        document.querySelectorAll('.dynamic-item').forEach(item => {
            const id = item.getAttribute('data-id');
            const pr = item.getAttribute('data-price');
            const qt = item.querySelector('input').value;
            area.innerHTML += `<input type="hidden" name="productos_id[]" value="${id}">`;
            area.innerHTML += `<input type="hidden" name="productos_qty[]" value="${qt}">`;
            area.innerHTML += `<input type="hidden" name="productos_prc[]" value="${pr}">`;
        });
    }

    // Submit
    document.getElementById('btnProcesarPago').addEventListener('click', function() {
        crearInputsOcultos();
        
        const metodo = document.getElementById('metodoPagoInput').value;
        const recibido = parseFloat(document.getElementById('inputRecibido').value) || 0;
        
        if(metodo === 'efectivo' && recibido < globalTotal) {
            alert('Error: El monto recibido en efectivo es MENOR al total de la factura.');
            return;
        }
        
        if(confirm('¿Confirmar cobro y cerrar caja? Esta acción descontará inventario y generará la factura.')){
            document.getElementById('posForm').submit();
        }
    });

    // Init Math
    recalcularTodo();