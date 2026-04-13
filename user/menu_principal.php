<?php
session_name('SOUNDVERSE_USER');
session_start();

// BLOQUE DE SEGURIDAD - PORTAL DE OYENTES
// Si no hay sesión, redirigir al login de usuario
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
// Control de inactividad: 1800 segundos = 30 minutos
if (isset($_SESSION['time']) && (time() - $_SESSION['time']) > 1800) {
    header("Location: php/logout.php");
    exit;
}

// Renovar el tiempo de actividad si el usuario interactuó / refrescó antes de los 5 min
$_SESSION['time'] = time();

// Tipo de suscripción para mensajes personalizados
$tipo       = $_SESSION['tipo_suscripcion'] ?? 1;
$es_premium = ($tipo == 2);

// Verificar si el usuario es gestor de algún artista (Req. Artistas)
require_once __DIR__ . '/../classes/Conexion.php';
$con = new Conexion();
$db = $con->conectar();
$stmtGest = $db->prepare("SELECT PK_id_artista FROM Artista WHERE FK_id_usuario_gestor = ? LIMIT 1");
$stmtGest->execute([$_SESSION['usuario_id']]);
$respGest = $stmtGest->fetch(PDO::FETCH_ASSOC);
$id_artista_gestor = $respGest ? $respGest['PK_id_artista'] : 0;
$db = null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soundverse - Reproductor</title>
    <link rel="shortcut icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        :root { --bs-primary: #6B46C1; }
        .btn-primary { background-color: var(--bs-primary); border-color: var(--bs-primary); }
        .btn-primary:hover { background-color: #55359e; border-color: #55359e; }
        body { background-color: #f4f6f9; overflow-x: hidden; }
        .sidebar {
            min-height: 100vh;
            background-color: #2D3748;
            color: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: #adb5bd !important;
            padding: 12px 20px;
            transition: 0.3s;
            border-left: 4px solid transparent;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff !important;
            background-color: #6B46C1;
            border-left: 4px solid #FBBF24;
        }
        .logo-container {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .reproductor-fijo {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #1a1e21;
            color: white;
            padding: 10px 20px;
            z-index: 1000;
            border-top: 1px solid #333;
        }
        main { margin-bottom: 90px; }
        @media (max-width: 768px) {
            .sidebar { min-height: auto; }
        }

        /* Contenedor de letras Premium (Glassmorphism & Sincronización) */
        #lyrics-container {
            position: fixed;
            top: 20px;
            right: -420px;
            width: 400px;
            height: calc(100vh - 130px);
            background: rgba(10, 10, 10, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 1040;
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            color: #fff;
            padding: 0;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px 0 0 20px;
            box-shadow: -20px 0 50px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        #lyrics-container.active {
            right: 0;
        }

        .lyrics-header {
            padding: 20px 25px;
            background: rgba(107, 70, 193, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .lyrics-body {
            padding: 30px 40px;
            overflow-y: auto;
            flex-grow: 1;
            scroll-behavior: smooth;
            max-height: calc(100vh - 130px);
        }
        
        .lyrics-body::-webkit-scrollbar {
            width: 4px;
        }
        
        .lyrics-body::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
        }
        
        .lyrics-body::-webkit-scrollbar-thumb {
            background: rgba(107,70,193,0.5);
            border-radius: 4px;
        }

        .lyric-line {
            padding: 15px 0;
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.3);
            transition: all 0.4s ease;
            cursor: pointer;
            line-height: 1.4;
            font-weight: 600;
        }

        .lyric-line:hover {
            color: rgba(255, 255, 255, 0.7);
        }

        .lyric-line.active {
            color: #fff;
            font-size: 1.8rem;
            font-weight: 800;
            text-shadow: 0 0 20px rgba(107, 70, 193, 0.6);
            transform: scale(1.02);
            padding: 20px 0;
        }

        /* Estilos exclusivos del nuevo Dashboard Inicial */
        .fade-in { animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        .quick-card { transition: all 0.3s ease; border-left: 4px solid transparent; }
        .quick-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(107, 70, 193, 0.15) !important; border-left: 4px solid var(--bs-primary); }
        
        /* Fondo con artistas en el Hero */
        .hero-background {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 0;
            opacity: 0.3; /* Transparencia para que el texto resalte */
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            overflow: hidden;
            pointer-events: none; /* No clickeable */
        }
        .hero-background img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: grayscale(30%) contrast(1.2);
        }
        .hero-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(43,17,84,0.95) 0%, rgba(107,70,193,0.85) 100%);
            z-index: 1;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0">

        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="logo-container">
                <img src="../assets/img/logo_soundverse_white.png" alt="Soundverse"
                     class="img-fluid px-3" style="max-height: 80px;">
                <h6 class="text-uppercase fw-bold text-light mt-2">Soundverse</h6>
                <span class="badge <?php echo $es_premium ? 'bg-warning text-dark' : 'bg-secondary'; ?>">
                    <?php echo $es_premium ? '⭐ Premium' : 'Free'; ?>
                </span>
                <div class="mt-2 d-flex justify-content-center gap-2">
                    <button class="btn-es btn btn-sm btn-outline-light px-2 py-0" onclick="cambiarIdioma('es')" style="font-size:11px;border-radius:20px;">🇲🇽 ES</button>
                    <button class="btn-en btn btn-sm btn-outline-light opacity-50 px-2 py-0" onclick="cambiarIdioma('en')" style="font-size:11px;border-radius:20px;">🇺🇸 EN</button>
                </div>
            </div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="cargarVista('vistas/catalogo.php')">
                        <i class="fas fa-music me-2"></i> <span data-key="nav_catalogo">Catálogo</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="cargarVista('vistas/artistas_explorar.php')">
                        <i class="fas fa-microphone-alt me-2 text-info"></i> <span data-key="nav_artistas">Explorar Artistas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="cargarVista('vistas/Playlists.php')">
                        <i class="fas fa-list me-2"></i> <span data-key="nav_playlists">Mis Playlists</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="cargarVista('vistas/estadisticas.php')">
                        <i class="fas fa-chart-line me-2"></i> <span data-key="nav_estadisticas">Mis Estadísticas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-info" href="#" onclick="cargarVista('vistas/descubrimiento.php')">
                        <i class="fas fa-magic me-2"></i> <span data-key="nav_descubrimiento">Descubrimiento Semanal</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-warning" href="#" onclick="cargarVista('vistas/suscripcion.php')">
                        <i class="fas fa-crown me-2"></i> <span data-key="nav_planes">Planes / Premium</span>
                    </a>
                </li>
                
                <?php if ($id_artista_gestor > 0): ?>
                <hr class="text-secondary mx-3">
                <li class="nav-item">
                    <a class="nav-link text-warning" href="#" onclick="cargarVista('vistas/panel_artista_gestor.php')">
                        <i class="fas fa-compact-disc me-2"></i> <span data-key="nav_dashboard_artista">Dashboard Artista</span>
                    </a>
                </li>
                <?php endif; ?>

                <hr class="text-secondary mx-3">
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="cargarVista('vistas/perfil.php')">
                        <i class="fas fa-user-circle me-2 text-primary"></i> <span data-key="nav_perfil">Mi Perfil</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="cargarVista('vistas/historial.php')">
                        <i class="fas fa-history me-2 text-primary"></i> <span data-key="nav_historial">Mi Historial</span>
                    </a>
                </li>
                <hr class="text-secondary mx-3">
                <li class="nav-item">
                    <a class="nav-link text-danger" href="#" onclick="window.location.href='php/logout.php'; return false;">
                        <i class="fas fa-sign-out-alt me-2"></i> <span data-key="nav_salir">Cerrar Sesión</span>
                    </a>
                </li>
                <li class="nav-item px-3 mt-2">
                    <div class="dropdown w-100">
                        <button class="btn btn-dark w-100 text-start rounded-3 border-0 position-relative"
                                id="btn-notificaciones" data-bs-toggle="dropdown" aria-expanded="false"
                                onclick="abrirNotificaciones()">
                            <i class="fas fa-bell text-warning me-2"></i>
                            <span class="text-light small" data-key="nav_notificaciones">Notificaciones</span>
                            <span id="badge-notif" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark shadow-lg" id="lista-notif-dropdown"
                            style="min-width:300px; max-height:340px; overflow-y:auto;">
                            <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-secondary">
                                <strong class="text-light small text-uppercase" data-key="notif_titulo">Notificaciones</strong>
                                <button class="btn btn-link btn-sm text-secondary p-0" onclick="marcarTodasLeidas()"><span data-key="notif_marcar">Marcar todas leídas</span></button>
                            </li>
                            <li id="notif-placeholder" class="text-center py-3 text-light small" data-key="notif_cargando">Cargando...</li>
                        </ul>
                    </div>
                </li>
            </ul>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-4">
            <div id="contenedor-vistas">
                
                <div class="fade-in">
                    <div class="welcome-hero p-5 mb-4 rounded-4 shadow position-relative" style="background-color: #1a1a1a; color: white; overflow: hidden; min-height: 280px; display:flex; align-items:center;">
                        
                        <div class="hero-background">
                            <img src="../assets/img/artistas/ART_69cd4eb15f8f8.jpg" alt="Adele">
                            <img src="../assets/img/artistas/ART_69cd4ea3685e3.jpg" alt="Daft Punk">
                            <img src="../assets/img/artistas/ART_69cd4ebb99f44.jpg" alt="The Beatles">
                        </div>
                        <div class="hero-overlay"></div>
                        
                        <div class="position-relative w-100" style="z-index: 2;">
                            <h1 class="display-4 fw-bold mb-3" style="letter-spacing: -1px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">
                                <span data-key="welcome_main_title">Bienvenidos Usuarios de Soundverse</span>
                            </h1>
                            <p class="lead opacity-75 mb-4" style="max-width: 600px;" data-key="welcome_msg">Selecciona una opción del menú para comenzar a escuchar música.</p>
                            <button class="btn btn-warning btn-lg rounded-pill fw-bold px-4 shadow text-dark" onclick="cargarVista('vistas/descubrimiento.php')">
                                <i class="fas fa-play me-2"></i> <span data-key="nav_descubrimiento">Descubrimiento Semanal</span>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 mt-5">
                        <h4 class="fw-bold text-dark mb-0"><i class="fas fa-bolt text-warning me-2"></i><span id="quick-access-title">Accesos Rápidos</span></h4>
                    </div>
                    <div class="row g-3 mb-5">
                        <div class="col-6 col-md-4 col-lg flex-fill">
                            <div class="card border-0 shadow-sm quick-card h-100" style="border-radius: 12px; cursor: pointer; background: #fff;" onclick="cargarVista('vistas/catalogo.php')">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 p-3 rounded-3 text-primary">
                                        <i class="fas fa-music fa-lg"></i>
                                    </div>
                                    <h6 class="mb-0 fw-bold text-dark" data-key="nav_catalogo">Catálogo</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg flex-fill">
                            <div class="card border-0 shadow-sm quick-card h-100" style="border-radius: 12px; cursor: pointer; background: #fff;" onclick="cargarVista('vistas/artistas_explorar.php')">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="bg-info bg-opacity-10 p-3 rounded-3 text-info">
                                        <i class="fas fa-microphone-alt fa-lg"></i>
                                    </div>
                                    <h6 class="mb-0 fw-bold text-dark" data-key="quick_artistas">Artistas</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg flex-fill">
                            <div class="card border-0 shadow-sm quick-card h-100" style="border-radius: 12px; cursor: pointer; background: #fff;" onclick="cargarVista('vistas/Playlists.php')">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="bg-success bg-opacity-10 p-3 rounded-3 text-success">
                                        <i class="fas fa-list fa-lg"></i>
                                    </div>
                                    <h6 class="mb-0 fw-bold text-dark" data-key="nav_playlists">Mis Playlists</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg flex-fill">
                            <div class="card border-0 shadow-sm quick-card h-100" style="border-radius: 12px; cursor: pointer; background: #fff;" onclick="cargarVista('vistas/estadisticas.php')">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="bg-danger bg-opacity-10 p-3 rounded-3 text-danger">
                                        <i class="fas fa-chart-line fa-lg"></i>
                                    </div>
                                    <h6 class="mb-0 fw-bold text-dark" data-key="nav_estadisticas">Estadísticas</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg flex-fill">
                            <div class="card border-0 shadow-sm quick-card h-100" style="border-radius: 12px; cursor: pointer; background: #fff;" onclick="cargarVista('vistas/historial.php')">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="bg-warning bg-opacity-10 p-3 rounded-3 text-warning">
                                        <i class="fas fa-history fa-lg"></i>
                                    </div>
                                    <h6 class="mb-0 fw-bold text-dark" data-key="nav_historial">Mi Historial</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

    </div>
</div>

<div id="lyrics-container">
    <div class="lyrics-header">
        <span class="fw-bold"><i class="fas fa-align-left me-2"></i>Letra</span>
        <button class="btn btn-sm btn-link text-white p-0" onclick="$('#lyrics-container').removeClass('active')">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="lyrics-body" id="lyrics-content">
        <!-- Contenido de la letra -->
    </div>
</div>

<div class="reproductor-fijo">
    <div class="row align-items-center">
        <div class="col-md-3 col-4">
            <strong id="now-playing" style="color: #ffffff; font-size: 1rem;" data-key="player_no_playing">No reproduciendo</strong><br>
            <small id="now-artist" style="color: #cbd5e0; font-size: 0.85rem;">&nbsp;</small>
        </div>
        <div class="col-md-6 col-4 text-center">
            <audio id="player-audio" preload="metadata">
                Tu navegador no soporta audio.
            </audio>
            <div class="btn-group mb-1" role="group">
                <button id="btn-shuffle" class="btn btn-outline-secondary btn-sm" title="Modo Aleatorio">
                    <i class="fas fa-random"></i>
                </button>
                <button id="btn-prev" class="btn btn-outline-light btn-sm" title="Anterior / Reiniciar">
                    <i class="fas fa-backward"></i>
                </button>
                <button id="btn-playpause" class="btn btn-outline-light btn-sm" title="Reproducir / Pausar">
                    <i class="fas fa-play"></i>
                </button>
                <button id="btn-skip" class="btn btn-outline-light btn-sm" title="Siguiente">
                    <i class="fas fa-forward"></i>
                </button>
                <button id="btn-repeat" class="btn btn-outline-secondary btn-sm" title="Repetir: Apagado">
                    <i class="fas fa-redo"></i>
                </button>
            </div>
            <div class="progress sv-progress" style="height: 6px; cursor: pointer;" title="Clic para saltar">
                <div id="progress-bar" class="progress-bar bg-primary" style="width: 0%"></div>
            </div>
            <small id="time-display" style="color: #cbd5e0;">0:00 / 0:00</small>
        </div>
        <div class="col-md-3 col-4 text-end align-items-center">
            <button id="btn-letra" class="btn btn-sm btn-outline-info me-2" title="Ver Letra" onclick="verLetraCancion()" style="display:none;">
                <i class="fas fa-align-left"></i>
            </button>
            <span id="quality-badge" class="badge <?php echo $es_premium ? 'bg-warning text-dark' : 'bg-secondary'; ?> me-2">
                <?php echo $es_premium ? '320 kbps' : '128 kbps'; ?>
            </span>
            <label class="text-light small" data-key="player_vol">Vol</label>
            <input type="range" id="volume-control" min="0" max="1" step="0.1" value="0.8" class="form-range" style="width:80px; display:inline-block;">
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/funciones_user.js"></script>

<script>
function actualizarSaludo(codigoLang) {
    const qa = document.getElementById('quick-access-title');
    if (qa) {
        qa.innerText = (codigoLang === 'en') ? 'Quick Access' : 'Accesos Rápidos';
    }
}

window.cambiarIdioma = function(codigo) {
    let formData = new FormData();
    formData.append('idioma', codigo);
    
    fetch('php/queries.php?caso=cargar_idioma', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                localStorage.setItem('idiomaSite', codigo);
                
                document.querySelectorAll('.btn-es').forEach(btn => {
                    btn.classList.toggle('btn-light', codigo === 'es');
                    btn.classList.toggle('text-dark', codigo === 'es');
                    btn.classList.toggle('btn-outline-light', codigo !== 'es');
                    btn.classList.toggle('opacity-50', codigo !== 'es');
                });
                document.querySelectorAll('.btn-en').forEach(btn => {
                    btn.classList.toggle('btn-light', codigo === 'en');
                    btn.classList.toggle('text-dark', codigo === 'en');
                    btn.classList.toggle('btn-outline-light', codigo !== 'en');
                    btn.classList.toggle('opacity-50', codigo !== 'en');
                });
                
                const traducciones = data.data;
                document.querySelectorAll('[data-key]').forEach(elemento => {
                    const clave = elemento.getAttribute('data-key');
                    if (traducciones[clave]) {
                        elemento.innerText = traducciones[clave];
                    }
                });

                const ph = document.getElementById('notif-placeholder');
                if (ph && (!window._notificaciones || window._notificaciones.length === 0)) {
                    ph.textContent = codigo === 'en' ? 'You have no notifications.' : 'No tienes notificaciones.';
                }
                
                actualizarSaludo(codigo);
            }
        });
}

