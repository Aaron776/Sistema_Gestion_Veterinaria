function getIconoEspecie(especie) {
        const iconos = { 'perro': '🐕', 'gato': '🐈', 'ave': '🦜', 'roedor': '🐹', 'otro': '🐾' };
        return iconos[especie] || '🐾';
    }

    function actualizarVistaPrevia() {
        const nombre = document.getElementById('nombreMascota').value.trim();
        const especie = document.getElementById('especieMascota').value;
        const raza = document.getElementById('razaMascota').value.trim();
        const sexo = document.querySelector('input[name="sexo"]:checked');
        const fecha = document.getElementById('fechaNacimiento').value;

        document.getElementById('previewNombre').textContent = nombre || '—';
        document.getElementById('previewEspecie').textContent = especie ? `${getIconoEspecie(especie)} ${especie.charAt(0).toUpperCase() + especie.slice(1)}` : '—';
        document.getElementById('previewRaza').textContent = raza || '—';
        
        if (sexo) {
            document.getElementById('previewSexo').textContent = sexo.value === 'M' ? '♂ Macho' : '♀ Hembra';
            document.getElementById('sexoMachoLabel').classList.toggle('selected', sexo.value === 'M');
            document.getElementById('sexoHembraLabel').classList.toggle('selected', sexo.value === 'F');
        }

        document.getElementById('previewEdad').textContent = fecha || 'No especificada';
    }

    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const previewFotoContainer = document.getElementById('previewFotoContainer');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
                previewFotoContainer.innerHTML = `<img src="${e.target.result}" class="preview-foto" alt="Nueva Foto">`;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById('nombreMascota').addEventListener('input', actualizarVistaPrevia);
    document.getElementById('especieMascota').addEventListener('change', actualizarVistaPrevia);
    document.getElementById('razaMascota').addEventListener('input', actualizarVistaPrevia);
    document.querySelectorAll('input[name="sexo"]').forEach(r => r.addEventListener('change', actualizarVistaPrevia));
    document.getElementById('fechaNacimiento').addEventListener('change', actualizarVistaPrevia);

    document.addEventListener('DOMContentLoaded', actualizarVistaPrevia);