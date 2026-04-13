/**
 * ARCHIVO: funciones.js
 * AUTOR: Mario Roger Mejía Elvir - Equipo Proyecto 6
 * PROPÓSITO:
 * Motor principal para la navegación SPA (Single Page Application) usando
 * Vanilla JavaScript (Fetch API).
 * Permite recargar solo el contenedor principal sin actualizar toda la página.
 */

function cargarVista(urlVista) {
    const contenedor = document.getElementById('contenedor-vistas');

    // Manejar el sombreado activo en el menú lateral
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('onclick')?.includes(urlVista)) {
            link.classList.add('active');
        }
    });

    // Acciones especiales por vista (FUERA del forEach)
    if (urlVista.includes('albumes.php')) {
        // cargarAlbumes() se llama desde el then() tras inyectar el HTML
    } else if (urlVista.includes('dashboard.php')) {
        // cargarDatosDashboardVista() se llama desde el then() tras inyectar el HTML
    }

    // Spinner de carga mientras responde el servidor
    contenedor.innerHTML = `
        <div class="text-center mt-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 text-muted">Cargando módulo...</p>
        </div>`;

    fetch(urlVista)
        .then(response => {
            if (!response.ok) throw new Error('No se encontró el archivo: ' + urlVista);
            return response.text();
        })
        .then(html => {
            // Inyectar HTML en el DOM
            contenedor.innerHTML = html;

            // REFUERZO MULTILENGUAJE: Forzar traducción de los nuevos elementos inyectados
            let idiomaActual = localStorage.getItem('idiomaSite') || 'es';
            if (typeof cambiarIdioma === 'function') {
                cambiarIdioma(idiomaActual);
            }

            // Inicializar scripts de la vista correspondiente (admin)
            if (urlVista.includes('usuarios.php')) {
                configurarFormularioUsuarios(); // llama cargarListaUsuarios() internamente
            } else if (urlVista.includes('dashboard.php')) {
                cargarDatosDashboardVista();
            } else if (urlVista.includes('catalogo.php')) {
                cargarSelectsCancion();
                cargarTablaCanciones();
            } else if (urlVista.includes('artistas.php')) {
                cargarArtistas();
            } else if (urlVista.includes('albumes.php')) {
                cargarAlbumes();
            } else if (urlVista.includes('generos.php')) {
                cargarGeneros(); // Inicializa SPA de Géneros
            }
        })
        .catch(error => {
            console.error('Error en petición AJAX:', error);
            contenedor.innerHTML = `
                <div class="alert alert-danger mt-3 border-0 border-start border-5 border-danger shadow-sm">
                    <h4><i class="fas fa-exclamation-triangle"></i> Módulo no disponible</h4>
                    <p>El archivo <strong>${urlVista}</strong> aún no ha sido creado o tiene errores.</p>
                    <hr>
                    <p class="mb-0 text-muted"><code style="white-space: pre-wrap;">Detalle técnico: ${error.message}\n${error.stack}</code></p>
                </div>`;
            Swal.fire({
                icon: 'error',
                title: 'Error de navegación',
                text: 'No se pudo cargar el módulo solicitado.',
                confirmButtonColor: '#6B46C1'
            });
        });
}

/**
 * cargarListaUsuarios()
 * Reemplaza el PHP inline de usuarios.php.
 * Llama a queries.php?caso=listarUsuarios y pinta la tabla #tabla-usuarios via AJAX.
 */
