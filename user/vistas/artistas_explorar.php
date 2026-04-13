<?php
// Asegurar que no se acceda directamente y CONECTAR CON LA SESIÓN CORRECTA
session_name('SOUNDVERSE_USER');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { exit; }
?>
<div class="fade-in container-fluid pb-5">
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h2><i class="fas fa-microphone-alt me-2 text-warning"></i> <span data-key="ae_titulo">Descubre Nuevos Talentos</span></h2>
            <p class="text-muted" data-key="ae_subtitulo">Explora la lista completa de artistas disponibles en Soundverse.</p>
        </div>
        <div class="col-md-5">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" id="buscador-artistas" class="form-control border-start-0" 
                       placeholder="Buscar artista por nombre..." onkeyup="filtrarArtistas()">
            </div>
        </div>
    </div>

    <div class="row g-4" id="lista-todos-artistas">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-warning" role="status"></div>
            <p class="mt-2 text-muted" data-key="notif_cargando">Cargando...</p>
        </div>
    </div>
</div>

<style>
    .artista-card-item .card { transition: 0.3s; }
    .artista-card-item .card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(107, 70, 193, 0.15) !important; }
</style>

<script>
// Truco: Este intervalo revisa el idioma cada medio segundo y traduce el placeholder al instante
setInterval(function() {
    const curLang = localStorage.getItem('idiomaSite') || 'es';
    const input = document.getElementById('buscador-artistas');
    if (input) {
        input.placeholder = (curLang === 'en') ? 'Search artist by name...' : 'Buscar artista por nombre...';
    }
}, 500);

window._todosArtistas = [];

window.cargarTodosArtistas = function() {
    if (typeof $ === 'undefined') return; // Seguridad

    $.ajax({
        url: 'php/queries.php?caso=listar_todos_artistas',
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            const contenedor = $('#lista-todos-artistas');
            contenedor.empty();

            if (resp.status !== 'success' || !resp.data || resp.data.length === 0) {
                // Si no hay datos, lo manejamos con data-key para el idioma
                contenedor.html(`
                    <div class="col-12 text-center text-muted">
                        <i class="fas fa-microphone-slash fa-2x mb-2"></i>
                        <p data-key="ae_sin_resultados">No hay artistas disponibles por el momento.</p>
                    </div>`);
                if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
                return;
            }

            window._todosArtistas = resp.data;
            renderizarArtistas(window._todosArtistas);
        },
        error: function() {
            const contenedor = $('#lista-todos-artistas');
            contenedor.html(`<div class="col-12 alert alert-danger" data-key="st_err_artistas">Error al cargar los artistas.</div>`);
            if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
        }
    });
}

function renderizarArtistas(lista) {
    const contenedor = $('#lista-todos-artistas');
    contenedor.empty();

    if (lista.length === 0) {
        contenedor.html(`
            <div class="col-12 text-center text-muted mt-4">
                <i class="fas fa-search fa-2x mb-2"></i>
                <p data-key="ae_sin_resultados">No se encontraron artistas que coincidan con tu búsqueda.</p>
            </div>`);
        if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
        return;
    }

    lista.forEach(a => {
        const foto = a.ruta_foto_perfil && a.ruta_foto_perfil.trim() !== '' ? '../' + a.ruta_foto_perfil : '../assets/img/artistas/default_artista.png';
        
        // Etiqueta de verificado
        const verificado = a.verificado == 1 ? `<span class="badge bg-primary rounded-pill position-absolute" style="top:10px; right:10px;" title="Verificado"><i class="fas fa-check"></i></span>` : '';
        
        let bioShort = a.biografia || '';
        if (bioShort.length > 80) bioShort = bioShort.substring(0, 77) + '...';

        const html = `
        <div class="col-lg-3 col-md-4 col-sm-6 artista-card-item mb-3">
            <div class="card h-100 shadow-sm border-0 position-relative" style="border-radius: 15px; overflow: hidden; background: #fff;">
                ${verificado}
                <div class="text-center p-4 pb-0">
                    <img src="${foto}" alt="${a.nombre_artistico}" class="rounded-circle shadow" 
                         style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #f8f9fa;"
                         onerror="this.src='../assets/img/logo_soundverse_white.png'">
                </div>
                <div class="card-body text-center d-flex flex-column">
                    <h5 class="card-title fw-bold text-dark mb-1">${a.nombre_artistico}</h5>
                    
                    <p class="card-text text-muted small flex-grow-1" style="min-height:42px;">${bioShort}</p>
                    
                    <button class="btn btn-warning text-dark btn-sm rounded-pill px-4 fw-bold mt-2" 
                            onclick="verPerfilArtista(${a.PK_id_artista})">
                        <i class="fas fa-eye me-1"></i> <span data-key="ae_ver_perfil">Ver Perfil</span>
                    </button>
                </div>
            </div>
        </div>`;
        contenedor.append(html);
    });

    // Inyectamos las tarjetas y FORZAMOS que el sistema las traduzca inmediatamente
    if (typeof cambiarIdioma === 'function') {
        cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
    }
}

window.filtrarArtistas = function() {
    const q = $('#buscador-artistas').val().toLowerCase().trim();
    if (!window._todosArtistas || window._todosArtistas.length === 0) return;
    if (q === '') { renderizarArtistas(window._todosArtistas); return; }
    const filtrados = window._todosArtistas.filter(a => a.nombre_artistico.toLowerCase().includes(q));
    renderizarArtistas(filtrados);
}

if (window._todosArtistas && window._todosArtistas.length === 0) {
    cargarTodosArtistas();
}
</script>