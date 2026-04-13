<div class="container-fluid">
    <h2><i class="fas fa-music me-2 text-primary"></i> <span data-key="cat_titulo">Catálogo Musical</span></h2>
    <p class="text-muted" data-key="cat_subtitulo">Explora los álbumes o descubre todas las canciones disponibles.</p>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-4" id="catalogoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-dark" id="albumes-tab" data-bs-toggle="tab" data-bs-target="#albumes" type="button" role="tab" aria-controls="albumes" aria-selected="true" onclick="cargarAlbumes()">
                <i class="fas fa-compact-disc me-1 text-primary"></i> Álbumes
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark" id="canciones-tab" data-bs-toggle="tab" data-bs-target="#canciones" type="button" role="tab" aria-controls="canciones" aria-selected="false" onclick="cargarTodasCanciones()">
                <i class="fas fa-list me-1 text-primary"></i> Todas las Canciones
            </button>
        </li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content" id="catalogoTabsContent">
        <!-- Pestaña: Álbumes -->
        <div class="tab-pane fade show active" id="albumes" role="tabpanel" aria-labelledby="albumes-tab">
            <div class="row" id="lista-albumes">
                <div class="col-12 text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Cargando álbumes...</p>
                </div>
            </div>

            <div class="mt-4">
                <h4 class="fw-bold">Canciones del álbum</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Título</th>
                                <th>Artista</th>
                                <th>Duración</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-canciones">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <i class="fas fa-hand-point-up me-1"></i> Selecciona un álbum
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pestaña: Todas las canciones -->
        <div class="tab-pane fade" id="canciones" role="tabpanel" aria-labelledby="canciones-tab">
            <div class="row mb-3 align-items-center">
                <div class="col-md-8">
                    <input type="text" id="buscador-todas-canciones" class="form-control" placeholder="Buscar por título, artista o álbum..." onkeyup="filtrarTodasCanciones()">
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-primary rounded-pill fw-bold shadow-sm w-100 mt-2 mt-md-0" onclick="reproducirGlobal()">
                        <i class="fas fa-random me-1"></i> Aleatorio Global
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="tabla-global-canciones">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Título</th>
                            <th>Álbum / Artista</th>
                            <th>Duración</th>
                            <th class="text-end px-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-todas-canciones">
                        <tr><td colspan="5" class="text-center"><div class="spinner-border text-primary spinner-border-sm"></div> Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function cargarAlbumes() {
    $.ajax({
        url: 'php/queries.php?caso=listar_albumes',
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            // Extraer datos correctamente
            let datos = [];
            if (resp.status === 'success' && resp.data) {
                datos = resp.data;
            } else if (Array.isArray(resp)) {
                datos = resp;
            }

            if (datos.length === 0) {
                $('#lista-albumes').html('<div class="col-12"><p class="text-muted">No hay álbumes disponibles.</p></div>');
                return;
            }

            let html = '';
            datos.forEach(function(album) {
                let rutaPortada = '../assets/img/logo_soundverse_white.png';
                if (album.ruta_portada && album.ruta_portada.trim() !== '') {
                    rutaPortada = '../' + album.ruta_portada;
                }

                html += `<div class="col-md-3 col-sm-6 mb-3">
                    <div class="card h-100 shadow-sm">
                        <img src="${rutaPortada}"
                             class="card-img-top" style="height: 150px; object-fit: cover;"
                             onerror="this.src='../assets/img/logo_soundverse_white.png'">
                        <div class="card-body">
                            <h5 class="card-title">${album.titulo}</h5>
                            <p class="card-text text-muted small mb-2">
                                <i class="fas fa-clock me-1"></i> ${album.duracion_formateada || '0:00'}
                            </p>
                            <button class="btn btn-primary btn-sm w-100"
                                    onclick="cargarCanciones(${album.PK_id_album})">
                                <i class="fas fa-list me-1"></i> Ver canciones
                            </button>
                        </div>
                    </div>
                </div>`;
            });
            $('#lista-albumes').html(html);
        },
        error: function() {
            $('#lista-albumes').html('<div class="col-12"><div class="alert alert-danger">Error al conectar con el servidor.</div></div>');
        }
    });
}

// Cache del álbum actualmente cargado (para construir la cola al reproducir)
window.albumCancionesData = [];