function cargarListaUsuarios() {
    const tbody = document.getElementById('tabla-usuarios');
    if (!tbody) return;

    let datos = new FormData();
    datos.append('caso', 'listar_usuarios');

    fetch('php/queries.php', { method: 'POST', body: datos })
        .then(res => res.json())
        .then(lista => {
            const badge = document.getElementById('badge-total-usuarios');
            if (badge) badge.textContent = 'Total: ' + lista.length;

            if (lista.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay usuarios registrados.</td></tr>';
                return;
            }

            let html = '';
            lista.forEach(user => {
                const badgePlan = user.nombre_plan === 'Premium'
                    ? '<span class="badge bg-warning text-dark"><i class="fas fa-star"></i> Premium</span>'
                    : '<span class="badge bg-info text-dark">' + user.nombre_plan + '</span>';
                const badgeAdmin = user.es_admin == 1
                    ? '<span class="badge bg-danger ms-1"><i class="fas fa-shield-alt"></i> Admin</span>'
                    : '';

                html += `<tr>
                    <td>${user.PK_id_usuario}</td>
                    <td><strong>${user.nombre_completo}</strong></td>
                    <td>${user.correo}</td>
                    <td>${badgePlan}${badgeAdmin}</td>
                    <td class="text-center">
                        <button class="btn btn-outline-warning btn-sm me-1" title="Editar"
                            onclick="editarUsuario(${user.PK_id_usuario},'${user.nombre_completo.replace(/'/g,"\\'")}',' ${user.correo}',${user.nombre_plan==='Premium'?2:1},${user.es_admin})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" title="Eliminar"
                            onclick="eliminarUsuario(${user.PK_id_usuario},'${user.nombre_completo.replace(/'/g,"\\'")}')"> 
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
        })
        .catch(err => {
            console.error('Error cargando usuarios:', err);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Error al cargar la lista.</td></tr>';
        });
}

/**
 * Módulo de Usuarios del panel ADMIN (Nuevo o Actualizar)
 */
function configurarFormularioUsuarios() {
    const form = document.getElementById('formNuevoUsuario');
    if (!form) return;

    // Cargar la tabla va AJAX al inicializar el módulo
    cargarListaUsuarios();

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const id = document.getElementById('id_usuario').value;
        const esEditar = (id && parseInt(id) > 0);
        const password = document.getElementById('password').value;

        // Validación frontend
        if (!esEditar && password === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'Debes ingresar una contraseña para registrar un usuario.',
                confirmButtonColor: '#6B46C1'
            });
            return;
        }

        if (password !== '' && password.length < 8) {
            Swal.fire({
                icon: 'warning',
                title: 'Contraseña insegura',
                text: 'La contraseña debe tener un mínimo de 8 caracteres.',
                confirmButtonColor: '#6B46C1'
            });
            return;
        }

        const datos = new FormData();
        datos.append('id_usuario', id);
        datos.append('nombre',     document.getElementById('nombre').value);
        datos.append('email',      document.getElementById('email').value);
        datos.append('rol',        document.getElementById('rol').value);
        datos.append('password',   password);

        const checkAdmin = document.getElementById('es_admin');
        if (checkAdmin) {
            datos.append('es_admin', checkAdmin.checked ? '1' : '0');
        }

        const casoRequerido = esEditar ? 'actualizarUsuario' : 'registrarUsuario';

        fetch(`php/queries.php?caso=${casoRequerido}`, { method: 'POST', body: datos })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: esEditar ? '¡Actualizado!' : '¡Registro Exitoso!',
                        text: data.message,
                        confirmButtonColor: '#6B46C1'
                    });
                    cancelarEdicionUsuario();
                    cargarVista('vistas/usuarios.php');
                } else {
                    Swal.fire('Error al procesar', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                Swal.fire('Error Crítico', 'No se pudo procesar la solicitud con el servidor.', 'error');
            });
    });
}

window.editarUsuario = function(id, nombre, email, rol, es_admin = 0) {
    document.getElementById('id_usuario').value = id;
    document.getElementById('nombre').value     = nombre;
    document.getElementById('email').value      = email;
    document.getElementById('rol').value        = rol;
    document.getElementById('password').value   = '';

    const checkAdmin = document.getElementById('es_admin');
    if (checkAdmin) {
        checkAdmin.checked = (es_admin == 1);
    }

    const btn = document.getElementById('btn-submit-usuario');
    btn.classList.replace('btn-primary', 'btn-warning');
    btn.innerHTML = '<i class="fas fa-save me-2"></i>Actualizar Usuario';

    document.getElementById('btn-cancelar-usuario').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

window.cancelarEdicionUsuario = function() {
    const form = document.getElementById('formNuevoUsuario');
    if (form) form.reset();
    document.getElementById('id_usuario').value = '0';

    const checkAdmin = document.getElementById('es_admin');
    if (checkAdmin) {
        checkAdmin.checked = false;
    }

    const btn = document.getElementById('btn-submit-usuario');
    if (btn) {
        btn.classList.replace('btn-warning', 'btn-primary');
        btn.innerHTML = '<i class="fas fa-save me-2"></i>Registrar Usuario';
        document.getElementById('btn-cancelar-usuario').style.display = 'none';
    }
};

window.eliminarUsuario = function(id, nombre) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Estás a punto de eliminar lógicamente a ${nombre}. No aparecerá más, pero sus registros seguirán existiendo en base de datos.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash"></i> Sí, desactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            let datos = new FormData();
            datos.append('id_usuario', id);

            fetch('php/queries.php?caso=eliminarUsuario', { method: 'POST', body: datos })
                .then(respuesta => respuesta.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('¡Desactivado!', data.message, 'success');
                        cargarVista('vistas/usuarios.php');
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error("Fetch Error: ", error);
                    Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
                });
        }
    });
};

// =========================================================================
// MÓDULO CANCIONES (admin)
// =========================================================================
let modalCancionInstancia;

function abrirModalCancion() {
    document.getElementById('formularioCancion').reset();
    document.getElementById('id_cancion').value = '0';
    document.getElementById('tituloModalCancion').innerText = 'Registrar Nueva Canción';
    
    // Hace obligatorio el archivo de audio al ser nuevo registro
    document.getElementById('archivo_audio').setAttribute('required', 'required');
    
    modalCancionInstancia = new bootstrap.Modal(document.getElementById('modalCancion'));
    modalCancionInstancia.show();
}

function cargarSelectsCancion(idAlbumSel = null, idGeneroSel = null) {
    let formData = new FormData();
    formData.append('caso', 'datos_selects_cancion');

    fetch('php/queries.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            let selectAlbum = document.getElementById('lista-albumes');
            let selectGenero = document.getElementById('genero');
            
            if (selectAlbum) {
                let htmlAlbum = '';
                data.albumes.forEach(a => {
                    htmlAlbum += `<option value="${a.titulo}"></option>`;
                });
                selectAlbum.innerHTML = htmlAlbum;
            }

            if (selectGenero) {
                let htmlGenero = '<option value="">Seleccione Género...</option>';
                data.generos.forEach(g => {
                    let sel = (g.PK_id_genero == idGeneroSel) ? 'selected' : '';
                    htmlGenero += `<option value="${g.PK_id_genero}" ${sel}>${g.nombre_genero}</option>`;
                });
                selectGenero.innerHTML = htmlGenero;
            }
        });
}

function cargarTablaCanciones() {
    let formData = new FormData();
    formData.append('caso', 'listar_canciones');

    fetch('php/queries.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(canciones => {
            const tbody = document.getElementById('tbody-canciones');
            let html = '';
            canciones.forEach(c => {
                let min = Math.floor(c.duracion_segundos / 60);
                let seg = c.duracion_segundos % 60;
                let duracionFormato = `${min}:${seg < 10 ? '0' : ''}${seg}`;
                let rutaAudio = c.ruta_archivo_audio ? '../' + c.ruta_archivo_audio : '';

                html += `<tr>
                    <td><strong>${c.titulo}</strong></td>
                    <td>${c.artista}</td>
                    <td><span class="badge bg-secondary">${c.album}</span></td>
                    <td>${c.genero}</td>
                    <td>${duracionFormato}</td>
                    <td class="text-center">
                        <audio controls style="height: 30px; width: 120px;">
                            <source src="${rutaAudio}" type="audio/mpeg">
                        </audio>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-warning" onclick="editarCancion(${c.PK_id_cancion})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarCancion(${c.PK_id_cancion})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
        });
}

function guardarCancionForm(evento) {
    evento.preventDefault();

    // VALIDACIÓN CLIENTE: verificar tamaño del archivo antes de enviarlo al servidor
    const inputAudio = document.getElementById('archivo_audio');
    if (inputAudio && inputAudio.files && inputAudio.files.length > 0) {
        const archivo = inputAudio.files[0];
        const tamanoMaximoBytes = 15 * 1024 * 1024; // 15 MB
        if (archivo.size > tamanoMaximoBytes) {
            const tamanoMB = (archivo.size / (1024 * 1024)).toFixed(2);
            Swal.fire({
                icon: 'error',
                title: 'Archivo demasiado grande',
                html: `El archivo <strong>${archivo.name}</strong> pesa <strong>${tamanoMB} MB</strong>.<br>El límite permitido es <strong>15 MB</strong>.`,
                confirmButtonColor: '#6B46C1'
            });
            return; // No enviar el formulario
        }
    }

    let formulario = document.getElementById('formularioCancion');
    let formData = new FormData(formulario);

    fetch('php/queries.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire('¡Éxito!', data.message, 'success');
                modalCancionInstancia.hide();
                cargarTablaCanciones();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            console.error('Error al guardar canción:', err);
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        });
}

window.editarCancion = function(id) {
    let datos = new FormData();
    datos.append('caso', 'obtener_cancion');
    datos.append('id', id);

    fetch('php/queries.php', { method: 'POST', body: datos })
        .then(res => res.json())
        .then(data => {
            document.getElementById('id_cancion').value = data.PK_id_cancion;
            document.getElementById('titulo').value = data.titulo;
            document.getElementById('duracion_segundos').value = data.duracion_segundos;
            document.getElementById('letra_sincronizada').value = data.letra_sincronizada;

            // NUEVO: Pre-llenar numero_pista
            const campoNP = document.getElementById('numero_pista');
            if (campoNP) campoNP.value = data.numero_pista || 1;
            
            // Asignar el texto del album al input
            document.getElementById('album').value = data.album_nombre;

            // Quita la obligatoriedad del audio al editar
            document.getElementById('archivo_audio').removeAttribute('required');

            cargarSelectsCancion(null, data.FK_id_genero);

            document.getElementById('tituloModalCancion').innerText = 'Editar Canción';
            modalCancionInstancia = new bootstrap.Modal(document.getElementById('modalCancion'));
            modalCancionInstancia.show();
        });
};

window.eliminarCancion = function(id) {
    Swal.fire({
        title: '¿Eliminar canción?',
        text: "Se aplicará un borrado lógico.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            let datos = new FormData();
            datos.append('caso', 'eliminar_cancion');
            datos.append('id', id);

            fetch('php/queries.php', { method: 'POST', body: datos })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Eliminado', data.message, 'success');
                        cargarTablaCanciones();
                    }
                });
        }
    });
};