document.addEventListener("DOMContentLoaded", function() {
    // Inyectar usuarioId globalmente para usarlo dinámicamente
    window.usuarioId = <?php echo isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 'null'; ?>;

    let idioma = localStorage.getItem('idiomaSite') || 'es';
    cambiarIdioma(idioma);

    // [PREMIUM-O] Verificación de sesión concurrente cada 60 segundos
    setInterval(function() {
        fetch('php/queries.php?caso=validar_sesion_concurrente')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'conflict') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sesión Duplicada',
                        text: 'Tu cuenta se ha abierto en otro dispositivo. Se cerrará esta sesión.',
                        confirmButtonText: 'Entendido',
                        allowOutsideClick: false
                    }).then(() => {
                        window.location.href = 'php/logout.php';
                    });
                }
            });
    }, 60000); 
});

function cargarVista(urlVista) {
    const contenedor = document.getElementById('contenedor-vistas');

    document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
        link.classList.remove('active');
        if (link.getAttribute('onclick') && link.getAttribute('onclick').includes(urlVista)) {
            link.classList.add('active');
        }
    });

    const currentLang = localStorage.getItem('idiomaSite') || 'es';
    const textCargando = currentLang === 'en' ? 'Loading...' : 'Cargando...';
    contenedor.innerHTML = `
        <div class="text-center mt-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">${textCargando}</p>
        </div>`;
    
    restaurarReproduccion();

    const urlBuster = urlVista.includes('?') ? (urlVista + '&t=' + new Date().getTime()) : (urlVista + '?t=' + new Date().getTime());
    
    fetch(urlBuster)
        .then(function(response) {
            if (!response.ok) throw new Error('No se encontró: ' + urlVista);
            return response.text();
        })
        .then(function(html) {
            contenedor.innerHTML = html;

            setTimeout(function() {
                restaurarReproduccion();
            }, 100);

            let idiomaActual = localStorage.getItem('idiomaSite') || 'es';
            if (typeof cambiarIdioma === 'function') {
                cambiarIdioma(idiomaActual);
            }

            const scripts = contenedor.querySelectorAll('script');
            scripts.forEach(function(oldScript) {
                const newScript = document.createElement('script');
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                document.body.appendChild(newScript);
                oldScript.remove();
            });

            setTimeout(function() {
                if (urlVista.includes('Playlists.php')) {
                    if (typeof window.cargarPlaylists === 'function') window.cargarPlaylists();
                    if (typeof window.buscarPlaylistsPublicas === 'function') window.buscarPlaylistsPublicas('');
                } else if (urlVista.includes('perfil.php') && !urlVista.includes('perfil_artista')) {
                    if (typeof window.cargarArtistasSeguidos === 'function') window.cargarArtistasSeguidos();
                } else if (urlVista.includes('estadisticas.php')) {
                    if (typeof window.cargarEstadisticas === 'function') window.cargarEstadisticas();
                } else if (urlVista.includes('descubrimiento.php')) {
                    if (typeof window.cargarDescubrimiento === 'function') window.cargarDescubrimiento();
                } else if (urlVista.includes('catalogo.php')) {
                    if (typeof window.cargarCatalogo === 'function') window.cargarCatalogo();
                } else if (urlVista.includes('historial.php')) {
                    if (typeof window.cargarHistorial === 'function') window.cargarHistorial();
                } else if (urlVista.includes('suscripcion.php')) {
                    if (typeof window.cargarMetodosPago === 'function') window.cargarMetodosPago();
                } else if (urlVista.includes('artistas_explorar.php')) {
                    // *** LLAMADA A LA NUEVA FUNCIÓN ***
                    if (typeof window.cargarTodosArtistas === 'function') window.cargarTodosArtistas();
                }
            }, 200);

        })
        .catch(function(error) {
            console.error('Error cargando vista:', error);
            const textError = (localStorage.getItem('idiomaSite') === 'en') ? 'Error loading requested module.' : 'Error al cargar el módulo solicitado.';
            contenedor.innerHTML = `
                <div class="alert alert-danger mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${textError}
                </div>`;
        });
}

