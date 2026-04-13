window.cargarPlaylists = function() {
    $.ajax({
        url: 'php/queries.php?caso=listar_playlists',
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                let html = '';
                if (resp.data.length === 0) {
                    html = `
                    <div class="col-12 text-center my-5">
                        <div class="p-5 bg-white rounded-4 shadow-sm">
                            <i class="fas fa-compact-disc fa-4x text-muted mb-3 opacity-50"></i>
                            <h4 class="text-muted">Aún no tienes playlists</h4>
                            <p class="text-secondary mb-4">Crea tu primera lista o explora las de otros usuarios.</p>
                            <button class="btn btn-primary px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalNuevaPlaylist">
                                Crear mi primera Playlist
                            </button>
                        </div>
                    </div>`;
                } else {
                    resp.data.forEach(function(pl) {
                        let iconoVis = pl.visibilidad === 'Publica' ? 'fa-globe-americas' : 'fa-lock';
                        let colorVis = pl.visibilidad === 'Publica' ? 'text-success' : 'text-warning';
                        
                        // Validaciones por si vienen nulos desde la BD
                        let totalCanciones = pl.total_canciones ? parseInt(pl.total_canciones) : 0;
                        let totalSegundos = pl.duracion_total ? parseInt(pl.duracion_total) : 0;
                        
                        // Lógica para formatear la duración
                        let horas = Math.floor(totalSegundos / 3600);
                        let minutos = Math.floor((totalSegundos % 3600) / 60);
                        let segundos = totalSegundos % 60;
                        
                        let textoDuracion = '';
                        if (horas > 0) {
                            textoDuracion = `${horas} h ${minutos} min`;
                        } else if (minutos > 0) {
                            textoDuracion = `${minutos} min ${segundos} seg`;
                        } else {
                            textoDuracion = `${segundos} seg`;
                        }
                        
                        // Si no hay canciones, mostrar 0 min
                        if (totalCanciones === 0) textoDuracion = '0 min';

                        html += `
                        <div class="col-md-4 col-sm-6 mb-4">
                            <div class="card h-100 shadow-sm border-0 playlist-card bg-white">
                                <div class="card-body p-4">
                                    <h5 class="card-title fw-bold text-truncate" title="${pl.nombre_playlist}">${pl.nombre_playlist}</h5>
                                    
                                    <div class="mt-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas ${iconoVis} ${colorVis} me-2" style="width: 20px;"></i>
                                            <span class="small fw-semibold">${pl.visibilidad}</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2 text-muted">
                                            <i class="fas fa-music me-2" style="width: 20px;"></i>
                                            <span class="small">${totalCanciones} pistas</span>
                                        </div>
                                        <div class="d-flex align-items-center text-muted">
                                            <i class="fas fa-clock me-2" style="width: 20px;"></i>
                                            <span class="small">${textoDuracion}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0 d-flex gap-2 p-3 pt-0">
                                    <button class="btn btn-outline-primary flex-fill fw-bold rounded-pill"
                                        onclick="verPlaylist(${pl.PK_id_playlist})">
                                        <i class="fas fa-play me-1"></i> <span data-key="pl_ver">Ver</span>
                                    </button>
                                    <button class="btn btn-outline-danger px-3 rounded-pill" title="Eliminar"
                                        onclick="eliminarPlaylist(${pl.PK_id_playlist}, '${pl.nombre_playlist.replace(/'/g, "\\'")}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>`;
                    });
                }
                $('#lista-playlists').html(html);
            } else {
                $('#lista-playlists').html('<div class="col-12"><div class="alert alert-danger">Error al cargar playlists.</div></div>');
            }
        },
        error: function() {
            $('#lista-playlists').html('<div class="col-12"><div class="alert alert-danger">Error de conexión.</div></div>');
        }
    });
}

$('#guardar-playlist').off('click').on('click', function() {
    let nombre      = $('#playlist-nombre').val().trim();
    let visibilidad = $('#playlist-visibilidad').val();

    if (nombre === '') {
        Swal.fire('Campo requerido', 'Debes ingresar un nombre para la playlist.', 'warning');
        return;
    }

    $.ajax({
        url: 'php/queries.php?caso=crear_playlist',
        type: 'POST',
        data: { nombre: nombre, visibilidad: visibilidad },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success' || resp.success === true) {
                Swal.fire('¡Creada!', 'Playlist creada exitosamente.', 'success');
                $('#modalNuevaPlaylist').modal('hide');
                $('#playlist-nombre').val('');
                cargarPlaylists();
            } else {
                Swal.fire('Atención', resp.message || 'No se pudo crear la playlist.', 'warning');
            }
        },
        error: function() {
            Swal.fire('Error', 'Error de conexión con el servidor.', 'error');
        }
    });
});

