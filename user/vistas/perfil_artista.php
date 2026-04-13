<?php
// Asegurar que no se acceda directamente y CONECTAR CON LA SESIÓN CORRECTA
session_name('SOUNDVERSE_USER'); // <-- ESTA ERA LA LÍNEA FALTANTE
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { exit; }
?>
<div class="container-fluid fade-in" id="perfil-artista-container">
    <div id="pa-loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-3 text-muted" data-key="pa_loading_perfil">Cargando perfil del artista...</p>
    </div>

    <div id="pa-contenido" style="display:none;">

        <div id="pa-hero" class="rounded-4 p-4 mb-4 text-white position-relative overflow-hidden shadow-lg"
             style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); min-height: 220px;">
            <div class="position-absolute top-0 end-0 opacity-10" style="font-size: 12rem; line-height:1; pointer-events:none;">
                <i class="fas fa-music"></i>
            </div>

            <div class="d-flex flex-column flex-md-row align-items-center gap-4 position-relative">
                <div class="flex-shrink-0">
                    <img id="pa-foto" src="" alt="Foto del artista"
                         class="rounded-circle shadow-lg border border-4 border-white"
                         style="width:130px; height:130px; object-fit:cover;"
                         onerror="this.src='../assets/img/logo_soundverse_white.png'">
                </div>

                <div class="flex-grow-1 text-center text-md-start">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-1">
                        <h1 id="pa-nombre" class="h2 fw-bold mb-0 text-white"></h1>
                        <span id="pa-verificado-badge" class="d-none badge bg-primary rounded-pill fs-6 px-2">
                            <i class="fas fa-check-circle me-1"></i><span data-key="pa_verificado">Verificado</span>
                        </span>
                    </div>

                    <div id="pa-generos" class="mb-3 d-flex flex-wrap justify-content-center justify-content-md-start gap-1"></div>

                    <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-4 mb-3">
                        <div class="text-center">
                            <div id="pa-reproducciones" class="fw-bold fs-5 text-warning">0</div>
                            <small class="text-white-50" data-key="pa_reproducciones">Reproducciones</small>
                        </div>
                        <div class="text-center px-2">
                            <div id="pa-seguidores" class="fw-bold fs-5 text-danger">0</div>
                            <small class="text-white-50" data-key="pa_seguidores">Seguidores</small>
                        </div>
                        <div class="text-center">
                            <div id="pa-total-albumes" class="fw-bold fs-5 text-info">0</div>
                            <small class="text-white-50" data-key="pa_albumes">Álbumes</small>
                        </div>
                        <div class="text-center">
                            <div id="pa-total-canciones" class="fw-bold fs-5 text-success">0</div>
                            <small class="text-white-50" data-key="pa_canciones">Canciones</small>
                        </div>
                    </div>

                    <button id="pa-btn-seguir" class="btn btn-lg rounded-pill px-4 fw-bold shadow-sm"
                            onclick="alternarSeguimientoArtista()">
                        <i class="fas fa-heart me-2"></i><span id="pa-texto-seguir">Seguir</span>
                    </button>
                </div>
            </div>
        </div>

        <div id="pa-bio-section" class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-primary"></i><span data-key="pa_acerca_de">Acerca del artista</span></h5>
                <p id="pa-biografia" class="text-muted mb-0" style="line-height:1.8;"></p>
            </div>
        </div>

        <div class="mb-2">
            <div id="pa-destacadas-section" class="mb-5 d-none">
                <h4 class="fw-bold mb-3">
                    <i class="fas fa-star text-warning me-2"></i><span data-key="pa_destacadas_titulo">Canciones Destacadas</span>
                    <small class="text-muted fw-normal fs-6 ms-2" data-key="pa_destacadas_sub">— selección del artista</small>
                </h4>
                <div class="row g-3" id="pa-destacadas-lista"></div>
            </div>

            <h4 class="fw-bold mb-4"><i class="fas fa-compact-disc me-2 text-primary"></i><span data-key="pa_discografia">Discografía</span></h4>
            <div id="pa-discografia" class="row"></div>
        </div>

    </div>

    <div id="pa-error" class="alert alert-danger rounded-4 d-none">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <span data-key="pa_err_cargar">No se pudo cargar el perfil del artista. Intente nuevamente.</span>
    </div>
</div>