window.verPerfilArtista = function(idArtista) {
    window.paArtistaId = idArtista;
    cargarVista('vistas/perfil_artista.php');
};

// =========================================================================
// REPRODUCTOR DE AUDIO & REGLAS DE NEGOCIO (PREMIUM VS FREE)
// =========================================================================
let audio         = document.getElementById('player-audio');
let currentSongId = null;
let isPlaying     = false;

window.currentSongInfo = null;
window.isAdPlaying = false;

window.esPremium = <?php echo $es_premium ? 'true' : 'false'; ?>;
let contadorCancionesFree = 0;
let umbralDinamicoAnuncio = Math.floor(Math.random() * 2) + 4; /* (4 o 5) */
const AD_URL = '../assets/audio/ads/premium_ad.mp3';

let modoShuffle = false;
let repeatMode  = 0; 

function actualizarBotonPlayPause() {
    const btn = document.getElementById('btn-playpause');
    if (!btn) return;
    if (isPlaying) {
        btn.innerHTML = '<i class="fas fa-pause"></i>';
        btn.title = 'Pausar';
    } else {
        btn.innerHTML = '<i class="fas fa-play"></i>';
        btn.title = 'Reproducir';
    }
}

function restaurarReproduccion() {
    let cancion = null;

    if (window.currentSongInfo && window.currentSongInfo.titulo) {
        cancion = window.currentSongInfo;
    } else {
        const saved = localStorage.getItem('sv_now_playing');
        if (saved) {
            try {
                cancion = JSON.parse(saved);
            } catch(e) { }
        }
    }

    if (cancion && cancion.titulo) {
        $('#now-playing').removeAttr('data-key').text(cancion.titulo);
        $('#now-artist').text(cancion.artista);
        currentSongId = cancion.id;
        window.currentSongInfo = cancion;
        $('#btn-letra').show();
        
        // Restaurar la fuente de audio para no bloquear el botón Play
        if (cancion.urlAudio) {
            const audio = document.getElementById('player-audio');
            // Solo asinar src si no está ya cargado para no interrumpir
            if (!audio.src || audio.src === window.location.href || audio.src.endsWith('undefined')) {
                audio.src = cancion.urlAudio;
                audio.load();
            }
        }
        
        actualizarBotonPlayPause();
    }
}

