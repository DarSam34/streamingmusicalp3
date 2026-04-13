<?php
session_name('SOUNDVERSE_USER');
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soundverse - Acceso</title>
    <link rel="shortcut icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(135deg, #1A202C 0%, #2D3748 60%, #1A202C 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .auth-card {
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            overflow: hidden;
            width: 100%;
            max-width: 460px;
            background: #fff;
        }

        .auth-header {
            background: linear-gradient(135deg, #6B46C1, #9F7AEA);
            padding: 32px 30px 24px;
            text-align: center;
            color: white;
        }

        .auth-header h3 { font-weight: 800; letter-spacing: 2px; margin-bottom: 4px; }
        .auth-header p  { opacity: .75; margin: 0; font-size: .9rem; }

        /* Tabs */
        .auth-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 2px solid #e2e8f0;
        }
        .auth-tab {
            flex: 1;
            padding: 14px;
            text-align: center;
            font-weight: 600;
            color: #718096;
            cursor: pointer;
            border: none;
            background: transparent;
            transition: all .25s;
            font-size: .95rem;
        }
        .auth-tab.active {
            color: #6B46C1;
            border-bottom: 3px solid #6B46C1;
            background: white;
        }

        .auth-body { padding: 28px 30px; }

        .form-control:focus { border-color: #9F7AEA; box-shadow: 0 0 0 .2rem rgba(107,70,193,.2); }

        .btn-primary-sv {
            background: linear-gradient(135deg, #6B46C1, #805AD5);
            border: none;
            border-radius: 50px;
            padding: 12px;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: .5px;
            transition: all .3s;
        }
        .btn-primary-sv:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(107,70,193,.4);
            background: linear-gradient(135deg, #553c9a, #6B46C1);
        }

        /* Indicador de fortaleza de contraseña */
        .strength-bar {
            height: 5px;
            border-radius: 3px;
            background: #e2e8f0;
            margin-top: 6px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: width .4s, background .4s;
        }
        .strength-text { font-size: .78rem; margin-top: 3px; }

        .input-icon { position: relative; }
        .input-icon .form-control { padding-left: 2.5rem; }
        .input-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            z-index: 5;
        }

        .panel { display: none; }
        .panel.active { display: block; }

        .req-item { font-size: .8rem; color: #a0aec0; margin-bottom: 2px; }
        .req-item.ok { color: #38a169; }
        .req-item i { margin-right: 4px; width: 14px; }
    </style>
</head>
<body>
<div class="auth-card">

    <!-- Header -->
    <div class="auth-header position-relative">
        <!-- Selector de Idioma (RF-01) -->
        <div class="position-absolute top-0 end-0 p-2">
            <div class="d-flex gap-1">
                <button id="btn-es" onclick="cambiarIdioma('es')" class="btn btn-sm btn-light text-dark px-2 py-1" style="font-size:11px;border-radius:20px;">🇲🇽 ES</button>
                <button id="btn-en" onclick="cambiarIdioma('en')" class="btn btn-sm btn-outline-light opacity-50 px-2 py-1" style="font-size:11px;border-radius:20px;">🇺🇸 EN</button>
            </div>
        </div>
        <i class="fas fa-headphones fa-3x mb-3 d-block"></i>
        <h3>SOUNDVERSE</h3>
        <p data-key="slogan" style="font-size:0.9rem;">Tu universo musical conectado</p>
    </div>

    <!-- Tabs -->
    <div class="auth-tabs">
        <button class="auth-tab active" id="tab-login"   onclick="switchTab('login')">
            <i class="fas fa-sign-in-alt me-2"></i><span data-key="tab_iniciar_sesion">Iniciar Sesión</span>
        </button>
        <button class="auth-tab"        id="tab-registro" onclick="switchTab('registro')">
            <i class="fas fa-user-plus me-2"></i><span data-key="tab_crear_cuenta">Crear Cuenta</span>
        </button>
    </div>

    <!-- Cuerpo -->
    <div class="auth-body">

        <!-- ===== PANEL LOGIN ===== -->
        <div id="panel-login" class="panel active">
            <form id="form-login" novalidate>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small" data-key="login_correo">Correo Electrónico</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="loginEmail" name="email"
                               class="form-control" placeholder="usuario@ejemplo.com" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary small" data-key="login_contrasena">Contraseña</label>
                    <div class="input-group">
                        <div class="input-icon flex-grow-1 position-relative">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="loginPassword" name="password"
                                   class="form-control" placeholder="••••••••" required style="padding-left: 2.5rem;">
                        </div>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-top-right-radius: 0.375rem; border-bottom-right-radius: 0.375rem;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" id="btn-login" class="btn btn-primary-sv text-white w-100">
                        <span id="login-spinner" class="spinner-border spinner-border-sm me-2 d-none"></span>
                        <span data-key="login_btn">Iniciar Sesión</span> <i class="fas fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- ===== PANEL REGISTRO ===== -->
        <div id="panel-registro" class="panel">
            <form id="form-registro" novalidate>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small" data-key="registro_nombre">Nombre Completo</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="reg-nombre" name="nombre"
                               class="form-control" placeholder="Tu nombre completo" required minlength="3">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small" data-key="login_correo">Correo Electrónico</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="reg-email" name="email"
                               class="form-control" placeholder="tu@correo.com" required>
                    </div>
                    <div id="email-feedback" class="form-text d-none"></div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold text-secondary small" data-key="login_contrasena">Contraseña</label>
                    <div class="input-group">
                        <div class="input-icon flex-grow-1 position-relative">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="reg-pass" name="password"
                                   class="form-control" placeholder="Mínimo 8 caracteres" required style="padding-left: 2.5rem;">
                        </div>
                        <button class="btn btn-outline-secondary" type="button" id="toggleRegPassword" style="border-top-right-radius: 0.375rem; border-bottom-right-radius: 0.375rem;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <!-- Barra de fortaleza -->
                    <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                    <div class="strength-text" id="strength-text">Escribe tu contraseña</div>
                    <!-- Requisitos -->
                    <div class="mt-2">
                        <div class="req-item" id="req-len"><i class="fas fa-circle-xmark"></i> Mínimo 8 caracteres</div>
                        <div class="req-item" id="req-upper"><i class="fas fa-circle-xmark"></i> Al menos una mayúscula</div>
                        <div class="req-item" id="req-num"><i class="fas fa-circle-xmark"></i> Al menos un número</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small" data-key="registro_contrasena2">Confirmar Contraseña</label>
                    <div class="input-group">
                        <div class="input-icon flex-grow-1 position-relative">
                            <i class="fas fa-shield-alt"></i>
                            <input type="password" id="reg-pass2" name="password2"
                                   class="form-control" placeholder="Repite tu contraseña" required style="padding-left: 2.5rem;">
                        </div>
                        <button class="btn btn-outline-secondary" type="button" id="toggleRegPassword2" style="border-top-right-radius: 0.375rem; border-bottom-right-radius: 0.375rem;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="pass-match" class="form-text d-none"></div>
                </div>

                <!-- Campo País (RF-Artista: actividad por país) -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small">
                        <i class="fas fa-globe-americas me-1"></i> País
                    </label>
                    <select id="reg-pais" name="codigo_pais" class="form-select">
                        <option value="MX">🇲🇽 México</option>
                        <option value="GT">🇬🇹 Guatemala</option>
                        <option value="SV">🇸🇻 El Salvador</option>
                        <option value="HN">🇭🇳 Honduras</option>
                        <option value="NI">🇳🇮 Nicaragua</option>
                        <option value="CR">🇨🇷 Costa Rica</option>
                        <option value="PA">🇵🇦 Panamá</option>
                        <option value="US">🇺🇸 Estados Unidos</option>
                        <option value="ES">🇪🇸 España</option>
                        <option value="AR">🇦🇷 Argentina</option>
                        <option value="CO">🇨🇴 Colombia</option>
                        <option value="OT">🌍 Otro</option>
                    </select>
                </div>

                <div class="d-grid">
                    <button type="submit" id="btn-registro" class="btn btn-primary-sv text-white w-100">
                        <span id="reg-spinner" class="spinner-border spinner-border-sm me-2 d-none"></span>
                        <span data-key="registro_titulo">Crear Cuenta Gratis</span> <i class="fas fa-user-plus ms-1"></i>
                    </button>
                </div>
            </form>
        </div>


    </div><!-- /auth-body -->
</div><!-- /auth-card -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// ====================================================
// Multilenguaje Async (CU-30)
// ====================================================
window.cambiarIdioma = function(codigo) {
    let formData = new FormData();
    formData.append('idioma', codigo);
    
    fetch('php/queries.php?caso=cargar_idioma', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                localStorage.setItem('idiomaSite', codigo);
                
                const bES = document.getElementById('btn-es');
                const bEN = document.getElementById('btn-en');
                
                if (codigo === 'es') {
                    bES.className = "btn btn-sm btn-light text-dark px-2 py-1";
                    bEN.className = "btn btn-sm btn-outline-light opacity-50 px-2 py-1";
                } else {
                    bEN.className = "btn btn-sm btn-light text-dark px-2 py-1";
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

// Cargar preferencia al iniciar
document.addEventListener("DOMContentLoaded", function() {
    let idioma = localStorage.getItem('idiomaSite') || 'es';
    cambiarIdioma(idioma);
});

// ====================================================
// HELPER: cambio de tab
// ====================================================
function switchTab(tab) {
    document.getElementById('panel-login').classList.toggle('active', tab === 'login');
    document.getElementById('panel-registro').classList.toggle('active', tab === 'registro');
    document.getElementById('tab-login').classList.toggle('active', tab === 'login');
    document.getElementById('tab-registro').classList.toggle('active', tab === 'registro');
}

// ====================================================
// LOGIN
// ====================================================
document.getElementById('form-login').addEventListener('submit', function(e) {
    e.preventDefault();
    const email    = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        Swal.fire('Correo inválido', 'Ingresa un correo electrónico con formato válido.', 'warning');
        return;
    }
    if (password.length < 1) {
        Swal.fire('Campo requerido', 'Ingresa tu contraseña.', 'warning');
        return;
    }

    document.getElementById('login-spinner').classList.remove('d-none');
    document.getElementById('btn-login').disabled = true;

    let datos = new FormData();
    datos.append('email', email);
    datos.append('password', password);

    fetch('php/queries.php?caso=iniciarSesion', { method: 'POST', body: datos })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = 'menu_principal.php';
            } else {
                Swal.fire('Acceso Denegado', data.message, 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error'))
        .finally(() => {
            document.getElementById('login-spinner').classList.add('d-none');
            document.getElementById('btn-login').disabled = false;
        });
});

// ====================================================
// VALIDACIÓN DE CONTRASEÑA EN REGISTRO
// ====================================================
document.getElementById('reg-pass').addEventListener('input', function() {
    const v = this.value;
    const fill    = document.getElementById('strength-fill');
    const text    = document.getElementById('strength-text');
    const reqLen   = document.getElementById('req-len');
    const reqUpper = document.getElementById('req-upper');
    const reqNum   = document.getElementById('req-num');

    let score = 0;
    const hasLen   = v.length >= 8;
    const hasUpper = /[A-Z]/.test(v);
    const hasNum   = /[0-9]/.test(v);
    const hasSpec  = /[^A-Za-z0-9]/.test(v);

    if (hasLen)   { score++; reqLen.classList.add('ok');   reqLen.querySelector('i').className   = 'fas fa-circle-check'; }
    else          {          reqLen.classList.remove('ok'); reqLen.querySelector('i').className   = 'fas fa-circle-xmark'; }
    if (hasUpper) { score++; reqUpper.classList.add('ok'); reqUpper.querySelector('i').className = 'fas fa-circle-check'; }
    else          {          reqUpper.classList.remove('ok'); reqUpper.querySelector('i').className = 'fas fa-circle-xmark'; }
    if (hasNum)   { score++; reqNum.classList.add('ok');   reqNum.querySelector('i').className   = 'fas fa-circle-check'; }
    else          {          reqNum.classList.remove('ok'); reqNum.querySelector('i').className   = 'fas fa-circle-xmark'; }
    if (hasSpec)  { score++; }

    const configs = [
        { w: '0%',   bg: '#e2e8f0', t: 'Escribe tu contraseña' },
        { w: '25%',  bg: '#fc8181', t: 'Débil' },
        { w: '50%',  bg: '#f6ad55', t: 'Regular' },
        { w: '75%',  bg: '#68d391', t: 'Buena' },
        { w: '100%', bg: '#48bb78', t: 'Excelente 💪' }
    ];
    const cfg = configs[score] || configs[0];
    if (v.length === 0) { fill.style.width = '0%'; text.textContent = 'Escribe tu contraseña'; return; }
    fill.style.width      = cfg.w;
    fill.style.background = cfg.bg;
    text.textContent      = cfg.t;
    text.style.color      = cfg.bg;
});

// Verificar coincidencia de contraseñas en tiempo real
document.getElementById('reg-pass2').addEventListener('input', function() {
    const pass1 = document.getElementById('reg-pass').value;
    const pass2 = this.value;
    const fb = document.getElementById('pass-match');
    
    fb.classList.remove('d-none');
    if (pass2 === pass1 && pass2 !== '') {
        fb.innerHTML = '<i class="fas fa-check text-success me-1"></i><span class="text-success">Las contraseñas coinciden.</span>';
    } else {
        fb.innerHTML = '<i class="fas fa-times text-danger me-1"></i><span class="text-danger">Las contraseñas no coinciden.</span>';
    }
});

// Verificar email disponible
document.getElementById('reg-email').addEventListener('blur', function() {
    const email = this.value.trim();
    const fb    = document.getElementById('email-feedback');
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        fb.classList.remove('d-none');
        fb.innerHTML = '<i class="fas fa-exclamation-triangle text-warning me-1"></i><span class="text-warning">Formato de correo inválido.</span>';
        return;
    }
    let fd = new FormData();
    fd.append('email', email);
    fetch('php/queries.php?caso=verificar_email', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            fb.classList.remove('d-none');
            if (d.disponible) {
                fb.innerHTML = '<i class="fas fa-check text-success me-1"></i><span class="text-success">Correo disponible.</span>';
            } else {
                fb.innerHTML = '<i class="fas fa-times text-danger me-1"></i><span class="text-danger">Este correo ya está registrado.</span>';
            }
        })
        .catch(() => fb.classList.add('d-none'));
});

// ====================================================
// REGISTRO — Submit
// ====================================================
document.getElementById('form-registro').addEventListener('submit', function(e) {
    e.preventDefault();

    const nombre = document.getElementById('reg-nombre').value.trim();
    const email  = document.getElementById('reg-email').value.trim();
    const pass   = document.getElementById('reg-pass').value;
    const pass2  = document.getElementById('reg-pass2').value;

    if (nombre.length < 3) {
        Swal.fire('Nombre inválido', 'El nombre debe tener al menos 3 caracteres.', 'warning'); return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        Swal.fire('Correo inválido', 'Ingresa un correo electrónico válido.', 'warning'); return;
    }
    if (pass.length < 8) {
        Swal.fire('Contraseña muy corta', 'Debe tener al menos 8 caracteres.', 'warning'); return;
    }
    if (!/[A-Z]/.test(pass)) {
        Swal.fire('Contraseña insegura', 'Debe contener al menos una letra mayúscula.', 'warning'); return;
    }
    if (!/[0-9]/.test(pass)) {
        Swal.fire('Contraseña insegura', 'Debe contener al menos un número.', 'warning'); return;
    }
    if (pass !== pass2) {
        Swal.fire('Contraseñas distintas', 'Las contraseñas no coinciden.', 'warning'); return;
    }

    document.getElementById('reg-spinner').classList.remove('d-none');
    document.getElementById('btn-registro').disabled = true;

    let datos = new FormData();
    datos.append('nombre', nombre);
    datos.append('email', email);
    datos.append('password', pass);
    datos.append('codigo_pais', document.getElementById('reg-pais').value || 'MX');

    fetch('php/queries.php?caso=registrarUsuario', { method: 'POST', body: datos })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Cuenta creada!',
                    text: 'Bienvenido a Soundverse. Ahora puedes iniciar sesión.',
                    confirmButtonColor: '#6B46C1'
                }).then(() => switchTab('login'));
                document.getElementById('form-registro').reset();
                document.getElementById('strength-fill').style.width = '0%';
                document.getElementById('strength-text').textContent = 'Escribe tu contraseña';
                ['req-len','req-upper','req-num'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.classList.remove('ok');
                        const icon = el.querySelector('i');
                        if (icon) icon.className = 'fas fa-circle-xmark';
                    }
                });
            } else {
                Swal.fire('Error', data.message || 'No se pudo completar el registro.', 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error'))
        .finally(() => {
            document.getElementById('reg-spinner').classList.add('d-none');
            document.getElementById('btn-registro').disabled = false;
        });
});

// ====================================================
// Mostrar/Ocultar contraseña
// ====================================================
// Login
document.getElementById('togglePassword')?.addEventListener('click', function() {
    const input = document.getElementById('loginPassword');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Registro - Contraseña
document.getElementById('toggleRegPassword')?.addEventListener('click', function() {
    const input = document.getElementById('reg-pass');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Registro - Confirmar
document.getElementById('toggleRegPassword2')?.addEventListener('click', function() {
    const input = document.getElementById('reg-pass2');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>

</body>
</html>