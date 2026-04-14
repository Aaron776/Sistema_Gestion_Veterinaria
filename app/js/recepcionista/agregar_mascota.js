// Obtener icono según especie
    function getIconoEspecie(especie) {
        const iconos = {
            'Perro': '🐕',
            'Gato': '🐈',
            'Ave': '🦜',
            'Roedor': '🐹',
            'Otro': '🐾'
        };
        return iconos[especie] || '🐾';
    }

    // Vista previa en tiempo real
    function actualizarVistaPrevia() {
        const nombre = document.getElementById('nombreMascota').value.trim();
        const especie = document.getElementById('especieMascota').value;
        const raza = document.getElementById('razaMascota').value.trim();
        const sexo = document.querySelector('input[name="sexo"]:checked');
        const fechaNacimiento = document.getElementById('fechaNacimiento').value;
        const fotoInput = document.getElementById('fotoMascota');

        const previewCard = document.getElementById('previewCard');
        const previewNombre = document.getElementById('previewNombre');
        const previewEspecie = document.getElementById('previewEspecie');
        const previewRaza = document.getElementById('previewRaza');
        const previewSexo = document.getElementById('previewSexo');
        const previewEdad = document.getElementById('previewEdad');
        const previewFotoContainer = document.getElementById('previewFotoContainer');

        let tieneDatos = false;

        // Foto preview
        if (fotoInput.files && fotoInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewFotoContainer.innerHTML = `<img src="${e.target.result}" class="preview-foto" alt="Foto">`;
            }
            reader.readAsDataURL(fotoInput.files[0]);
            tieneDatos = true;
        } else {
            previewFotoContainer.innerHTML = `<div class="preview-foto-placeholder"><i class="fas fa-camera"></i></div>`;
        }

        if (nombre) {
            previewNombre.textContent = nombre;
            tieneDatos = true;
        } else {
            previewNombre.textContent = '—';
        }

        if (especie) {
            previewEspecie.textContent = `${getIconoEspecie(especie)} ${especie}`;
            tieneDatos = true;
        } else {
            previewEspecie.textContent = '—';
        }

        if (raza) {
            previewRaza.textContent = raza;
            tieneDatos = true;
        } else {
            previewRaza.textContent = '—';
        }

        if (sexo) {
            previewSexo.textContent = sexo.value === 'M' ? '♂ Macho' : '♀ Hembra';
            tieneDatos = true;
        } else {
            previewSexo.textContent = '—';
        }

        if (fechaNacimiento) {
            previewEdad.textContent = fechaNacimiento;
            tieneDatos = true;
        } else {
            previewEdad.textContent = 'No especificada';
        }
    }

    // Eventos para vista previa
    document.getElementById('nombreMascota').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('especieMascota').addEventListener('change', actualizarVistaPrevia);
    document.getElementById('razaMascota').addEventListener('input', actualizarVistaPrevia);
    document.querySelectorAll('input[name="sexo"]').forEach(radio => {
        radio.addEventListener('change', actualizarVistaPrevia);
    });
    document.getElementById('fechaNacimiento').addEventListener('change', actualizarVistaPrevia);
    document.getElementById('fotoMascota').addEventListener('change', actualizarVistaPrevia);



    // Limpiar formulario
    function limpiarFormulario() {
        document.getElementById('registroMascotaForm').reset();
        document.querySelectorAll('input[name="sexo"]').forEach(radio => radio.checked = false);
        actualizarVistaPrevia();
        actualizarEstiloSexo();
        document.getElementById('nombreMascota').focus();
    }

    // Preview de imagen
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
            previewImg.src = '';
        }
    }

    // Toggle sidebar
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });

    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('active');
            }
        });
    });
    actualizarVistaPrevia();