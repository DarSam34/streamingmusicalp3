<div class="container-fluid fade-in">
    <div class="mb-4">
        <h2><i class="fas fa-magic me-2 text-info"></i> <span data-key="ds_titulo">Descubrimiento Semanal</span></h2>
        <p class="text-muted" data-key="ds_subtitulo">Música nueva recomendada basada en tus géneros favoritos.</p>
    </div>

    <div class="row" id="lista-descubrimiento">
        <div class="col-12 text-center py-4">
            <div class="spinner-border text-info" role="status"></div>
            <p class="mt-2 text-muted" data-key="ds_cargando">Afinando los algoritmos de recomendación...</p>
        </div>
    </div>

    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-dark mb-0">
                <i class="fas fa-heart me-2 text-danger"></i> <span data-key="ds_basado_gustos">Basado en tus gustos</span>
            </h4>
            <small class="text-muted" data-key="ds_basado_gustos_sub">Canciones de artistas que sigues</small>
        </div>
        <div class="row" id="lista-recomendaciones-seguidos">
            <div class="col-12 text-center py-3">
                <div class="spinner-border text-danger spinner-border-sm" role="status"></div>
                <span class="ms-2 text-muted" data-key="ds_personalizando">Personalizando...</span>
            </div>
        </div>
    </div>

    <hr class="my-5 border-secondary opacity-25">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="text-primary fw-bold"><i class="fas fa-globe-americas me-2"></i> <span data-key="ds_explorar">Explorar Playlists</span></h2>
    </div>
    <p class="text-muted mb-4" data-key="explorar_desc">Descubre la música que otros usuarios de Soundverse están compartiendo. Copia sus listas públicas directamente a tus playlists.</p>

    <div class="row mb-4">
        <div class="col-md-6 col-lg-5">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" id="buscador-explorar" class="form-control border-start-0" placeholder="Buscar por nombre de playlist..." data-key="ds_buscar_ph">
                <button class="btn btn-primary" onclick="buscarPlaylists()"><span data-key="ds_buscar">Buscar</span></button>
            </div>
        </div>
    </div>

    <div class="row" id="contenedor-playlists-publicas">
        </div>
</div>