<style>
/* ===== PERFIL ARTISTA — ESTILOS ===== */
#perfil-artista-container .album-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 14px;
    cursor: pointer;
}
#perfil-artista-container .album-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 14px 28px rgba(0,0,0,0.15) !important;
}
#perfil-artista-container .album-portada {
    height: 160px;
    object-fit: cover;
    border-radius: 10px 10px 0 0;
}
#perfil-artista-container .genero-badge {
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 20px;
    background: rgba(255,255,255,0.18);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255,255,255,0.3);
}
#perfil-artista-container .cancion-pista-row:hover {
    background-color: rgba(107, 70, 193, 0.07);
}
#pa-btn-seguir.siguiendo {
    background-color: #6B46C1;
    color: #fff;
    border-color: #6B46C1;
}
#pa-btn-seguir:not(.siguiendo) {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: 2px solid rgba(255,255,255,0.5);
}
#pa-btn-seguir:not(.siguiendo):hover {
    background: rgba(255,255,255,0.25);
}
.pa-numero-reproducciones {
    font-size: 0.78rem;
}
</style>

<script>
// ============================================================
// PERFIL DE ARTISTA — Lógica JS
// ============================================================

// ID del artista actualmente cargado (para el botón seguir)
window._paIdArtista = 0;
window._paSigue     = false;

/**
 * Punto de entrada: carga el perfil completo via AJAX
 * @param {number} idArtista
 */
window.cargarPerfilArtista = function(idArtista) {
    window._paIdArtista = idArtista;

    // Mostrar loading
    $('#pa-loading').show();
    $('#pa-contenido').hide();
    $('#pa-error').addClass('d-none');
    
    // Forzamos traducción inicial de las etiquetas estáticas
    if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');

    $.ajax({
        url: 'php/queries.php',
        type: 'GET',
        data: { caso: 'perfil_artista', id_artista: idArtista },
        dataType: 'json',
        success: function(resp) {
            if (resp.status !== 'success') {
                $('#pa-loading').hide();
                $('#pa-error').removeClass('d-none');
                return;
            }
            renderizarPerfilArtista(resp.data);
        },
        error: function() {
            $('#pa-loading').hide();
            $('#pa-error').removeClass('d-none');
        }
    });
};

/**
 * Renderiza todos los datos en el perfil
 */
