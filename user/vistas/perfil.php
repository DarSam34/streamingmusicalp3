<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-dark fw-bold mb-0"><i class="fas fa-user-circle me-2 text-primary"></i><span data-key="nav_perfil">Mi Perfil</span></h2>
            <p class="text-secondary" data-key="prf_subtitulo">Gestiona tu información personal y seguridad de tu cuenta.</p>
        </div>
    </div>

    <div class="row">
        <!-- Actualizar Datos -->
        <div class="col-md-6 mb-4">
            <div class="card bg-dark text-light border-secondary h-100 shadow-sm" style="border-radius: 12px; background: linear-gradient(145deg, #1a1a1a, #121212) !important;">
                <div class="card-header border-secondary bg-transparent pb-0 pt-3">
                    <h5 class="mb-0 text-light"><i class="fas fa-user-edit me-2 text-primary"></i><span data-key="prf_datos">Datos Personales</span></h5>
                </div>
                <div class="card-body">
                    <form id="form-perfil">
                        <div class="mb-3">
                            <label class="form-label text-light small text-uppercase fw-bold" style="opacity:0.8;" data-key="registro_nombre">Nombre Completo</label>
                            <input type="text" class="form-control bg-dark border-secondary px-3 py-2" id="perfil-nombre" required minlength="3" style="border-radius: 8px; color: #ffffff !important;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-light small text-uppercase fw-bold" style="opacity:0.8;" data-key="login_correo">Correo Electrónico</label>
                            <input type="email" class="form-control bg-dark border-secondary px-3 py-2" id="perfil-email" required style="border-radius: 8px; color: #ffffff !important;">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-light small text-uppercase fw-bold" style="opacity:0.8;" data-key="prf_nivel">Nivel de Suscripción</label>
                            <input type="text" class="form-control bg-dark border-secondary px-3 py-2" id="perfil-tipo" readonly disabled style="border-radius: 8px; color: #fff; font-weight: bold;">
                            <small class="text-light mt-2 d-block" style="opacity:0.6;"><i class="fas fa-info-circle me-1"></i><span data-key="prf_mejorar">Para mejorar tu cuenta, ve a la sección Premium.</span></small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow"><i class="fas fa-save me-2"></i><span data-key="btn_guardar">Guardar Cambios</span></button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Actualizar Contraseña -->
        <div class="col-md-6 mb-4">
            <div class="card bg-dark text-light border-secondary h-100 shadow-sm" style="border-radius: 12px; background: linear-gradient(145deg, #121212, #1a1a1a) !important;">
                <div class="card-header border-secondary bg-transparent pb-0 pt-3">
                    <h5 class="mb-0 text-light"><i class="fas fa-lock me-2 text-warning"></i><span data-key="prf_seguridad">Seguridad</span></h5>
                </div>
                <div class="card-body">
                    <form id="form-password">
                        <div class="mb-3">
                            <label class="form-label text-light small text-uppercase fw-bold" style="opacity:0.8;" data-key="prf_pass_actual">Contraseña Actual</label>
                            <input type="password" class="form-control bg-dark border-secondary px-3 py-2" id="pass-antigua" required style="border-radius: 8px; color: #ffffff !important;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-light small text-uppercase fw-bold" style="opacity:0.8;" data-key="prf_pass_nueva">Nueva Contraseña</label>
                            <input type="password" class="form-control bg-dark border-secondary px-3 py-2" id="pass-nueva" required minlength="8" style="border-radius: 8px; color: #ffffff !important;">
                            <small class="text-light" style="opacity:0.6;"><i class="fas fa-shield-alt me-1 text-success"></i><span data-key="prf_pass_hint">Debe contener 8 caracteres, números y mayúsculas.</span></small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-light small text-uppercase fw-bold" style="opacity:0.8;" data-key="registro_contrasena2">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control bg-dark border-secondary px-3 py-2" id="pass-confirm" required minlength="8" style="border-radius: 8px; color: #ffffff !important;">
                        </div>
                        <button type="submit" class="btn border-warning text-warning w-100 rounded-pill fw-bold py-2 btn-outline-warning shadow-sm"><i class="fas fa-key me-2"></i><span data-key="prf_pass_btn">Actualizar Contraseña</span></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Artistas Seguidos -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-dark text-light border-secondary shadow-sm" style="border-radius: 12px; background: linear-gradient(145deg, #121212, #1a1a1a) !important;">
                <div class="card-header border-secondary bg-transparent pb-0 pt-3">
                    <h5 class="mb-0 text-light"><i class="fas fa-heart me-2 text-danger"></i>Artistas a los que sigo</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="lista-artistas-seguidos">
                        <div class="col-12 text-center py-4 text-light border border-secondary rounded" style="border-style: dashed !important; color: white !important;">
                            <div class="spinner-border text-light spinner-border-sm me-2"></div> Cargando...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Cargar datos
    $.post('php/queries.php', { caso: 'obtener_perfil' }, function(res) {
        if(res.status === 'success') {
            $('#perfil-nombre').val(res.data.nombre_completo);
            $('#perfil-email').val(res.data.correo);
            
            const tipoText = res.data.FK_id_tipo == 2 ? '👑 Premium VIP' : '🎧 Free Estándar';
            const colorClass = res.data.FK_id_tipo == 2 ? 'text-warning font-weight-bold' : 'text-secondary';
            $('#perfil-tipo').val(tipoText).addClass(colorClass); // Note: addClass doesn't override text color immediately if inline styling is present, but it's okay.
            
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');

    // Submit Perfil
    $('#form-perfil').off('submit').submit(function(e) {
        e.preventDefault();
        const btn = $(this).find('button');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');
        
        $.post('php/queries.php', {
            caso: 'actualizar_perfil',
            nombre: $('#perfil-nombre').val(),
            email: $('#perfil-email').val()
        }, function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Cambios');
            if(res.status === 'success') {
                Swal.fire('¡Éxito!', res.message, 'success');
                // Actualizar info visual
                $('#nav-user-name').text($('#perfil-nombre').val());
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });

    // Submit Password
    $('#form-password').off('submit').submit(function(e) {
        e.preventDefault();
        if ($('#pass-nueva').val() !== $('#pass-confirm').val()) {
            Swal.fire('Aviso', 'Las nuevas contraseñas no coinciden.', 'warning');
            return;
        }

        const btn = $(this).find('button');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...');

        $.post('php/queries.php', {
            caso: 'actualizar_password',
            antigua: $('#pass-antigua').val(),
            nueva: $('#pass-nueva').val()
        }, function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-key me-2"></i>Actualizar Contraseña');
            if(res.status === 'success') {
                Swal.fire('¡Éxito!', res.message, 'success');
                $('#form-password')[0].reset();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });

window.cargarArtistasSeguidos = function() {
    let contenedor = $('#lista-artistas-seguidos');

    // Timeout de seguridad: si en 8s no responde, mostrar mensaje
    let timeoutId = setTimeout(function() {
        if (contenedor.find('.spinner-border').length > 0) {
            contenedor.html('<div class="col-12 text-center py-4 text-muted"><i class="fas fa-wifi-slash fa-2x mb-2 d-block opacity-50"></i>No se pudo cargar. <a href="#" onclick="window.cargarArtistasSeguidos()" class="text-primary">Reintentar</a></div>');
        }
    }, 8000);

    fetch('php/queries.php?caso=artistas_seguidos')
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch(e) {
            console.error("JSON parse failed:", text);
            throw new Error('Respuesta inválida del servidor');
        }
    })
    .then(data => {
        clearTimeout(timeoutId);
        contenedor.empty();
        if (data.status === 'success') {
            if (!data.data || data.data.length === 0) {
                contenedor.html('<div class="col-12 text-center py-4 text-muted border border-secondary rounded" style="border-style: dashed !important;"><i class="fas fa-user-slash fs-2 mb-2 d-block"></i>No sigues a ningún artista aún.<br><a href="#" onclick="cargarVista(\'vistas/catalogo.php\')" class="text-primary text-decoration-none mt-2 d-inline-block"><i class="fas fa-music me-1"></i>Explorar catálogo</a></div>');
            } else {
                let html = '';
                data.data.forEach(ar => {
                    let foto = ar.foto_perfil && ar.foto_perfil.trim() !== '' ? '../' + ar.foto_perfil : '../assets/img/logo_soundverse_white.png';
                    html += `
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="d-flex align-items-center bg-black border border-secondary p-2 rounded-pill shadow-sm" style="transition: transform 0.2s;">
                            <img src="${foto}" alt="${ar.nombre_artistico}" class="rounded-circle object-fit-cover me-3 border border-secondary" style="width: 50px; height: 50px;"
                                 onerror="this.src='../assets/img/logo_soundverse_white.png'">
                            <div class="flex-grow-1 overflow-hidden">
                                <h6 class="mb-0 text-truncate text-light fw-bold">${ar.nombre_artistico}</h6>
                            </div>
                            <button class="btn btn-sm btn-outline-danger rounded-circle me-2 px-2" title="Dejar de seguir"
                                data-id="${ar.PK_id_artista}"
                                onclick="quitarSeguimiento(this.dataset.id)">
                                <i class="fas fa-user-minus"></i>
                            </button>
                        </div>
                    </div>`;
                });
                contenedor.html(html);
            }
        } else {
            contenedor.html('<div class="col-12 text-center text-danger py-3"><i class="fas fa-exclamation-circle me-2"></i>' + (data.message || 'Error al cargar.') + '</div>');
        }
    })
    .catch(err => {
        clearTimeout(timeoutId);
        contenedor.html('<div class="col-12 text-center text-warning py-3"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + err.message + '<br><a href="#" onclick="window.cargarArtistasSeguidos()" class="text-primary">Reintentar</a></div>');
    });
};

window.quitarSeguimiento = function(idArtista) {
    if(!idArtista) return;
    $.post('php/queries.php', { caso: 'alternar_seguimiento', id_artista: idArtista }, function(res) {
        if (res.status === 'success') {
            window.cargarArtistasSeguidos();
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');
};

// Autoinicialización
window.cargarArtistasSeguidos();
</script>