window.eliminarPlaylist = function(id, nombre) {
    Swal.fire({
        title: '¿Eliminar playlist?',
        text: `Se eliminará "${nombre}". Las canciones seguirán existiendo en el catálogo.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: 'php/queries.php?caso=eliminar_playlist',
                type: 'POST',
                data: { id_playlist: id },
                dataType: 'json',
                success: function(resp) {
                    if (resp.status === 'success') {
                        Swal.fire('¡Eliminada!', 'La playlist fue enviada a la papelera.', 'success');
                        cargarPlaylists();
                    } else {
                        Swal.fire('Error', 'No se pudo eliminar la playlist.', 'error');
                    }
                }
            });
        }
    });
};

window.verPlaylist = function(id) {
    // Guardar id para reordenamiento
    window._playlistActualId = id;

    $('#contenido-playlist').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Cargando pistas...</p></div>');
    $('#modalVerPlaylist').modal('show');

    $.ajax({
        url: 'php/queries.php?caso=obtener_canciones_playlist',
        type: 'POST',
        data: { id_playlist: id },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                if (resp.data.length === 0) {
                    $('#contenido-playlist').html('<div class="alert alert-info m-4 text-center"><i class="fas fa-compact-disc fa-2x mb-2 d-block"></i>Esta playlist aún está vacía. Ve al catálogo para agregar canciones.</div>');
                    return;
                }
                // Inyectamos los datos en la vista para poder referenciarlos
                window.playlistActualData = resp.data;

                let html = `
                <div class="px-3 pt-3 pb-2 text-end bg-light border-bottom">
                    <button class="btn btn-primary rounded-pill fw-bold shadow-sm" onclick="reproducirPlaylistCompleta()">
                        <i class="fas fa-play-circle me-1"></i> Reproducir Toda la Playlist
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">#</th>
                                <th>Pista</th>
                                <th>Duración</th>
                                <th class="text-center" style="width:90px;" title="Reordenar pista">
                                    <i class="fas fa-sort text-muted"></i>
                                </th>
                                <th class="text-end px-3">Acción</th>
                            </tr>
                        </thead>
                        <tbody>`;

                resp.data.forEach(function(c, idx) {
                    let duracion = Math.floor(c.duracion_segundos / 60) + ':' +
                                  (c.duracion_segundos % 60).toString().padStart(2, '0');
                    let esFirst = (idx === 0);
                    let esLast  = (idx === resp.data.length - 1);

                    html += `<tr id="fila-cancion-${c.PK_id_cancion}">
                        <td class="text-muted px-3">${idx + 1}</td>
                        <td>
                            <div class="fw-bold text-dark">${c.titulo}</div>
                            <div class="small text-muted">${c.artista}</div>
                        </td>
                        <td class="text-muted"><i class="fas fa-clock small me-1"></i> ${duracion}</td>
                        <td class="text-center">
                            <div class="d-flex flex-column gap-1 align-items-center">
                                <button class="btn btn-sm btn-outline-secondary px-2 py-0 lh-1"
                                        title="Mover arriba"
                                        ${esFirst ? 'disabled' : ''}
                                        onclick="reordenarCancionEnPlaylist(${id}, ${c.PK_id_cancion}, 'up')">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary px-2 py-0 lh-1"
                                        title="Mover abajo"
                                        ${esLast ? 'disabled' : ''}
                                        onclick="reordenarCancionEnPlaylist(${id}, ${c.PK_id_cancion}, 'down')">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </td>
                        <td class="text-end px-3">
                            <button class="btn btn-sm btn-success text-white me-1" 
                                onclick="reproducirCancion(${c.PK_id_cancion}, '${c.titulo.replace(/'/g, "\\'")}', '${c.artista.replace(/'/g, "\\'")}', '../${c.ruta_archivo_audio}')">
                                <i class="fas fa-play"></i>
                            </button>
                            <button class="btn btn-sm btn-light text-danger rounded-circle" title="Quitar de la lista"
                                onclick="removerCancionPlaylist(${id}, ${c.PK_id_cancion})">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>`;
                });

                html += '</tbody></table></div>';
                $('#contenido-playlist').html(html);
            }
        },
        error: function() {
            $('#contenido-playlist').html('<div class="alert alert-danger m-3">Error al cargar las canciones.</div>');
        }
    });
};

window.reproducirPlaylistCompleta = function(esPropietario = true) {
    if (!window.playlistActualData || window.playlistActualData.length === 0) return;

    // Clonamos el arreglo para no afectar el original y lo pasamos a la cola global
    window.colaOriginal = JSON.parse(JSON.stringify(window.playlistActualData));
    window.colaReproduccion = JSON.parse(JSON.stringify(window.playlistActualData));
    window.radioActiva = true; // Permite avanzar en la cola al terminar (auto-skip)

    // RF-Freemium f): Usuarios Free DEBEN escuchar playlists ajenas en modo aleatorio
    const esFreeYAjena = !window.esPremium && !esPropietario;
    if (esFreeYAjena) {
        // Fisher-Yates shuffle obligatorio
        for (let i = window.colaReproduccion.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [window.colaReproduccion[i], window.colaReproduccion[j]] =
            [window.colaReproduccion[j], window.colaReproduccion[i]];
        }
        // Activar visualmente el botón de shuffle
        const btnShuffle = document.getElementById('btn-shuffle');
        if (btnShuffle && !btnShuffle.classList.contains('btn-primary')) {
            btnShuffle.classList.replace('btn-outline-secondary', 'btn-primary');
            btnShuffle.classList.add('text-white');
        }
        window.modoShuffle = true;
        Swal.fire({
            icon: 'info',
            title: 'Modo Aleatorio Activado',
            html: 'Los usuarios <strong>Free</strong> deben escuchar playlists ajenas en modo aleatorio.<br><small class="text-warning">¡Mejora a Premium para escuchar en orden!</small>',
            confirmButtonColor: '#6B46C1',
            confirmButtonText: 'Entendido'
        });
    }

    // Extraemos la primera
    let primera = window.colaReproduccion.shift();
    
    Swal.fire({
        title: 'Iniciando Playlist',
        text: 'Reproduciendo todas las canciones...',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
    });
    
    // Reproducimos la primera (menu_principal.php ya tiene la función global)
    reproducirCancion(primera.PK_id_cancion, primera.titulo, primera.artista, '../' + primera.ruta_archivo_audio);
    $('#modalVerPlaylist').modal('hide');
};

window.removerCancionPlaylist = function(idPlaylist, idCancion) {
    $.ajax({
        url: 'php/queries.php?caso=remover_cancion_playlist',
        type: 'POST',
        data: { id_playlist: idPlaylist, id_cancion: idCancion },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                verPlaylist(idPlaylist); // Refrescar el modal
                cargarPlaylists(); // Refrescar la vista de atrás para que actualice la duración y cantidad
            } else {
                Swal.fire('Error', 'No se pudo quitar la canción.', 'error');
            }
        }
    });
};


/**
 * Busca playlists públicas de otros usuarios en tiempo real.
 * Llama al método Playlist::listarPlaylistsPublicas() via queries.php
 */
let debounceTimer;
window.buscarPlaylistsPublicas = function(termino) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function() {
        const contenedor = $('#lista-playlists-publicas');
        contenedor.html('<div class="col-12 text-center py-3"><div class="spinner-border spinner-border-sm text-success"></div><span class="ms-2 text-muted">Buscando...</span></div>');

        $.ajax({
            url: 'php/queries.php?caso=listar_playlists_publicas',
            type: 'GET',
            data: { q: termino },
            dataType: 'json',
            success: function(resp) {
                if (!resp.data || resp.data.length === 0) {
                    contenedor.html('<div class="col-12 text-center py-3 text-muted"><i class="fas fa-search me-1"></i> No se encontraron playlists públicas con ese nombre.</div>');
                    return;
                }

                let html = '';
                resp.data.forEach(function(pl) {
                    let mins = Math.floor(pl.duracion_total / 60);
                    html += `
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="card border-0 shadow-sm h-100" style="border-radius:12px; border-left: 4px solid #48bb78 !important;">
                            <div class="card-body">
                                <h6 class="fw-bold text-dark mb-0">${pl.nombre_playlist}</h6>
                                <small class="text-muted">por <strong>${pl.propietario}</strong></small>
                                <div class="d-flex gap-2 mt-2">
                                    <span class="badge bg-success">${pl.total_canciones} pistas</span>
                                    <span class="badge bg-secondary">${mins} min</span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0 d-flex gap-1">
                                <button class="btn btn-outline-primary btn-sm rounded-pill flex-fill"
                                    onclick="reproducirPlaylistPublica(${pl.PK_id_playlist})">
                                    <i class="fas fa-play me-1"></i> ${window.esPremium ? 'Reproducir' : '<i class="fas fa-random"></i> Aleatorio'}
                                </button>
                                <button class="btn btn-outline-success btn-sm rounded-pill flex-fill"
                                    onclick="copiarPlaylistPublica(${pl.PK_id_playlist}, '${pl.nombre_playlist.replace(/'/g, "\\'")}')">
                                    <i class="fas fa-copy me-1"></i> Guardar
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
                contenedor.html(html);
            },
            error: function() {
                contenedor.html('<div class="col-12"><div class="alert alert-danger">Error al buscar playlists públicas.</div></div>');
            }
        });
    }, 400); // debounce 400ms
};