$(document).on('click', '#btn-shuffle', function() {
    if ($(this).prop('disabled')) {
        const isEn = localStorage.getItem('idiomaSite') === 'en';
        Swal.fire({
            icon: 'info',
            title: isEn ? 'Forced Shuffle Mode' : 'Modo Aleatorio Forzado',
            text: isEn ? 'Free users must listen to public playlists on shuffle. Upgrade to Premium to disable.' : 'Los usuarios Free deben escuchar playlists públicas en modo aleatorio. Mejora a Premium para desactivarlo.',
            confirmButtonColor: '#6B46C1'
        });
        return;
    }
    modoShuffle = !modoShuffle;
    $(this).toggleClass('btn-outline-secondary btn-primary text-white');
    if (modoShuffle && window.colaReproduccion && window.colaReproduccion.length > 1) {
        for (let i = window.colaReproduccion.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [window.colaReproduccion[i], window.colaReproduccion[j]] =
            [window.colaReproduccion[j], window.colaReproduccion[i]];
        }
    }
    const isEn = localStorage.getItem('idiomaSite') === 'en';
    Swal.fire({ icon: modoShuffle ? 'success' : 'info', title: modoShuffle ? (isEn ? 'Shuffle On' : 'Aleatorio activado') : (isEn ? 'Shuffle Off' : 'Aleatorio desactivado'), timer: 1000, showConfirmButton: false });
});

