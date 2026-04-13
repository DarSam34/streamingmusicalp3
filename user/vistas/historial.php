<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-12">
            <h2 class="text-dark fw-bold mb-0"><i class="fas fa-history me-2 text-primary"></i>Mi Historial</h2>
            <p class="text-secondary">Aquí verás tu actividad reciente y las canciones que has reproducido (Para
                analíticas de monetización mayores a 30s).</p>
            <div class="alert alert-info rounded-4 border-0 shadow-sm mt-2 mb-0 d-inline-block p-2 px-3 small">
                <i class="fas fa-info-circle me-1"></i> Se muestran únicamente tus últimas 50 reproducciones por
                rendimiento.
            </div>
        </div>
    </div>

    <div class="card bg-dark text-light border-secondary shadow-sm"
        style="border-radius: 12px; background: linear-gradient(145deg, #121212, #1a1a1a) !important;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle border-secondary">
                    <thead class="border-secondary">
                        <tr>
                            <th class="ps-4 fw-bold text-secondary text-uppercase small">🎵 Canción</th>
                            <th class="fw-bold text-secondary text-uppercase small">🎤 Artista</th>
                            <th class="fw-bold text-secondary text-uppercase small">💿 Álbum</th>
                            <th class="fw-bold text-secondary text-uppercase small">⏱️ Escuchado</th>
                            <th class="fw-bold text-secondary text-uppercase small">📅 Fecha</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-historial">
                        <tr>
                            <td colspan="5" class="text-center py-5"><i
                                    class="fas fa-spinner fa-spin text-primary fs-3"></i>
                                <p class="mt-2 text-secondary">Cargando...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Botón Cargar Más -->
    <div class="text-center mt-4 pb-4">
        <button id="btn-cargar-mas" class="btn btn-outline-primary rounded-pill px-5 fw-bold" style="display:none;">
            <i class="fas fa-plus-circle me-2"></i>Cargar más actividad
        </button>
    </div>
</div>

<script>
    $(document).ready(function () {
        let offsetActual = 0;
        const limitePorCarga = 20;

        // Carga inicial
        cargarHistorial();

        $('#btn-cargar-mas').on('click', function () {
            $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Cargando...');
            cargarHistorial();
        });

        function cargarHistorial() {
            $.post('php/queries.php', {
                caso: 'historial_usuario',
                limite: limitePorCarga,
                offset: offsetActual
            }, function (res) {
                let html = '';
                if (res.status === 'success') {
                    if (res.data.length === 0) {
                        if (offsetActual === 0) {
                            html = '<tr><td colspan="5" class="text-center py-5 text-light"><i class="fas fa-ghost fs-1 mb-3 d-block"></i>Aún no has escuchado ninguna canción. ¡Empieza ahora!</td></tr>';
                            $('#tabla-historial').html(html);
                        }
                        $('#btn-cargar-mas').hide();
                    } else {
                        res.data.forEach(item => {
                            const d = new Date(item.fecha_hora_reproduccion);
                            const strFecha = d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            const trClass = item.segundos_escuchados < 10 ? 'opacity-50' : '';
                            const bagdeTime = item.segundos_escuchados >= 30 ? 'bg-success' : 'bg-secondary';

                            html += `
                            <tr class="${trClass}">
                                <td class="ps-4 fw-bold text-light">${item.titulo}</td>
                                <td><span class="badge bg-dark border border-secondary text-light px-2 py-1"><i class="fas fa-check-circle me-1 text-primary" style="font-size:10px;"></i>${item.artista}</span></td>
                                <td class="text-light">${item.album}</td>
                                <td><span class="badge ${bagdeTime}">${item.segundos_escuchados} sec</span></td>
                                <td class="text-light"><small><i class="far fa-clock me-1"></i>${strFecha}</small></td>
                            </tr>
                        `;
                        });

                        if (offsetActual === 0) {
                            $('#tabla-historial').html(html);
                        } else {
                            $('#tabla-historial').append(html);
                        }

                        // Preparar siguiente carga
                        offsetActual += res.data.length;

                        // Solo mostramos el botón si devolvió el máximo solicitado (posibilidad de más datos)
                        if (res.data.length === limitePorCarga) {
                            $('#btn-cargar-mas').show().prop('disabled', false).html('<i class="fas fa-plus-circle me-2"></i>Cargar más actividad');
                        } else {
                            $('#btn-cargar-mas').hide();
                        }
                    }
                } else {
                    if (offsetActual === 0) {
                        $('#tabla-historial').html('<tr><td colspan="5" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al cargar historial</td></tr>');
                    }
                    $('#btn-cargar-mas').hide();
                }
            }, 'json');
        }
    });
</script>