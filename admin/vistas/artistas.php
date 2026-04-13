<div class="container-fluid pt-4 fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 data-key="adm_art_titulo"><i class="fas fa-microphone text-primary me-2"></i> Gestión de Artistas</h2>
        <button class="btn btn-primary shadow-sm" onclick="abrirModalArtista()">
            <i class="fas fa-plus me-1"></i> <span data-key="adm_art_nuevo">Nuevo Artista</span>
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="px-4" data-key="adm_art_th_foto">Foto</th>
                            <th data-key="adm_art_th_nombre">Nombre Artístico</th>
                            <th data-key="adm_art_th_bio">Biografía</th>
                            <th data-key="adm_art_th_estado">Estado</th>
                            <th class="text-center px-4" data-key="adm_usr_th_acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaArtistas">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalArtista" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formularioArtista" onsubmit="guardarArtista(event)" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tituloModalArtista">Nuevo Artista</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id_artista" name="id_artista" value="0">
                    <input type="hidden" name="caso" value="guardar_artista">
                    
                    <div class="mb-3">
                        <label for="nombre_artistico" class="form-label fw-bold" data-key="adm_art_th_nombre">Nombre Artístico <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre_artistico" name="nombre_artistico" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="biografia" class="form-label fw-bold" data-key="adm_art_th_bio">Biografía</label>
                        <textarea class="form-control" id="biografia" name="biografia" rows="3" placeholder="Breve historia del artista..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="foto" class="form-label fw-bold" data-key="adm_art_foto">Foto de Perfil</label>
                        <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                        <small class="text-muted">Dejar vacío si no desea cambiar la foto actual.</small>
                    </div>
                    
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input fs-5" type="checkbox" id="verificado" name="verificado" value="1">
                        <label class="form-check-label ms-2 mt-1 fw-bold text-primary" for="verificado" data-key="adm_art_verificado">Cuenta Verificada</label>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" data-key="btn_cancelar">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-submit-artista" data-key="btn_guardar">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Función para listar y pintar la tabla
    function cargarArtistas() {
        let datos = new FormData();
        datos.append('caso', 'listar_artistas');

        fetch('php/queries.php', { method: 'POST', body: datos })
            .then(res => res.json())
            .then(data => {
                let tbody = document.getElementById('cuerpoTablaArtistas');
                let html = '';
                
                if(data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-5"><i class="fas fa-microphone-alt-slash fa-2x mb-3 d-block opacity-50"></i>No hay artistas registrados.</td></tr>';
                    return;
                }

                data.forEach(artista => {
                    // AQUÍ ESTÁ LA MAGIA: Ajustamos la ruta agregando ".." al inicio
                    let rutaImg = artista.ruta_foto_perfil ? '..' + artista.ruta_foto_perfil : '../assets/img/logo_soundverse_white.png';
                    
                    let bio = artista.biografia ? artista.biografia : '<span class="text-muted fst-italic">Sin biografía</span>';
                    let estado = artista.estado_disponible == 1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                    let verificado = artista.verificado == 1 ? ' <i class="fas fa-check-circle text-primary ms-1" title="Verificado"></i>' : '';

                    html += `<tr>
                        <td class="px-4">
                            <img src="${rutaImg}" alt="Foto" style="width: 55px; height: 55px; object-fit: cover; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.15);" onerror="this.src='../assets/img/logo_soundverse_white.png'">
                        </td>
                        <td class="fw-bold">${artista.nombre_artistico}${verificado}</td>
                        <td><div class="text-truncate text-muted" style="max-width: 250px;" title="${artista.biografia || ''}">${bio}</div></td>
                        <td>${estado}</td>
                        <td class="text-center px-4">
                            <button class="btn btn-sm btn-warning rounded-circle shadow-sm" title="Editar" onclick='editarArtista(${JSON.stringify(artista)})'><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger rounded-circle shadow-sm ms-1" title="Eliminar" onclick="eliminarArtista(${artista.PK_id_artista})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                });
                tbody.innerHTML = html;
            })
            .catch(err => console.error("Error cargando artistas:", err));
    }

    // Configurar modal para NUEVO artista
    window.abrirModalArtista = function() {
        document.getElementById('formularioArtista').reset();
        document.getElementById('id_artista').value = '0';
        document.getElementById('tituloModalArtista').innerHTML = '<i class="fas fa-plus-circle me-2"></i> Nuevo Artista';
        document.getElementById('btn-submit-artista').classList.replace('btn-warning', 'btn-primary');
        
        var modal = new bootstrap.Modal(document.getElementById('modalArtista'));
        modal.show();
    }

    // Configurar modal para EDITAR artista
    window.editarArtista = function(artista) {
        document.getElementById('id_artista').value = artista.PK_id_artista;
        document.getElementById('nombre_artistico').value = artista.nombre_artistico;
        document.getElementById('biografia').value = artista.biografia;
        document.getElementById('verificado').checked = (artista.verificado == 1);
        
        document.getElementById('tituloModalArtista').innerHTML = '<i class="fas fa-edit me-2"></i> Editar Artista';
        document.getElementById('btn-submit-artista').classList.replace('btn-primary', 'btn-warning');
        
        var modal = new bootstrap.Modal(document.getElementById('modalArtista'));
        modal.show();
    }

    // Enviar datos al servidor
    window.guardarArtista = function(e) {
        e.preventDefault();
        let form = document.getElementById('formularioArtista');
        let formData = new FormData(form);

        fetch('php/queries.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire('¡Éxito!', data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalArtista')).hide();
                    cargarArtistas();
                } else {
                    Swal.fire('Atención', data.message, 'warning');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Problema al comunicarse con el servidor.', 'error');
            });
    }

    // Eliminar lógicamente al artista
    window.eliminarArtista = function(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "El artista se ocultará del sistema y catálogos.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let datos = new FormData();
                datos.append('caso', 'eliminar_artista');
                datos.append('id', id);

                fetch('php/queries.php', { method: 'POST', body: datos })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            Swal.fire('¡Eliminado!', data.message, 'success');
                            cargarArtistas();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
            }
        });
    }

    // Inicializar la tabla al entrar a la vista
    cargarArtistas();
</script>