window.cargarCanciones = function(idAlbum) {
    $('#tbody-canciones').html('<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando...</td></tr>');

    $.ajax({
        url: 'php/queries.php?caso=canciones_por_album',
        type: 'POST',
        data: { id_album: idAlbum },
        dataType: 'json',
        success: function(resp) {
            let datos = resp.data !== undefined ? resp.data : (Array.isArray(resp) ? resp : []);

            if (resp.status === 'error') {
                $('#tbody-canciones').html('<tr><td colspan="5" class="text-center text-danger">Error al cargar canciones.</td></tr>');
                return;
            }

            let tbody = $('#tbody-canciones');
            tbody.empty();

            if (datos.length === 0) {
                tbody.append('<tr><td colspan="5" class="text-center text-muted">Este álbum no tiene canciones disponibles.</td></tr>');
                return;
            }

            // ✅ FIX COLA: Guardar todas las canciones del álbum para construir la cola
            window.albumCancionesData = datos;

            datos.forEach(function(cancion, idx) {
                let duracion = Math.floor(cancion.duracion_segundos / 60) + ':' +
                              (cancion.duracion_segundos % 60).toString().padStart(2, '0');
                
                let tituloSeguro = cancion.titulo ? cancion.titulo.replace(/'/g, "\\'") : 'Desconocido';
                let artistaSeguro = cancion.artista ? cancion.artista.replace(/'/g, "\\'") : 'Desconocido';
                let rutaAudio = cancion.ruta_archivo_audio ? '../' + cancion.ruta_archivo_audio : '';

                tbody.append(`
                    <tr>
                        <td>${idx + 1}</td>
                        <td><strong>${cancion.titulo}</strong></td>
                        <td>${cancion.artista}</td>
                        <td>${duracion}</td>
                        <td>
                            <button class="btn btn-sm btn-success me-1"
                                onclick="reproducirDesdeAlbum(${idx})">
                                <i class="fas fa-play me-1"></i> Reproducir
                            </button>
                            <button class="btn btn-sm btn-outline-secondary"
                                onclick="agregarAPlaylist(${cancion.PK_id_cancion})">
                                <i class="fas fa-plus me-1"></i> Playlist
                            </button>
                            <button class="btn btn-sm btn-outline-info"
                                onclick="iniciarRadio(${cancion.PK_id_cancion})">
                                <i class="fas fa-broadcast-tower me-1"></i> Radio
                            </button>
                            <button class="btn btn-sm btn-outline-primary"
                                onclick="descargarCancion('${tituloSeguro}', '${rutaAudio}')">
                                <i class="fas fa-download"></i>
                            </button>
                            ${cancion.sigue_artista > 0 ? 
                                `<button class="btn btn-sm btn-warning text-white" onclick="alternarSeguimiento(${cancion.PK_id_artista}, this)">
                                    <i class="fas fa-heart me-1"></i>Siguiendo
                                 </button>` : 
                                `<button class="btn btn-sm btn-outline-warning" onclick="alternarSeguimiento(${cancion.PK_id_artista}, this)">
                                    <i class="fas fa-heart me-1"></i>Seguir
                                 </button>`
                            }
                            <button class="btn btn-sm btn-outline-info"
                                onclick="verPerfilArtista(${cancion.PK_id_artista})">
                                <i class="fas fa-user-circle me-1"></i>Artista
                            </button>
                        </td>
                    </tr>
                `);
            });
        },
        error: function() {
            $('#tbody-canciones').html('<tr><td colspan="5" class="text-center text-danger">Error de conexión.</td></tr>');
        }
    });
};

/**
 * ✅ FIX COLA: Reproduce desde el álbum construyendo la cola con las canciones restantes.
 * @param {number} idx  Índice de la canción clickeada dentro de albumCancionesData
 */
window.reproducirDesdeAlbum = function(idx) {
    const lista = window.albumCancionesData;
    if (!lista || lista.length === 0) return;

    // La cola original es todo el álbum (para botón Anterior)
    window.colaOriginal = JSON.parse(JSON.stringify(lista));

    // La cola de reproducción son las canciones DESPUÉS de la clickeada
    window.colaReproduccion = lista.slice(idx + 1).map(function(c) {
        return Object.assign({}, c);
    });
    window.radioActiva = true;

    const cancion = lista[idx];
    const rutaAudio = cancion.ruta_archivo_audio ? '../' + cancion.ruta_archivo_audio : '';
    reproducirCancion(cancion.PK_id_cancion, cancion.titulo, cancion.artista, rutaAudio);
};

window.agregarAPlaylist = function(idCancion) {
    $.ajax({
        url: 'php/queries.php?caso=listar_playlists_para_agregar',
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success' && resp.data.length > 0) {
                let opciones = {};
                resp.data.forEach(function(pl) {
                    let etiqueta = pl.nombre_playlist;
                    if (parseInt(pl.es_mia) === 0) {
                        etiqueta += ` (Colaborativa - por ${pl.propietario})`;
                    }
                    opciones[pl.PK_id_playlist] = etiqueta;
                });

                Swal.fire({
                    title: 'Selecciona una playlist',
                    input: 'select',
                    inputOptions: opciones,
                    inputPlaceholder: 'Elige una playlist...',
                    showCancelButton: true,
                    confirmButtonText: 'Agregar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#6B46C1'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'php/queries.php?caso=agregar_cancion_playlist',
                            type: 'POST',
                            data: { id_playlist: result.value, id_cancion: idCancion },
                            dataType: 'json',
                            success: function(r) {
                                if (r.status === 'success') {
                                    Swal.fire('¡Listo!', 'Canción agregada a la playlist.', 'success');
                                } else {
                                    Swal.fire('Aviso', r.message || 'No se pudo agregar.', 'warning');
                                }
                            }
                        });
                    }
                });
            } else {
                Swal.fire('Sin playlists', 'Primero crea una playlist desde el menú "Mis Playlists".', 'info');
            }
        }
    });
};