// =========================================================================
// MÓDULO LOGIN (Admin)
// CORRECCIÓN: función definida una sola vez (se eliminó la copia duplicada)
// =========================================================================
// =========================================================================
// MÓDULO LOGIN (Admin)
// =========================================================================
function configurarLogin() {
    const form = document.getElementById('form-login');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const email    = document.getElementById('loginEmail').value.trim();
        const password = document.getElementById('loginPassword').value;

        // Validación cliente explícita (Regla 5: doble validación)
        if (!email || !password) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos requeridos',
                text: 'Por favor ingresa tu correo y contraseña.',
                confirmButtonColor: '#6B46C1'
            });
            return;
        }

        let datos = new FormData();
        datos.append('email',    email);
        datos.append('password', password);

        fetch('php/queries.php?caso=iniciarSesion', { method: 'POST', body: datos })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // BIFURCACIÓN: admin va al panel, no-admin va al frontend
                    if (data.es_admin == 1) {
                        window.location.href = 'menu_principal.php';
                    } else {
                        window.location.href = '../user/index.php';
                    }
                } else {
                    Swal.fire('Acceso Denegado', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error Login:', error);
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            });
    });
}

// =========================================================
// FUNCIONES PARA ARTISTAS
// =========================================================
let modalArtistaInstancia;

