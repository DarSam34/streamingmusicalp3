<div class="container-fluid pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestión de Álbumes</h2>
        <button class="btn btn-primary" onclick="abrirModalAlbum()">
            <i class="fas fa-plus"></i> Nuevo Álbum
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Portada</th>
                            <th>Título</th>
                            <th>Artista</th>
                            <th>Discográfica</th>
                            <th>Fecha</th>
                            <th>Duración</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaAlbumes">
                        <tr><td colspan="7" class="text-center">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para nuevo/editar álbum -->
<div class="modal fade" id="modalAlbum" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formularioAlbum" onsubmit="guardarAlbum(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalAlbum">Nuevo Álbum</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id_album" name="id_album" value="0">
                    <input type="hidden" name="caso" value="guardar_album">
                    
                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" class="form-control" id="titulo_album" name="titulo" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Artista *</label>
                        <select class="form-select" id="fk_artista" name="fk_artista" required>
                            <option value="">Seleccione un artista...</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha de Lanzamiento *</label>
                        <input type="date" class="form-control" id="fecha_lanzamiento" name="fecha_lanzamiento" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sello Discográfico</label>
                        <input type="text" class="form-control" id="discografica_album" name="discografica" placeholder="Ej. Universal Music">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Portada</label>
                        <input type="file" class="form-control" id="portada" name="portada" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function cargarAlbumes() {
    fetch('http://localhost/soundverse-streaming/admin/php/queries.php?caso=listar_albumes')
        .then(response => response.json())
        .then(data => {
            let tbody = document.getElementById('cuerpoTablaAlbumes');
            if (!tbody) return;
            tbody.innerHTML = '';
            if (data.status === 'success' && data.data) {
                for (let i = 0; i < data.data.length; i++) {
                    let a = data.data[i];
                    let portada = a.ruta_portada ? '../' + a.ruta_portada : '../assets/img/logo_soundverse_white.png';
                    tbody.innerHTML += `
                        <tr>
                            <td><img src="${portada}" style="width:50px;height:50px;object-fit:cover;border-radius:8px;"></td>
                            <td>${a.titulo}</td>
                            <td>${a.nombre_artistico}</td>
                            <td>${a.discografica || '-'}</td>
                            <td>${a.fecha_lanzamiento || 'N/A'}</td>
                            <td>${a.duracion_formateada || '0:00'}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning" onclick="editarAlbum(${a.PK_id_album})"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarAlbum(${a.PK_id_album})"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    `;
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

function cargarSelectArtistas() {
    fetch('http://localhost/soundverse-streaming/admin/php/queries.php?caso=listar_artistas')
        .then(response => response.json())
        .then(data => {
            let select = document.getElementById('fk_artista');
            if (!select) return;
            select.innerHTML = '<option value="">Seleccione un artista...</option>';
            if (Array.isArray(data)) {
                data.forEach(artista => {
                    select.innerHTML += `<option value="${artista.PK_id_artista}">${artista.nombre_artistico}</option>`;
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

function abrirModalAlbum() {
    document.getElementById('formularioAlbum').reset();
    document.getElementById('id_album').value = '0';
    document.getElementById('tituloModalAlbum').innerText = 'Nuevo Álbum';
    cargarSelectArtistas();
    new bootstrap.Modal(document.getElementById('modalAlbum')).show();
}

function editarAlbum(id) {
    fetch(`http://localhost/soundverse-streaming/admin/php/queries.php?caso=obtener_album&id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('id_album').value = data.PK_id_album;
            document.getElementById('titulo_album').value = data.titulo;
            document.getElementById('fecha_lanzamiento').value = data.fecha_lanzamiento;
            document.getElementById('discografica_album').value = data.discografica || '';
            document.getElementById('tituloModalAlbum').innerText = 'Editar Álbum';
            cargarSelectArtistas().then(() => {
                document.getElementById('fk_artista').value = data.FK_id_artista;
            });
            new bootstrap.Modal(document.getElementById('modalAlbum')).show();
        })
        .catch(error => console.error('Error:', error));
}

function guardarAlbum(event) {
    event.preventDefault();
    let formData = new FormData(document.getElementById('formularioAlbum'));
    
    fetch('http://localhost/soundverse-streaming/admin/php/queries.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire('Éxito', data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalAlbum')).hide();
            cargarAlbumes();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => console.error('Error:', error));
}

function eliminarAlbum(id) {
    Swal.fire({
        title: '¿Eliminar álbum?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            let formData = new FormData();
            formData.append('caso', 'eliminar_album');
            formData.append('id', id);
            fetch('http://localhost/soundverse-streaming/admin/php/queries.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Eliminado', data.message, 'success');
                    cargarAlbumes();
                }
            });
        }
    });
}

cargarAlbumes();
</script>