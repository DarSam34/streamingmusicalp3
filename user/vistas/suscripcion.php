<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h2 class="text-dark fw-bold mb-0 text-uppercase"><i class="fas fa-crown me-2 text-warning"></i><span data-key="sub_titulo">Suscripción Premium</span></h2>
            <p class="text-secondary" data-key="sub_subtitulo">Descubre la música sin límites y apoya a tus artistas favoritos.</p>
        </div>
    </div>

    <div id="seccion-upgrade" class="row justify-content-center mb-5" style="display:none;">
        <div class="col-md-8 col-lg-6">
            <div class="card bg-dark text-light border-warning shadow-lg" style="border-radius: 12px; background: linear-gradient(145deg, #121212, #2b1d03) !important;">
                <div class="card-body p-5 text-center">
                    <h3 class="text-warning fw-bold mb-3" data-key="sub_pasate">Pásate a Premium</h3>
                    <ul class="list-unstyled text-start mx-auto mb-4" style="max-width: 300px;">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><span data-key="sub_feat_1">Música sin anuncios</span></li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><span data-key="sub_feat_2">Saltos de canción ilimitados</span></li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><span data-key="sub_feat_3">Calidad de audio Máxima (320kbps)</span></li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><span data-key="sub_feat_4">Playlists ilimitadas</span></li>
                    </ul>
                    
                    <h4 class="mb-4 text-light"><strong>$ 149.00</strong> <small class="text-muted" data-key="sub_mes">/ mes</small></h4>

                    <form id="form-upgrade">
                        <select class="form-select bg-black text-light border-secondary mb-3 w-75 mx-auto" id="select-metodo" required>
                            <option value="" data-key="sub_select_pago">Selecciona Método de Pago...</option>
                        </select>
                        <button type="submit" class="btn btn-warning w-75 rounded-pill fw-bold py-3 text-dark fs-5 shadow-sm">
                            <i class="fas fa-rocket me-2"></i><span data-key="sub_mejorar">Mejorar Cuenta Ahora</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div id="seccion-activa" class="row justify-content-center mb-5" style="display:none;">
        <div class="col-md-8 text-center">
            <div class="alert bg-dark border-warning text-light py-4 shadow-sm" style="border-radius: 12px;">
                <h3 class="text-warning mb-3"><i class="fas fa-star me-2"></i><span data-key="sub_miembro">¡Eres miembro Premium!</span></h3>
                <p class="mb-0 text-secondary" data-key="sub_beneficios">Estás disfrutando de Soundverse con todos los beneficios desbloqueados.</p>
                <div class="mt-4 pt-3 border-top border-secondary">
                    <button class="btn btn-outline-warning rounded-pill px-4 me-2"><span data-key="sub_btn_cambiar_pago" onclick="cambiarMetodoPago()">Cambiar Método de Pago</span></button>
                    <button class="btn btn-outline-danger rounded-pill px-4"><span data-key="sub_btn_cancelar" onclick="cancelarSuscripcion()">Cancelar Suscripción</span></button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h4 class="text-dark mb-3"><i class="fas fa-file-invoice-dollar me-2 text-secondary"></i><span data-key="sub_historial">Historial de Facturación</span></h4>
            <div class="card bg-dark text-light border-secondary shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead class="border-secondary">
                                <tr>
                                    <th class="ps-4 fw-bold text-secondary text-uppercase small" data-key="sub_th_factura">Nº Factura</th>
                                    <th class="fw-bold text-secondary text-uppercase small" data-key="sub_th_fecha">Fecha</th>
                                    <th class="fw-bold text-secondary text-uppercase small" data-key="sub_th_plan">Plan</th>
                                    <th class="fw-bold text-secondary text-uppercase small" data-key="sub_th_metodo">Método de Pago</th>
                                    <th class="fw-bold text-secondary text-uppercase small text-end pe-4" data-key="sub_th_total">Total</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-facturas">
                                <tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin text-warning fs-3"></i></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    verificarEstadoSuscripcion();
    cargarFacturas();
    cargarMetodosPago();

    function verificarEstadoSuscripcion() {
        $.post('php/queries.php', { caso: 'obtener_perfil' }, function(res) {
            if(res.status === 'success') {
                if (res.data.FK_id_tipo == 2) {
                    $('#seccion-upgrade').hide();
                    $('#seccion-activa').fadeIn();
                } else {
                    $('#seccion-activa').hide();
                    $('#seccion-upgrade').fadeIn();
                }
            }
        }, 'json');
    }

    function cargarMetodosPago() {
        $.post('php/queries.php', { caso: 'metodos_pago' }, function(res) {
            if (res.status === 'success') {
                let html = '<option value="" data-key="sub_select_pago">Selecciona Método de Pago...</option>';
                res.data.forEach(m => {
                    html += `<option value="${m.PK_id_metodo}">${m.nombre_metodo}</option>`;
                });
                $('#select-metodo').html(html);
                if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
            }
        }, 'json');
    }

    function cargarFacturas() {
        $.post('php/queries.php', { caso: 'historial_facturas' }, function(res) {
            let html = '';
            if (res.status === 'success') {
                if (res.data.length === 0) {
                    html = '<tr><td colspan="5" class="text-center py-5 text-secondary"><i class="fas fa-receipt fs-1 mb-3 d-block"></i><span data-key="sub_no_facturas">No tienes facturas generadas.</span></td></tr>';
                } else {
                    res.data.forEach(item => {
                        const d = new Date(item.fecha_emision);
                        const strFecha = d.toLocaleDateString();
                        const plan = item.FK_id_tipo_suscripcion == 2 ? 'Premium VIP' : 'Otro';

                        html += `
                            <tr>
                                <td class="ps-4 text-secondary">#FAC-${item.PK_id_factura.toString().padStart(6, '0')}</td>
                                <td class="text-light">${strFecha}</td>
                                <td><span class="badge bg-warning text-dark">${plan}</span></td>
                                <td class="text-muted"><i class="fas fa-credit-card me-2"></i>${item.nombre_metodo}</td>
                                <td class="text-end pe-4 text-success fw-bold">$${item.monto_total}</td>
                            </tr>
                        `;
                    });
                }
            } else {
                html = '<tr><td colspan="5" class="text-center py-4 text-danger" data-key="sub_error_facturas">Error al cargar facturas</td></tr>';
            }
            $('#tabla-facturas').html(html);
            // Traducir dinámicamente
            if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
        }, 'json');
    }

    // Submit Upgrade
    $('#form-upgrade').submit(function(e) {
        e.preventDefault();
        const m_id = $('#select-metodo').val();
        if (!m_id) return;

        Swal.fire({
            title: '¿Confirmar compra?',
            text: "Se generará un cobro simulado a tu método de pago por $149.00",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, Pagar Ahora',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#form-upgrade').find('button');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Procesando Pago...');

                $.post('php/queries.php', { caso: 'procesar_upgrade', id_metodo: m_id }, function(res) {
                    if(res.status === 'success') {
                        Swal.fire({
                            title: '¡Pago Exitoso!',
                            text: res.message,
                            icon: 'success',
                            confirmButtonColor: '#f59e0b'
                        }).then(() => {
                            $('#seccion-upgrade').slideUp();
                            $('#seccion-activa').slideDown();
                            $('.sidebar .badge').removeClass('bg-secondary text-light text-white').addClass('bg-warning text-dark').text('⭐ Premium');
                            window.esPremium = true; 
                            cargarFacturas(); 
                        });
                    } else {
                        btn.prop('disabled', false).html('<i class="fas fa-rocket me-2"></i><span data-key="sub_mejorar">Mejorar Cuenta Ahora</span>');
                        Swal.fire('Error en la transacción', res.message, 'error');
                        if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
                    }
                }, 'json').fail(function() {
                    btn.prop('disabled', false).html('<i class="fas fa-rocket me-2"></i><span data-key="sub_mejorar">Mejorar Cuenta Ahora</span>');
                    Swal.fire('Error', 'Problema de conexión con la pasarela de pago.', 'error');
                    if (typeof cambiarIdioma === 'function') cambiarIdioma(localStorage.getItem('idiomaSite') || 'es');
                });
            }
        });
    });

    window.cancelarSuscripcion = function() {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Perderás acceso a descargas, skips ilimitados y música sin anuncios.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cancelar Premium',
            cancelButtonText: 'Mantener Premium'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('php/queries.php', { caso: 'cancelar_suscripcion' }, function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Cancelada', res.message, 'success').then(() => {
                            window.esPremium = false;
                            $('.sidebar .badge').addClass('bg-secondary text-light text-white').removeClass('bg-warning text-dark').text('Free');
                            $('#seccion-activa').slideUp();
                            $('#seccion-upgrade').slideDown();
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            }
        });
    };

    window.cambiarMetodoPago = function() {
        let optionsHTML = $('#select-metodo').html();
        Swal.fire({
            title: 'Selecciona una nueva tarjeta',
            html: '<select id="nuevo-metodo" class="form-select bg-dark text-light border-secondary mt-3">' + optionsHTML + '</select>',
            showCancelButton: true,
            confirmButtonText: 'Actualizar',
            cancelButtonText: 'Cerrar',
            preConfirm: () => {
                const val = document.getElementById('nuevo-metodo').value;
                if (!val) Swal.showValidationMessage('Debes seleccionar un método de pago');
                return val;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Actualizado', 'Se ha cambiado tu tarjeta principal exitosamente. Los próximos cargos se realizarán ahí.', 'success');
            }
        });
    };
});
</script>