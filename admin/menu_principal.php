<?php
/**
 * ARCHIVO: menu_principal.php
 * AUTOR: Mario Roger Mejía Elvir - Equipo Proyecto 6
 * PROPÓSITO:
 * Actuar como la plantilla principal (Master Page) del panel de administración.
 * Implementa una arquitectura SPA (Single Page Application), donde el menú 
 * permanece estático y el contenido central se recarga dinámicamente vía AJAX.

 * 1. Uso de HTML5 semántico.
 * 2. Integración de Bootstrap para diseño responsivo.
 * 3. Preparación de validación de sesión para seguridad.
 */

// BLOQUE DE SEGURIDAD — REGLA 6: Sesiones seguras (innegociable)
session_name('SOUNDVERSE_ADMIN');
session_start();

// Guard de acceso: si no hay sesión válida de administrador, redirigir al login
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['es_admin'])) {
    header("Location: index.php");
    exit();
}
// Control de inactividad: 1800 segundos = 30 minutos
if (isset($_SESSION['time'])) {
    if ((time() - $_SESSION['time']) > 600) {
        header("Location: php/logout.php");
        exit();
    }
}
$_SESSION['time'] = time();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soundverse - Panel de Administración</title>

    <link rel="shortcut icon" href="../assets/img/favicon.ico" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bs-primary: #6B46C1;
            --bs-primary-rgb: 107, 70, 193;
            --bs-warning: #FBBF24;
            --bs-warning-rgb: 251, 191, 36;
            --bs-info: #9F7AEA;
        }

        /* Sobremarcha de Bootstrap */
        .btn-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .btn-primary:hover {
            background-color: #55359e;
            border-color: #55359e;
        }

        .bg-primary {
            background-color: var(--bs-primary) !important;
        }

        .text-primary {
            color: var(--bs-primary) !important;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f4f6f9;
            /* Tono suave para la vista central */
        }

        /* Estilos del Menú Lateral (Sidebar) */
        .sidebar {
            min-height: 100vh;
            background-color: #2D3748;
            /* Gris oscuro oficial */
            color: white;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: #adb5bd !important;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: 0.3s;
            font-weight: 500;
            border-left: 4px solid transparent;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link:focus {
            color: #fff !important;
            background-color: #4B318B !important;
            /* Morado 20% más oscuro para hover */
            border-left: 4px solid transparent;
            outline: none;
        }

        .sidebar .nav-link.active {
            color: #fff !important;
            background-color: #6B46C1 !important;
            /* Morado de selección Oficial */
            border-left: 4px solid #FBBF24 !important;
            /* Acento amarillo */
            outline: none;
        }

        /* Contenedor de la línea gráfica */
        .logo-container {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="container-fluid p-0">
        <div class="row g-0">

            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">

                <div class="logo-container">
                    <img src="../assets/img/logo_soundverse_white.png" alt="Soundverse Logo" class="img-fluid px-3"
                        style="max-height: 80px; filter: drop-shadow(0px 0px 2px rgba(255,255,255,0.5));">

                    <h6 class="text-uppercase fw-bold text-light mt-2" style="letter-spacing: 2px;">
                        Soundverse <span class="text-primary">Admin</span>
                    </h6>
                    <div class="mt-2 d-flex justify-content-center gap-2">
                        <button id="btn-es" onclick="cambiarIdioma('es')" class="btn btn-sm btn-light text-dark px-2 py-0" style="font-size:11px;border-radius:20px;">🇲🇽 ES</button>
                        <button id="btn-en" onclick="cambiarIdioma('en')" class="btn btn-sm btn-outline-light opacity-50 px-2 py-0" style="font-size:11px;border-radius:20px;">🇺🇸 EN</button>
                    </div>
                </div>

                <div class="position-sticky">
                    <ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link" href="#" onclick="cargarVista('vistas/dashboard.php')">
            <i class="fas fa-chart-line me-2"></i> <span data-key="nav_dashboard">Dashboard</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" onclick="cargarVista('vistas/usuarios.php')">
            <i class="fas fa-users-cog me-2"></i> <span data-key="nav_usuarios">Gestión de Usuarios</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a class="nav-link" href="#" onclick="cargarVista('vistas/artistas.php')">
            <i class="fas fa-microphone me-2"></i> <span data-key="nav_artistas">Artistas</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" onclick="cargarVista('vistas/albumes.php')">
            <i class="fas fa-images me-2"></i> <span data-key="nav_albumes">Álbumes</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" onclick="cargarVista('vistas/catalogo.php')">
            <i class="fas fa-music me-2"></i> <span data-key="nav_catalogo">Canciones</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" onclick="cargarVista('vistas/generos.php')">
            <i class="fas fa-tags me-2"></i> <span data-key="nav_generos">Géneros</span>
        </a>
    </li>
</ul>

                    <hr class="text-secondary mx-3">

                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="#" onclick="window.location.href='php/logout.php'; return false;">
                                <i class="fas fa-sign-out-alt me-2"></i> <span data-key="nav_salir">Cerrar Sesión</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-4">

                <div id="contenedor-vistas">

                    <div class="alert alert-primary shadow-sm border-0 border-start border-5 border-primary">
                        <h4 class="alert-heading"><i class="fas fa-info-circle"></i> <span data-key="adm_bienvenida">Bienvenido a Soundverse</span></h4>
                        <p data-key="adm_bienvenida_desc">El sistema está funcionando correctamente. Selecciona un módulo en el menú lateral izquierdo
                            para comenzar a administrar la plataforma.</p>
                    </div>

                </div>

            </main>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="../js/funciones.js?v=1.4"></script>
    
    <script>
        // ====================================================
        // Multilenguaje Async (CU-30)
        // ====================================================
        window.cambiarIdioma = function(codigo) {
            let formData = new FormData();
            formData.append('idioma', codigo);
            // El backend principal lo pusimos en user/php/queries.php
            fetch('../user/php/queries.php?caso=cargar_idioma', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        localStorage.setItem('idiomaSite', codigo);
                        
                        const bES = document.getElementById('btn-es');
                        const bEN = document.getElementById('btn-en');

                        if (codigo === 'es') {
                            bES.className = "btn btn-sm btn-light text-dark px-2 py-1";
                            bES.classList.remove('opacity-50');
                            bEN.className = "btn btn-sm btn-outline-light opacity-50 px-2 py-1";
                        } else {
                            bEN.className = "btn btn-sm btn-light text-dark px-2 py-1";
                            bEN.classList.remove('opacity-50');
                            bES.className = "btn btn-sm btn-outline-light opacity-50 px-2 py-1";
                        }

                        const traducciones = data.data;
                        document.querySelectorAll('[data-key]').forEach(elemento => {
                            const clave = elemento.getAttribute('data-key');
                            if (traducciones[clave]) {
                                elemento.innerText = traducciones[clave];
                            }
                        });
                    }
                });
        }
        
        window.onload = function () {
            // Cargar idioma
            let idioma = localStorage.getItem('idiomaSite') || 'es';
            cambiarIdioma(idioma);
            
            // Uncomment to load dashboard automatically
            // cargarVista('vistas/dashboard.php');
        };
    </script>

</body>

</html>