$('#btn-repeat').on('click', function() {
    repeatMode = (repeatMode + 1) % 3;
    audio.loop = (repeatMode === 1);
    const isEn = localStorage.getItem('idiomaSite') === 'en';
    $(this).removeClass('btn-outline-secondary btn-warning btn-success text-white text-dark');
    if (repeatMode === 0) {
        $(this).addClass('btn-outline-secondary').attr('title', isEn ? 'Repeat: Off' : 'Repetir: Apagado').html('<i class="fas fa-redo"></i>');
        Swal.fire({ icon: 'info',    title: isEn ? 'Repeat Off' : 'Repetición desactivada', timer: 900, showConfirmButton: false, toast: true, position: 'top-end' });
    } else if (repeatMode === 1) {
        $(this).addClass('btn-warning text-dark').attr('title', isEn ? 'Repeat: This song' : 'Repetir: Esta canción').html('<i class="fas fa-redo"></i><span class="ms-1" style="font-size:11px;">1</span>');
        Swal.fire({ icon: 'success', title: isEn ? 'Repeat Song' : 'Repetir canción', timer: 900, showConfirmButton: false, toast: true, position: 'top-end' });
    } else {
        $(this).addClass('btn-success text-white').attr('title', isEn ? 'Repeat: Entire playlist' : 'Repetir: Toda la playlist').html('<i class="fas fa-retweet"></i>');
        Swal.fire({ icon: 'success', title: isEn ? 'Repeat Playlist' : 'Repetir playlist', timer: 900, showConfirmButton: false, toast: true, position: 'top-end' });
    }
});

$('#btn-prev').on('click', function() {
    if (audio.currentTime > 3) {
        audio.currentTime = 0;
        return;
    }
    if (window.colaOriginal && window.colaOriginal.length > 0) {
        const idxActual = window.colaOriginal.findIndex(c => c.PK_id_cancion == currentSongId);
        const idxAnterior = idxActual > 0 ? idxActual - 1 : window.colaOriginal.length - 1;
        const anterior = window.colaOriginal[idxAnterior];
        procesarEscuchaActual();
        reproducirCancion(anterior.PK_id_cancion, anterior.titulo, anterior.artista, '../' + anterior.ruta_archivo_audio);
    } else {
        audio.currentTime = 0;
    }
});

$('#btn-playpause').on('click', function() {
    if (!audio.src || audio.src === window.location.href) return;
    if (isPlaying) { audio.pause(); isPlaying = false; } 
    else { audio.play().catch(e => console.log(e)); isPlaying = true; }
    actualizarBotonPlayPause();
});

