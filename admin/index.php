<?php
session_name('SOUNDVERSE_ADMIN');
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Soundverse Admin</title>
    <link rel="shortcut icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CORRECCIÓN: SweetAlert2 solo se carga UNA VEZ (se eliminó la copia duplicada que estaba aquí en el head) -->
    <style>
        :root {
            --bs-primary: #6B46C1;
            --bs-primary-rgb: 107, 70, 193;
        }
        .btn-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        .btn-primary:hover {
            background-color: #55359e;
            border-color: #55359e;
        }
        body {
            background-color: #2D3748;
            margin: 0;
        }
        .page-center {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #6B46C1, #9F7AEA);
            padding: 30px;
            text-align: center;
            color: white;
        }
    </style>
</head>
<body>
    <div class="page-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card login-card border-0">
                        <div class="login-header position-relative">
                            <!-- Selector de Idioma (RF-01) -->
                            <div class="position-absolute top-0 end-0 p-2">
                                <div class="d-flex gap-1">
                                    <button id="btn-es" onclick="cambiarIdioma('es')" class="btn btn-sm btn-light text-dark px-2 py-1" style="font-size:11px;border-radius:20px;">🇲🇽 ES</button>
                                    <button id="btn-en" onclick="cambiarIdioma('en')" class="btn btn-sm btn-outline-light opacity-50 px-2 py-1" style="font-size:11px;border-radius:20px;">🇺🇸 EN</button>
                                </div>
                            </div>
                            <i class="fas fa-compact-disc fa-3x mb-2 fa-spin" style="animation-duration: 3s;"></i>
                            <h3 class="fw-bold mb-0">Soundverse</h3>
                            <p class="text-white-50 mb-0" data-key="admin_panel">Panel de Administración</p>
                        </div>
                        <div class="card-body p-4">
                            <form id="form-login" method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-secondary" data-key="login_correo">Correo Electrónico</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                        <input type="email" id="loginEmail" name="email" class="form-control"
                                               placeholder="admin@soundverse.com" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-secondary" data-key="login_contrasena">Contraseña</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                        <input type="password" id="loginPassword" name="password" class="form-control"
                                               placeholder="********" required>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg fw-bold">
                                        <span data-key="login_btn">Iniciar Sesión</span> <i class="fas fa-sign-in-alt ms-2"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 cargado una sola vez aquí al final del body -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/funciones.js?v=1.3"></script>
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
        
        document.addEventListener("DOMContentLoaded", function() {
            let idioma = localStorage.getItem('idiomaSite') || 'es';
            cambiarIdioma(idioma);
        });

        configurarLogin();
    </script>

    
</body>
</html>