window.alternarSeguimiento = function(idArtista, btnElement) {
    if (!idArtista) return;
    $.ajax({
        url: 'php/queries.php?caso=alternar_seguimiento',
        type: 'POST',
        data: { id_artista: idArtista },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                if (btnElement) {
                    const $btn = $(btnElement);
                    if (resp.accion === 'follow') {
                        $btn.removeClass('btn-outline-warning').addClass('btn-warning text-white');
                        if ($btn.text().trim().toLowerCase().includes('seguir')) {
                            $btn.html('<i class="fas fa-heart me-1"></i>Siguiendo');
                        }
                    } else {
                        $btn.removeClass('btn-warning text-white').addClass('btn-outline-warning');
                        if ($btn.text().trim().toLowerCase().includes('siguiendo')) {
                            $btn.html('<i class="fas fa-heart me-1"></i>Seguir');
                        }
                    }
                }
                
                Swal.fire({
                    title: resp.accion === 'follow' ? '¡Siguiendo!' : 'Dejaste de seguir',
                    text: resp.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Atención', resp.message || 'Error al actualizar seguimiento.', 'warning');
            }
        }
    });
};

window.descargarCancion = function(titulo, urlAudio) {
    if (!window.esPremium) {
        Swal.fire({
            title: 'Exclusivo Premium',
            text: 'Las descargas para modo offline están reservadas para usuarios Premium. ¡Mejora tu plan hoy!',
            icon: 'warning',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    const a = document.createElement('a');
    a.style.display = 'none';
    a.href = urlAudio;
    a.download = titulo + '.mp3';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
};

// === LÓGICA PARA "TODAS LAS CANCIONES" ===
window.todasCancionesData = [];

window.cargarTodasCanciones = function() {
    if (window.todasCancionesData.length > 0) return; // Ya se cargó

    $('#tbody-todas-canciones').html('<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando catálogo completo...</td></tr>');

    $.ajax({
        url: 'php/queries.php?caso=listar_canciones',
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                window.todasCancionesData = resp.data || [];
                renderizarTodasCanciones(window.todasCancionesData);
            } else {
                $('#tbody-todas-canciones').html('<tr><td colspan="5" class="text-center text-danger">Error al cargar el catálogo completo.</td></tr>');
            }
        },
        error: function() {
            $('#tbody-todas-canciones').html('<tr><td colspan="5" class="text-center text-danger">Error de conexión.</td></tr>');
        }
    });
};

function renderizarTodasCanciones(lista) {
    let tbody = $('#tbody-todas-canciones');
    tbody.empty();

    if (lista.length === 0) {
        tbody.append('<tr><td colspan="5" class="text-center text-muted">No se encontraron canciones en el catálogo.</td></tr>');
        return;
    }

    lista.forEach(function(cancion, idx) {
        let duracion = Math.floor(cancion.duracion_segundos / 60) + ':' + (cancion.duracion_segundos % 60).toString().padStart(2, '0');
        let tituloSeguro = cancion.titulo ? cancion.titulo.replace(/'/g, "\\'") : 'Desconocido';
        let artistaSeguro = cancion.artista ? cancion.artista.replace(/'/g, "\\'") : 'Desconocido';
        let rutaAudio = cancion.ruta_archivo_audio ? '../' + cancion.ruta_archivo_audio : '';

        tbody.append(`
            <tr class="cancion-row">
                <td class="text-muted">${idx + 1}</td>
                <td><strong>${cancion.titulo}</strong></td>
                <td><small class="text-muted d-block">${cancion.album}</small>${cancion.artista}</td>
                <td>${duracion}</td>
                <td class="text-end px-3">
                    <button class="btn btn-sm btn-success me-1" title="Reproducir"
                        onclick="reproducirDesdeGlobal(${idx})">
                        <i class="fas fa-play d-none d-md-inline me-1"></i> <i class="fas fa-play d-inline d-md-none"></i>
                    </button>
                    ${cancion.sigue_artista > 0 ? 
                        `<button class="btn btn-sm btn-warning text-white me-1" title="Siguiendo" onclick="alternarSeguimiento(${cancion.PK_id_artista}, this)">
                            <i class="fas fa-heart"></i>
                         </button>` : 
                        `<button class="btn btn-sm btn-outline-warning me-1" title="Seguir" onclick="alternarSeguimiento(${cancion.PK_id_artista}, this)">
                            <i class="fas fa-heart"></i>
                         </button>`
                    }
                    <button class="btn btn-sm btn-outline-secondary" title="Agregar a Playlist"
                        onclick="agregarAPlaylist(${cancion.PK_id_cancion})">
                        <i class="fas fa-plus"></i>
                    </button>
                    ${window.esPremium ? 
                        `<a href="${rutaAudio}" class="btn btn-sm btn-info text-white ms-1" download title="Descargar canción">
                            <i class="fas fa-download"></i>
                         </a>` : 
                        `<button class="btn btn-sm btn-secondary ms-1" disabled title="Descarga exclusiva Premium" 
                            onclick="Swal.fire('Exclusivo Premium', 'Mejora a Premium para descargar canciones.', 'info')">
                            <i class="fas fa-download"></i>
                         </button>`
                    }
                </td>
            </tr>
        `);
    });
}

/**
 * ✅ FIX COLA: Reproduce desde la lista global construyendo cola con las canciones restantes.
 * Usa window.todasCancionesData (catálogo completo) para encolar desde idx+1 en adelante.
 * @param {number} idx  Índice de la canción clickeada dentro de todasCancionesData
 */
window.reproducirDesdeGlobal = function(idx) {
    // Usar los datos reales (no los filtrados) para que el Siguiente funcione
    const lista = window.todasCancionesData;
    if (!lista || lista.length === 0) return;

    window.colaOriginal = JSON.parse(JSON.stringify(lista));
    window.colaReproduccion = lista.slice(idx + 1).map(function(c) {
        return Object.assign({}, c);
    });
    window.radioActiva = true;

    const cancion = lista[idx];
    const rutaAudio = cancion.ruta_archivo_audio ? '../' + cancion.ruta_archivo_audio : '';
    reproducirCancion(cancion.PK_id_cancion, cancion.titulo, cancion.artista, rutaAudio);
};

window.filtrarTodasCanciones = function() {
    let q = $('#buscador-todas-canciones').val().toLowerCase();
    
    // Si la caché está vacía, no hace nada
    if (window.todasCancionesData.length === 0) return;
    
    let filtradas = window.todasCancionesData.filter(function(c) {
        return c.titulo.toLowerCase().includes(q) || 
               c.artista.toLowerCase().includes(q) || 
               c.album.toLowerCase().includes(q);
    });
    
    renderizarTodasCanciones(filtradas);
};

window.reproducirGlobal = function() {
    if (window.todasCancionesData.length === 0) return;
    
    window.colaOriginal = JSON.parse(JSON.stringify(window.todasCancionesData));
    window.colaReproduccion = JSON.parse(JSON.stringify(window.todasCancionesData));
    window.radioActiva = true; 
    
    // Shuffle the queue (Fisher-Yates) para el Aleatorio Global
    for (let i = window.colaReproduccion.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [window.colaReproduccion[i], window.colaReproduccion[j]] = [window.colaReproduccion[j], window.colaReproduccion[i]];
    }
    
    let primera = window.colaReproduccion.shift();
    
    Swal.fire({
        title: 'Aleatorio Global',
        text: 'Reproduciendo todo el catálogo en modo aleatorio.',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
    
    reproducirCancion(primera.PK_id_cancion, primera.titulo, primera.artista, '../' + primera.ruta_archivo_audio);
};

// Llamada de inicialización (se ejecuta cuando el script es inyectado)
cargarAlbumes();
</script>