$('#btn-skip').on('click', function() {
    const hayColaSiguiente = window.radioActiva && window.colaReproduccion && window.colaReproduccion.length > 0;
    if (!hayColaSiguiente) {
        if (repeatMode === 2 && window.colaOriginal && window.colaOriginal.length > 0) {
            window.colaReproduccion = JSON.parse(JSON.stringify(window.colaOriginal));
        } else {
            const isEn = localStorage.getItem('idiomaSite') === 'en';
            Swal.fire({ icon: 'info', title: isEn ? 'No next song' : 'Sin siguiente canción', text: isEn ? 'No more songs in the queue.' : 'No hay más canciones en la cola.', timer: 1800, showConfirmButton: false, toast: true, position: 'top-end' });
            return;
        }
    }
    if (!puedeSaltar()) return;
    procesarEscuchaActual();
    audio.pause(); audio.currentTime = 0;
    let siguiente = window.colaReproduccion.shift();
    reproducirCancion(siguiente.PK_id_cancion, siguiente.titulo, siguiente.artista, '../' + siguiente.ruta_archivo_audio);
});

$(document).on('click', '.sv-progress', function(e) {
    if (window.isAdPlaying) return; // BLOQUEO DE SALTO DE ANUNCIO
    if (!audio.src || isNaN(audio.duration) || audio.duration <= 0) return;
    const rect  = this.getBoundingClientRect();
    const ratio = (e.clientX - rect.left) / rect.width;
    audio.currentTime = ratio * audio.duration;
});

function puedeSaltar() {
    if (window.esPremium) return true;
    let skipsKey = 'sv_skips_' + window.usuarioId;
    let skipsStr = localStorage.getItem(skipsKey);
    let skips = skipsStr ? JSON.parse(skipsStr) : [];
    let ahora = Date.now();
    skips = skips.filter(ts => (ahora - ts) < 3600000);
    if (skips.length >= 6) {
        const isEn = localStorage.getItem('idiomaSite') === 'en';
        Swal.fire({ title: isEn ? 'Limit Reached' : 'Límite Alcanzado', text: isEn ? 'You have used your 6 hourly skips on the Free plan.' : 'Has usado los 6 saltos por hora del plan Free.', icon: 'warning', confirmButtonText: isEn ? 'Go Premium!' : '¡Hazte Premium!' });
        return false;
    }
    skips.push(ahora);
    localStorage.setItem(skipsKey, JSON.stringify(skips));
    return true;
}

function procesarEscuchaActual() {
    if (currentSongId && audio.currentTime > 0) {
        $.ajax({
            url: 'php/queries.php?caso=registrar_escucha', type: 'POST', data: { id_cancion: currentSongId, segundos: Math.floor(audio.currentTime) }, dataType: 'json',
            success: function(resp) { console.log('Escucha procesada:', resp.message); }
        });
    }
}

const handleEnded = function() {
    procesarEscuchaActual();
    isPlaying = false;
    actualizarBotonPlayPause();

    if (repeatMode === 1) {
        audio.play().catch(e => console.log(e));
        isPlaying = true;
        actualizarBotonPlayPause();
        return;
    }

    if (window.radioActiva) {
        if (window.colaReproduccion && window.colaReproduccion.length > 0) {
            let siguiente = window.colaReproduccion.shift();
            reproducirCancion(siguiente.PK_id_cancion, siguiente.titulo, siguiente.artista, '../' + siguiente.ruta_archivo_audio);
        } else if (repeatMode === 2 && window.colaOriginal && window.colaOriginal.length > 0) {
            window.colaReproduccion = JSON.parse(JSON.stringify(window.colaOriginal));
            if (modoShuffle) {
                for (let i = window.colaReproduccion.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [window.colaReproduccion[i], window.colaReproduccion[j]] = [window.colaReproduccion[j], window.colaReproduccion[i]];
                }
            }
            let primera = window.colaReproduccion.shift();
            reproducirCancion(primera.PK_id_cancion, primera.titulo, primera.artista, '../' + primera.ruta_archivo_audio);
        }
    }
};

audio.addEventListener('ended', handleEnded);

audio.addEventListener('timeupdate', function() {
    if (!isNaN(audio.duration) && audio.duration > 0) {
        let percent = (audio.currentTime / audio.duration) * 100;
        $('#progress-bar').css('width', percent + '%');

        let cMin = Math.floor(audio.currentTime / 60);
        let cSec = Math.floor(audio.currentTime % 60);
        let tMin = Math.floor(audio.duration / 60);
        let tSec = Math.floor(audio.duration % 60);
        $('#time-display').text(
            cMin + ':' + (cSec < 10 ? '0' + cSec : cSec) + ' / ' +
            tMin + ':' + (tSec < 10 ? '0' + tSec : tSec)
        );

        // SINCRONIZACIÓN DE LETRAS CON SCROLL AUTOMÁTICO
        if (typeof sincronizarLetras === 'function') {
            sincronizarLetras(audio.currentTime);
        }
    }
});

audio.addEventListener('play',  function() { isPlaying = true;  actualizarBotonPlayPause(); });
audio.addEventListener('pause', function() { isPlaying = false; actualizarBotonPlayPause(); });

$('#volume-control').on('input', function() { audio.volume = this.value; });

window.iniciarRadio = function(idCancion) {
    const isEn = localStorage.getItem('idiomaSite') === 'en';
    Swal.fire({ title: isEn ? 'Starting Radio...' : 'Iniciando Radio...', text: isEn ? 'Finding similar songs' : 'Buscando canciones similares', icon: 'info', timer: 1500, showConfirmButton: false });
    $.ajax({
        url: 'php/queries.php?caso=radio_cancion', type: 'GET', data: { id_cancion: idCancion }, dataType: 'json',
        success: function(resp) {
            if(resp.status === 'success' && resp.data.length > 0) {
                window.colaOriginal = JSON.parse(JSON.stringify(resp.data));
                window.colaReproduccion = resp.data;
                window.radioActiva = true;
                let siguiente = window.colaReproduccion.shift();
                reproducirCancion(siguiente.PK_id_cancion, siguiente.titulo, siguiente.artista, '../' + siguiente.ruta_archivo_audio);
            } else {
                Swal.fire('Atención', isEn ? 'Not enough songs of the same genre.' : 'No hay suficientes canciones del mismo género.', 'warning');
            }
        }
    });
}