function renderizarPerfilArtista(data) {
    const isEn = localStorage.getItem('idiomaSite') === 'en';

    // --- Foto ---
    let foto = (data.ruta_foto_perfil && data.ruta_foto_perfil.trim() !== '')
        ? '../' + data.ruta_foto_perfil
        : '../assets/img/logo_soundverse_white.png';
    $('#pa-foto').attr('src', foto);

    // --- Nombre y verificado ---
    $('#pa-nombre').text(data.nombre_artistico || 'Artista');
    if (parseInt(data.verificado) === 1) {
        $('#pa-verificado-badge').removeClass('d-none');
    } else {
        $('#pa-verificado-badge').addClass('d-none');
    }

    // --- Géneros (badges dinámicos) ---
    let generosHtml = '';
    if (data.generos && data.generos.length > 0) {
        data.generos.forEach(function(g) {
            generosHtml += `<span class="genero-badge">${g.nombre_genero}</span>`;
        });
    } else {
        generosHtml = `<span class="genero-badge opacity-50">${isEn ? 'No registered genres' : 'Sin géneros registrados'}</span>`;
    }
    $('#pa-generos').html(generosHtml);

    // --- Estadísticas ---
    let totalReprod  = parseInt(data.reproducciones || 0);
    let totalSeguid  = parseInt(data.seguidores || 0);
    let discografia  = data.discografia || [];
    let totalCanciones = discografia.reduce((s, a) => s + parseInt(a.total_canciones || 0), 0);

    $('#pa-reproducciones').text(totalReprod.toLocaleString('es-ES'));
    $('#pa-seguidores').text(totalSeguid.toLocaleString('es-ES'));
    $('#pa-total-albumes').text(discografia.length);
    $('#pa-total-canciones').text(totalCanciones);

    // --- Botón Seguir ---
    window._paSigue = data.sigue === true || data.sigue === 1;
    actualizarBtnSeguir();

    // --- Biografía ---
    let bio = data.biografia && data.biografia.trim() !== ''
        ? data.biografia
        : `<em class="opacity-50">${isEn ? 'This artist has no biography yet.' : 'Este artista aún no tiene biografía.'}</em>`;
    $('#pa-biografia').html(bio);

    // --- Canciones Destacadas (Requisito u) ---
    if (data.destacadas && data.destacadas.length > 0) {
        let destHtml = '';
        data.destacadas.forEach(function(c) {
            let dur = Math.floor(c.duracion_segundos / 60) + ':' + (c.duracion_segundos % 60).toString().padStart(2, '0');
            let tituloSeg  = c.titulo  ? c.titulo.replace(/'/g, "\\'")  : '';
            let artistaSeg = c.artista ? c.artista.replace(/'/g, "\\'") : '';
            let rutaAudio  = c.ruta_archivo_audio ? '../' + c.ruta_archivo_audio : '';
            destHtml += `
            <div class="col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 position-relative"
                     style="border-left: 4px solid #d69e2e !important; border-radius:12px;">
                    <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-2 rounded-pill">
                        <i class="fas fa-star me-1"></i>${isEn ? 'Featured' : 'Destacada'}
                    </span>
                    <div class="card-body pb-2 pt-3">
                        <h6 class="fw-bold text-dark mb-0 text-truncate pe-5" title="${c.titulo}">${c.titulo}</h6>
                        <small class="text-muted"><i class="fas fa-compact-disc me-1"></i>${c.album}</small>
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <span class="text-muted small"><i class="fas fa-clock me-1"></i>${dur}</span>
                            <span class="text-muted small"><i class="fas fa-play-circle me-1 text-success"></i>${parseInt(c.contador_reproducciones).toLocaleString('es')} ${isEn ? 'plays' : 'repr.'}</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 py-2">
                        <button class="btn btn-success btn-sm rounded-pill w-100 fw-bold"
                            onclick="reproducirCancion(${c.PK_id_cancion}, '${tituloSeg}', '${artistaSeg}', '${rutaAudio}')">
                            <i class="fas fa-play me-1"></i> ${isEn ? 'Play' : 'Reproducir'}
                        </button>
                    </div>
                </div>
            </div>`;
        });
        $('#pa-destacadas-lista').html(destHtml);
        $('#pa-destacadas-section').removeClass('d-none');
    } else {
        $('#pa-destacadas-section').addClass('d-none');
    }

    // --- Discografía ---
    let discHtml = '';
    if (discografia.length === 0) {
        discHtml = `<div class="col-12">
            <div class="alert alert-info rounded-4">
                <i class="fas fa-compact-disc me-2"></i>${isEn ? 'This artist has no albums in the catalog yet.' : 'Este artista aún no tiene álbumes en el catálogo.'}
            </div>
        </div>`;
    } else {
        discografia.forEach(function(album) {
            let portada = (album.ruta_portada && album.ruta_portada.trim() !== '')
                ? '../' + album.ruta_portada
                : '../assets/img/logo_soundverse_white.png';

            let totalSeg   = parseInt(album.duracion_total_segundos || 0);
            let mins       = Math.floor(totalSeg / 60);
            let totalCan   = parseInt(album.total_canciones || 0);
            let discograf  = album.discografica ? `<small class="text-muted d-block"><i class="fas fa-building me-1"></i>${album.discografica}</small>` : '';
            let anio       = album.fecha_lanzamiento ? album.fecha_lanzamiento.substring(0, 4) : '';

            discHtml += `
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card border-0 shadow-sm album-card h-100">
                    <img src="${portada}" alt="${album.titulo}" class="album-portada"
                         onerror="this.src='../assets/img/logo_soundverse_white.png'">
                    <div class="card-body pb-2">
                        <h6 class="fw-bold text-dark mb-0 text-truncate" title="${album.titulo}">${album.titulo}</h6>
                        ${discograf}
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <span class="badge bg-light text-dark border"><i class="fas fa-calendar-alt me-1 text-muted"></i>${anio}</span>
                            <span class="badge bg-light text-dark border"><i class="fas fa-music me-1 text-muted"></i>${totalCan} ${isEn ? 'tracks' : 'pistas'}</span>
                            <span class="badge bg-light text-dark border"><i class="fas fa-clock me-1 text-muted"></i>${mins} min</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                        <button class="btn btn-primary btn-sm rounded-pill w-100 fw-bold"
                                onclick="cargarCancionesAlbumArtista(${album.PK_id_album}, '${album.titulo.replace(/'/g, "\\'")}')">
                            <i class="fas fa-play-circle me-1"></i> ${isEn ? 'Play album' : 'Escuchar álbum'}
                        </button>
                    </div>
                </div>
            </div>`;
        });
    }
    $('#pa-discografia').html(discHtml);

    // Mostrar contenido
    $('#pa-loading').hide();
    $('#pa-contenido').show();
    
    // Forzamos traducción final
    if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
}

/**
 * Actualiza el estilo y texto del botón "Seguir / Dejar de seguir"
 */
function actualizarBtnSeguir() {
    const btn  = $('#pa-btn-seguir');
    const span = $('#pa-texto-seguir');
    const isEn = localStorage.getItem('idiomaSite') === 'en';
    
    if (window._paSigue) {
        btn.addClass('siguiendo').removeClass('btn-outline-light');
        span.text(isEn ? 'Following' : 'Siguiendo');
        btn.find('i').attr('class', 'fas fa-heart-broken me-2');
    } else {
        btn.removeClass('siguiendo').addClass('btn-outline-light');
        span.text(isEn ? 'Follow' : 'Seguir');
        btn.find('i').attr('class', 'fas fa-heart me-2');
    }
}

/**
 * Alterna follow/unfollow del artista actual
 */
window.alternarSeguimientoArtista = function() {
    if (!window._paIdArtista) return;
    const isEn = localStorage.getItem('idiomaSite') === 'en';

    $.ajax({
        url: 'php/queries.php',
        type: 'POST',
        data: { caso: 'alternar_seguimiento', id_artista: window._paIdArtista },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                window._paSigue = (resp.accion === 'follow');
                actualizarBtnSeguir();

                const msg = window._paSigue ? (isEn ? 'You are now following this artist!' : '¡Ahora sigues a este artista!') : (isEn ? 'You unfollowed this artist.' : 'Dejaste de seguir al artista.');
                const icono = window._paSigue ? 'success' : 'info';
                Swal.fire({ icon: icono, title: msg, timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });
            } else {
                Swal.fire('Atención', resp.message || 'Error al actualizar seguimiento.', 'warning');
            }
        },
        error: function() {
            Swal.fire('Error', isEn ? 'Server connection error.' : 'Error de conexión con el servidor.', 'error');
        }
    });
};