<style>
    .playlist-card { transition: transform 0.2s ease, box-shadow 0.2s ease; border-radius: 15px; background: linear-gradient(145deg, #ffffff, #f0f0f0); }
    .playlist-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(107, 70, 193, 0.2) !important; }
    .icon-wrapper { width: 70px; height: 70px; margin: 0 auto; background: rgba(107, 70, 193, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
</style>

<script>
function cargarDescubrimiento() {
    $.ajax({
        url: 'php/queries.php?caso=descubrimiento_semanal',
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                let html = '';
                if (resp.data.length === 0) {
                    html = '<div class="col-12"><div class="alert alert-warning" data-key="ds_vacio">No hay sugerencias en este momento. Sigue escuchando música para conocer tus gustos.</div></div>';
                } else {
                    resp.data.forEach(function(cancion) {
                        html += `
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 shadow-sm border-0 bg-light" style="border-left: 4px solid #0dcaf0 !important;">
                                <div class="card-body">
                                    <h5 class="card-title text-dark fw-bold"><i class="fas fa-music me-2 text-info"></i> ${cancion.titulo}</h5>
                                    <h6 class="card-subtitle mb-2 text-muted"><i class="fas fa-microphone-alt me-1"></i> ${cancion.artista}</h6>
                                    <p class="card-text small text-secondary mb-3"><i class="fas fa-compact-disc me-1"></i> ${cancion.album}</p>
                                    
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-info btn-sm flex-fill fw-bold" 
                                            onclick="reproducirCancion(${cancion.PK_id_cancion}, '${cancion.titulo.replace(/'/g, "\\'")}', '${cancion.artista.replace(/'/g, "\\'")}', '../${cancion.ruta_archivo_audio}')">
                                            <i class="fas fa-play me-1"></i> <span data-key="ds_escuchar">Escuchar Pista</span>
                                        </button>
                                        ${cancion.sigue_artista > 0 ? 
                                            `<button class="btn btn-warning text-white btn-sm px-3" onclick="alternarSeguimiento(${cancion.PK_id_artista}, this)"><i class="fas fa-heart"></i></button>` : 
                                            `<button class="btn btn-outline-warning btn-sm px-3" onclick="alternarSeguimiento(${cancion.PK_id_artista}, this)"><i class="fas fa-heart"></i></button>`
                                        }
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    });
                }
                $('#lista-descubrimiento').html(html);
                if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
            } else {
                $('#lista-descubrimiento').html('<div class="col-12"><div class="alert alert-danger" data-key="ds_err_cargar">Error al cargar el descubrimiento semanal.</div></div>');
                if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
            }
        },
        error: function() {
            $('#lista-descubrimiento').html('<div class="col-12"><div class="alert alert-danger" data-key="ds_err_conexion">Error de conexión con el servidor.</div></div>');
        }
    });
}

function cargarPlaylistsPublicas(query = '') {
    const contenedor = document.getElementById('contenedor-playlists-publicas');
    contenedor.innerHTML = `
        <div class="col-12 text-center my-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted" data-key="pl_buscando">Buscando playlists...</p>
        </div>`;
    if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');

    fetch(`php/queries.php?caso=playlists_publicas&q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.data.length === 0) {
                    contenedor.innerHTML = `
                        <div class="col-12 text-center my-4">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted" data-key="pl_no_encontrada">No se encontraron playlists públicas.</h5>
                        </div>`;
                    if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
                    return;
                }

                let html = '';
                data.data.forEach(p => {
                    let totalCanciones = p.total_canciones ? p.total_canciones : 0;
                    html += `
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card h-100 border-0 shadow-sm playlist-card">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper mb-3">
                                    <i class="fas fa-music fa-2x text-primary"></i>
                                </div>
                                <h5 class="card-title fw-bold text-truncate" title="${p.nombre_playlist}">${p.nombre_playlist}</h5>
                                <p class="card-text small text-muted mb-1">
                                    <i class="fas fa-user-circle me-1"></i> <span data-key="pl_de">De:</span> <strong>${p.propietario}</strong>
                                </p>
                                <p class="card-text small text-muted mb-4">
                                    <i class="fas fa-headphones-alt me-1"></i> ${totalCanciones} <span data-key="pl_canciones">canciones</span>
                                </p>
                                <button class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-bold" onclick="duplicarPlaylistExterna(${p.PK_id_playlist})">
                                    <i class="fas fa-copy me-1"></i> <span data-key="pl_btn_copiar_mis">Copiar a mis playlists</span>
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
                contenedor.innerHTML = html;
                if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
            } else {
                contenedor.innerHTML = `<div class="col-12"><div class="alert alert-danger">Error: ${data.message}</div></div>`;
            }
        })
        .catch(err => {
            contenedor.innerHTML = '<div class="col-12"><div class="alert alert-danger" data-key="pl_err_conexion_pub">Error de conexión al cargar las playlists.</div></div>';
            if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
        });
}

function buscarPlaylists() {
    const query = document.getElementById('buscador-explorar').value;
    cargarPlaylistsPublicas(query);
}

setTimeout(() => {
    const inputBuscador = document.getElementById('buscador-explorar');
    if(inputBuscador) {
        inputBuscador.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') buscarPlaylists();
        });
    }
}, 500);

window.duplicarPlaylistExterna = function(idPlaylist) {
    Swal.fire({
        title: '¿Copiar esta Playlist?',
        text: "Se creará una copia privada exacta en tus playlists con todas sus canciones.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6B46C1',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check"></i> Sí, copiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            let datos = new FormData();
            datos.append('caso', 'duplicar_playlist');
            datos.append('id_playlist', idPlaylist);

            fetch('php/queries.php', { method: 'POST', body: datos })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Copiada con éxito!',
                            text: 'La playlist ahora se encuentra en tu cuenta.',
                            confirmButtonColor: '#6B46C1',
                            confirmButtonText: 'Ir a Mis Playlists',
                            showCancelButton: true,
                            cancelButtonText: 'Seguir explorando',
                            cancelButtonColor: '#6c757d'
                        }).then((resBtn) => {
                            if (resBtn.isConfirmed) cargarVista('vistas/playlists.php');
                        });
                    } else {
                        Swal.fire('Atención', data.message, 'warning');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                });
        }
    });
}

cargarDescubrimiento();
cargarPlaylistsPublicas();

function cargarRecomendacionesSeguidos() {
    $.post('php/queries.php', { caso: 'recomendaciones_artistas_seguidos' }, function(resp) {
        const contenedor = $('#lista-recomendaciones-seguidos');
        if (resp.status !== 'success' || !resp.data || resp.data.length === 0) {
            contenedor.html('<div class="col-12"><div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><span data-key="ds_sigue_para_recom">Sigue a artistas desde el catálogo para ver recomendaciones personalizadas aquí.</span></div></div>');
            if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
            return;
        }
        let html = '';
        resp.data.forEach(function(c) {
            let titulo   = c.titulo  ? c.titulo.replace(/'/g, "\\'")  : 'Desconocido';
            let artista  = c.artista ? c.artista.replace(/'/g, "\\'") : 'Desconocido';
            let rutaAudio = c.ruta_archivo_audio ? '../' + c.ruta_archivo_audio : '';
            html += `
            <div class="col-md-4 col-lg-3 mb-3">
                <div class="card h-100 border-0 shadow-sm" style="border-left: 3px solid #e53e3e !important; border-radius:12px;">
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1 text-truncate" title="${c.titulo}">
                            <i class="fas fa-music me-1 text-danger"></i>${c.titulo}
                        </h6>
                        <div class="small text-muted mb-3">
                            <i class="fas fa-microphone-alt me-1"></i>${c.artista}
                            ${c.album ? '<br><i class="fas fa-compact-disc me-1"></i>' + c.album : ''}
                        </div>
                        <button class="btn btn-danger btn-sm rounded-pill flex-fill"
                            onclick="reproducirCancion(${c.PK_id_cancion}, '${titulo}', '${artista}', '${rutaAudio}')">
                            <i class="fas fa-play me-1"></i> <span data-key="ds_btn_escuchar">Escuchar</span>
                        </button>
                        <button class="btn btn-warning text-white btn-sm rounded-circle px-2 ms-2" title="Dejar de seguir"
                            onclick="alternarSeguimiento(${c.PK_id_artista}, this)">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
            </div>`;
        });
        contenedor.html(html);
        if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
    }, 'json').fail(function() {
        $('#lista-recomendaciones-seguidos').html('<div class="col-12"><div class="alert alert-danger" data-key="ds_err_recom">Error al cargar recomendaciones.</div></div>');
        if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
    });
}

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
                        if ($btn.text().trim().toLowerCase().includes('seguir') && !$btn.text().trim().toLowerCase().includes('siguiendo')) {
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

cargarRecomendacionesSeguidos();
</script>