function actualizarBadgeNotificaciones() {
    $.post('php/queries.php', { caso: 'obtener_notificaciones' }, function(res) {
        if (res.status !== 'success') return;
        const badge = document.getElementById('badge-notif');
        if (!badge) return;
        if (res.no_leidas > 0) {
            badge.textContent = res.no_leidas > 9 ? '9+' : res.no_leidas;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
        window._notificaciones = res.data || [];
    }, 'json');
}

window.abrirNotificaciones = function() {
    const lista = window._notificaciones || [];
    const ul    = document.getElementById('lista-notif-dropdown');
    const ph    = document.getElementById('notif-placeholder');
    const isEn  = localStorage.getItem('idiomaSite') === 'en';

    let items = '';
    if (lista.length === 0) {
        ph.textContent = isEn ? 'You have no notifications.' : 'No tienes notificaciones.';
        ph.style.display = 'block';
    } else {
        ph.style.display = 'none';
        lista.forEach(n => {
            const leida    = n.leida == 1;
            const bg       = leida ? '' : 'bg-primary bg-opacity-10';
            const iconoTipo = n.tipo === 'nueva_cancion' ? 'fa-music text-success' : 'fa-bell text-warning';
            const fecha    = new Date(n.fecha_creacion).toLocaleDateString(isEn ? 'en' : 'es', { day:'2-digit', month:'short' });
            items += `
            <li class="dropdown-item px-3 py-2 border-bottom border-secondary ${bg} cursor-pointer" onclick="marcarUnaLeida(${n.PK_id_notificacion}, this)" style="white-space:normal; cursor:pointer;">
                <div class="d-flex gap-2 align-items-start">
                    <i class="fas ${iconoTipo} mt-1 flex-shrink-0"></i>
                    <div style="line-height:1.3">
                        <div class="small text-light">${n.mensaje}</div>
                        <div class="text-secondary" style="font-size:.72rem;">${fecha}</div>
                    </div>
                    ${!leida ? '<span class="ms-auto flex-shrink-0 rounded-circle bg-primary" style="width:8px;height:8px;margin-top:4px;"></span>' : ''}
                </div>
            </li>`;
        });
    }

    const encabezado = ul.querySelector('li:first-child');
    const phItem = ul.querySelector('#notif-placeholder');
    ul.innerHTML = '';
    ul.appendChild(encabezado);
    ul.appendChild(phItem);
    if(items !== '') ul.insertAdjacentHTML('beforeend', items);
};

window.marcarTodasLeidas = function() {
    $.post('php/queries.php', { caso: 'marcar_notificacion_leida', id_notificacion: 0 }, function() {
        actualizarBadgeNotificaciones();
        document.querySelectorAll('#lista-notif-dropdown .bg-opacity-10').forEach(el => el.classList.remove('bg-primary','bg-opacity-10'));
        document.querySelectorAll('#lista-notif-dropdown .bg-primary.rounded-circle').forEach(el => el.remove());
    }, 'json');
};

window.marcarUnaLeida = function(id, el) {
    if (!el.classList.contains('bg-opacity-10')) return;
    $.post('php/queries.php', { caso: 'marcar_notificacion_leida', id_notificacion: id }, function(res) {
        if (res.status === 'success') {
            el.classList.remove('bg-primary', 'bg-opacity-10');
            const dot = el.querySelector('.bg-primary.rounded-circle');
            if (dot) dot.remove();
            actualizarBadgeNotificaciones();
        }
    }, 'json');
};

setTimeout(actualizarBadgeNotificaciones, 1500);
setInterval(actualizarBadgeNotificaciones, 90000);

window.reproducirCancion = function(id, titulo, artista, urlAudio, ignorarAd = false) {
    window.currentSongInfo = { id: id, titulo: titulo, artista: artista, urlAudio: urlAudio };
    localStorage.setItem('sv_now_playing', JSON.stringify(window.currentSongInfo));
    
    if (!window.esPremium && !ignorarAd) {
        contadorCancionesFree++;
        if (contadorCancionesFree >= umbralDinamicoAnuncio) {
            contadorCancionesFree = 0;            
            umbralDinamicoAnuncio = Math.floor(Math.random() * 2) + 4;
            window.isAdPlaying = true;
            $('.sv-progress').css('cursor', 'not-allowed');
            
            audio.removeEventListener('ended', handleEnded);
            $('#btn-skip, #btn-prev, #btn-shuffle, #btn-repeat').prop('disabled', true);
            
            audio.src = AD_URL;
            $('#now-playing').removeAttr('data-key').text("🎧 Soundverse Premium");
            $('#now-artist').text("Escucha música sin interrupciones. ¡Cámbiate hoy!");
            
            procesarEscuchaActual();
            currentSongId = null;
            
            audio.load();
            audio.play().catch(e => console.log(e));
            
            audio.onended = function() {
                window.isAdPlaying = false;
                $('.sv-progress').css('cursor', 'pointer');
                $('#btn-skip, #btn-prev, #btn-shuffle, #btn-repeat').prop('disabled', false);

                audio.onended = null;
                audio.removeEventListener('ended', handleEnded);
                audio.addEventListener('ended', handleEnded); 
                reproducirCancion(id, titulo, artista, urlAudio, true); 
            };
            return;
        }
    }

    procesarEscuchaActual(); 
    audio.src = urlAudio;
    $('#now-playing').removeAttr('data-key').text(titulo);
    $('#now-artist').text(artista);
    currentSongId = id;

    audio.load();
    audio.play().catch(function(e) { console.log(e); });
    isPlaying = true;
    $('#btn-letra').show();
};

$(document).ready(function() {
    restaurarReproduccion();
    $.ajax({
        url: 'php/queries.php?caso=calidad_usuario', type: 'GET', dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                const badge = document.getElementById('quality-badge');
                if (badge) badge.textContent = resp.calidad_kbps + ' kbps';
                window.calidadKbps = resp.calidad_kbps;
            }
        }
    });
});