/**
 * Carga las canciones de un álbum desde el perfil del artista.
 */
window.cargarCancionesAlbumArtista = function(idAlbum, tituloAlbum) {
    const isEn = localStorage.getItem('idiomaSite') === 'en';
    Swal.fire({
        title: `<i class="fas fa-compact-disc me-2 text-primary"></i>${tituloAlbum}`,
        html: `<div id="swal-canciones-album">
                   <div class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm"></div> ${isEn ? 'Loading tracks...' : 'Cargando pistas...'}</div>
               </div>`,
        showConfirmButton: false,
        showCloseButton: true,
        customClass: { popup: 'rounded-4', htmlContainer: 'text-start' },
        width: '600px',
        didOpen: function() {
            $.ajax({
                url: 'php/queries.php',
                type: 'POST',
                data: { caso: 'canciones_por_album', id_album: idAlbum },
                dataType: 'json',
                success: function(resp) {
                    let datos = resp.data !== undefined ? resp.data : (Array.isArray(resp) ? resp : []);
                    if (!datos || datos.length === 0) {
                        $('#swal-canciones-album').html(`<div class="alert alert-info text-center">${isEn ? 'This album has no available songs yet.' : 'Este álbum no tiene canciones disponibles aún.'}</div>`);
                        return;
                    }
                    let rows = '';
                    datos.forEach(function(c, idx) {
                        let dur = Math.floor(c.duracion_segundos / 60) + ':' + (c.duracion_segundos % 60).toString().padStart(2, '0');
                        let pista = c.numero_pista || (idx + 1);
                        let tituloSeg  = c.titulo  ? c.titulo.replace(/'/g, "\\'")  : '';
                        let artistaSeg = c.artista ? c.artista.replace(/'/g, "\\'") : '';
                        let rutaAudio  = c.ruta_archivo_audio ? '../' + c.ruta_archivo_audio : '';
                        rows += `
                        <tr class="cancion-pista-row">
                            <td class="text-muted px-3 fw-bold">${pista}</td>
                            <td>
                                <strong>${c.titulo}</strong>
                                <div class="small text-muted">${c.artista || ''}</div>
                            </td>
                            <td class="text-muted"><i class="fas fa-clock me-1 small"></i>${dur}</td>
                            <td class="px-3">
                                <button class="btn btn-success btn-sm rounded-circle" title="${isEn ? 'Play' : 'Reproducir'}"
                                    onclick="Swal.close(); reproducirCancion(${c.PK_id_cancion}, '${tituloSeg}', '${artistaSeg}', '${rutaAudio}')">
                                    <i class="fas fa-play"></i>
                                </button>
                            </td>
                        </tr>`;
                    });
                    $('#swal-canciones-album').html(`
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-3">#</th><th>${isEn ? 'Song' : 'Canción'}</th><th>${isEn ? 'Duration' : 'Duración'}</th><th></th>
                                    </tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>`);
                },
                error: function() {
                    $('#swal-canciones-album').html(`<div class="alert alert-danger">${isEn ? 'Error loading songs.' : 'Error al cargar las canciones.'}</div>`);
                }
            });
        }
    });
};

if (window.paArtistaId) {
    cargarPerfilArtista(window.paArtistaId);
}
</script>