function abrirModalArtista() {
    document.getElementById('formularioArtista').reset();
    document.getElementById('id_artista').value = '0';
    document.getElementById('tituloModalArtista').innerText = 'Nuevo Artista';
    modalArtistaInstancia = new bootstrap.Modal(document.getElementById('modalArtista'));
    modalArtistaInstancia.show();
}

function cargarArtistas() {
    let formData = new FormData();
    formData.append('caso', 'listar_artistas');

    fetch('php/queries.php', {
        method: 'POST',
        body: formData
    })
    .then(respuesta => respuesta.json())
    .then(datos => {
        let filas = '';
        datos.forEach(artista => {
            let iconoVerificado = artista.verificado == 1 ? '<i class="fas fa-check-circle text-primary"></i>' : '';
            let rutaFoto = artista.ruta_foto_perfil ? '../' + artista.ruta_foto_perfil : '../assets/img/default-avatar.png';
            
            filas += `
                <tr>
                    <td><img src="${rutaFoto}" alt="Foto" width="50" height="50" class="rounded-circle" style="object-fit: cover;"></td>
                    <td>${artista.nombre_artistico} ${iconoVerificado}</td>
                    <td>${artista.biografia ? artista.biografia.substring(0, 50) + '...' : 'Sin biografía'}</td>
                    <td><span class="badge bg-success">Activo</span></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-warning" onclick="editarArtista(${artista.PK_id_artista})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarArtista(${artista.PK_id_artista})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
        });
        document.getElementById('cuerpoTablaArtistas').innerHTML = filas;
    })
    .catch(error => console.error('Error:', error));
}

function guardarArtista(evento) {
    evento.preventDefault();
    let formulario = document.getElementById('formularioArtista');
    let formData = new FormData(formulario);

    fetch('php/queries.php', {
        method: 'POST',
        body: formData
    })
    .then(respuesta => respuesta.json())
    .then(datos => {
        if (datos.status === 'success') {
            Swal.fire('¡Éxito!', datos.message, 'success');
            modalArtistaInstancia.hide();
            cargarArtistas();
        } else {
            Swal.fire('Error', datos.message, 'error');
        }
    });
}

function editarArtista(id) {
    let formData = new FormData();
    formData.append('caso', 'obtener_artista');
    formData.append('id', id);

    fetch('php/queries.php', {
        method: 'POST',
        body: formData
    })
    .then(respuesta => respuesta.json())
    .then(datos => {
        document.getElementById('id_artista').value = datos.PK_id_artista;
        document.getElementById('nombre_artistico').value = datos.nombre_artistico;
        document.getElementById('biografia').value = datos.biografia;
        document.getElementById('verificado').checked = (datos.verificado == 1);
        
        document.getElementById('tituloModalArtista').innerText = 'Editar Artista';
        modalArtistaInstancia = new bootstrap.Modal(document.getElementById('modalArtista'));
        modalArtistaInstancia.show();
    });
}

function eliminarArtista(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: "Se dará de baja a este artista del sistema.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((resultado) => {
        if (resultado.isConfirmed) {
            let formData = new FormData();
            formData.append('caso', 'eliminar_artista');
            formData.append('id', id);

            fetch('php/queries.php', {
                method: 'POST',
                body: formData
            })
            .then(respuesta => respuesta.json())
            .then(datos => {
                if(datos.status === 'success'){
                    Swal.fire('Eliminado', datos.message, 'success');
                    cargarArtistas();
                }
            });
        }
    });
}

// =========================================================
// FUNCIONES PARA ÁLBUMES
// =========================================================
let modalAlbumInstancia;

function abrirModalAlbum() {
    document.getElementById('formularioAlbum').reset();
    document.getElementById('id_album').value = '0';
    document.getElementById('tituloModalAlbum').innerText = 'Nuevo Álbum';
    cargarSelectArtistas();
    modalAlbumInstancia = new bootstrap.Modal(document.getElementById('modalAlbum'));
    modalAlbumInstancia.show();
}

function cargarSelectArtistas(idSeleccionado = null) {
    let formData = new FormData();
    formData.append('caso', 'listar_artistas');
    
    fetch('php/queries.php', { method: 'POST', body: formData })
    .then(respuesta => respuesta.json())
    .then(datos => {
        let opciones = '<option value="">Seleccione un artista...</option>';
        datos.forEach(artista => {
            let seleccionado = (idSeleccionado == artista.PK_id_artista) ? 'selected' : '';
            opciones += `<option value="${artista.PK_id_artista}" ${seleccionado}>${artista.nombre_artistico}</option>`;
        });
        document.getElementById('fk_artista').innerHTML = opciones;
    });
}

function cargarAlbumes() {
    fetch('http://localhost/soundverse-streaming/admin/php/queries.php?caso=listar_albumes')
        .then(response => response.json())
        .then(data => {
            let tbody = document.getElementById('cuerpoTablaAlbumes');
            if (!tbody) return;
            tbody.innerHTML = '';
            
            if (data.status === 'success' && data.data) {
                for (let i = 0; i < data.data.length; i++) {
                    let a = data.data[i];
                    let portada = a.ruta_portada ? '../' + a.ruta_portada : '../assets/img/logo_soundverse_white.png';
                    tbody.innerHTML += `
                        <tr>
                            <td><img src="${portada}" style="width:50px;height:50px;object-fit:cover;border-radius:8px;"></td>
                            <td>${a.titulo}</td>
                            <td>${a.nombre_artistico}</td>
                            <td>${a.discografica || '-'}</td>
                            <td>${a.fecha_lanzamiento || 'N/A'}</td>
                            <td>${a.duracion_formateada || '0:00'}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning" onclick="editarAlbum(${a.PK_id_album})"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarAlbum(${a.PK_id_album})"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    `;
                }
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay álbumes registrados</td></tr>';
            }
        })
        .catch(error => console.error('Error:', error));
}

function guardarAlbum(evento) {
    evento.preventDefault();
    let formulario = document.getElementById('formularioAlbum');
    let formData = new FormData(formulario);

    fetch('php/queries.php', { method: 'POST', body: formData })
    .then(respuesta => respuesta.json())
    .then(datos => {
        if (datos.status === 'success') {
            Swal.fire('¡Éxito!', datos.message, 'success');
            modalAlbumInstancia.hide();
            cargarAlbumes();
        } else {
            Swal.fire('Error', datos.message, 'error');
        }
    });
}

function editarAlbum(id) {
    let formData = new FormData();
    formData.append('caso', 'obtener_album');
    formData.append('id', id);

    fetch('php/queries.php', { method: 'POST', body: formData })
    .then(respuesta => respuesta.json())
    .then(datos => {
        document.getElementById('id_album').value = datos.PK_id_album;
        document.getElementById('titulo_album').value = datos.titulo;
        document.getElementById('fecha_lanzamiento').value = datos.fecha_lanzamiento;

        // NUEVO: Pre-llenar discografica si existe el campo
        const campoDisc = document.getElementById('discografica_album');
        if (campoDisc) campoDisc.value = datos.discografica || '';

        cargarSelectArtistas(datos.FK_id_artista);
        
        document.getElementById('tituloModalAlbum').innerText = 'Editar Álbum';
        modalAlbumInstancia = new bootstrap.Modal(document.getElementById('modalAlbum'));
        modalAlbumInstancia.show();
    });
}

function eliminarAlbum(id) {
    Swal.fire({
        title: '¿Eliminar álbum?',
        text: "Se aplicará un borrado lógico.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((resultado) => {
        if (resultado.isConfirmed) {
            let formData = new FormData();
            formData.append('caso', 'eliminar_album');
            formData.append('id', id);

            fetch('php/queries.php', { method: 'POST', body: formData })
            .then(respuesta => respuesta.json())
            .then(datos => {
                if(datos.status === 'success'){
                    Swal.fire('Eliminado', datos.message, 'success');
                    cargarAlbumes();
                }
            });
        }
    });
}

// =========================================================================
// LOGIN DEL USUARIO (CLIENTE)
// =========================================================================
function configurarLoginUsuario() {
    const form = document.getElementById('form-login');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const email    = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;

        let datos = new FormData();
        datos.append('email',    email);
        datos.append('password', password);

        fetch('php/queries.php?caso=iniciarSesion', { method: 'POST', body: datos })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = 'menu_principal.php';
                } else {
                    Swal.fire('Acceso Denegado', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error Login:', error);
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            });
    });
}

// =========================================================
// MÓDULO DASHBOARD — KPIs globales de la plataforma
// =========================================================
function cargarDatosDashboardVista() {
    // Spinner en las tarjetas mientras carga
    document.querySelectorAll('[id^="stat-"]').forEach(el => { el.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; });

    let formData = new FormData();
    formData.append('caso', 'cargar_dashboard');

    fetch('php/queries.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(d => {
            // ── KPI CARDS ───────────────────────────────────────────────
            const set = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = val; };

            set('stat-usuarios',  d.totalUsuarios  ?? 0);
            set('stat-canciones', d.totalCanciones ?? 0);
            set('stat-artistas',  d.totalArtistas  ?? 0);
            set('stat-albumes',   d.totalAlbumes   ?? 0);
            set('stat-dau',       d.dau ?? 0);
            set('stat-mau',       d.mau ?? 0);
            set('stat-rep-hoy',   d.reproducciones_hoy ?? 0);
            set('stat-horas',     (d.horas_streaming ?? 0) + ' hrs');
            set('stat-pro',       d.totalPro ?? 0);
            set('stat-free',      d.totalFree ?? 0);
            set('stat-ratio',     (d.ratio_convertidos ?? 0) + '%');         // % Premium sobre total
            set('stat-retencion', (d.tasa_retencion ?? 0) + '%');
            set('stat-ingresos',  'L. ' + parseFloat(d.ingresos_mes ?? 0).toLocaleString('es', {minimumFractionDigits:2}));
            // Tasa de conversión real Free→Premium (nuevo KPI)
            set('stat-conversion-real', (d.tasa_conversion ?? 0) + '%');
            const elConv = document.getElementById('stat-convertidos-mes');
            if (elConv) elConv.textContent = (d.convertidos_mes ?? 0) + ' nuevos Premium';

            // ── TOP ARTISTAS ────────────────────────────────────────────
            const tbArt = document.getElementById('tabla-top-artistas');
            if (tbArt && d.top_artistas && d.top_artistas.length > 0) {
                const medal = ['🥇','🥈','🥉'];
                tbArt.innerHTML = d.top_artistas.map((a, i) => `
                    <tr>
                        <td class="fw-bold text-center">${medal[i] || (i+1)}</td>
                        <td><i class="fas fa-microphone-alt text-info me-2"></i>${a.artista}</td>
                        <td class="text-center"><span class="badge bg-info text-dark">${a.reproducciones}</span></td>
                        <td class="text-center text-muted small">${a.minutos} min</td>
                    </tr>`).join('');
            } else if (tbArt) {
                tbArt.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Sin datos</td></tr>';
            }

            // ── TABLA TRENDING TOP 10 ────────────────────────────────────
            const tbTrend = document.getElementById('tabla-trending');
            if (tbTrend && d.top_canciones) {
                const badgeColor = (i) => ['bg-warning text-dark','bg-secondary','bg-secondary'][i] || 'bg-light text-dark border';
                tbTrend.innerHTML = d.top_canciones.slice(0, 50).map((c, i) => `
                    <tr>
                        <td><span class="badge ${badgeColor(i)}">${i+1}</span></td>
                        <td class="fw-semibold text-truncate" style="max-width: 150px;" title="${c.titulo}">${c.titulo}</td>
                        <td><span class="badge bg-dark">${c.reproducciones_semana ?? c.contador_reproducciones}</span></td>
                    </tr>`).join('');
            }
            
            // ── TABLA PAÍSES ────────────────────────────────────────────
            const tbPaises = document.getElementById('tabla-paises');
            if (tbPaises && d.paises_actividad && d.paises_actividad.length > 0) {
                tbPaises.innerHTML = d.paises_actividad.map((p) => `
                    <tr>
                        <td class="fw-bold"><i class="fas fa-flag text-primary me-2"></i> ${p.pais}</td>
                        <td class="text-center"><span class="badge bg-success">${p.actividad}</span></td>
                    </tr>`).join('');
            } else if (tbPaises) {
                tbPaises.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-muted">Sin datos</td></tr>';
            }

            // ── GOOGLE CHARTS ────────────────────────────────────────────
            if (typeof google === 'undefined') return; // sin conexión al CDN

            google.charts.load('current', { packages: ['corechart'] });
            google.charts.setOnLoadCallback(function() {

                // ─ GRÁFICO 1: Donut Free vs Premium ─────────────────────
                const elDonut = document.getElementById('chart-ratio');
                if (elDonut && d.ratio_chart && d.ratio_chart.length > 0) {
                    let dtDonut = google.visualization.arrayToDataTable([
                        ['Plan', 'Usuarios'],
                        ...d.ratio_chart.map(r => [r.tipo, r.total])
                    ]);
                    new google.visualization.PieChart(elDonut).draw(dtDonut, {
                        pieHole: 0.5,
                        backgroundColor: 'transparent',
                        legend: { position: 'bottom', textStyle: { color: '#555' } },
                        colors: ['#6B46C1', '#FBBF24'],
                        chartArea: { width: '90%', height: '80%' },
                        pieSliceTextStyle: { color: '#fff', bold: true }
                    });
                } else if (elDonut) {
                    elDonut.innerHTML = '<div class="text-center text-muted py-4 small">Sin datos</div>';
                }

                // ─ GRÁFICO 2: Área — Actividad semanal ───────────────────
                const elArea = document.getElementById('chart-actividad-semanal');
                if (elArea && d.trending_semanal && d.trending_semanal.length > 0) {
                    let dtArea = google.visualization.arrayToDataTable([
                        ['Día', 'Reproducciones'],
                        ...d.trending_semanal.map(t => [t.dia, parseInt(t.reproducciones)])
                    ]);
                    new google.visualization.AreaChart(elArea).draw(dtArea, {
                        backgroundColor: 'transparent',
                        legend: { position: 'none' },
                        colors: ['#6B46C1'],
                        chartArea: { width: '88%', height: '80%' },
                        hAxis: { textStyle: { color: '#666', fontSize: 12 } },
                        vAxis: { minValue: 0, gridlines: { color: '#eee' }, textStyle: { color: '#666' } },
                        areaOpacity: 0.25,
                        lineWidth: 3,
                        pointSize: 6,
                        animation: { startup: true, duration: 700, easing: 'out' },
                        curveType: 'function'
                    });
                } else if (elArea) {
                    elArea.innerHTML = '<div class="text-center text-muted py-4 small">Sin actividad en los últimos 7 días</div>';
                }
            });
        })
        .catch(err => console.error('Dashboard error:', err));
}


// ===================================
// SCRIPTS PARA GÉNEROS MUSICALES
// ===================================

window.cargarGeneros = function() {
    $.post('php/queries.php', { caso: 'listar_generos' }, function(res) {
        let html = '';
        if (res.status === 'success') {
            if (res.data.length === 0) {
                html = '<tr><td colspan="3" class="text-center">No hay géneros registrados.</td></tr>';
            } else {
                res.data.forEach(g => {
                    html += `
                        <tr>
                            <td>${g.PK_id_genero}</td>
                            <td class="fw-bold">${g.nombre_genero}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary me-2" onclick="abrirModalGenero(${g.PK_id_genero}, '${g.nombre_genero}')" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarGenero(${g.PK_id_genero})" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
        } else {
            html = '<tr><td colspan="3" class="text-center text-danger">Error al cargar géneros.</td></tr>';
        }
        $('#cuerpoTablaGeneros').html(html);
    }, 'json');
}

let modalG = null;
window.abrirModalGenero = function(id = 0, nombre = '') {
    if(!modalG) modalG = new bootstrap.Modal(document.getElementById('modalGenero'));
    $('#id_genero').val(id);
    $('#nombre_genero').val(nombre);
    $('#tituloModalGenero').text(id === 0 ? 'Nuevo Género' : 'Editar Género');
    modalG.show();
}

window.guardarGenero = function(e) {
    if(e) e.preventDefault();
    const id = $('#id_genero').val();
    const nombre = $('#nombre_genero').val();
    const accion = id == '0' ? 'crear_genero' : 'actualizar_genero';
    
    $.post('php/queries.php', {
        caso: accion,
        id_genero: id,
        nombre: nombre
    }, function(res) {
        if (res.status === 'success' || res === 1 || res === true) {
            Swal.fire('Éxito', res.message || 'Operación realizada', 'success');
            modalG.hide();
            cargarGeneros();
        } else {
            Swal.fire('Atención', res.message || 'Error', 'warning');
        }
    }, 'json').fail(function(){
        Swal.fire('Error', 'No se pudo comunicar con el servidor.', 'error');
    });
}

window.eliminarGenero = function(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Se eliminará permanentemente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#secondary',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('php/queries.php', { caso: 'eliminar_genero', id_genero: id }, function(res) {
                if (res.status === 'success') {
                    Swal.fire('Eliminado', res.message, 'success');
                    cargarGeneros();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
}