window.addEventListener('pageshow', function() { restaurarReproduccion(); });

window.verLetraCancion = function() {
    const isEn = localStorage.getItem('idiomaSite') === 'en';
    if (!currentSongId) {
        Swal.fire({ icon: 'warning', title: isEn ? 'Attention' : 'Atención', text: isEn ? 'No song is currently playing.' : 'No hay ninguna canción reproduciéndose.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
        return;
    }

    const container = $('#lyrics-container');
    const content   = $('#lyrics-content');

    if (container.hasClass('active')) {
        container.removeClass('active');
        return;
    }

    // Se asume que cargará exitosamente, así que abrimos el panel y mostramos un loading temporal
    content.html('<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> ' + (isEn ? 'Loading...' : 'Cargando...') + '</div>');
    container.addClass('active');
    
    $.ajax({
        url: 'php/queries.php',
        type: 'GET',
        data: { caso: 'obtener_letra', id_cancion: currentSongId },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success' && resp.data && resp.data.trim() !== '') {
                renderizarLetrasLRC(resp.data);
            } else {
                // No hay letra: quitamos el panel y mostramos un SweetAlert
                container.removeClass('active');
                Swal.fire({
                    icon: 'info',
                    title: isEn ? 'Lyrics unavailable' : 'Letra no disponible',
                    text: isEn ? "We haven't added the lyrics for this song yet." : 'Aún no hemos agregado la letra para esta canción.',
                    confirmButtonColor: '#6B46C1'
                });
            }
        },
        error: function() {
            // Error de conexión u otro: quitamos panel y mostramos error en SweetAlert
            container.removeClass('active');
            Swal.fire(
                'Error', 
                isEn ? 'Could not load song lyrics due to a server error.' : 'No se pudo cargar la letra debido a un error del servidor.', 
                'error'
            );
        }
    });
};

function renderizarLetrasLRC(texto) {
    const content = $('#lyrics-content');
    content.empty();

    const regex = /\[(\d{2}):(\d{2})\.(\d{2,3})\](.*)/;
    const lineas = texto.split('\n');
    let haySincronizacion = false;

    lineas.forEach(linea => {
        const match = regex.exec(linea);
        if (match) {
            haySincronizacion = true;
            const min = parseInt(match[1]);
            const seg = parseInt(match[2]);
            const ms  = parseInt(match[3]);
            const totalSegundos = (min * 60) + seg + (ms/1000);
            const letra = match[4].trim();

            if (letra) {
                content.append(`<div class="lyric-line" data-time="${totalSegundos}" onclick="audio.currentTime = ${totalSegundos}">${letra}</div>`);
            }
        } else if (linea.trim() !== '') {
            content.append(`<div class="lyric-line text-center opacity-75" data-time="0">${linea}</div>`);
        }
    });

    if (!haySincronizacion && texto.trim() !== '') {
        content.html(`<div class="text-center fs-5 py-4" style="white-space:pre-wrap; line-height:2;">${texto}</div>`);
    }
}

function sincronizarLetras(currentTime) {
    const lines = document.querySelectorAll('.lyric-line');
    if (lines.length === 0) return;

    let activeIndex = -1;
    for (let i = 0; i < lines.length; i++) {
        const lineTime = parseFloat(lines[i].getAttribute('data-time'));
        if (currentTime >= lineTime) {
            activeIndex = i;
        } else {
            break;
        }
    }

    if (activeIndex !== -1) {
        const activeLine = lines[activeIndex];
        if (!activeLine.classList.contains('active')) {
            lines.forEach(l => l.classList.remove('active'));
            activeLine.classList.add('active');
            
            const container = document.getElementById('lyrics-content');
            if (container) {
                const lineTop = activeLine.offsetTop;
                const containerHeight = container.clientHeight;
                const lineHeight = activeLine.clientHeight;
                container.scrollTo({
                    top: lineTop - (containerHeight / 2) + (lineHeight / 2),
                    behavior: 'smooth'
                });
            }
        }
    }
}


window.actualizarEstadoBotonShuffle = function(deshabilitado) {
    const btnShuffle = $('#btn-shuffle');
    const isEn = localStorage.getItem('idiomaSite') === 'en';
    if (deshabilitado) {
        if (!modoShuffle) {
            modoShuffle = true;
            btnShuffle.addClass('btn-primary text-white').removeClass('btn-outline-secondary');
        }
        btnShuffle.prop('disabled', true).attr('title', isEn ? 'Forced shuffle for public playlists (Free)' : 'Modo aleatorio forzado para playlists públicas (Free)');
    } else {
        btnShuffle.prop('disabled', false).attr('title', isEn ? 'Shuffle Mode' : 'Modo Aleatorio');
    }
};
</script>

</body>
</html>