/**
 * Copia la playlist pública al biblioteca del usuario actual.
 * Llama al método Playlist::duplicarPlaylist() via queries.php
 */
window.copiarPlaylistPublica = function(idPlaylist, nombre) {
    Swal.fire({
        title: '¿Guardar playlist?',
        text: `"¿Deseas guardar "${nombre}" en tu biblioteca como copia privada?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#38a169',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-copy me-1"></i> Sí, Guardar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: 'php/queries.php?caso=duplicar_playlist',
                type: 'POST',
                data: { id_playlist: idPlaylist },
                dataType: 'json',
                success: function(resp) {
                    if (resp.status === 'success') {
                        Swal.fire('¡Guardada!', resp.message, 'success');
                        cargarPlaylists(); // Refrescar mis playlists
                    } else {
                        Swal.fire('Atención', resp.message, 'warning');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo guardar la playlist.', 'error');
                }
            });
        }
    });
};

/**
 * RF-Freemium f) — Reproduce una playlist ajena directamente.
 * Para usuarios Free: activa shuffle obligatorio.
 * Para usuarios Premium: reproduce en el orden original.
 */
window.reproducirPlaylistPublica = function(idPlaylist) {
    $.ajax({
        url: 'php/queries.php?caso=obtener_canciones_playlist',
        type: 'POST',
        data: { id_playlist: idPlaylist },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success' && resp.data.length > 0) {
                window.playlistActualData = resp.data;
                // esPropietario = false → activa shuffle si es Free
                reproducirPlaylistCompleta(false);
            } else {
                Swal.fire('Sin canciones', 'Esta playlist está vacía.', 'info');
            }
        },
        error: function() {
            Swal.fire('Error', 'No se pudieron cargar las canciones de la playlist.', 'error');
        }
    });
};

// La inicialización es manejada por cargarVista() en menu_principal.php


/**
 * Mueve una canción hacia arriba o abajo dentro de la playlist.
 * Llama al backend y recarga el modal para que los números se actualicen.
 * @param {number} idPlaylist
 * @param {number} idCancion
 * @param {string} direccion 'up' | 'down'
 */
window.reordenarCancionEnPlaylist = function(idPlaylist, idCancion, direccion) {
    $.ajax({
        url: 'php/queries.php?caso=reordenar_cancion_playlist',
        type: 'POST',
        data: { id_playlist: idPlaylist, id_cancion: idCancion, direccion: direccion },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                // Recargar el modal con el nuevo orden sin cerrar el modal
                verPlaylist(idPlaylist);
            } else {
                // Ya está en el extremo — no hacer nada (el botón está disabled de esa posición)
                Swal.fire({
                    icon: 'info',
                    title: 'Sin cambios',
                    text: resp.message || 'La canción ya está en esa posición.',
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        },
        error: function() {
            Swal.fire('Error', 'No se pudo reordenar la canción.', 'error');
        }
    });
};

// Autoinicialización directa (garantizada en el parseo del script)
try {
    window.cargarPlaylists();
    buscarPlaylistsPublicas('');
} catch(e) {
    console.error("Error inicializando playlists:", e);
}




