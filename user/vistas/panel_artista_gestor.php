<?php
/**
 * VISTA: Panel Artista Gestor (PREMIUM OVERHAUL)
 * PROPÓSITO: Proporcionar una interfaz de analíticas de alto nivel para los gestores de artistas.
 */
session_name('SOUNDVERSE_USER');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) exit;
?>
<div class="container-fluid py-4 fade-in" id="panel-artista-gestor">

    <!-- Encabezado Premium -->
    <div class="row mb-5 align-items-center" id="gestor-header">
        <div class="col-12 text-center py-5">
            <div class="spinner-grow text-primary" role="status"></div>
            <p class="mt-3 text-muted fw-bold">Sincronizando métricas globales del artista...</p>
        </div>
    </div>

    <!-- KPIs Estilo Premium -->
    <div class="row g-4 mb-5 d-none" id="gestor-kpis">
        <div class="col-md-4">
            <div class="card border-0 shadow-lg h-100 overflow-hidden position-relative" 
                 style="background: linear-gradient(135deg, #6B46C1 0%, #4c2fb3 100%); color:#fff; border-radius: 20px;">
                <div class="position-absolute top-0 end-0 p-3 opacity-25" style="font-size: 5rem; transform: rotate(15deg); margin-top: -10px; margin-right: -10px;">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="card-body p-4 position-relative">
                    <h6 class="text-uppercase small fw-bold opacity-75 mb-3" style="letter-spacing: 1px;">Reproducciones Válidas</h6>
                    <h1 class="display-5 fw-bold mb-1" id="kpi-reps-validas">0</h1>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-white bg-opacity-25 rounded-pill px-2 py-1 small">
                            <i class="fas fa-check-double me-1"></i> Verificadas
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-lg h-100 overflow-hidden position-relative" 
                 style="background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%); color:#fff; border-radius: 20px;">
                <div class="position-absolute top-0 end-0 p-3 opacity-25" style="font-size: 5rem; transform: rotate(15deg); margin-top: -10px; margin-right: -10px;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-body p-4 position-relative">
                    <h6 class="text-uppercase small fw-bold opacity-75 mb-3" style="letter-spacing: 1px;">Tiempo de Escucha</h6>
                    <h1 class="display-5 fw-bold mb-1" id="kpi-minutos">0</h1>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-white bg-opacity-25 rounded-pill px-2 py-1 small">
                            <i class="fas fa-bolt me-1 text-warning"></i> Tiempo Real
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-lg h-100 overflow-hidden position-relative" 
                 style="background: linear-gradient(135deg, #38a169 0%, #2f855a 100%); color:#fff; border-radius: 20px;">
                <div class="position-absolute top-0 end-0 p-3 opacity-25" style="font-size: 5rem; transform: rotate(15deg); margin-top: -10px; margin-right: -10px;">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="card-body p-4 position-relative">
                    <h6 class="text-uppercase small fw-bold opacity-75 mb-3" style="letter-spacing: 1px;">Regalías Estimadas (USD)</h6>
                    <h1 class="display-5 fw-bold mb-1" id="kpi-regalia">$0.00</h1>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-white bg-opacity-25 rounded-pill px-2 py-1 small">
                            <i class="fas fa-info-circle me-1"></i> $0.005/repro.
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fila central: Tablas -->
    <div class="row g-4 d-none" id="gestor-tablas">
        
        <!-- Rendimiento de Canciones -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-4 px-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold text-dark mb-0"><i class="fas fa-chart-bar text-primary me-2"></i>Rendimiento del Catálogo</h5>
                        <small class="text-muted">Top 10 canciones por monetización activa</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm rounded-pill" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v text-muted"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4">
                            <li><a class="dropdown-item py-2 px-3" href="#" onclick="location.reload();"><i class="fas fa-sync me-2 text-muted"></i>Actualizar</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light bg-opacity-50">
                                <tr>
                                    <th class="ps-4 py-3 small fw-bold text-uppercase text-muted border-0">Canción</th>
                                    <th class="py-3 small fw-bold text-uppercase text-muted border-0">Reproducciones</th>
                                    <th class="py-3 small fw-bold text-uppercase text-muted border-0 text-end">Ganancia</th>
                                    <th class="py-3 small fw-bold text-uppercase text-muted border-0 text-center">Destacada</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-canciones-gestor">
                                <!-- JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Países -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-4 px-4">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-globe-americas text-success me-2"></i>Distribución Global</h5>
                    <small class="text-muted">Oyentes únicos por territorio</small>
                </div>
                <div class="card-body p-0">
                    <div id="tabla-paises-gestor-container">
                        <table class="table table-hover align-middle mb-0">
                            <tbody id="tabla-paises-gestor">
                                <!-- JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Error Display -->
    <div class="alert alert-danger rounded-4 shadow-sm d-none" id="gestor-error"></div>
</div>

<style>
    #panel-artista-gestor .card { transition: all 0.3s ease; }
    #panel-artista-gestor .table tr { border-bottom: 1px solid rgba(0,0,0,0.03); }
    #panel-artista-gestor .table tr:last-child { border-bottom: none; }
    #panel-artista-gestor .progress { background: rgba(0,0,0,0.05); }
    #panel-artista-gestor .btn-destacar { transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    #panel-artista-gestor .btn-destacar:hover { transform: scale(1.15); }
</style>

<script>
(function initGestor() {
    $.ajax({
        url: 'php/queries.php?caso=estadisticas_artista_gestor',
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (resp.status !== 'success') {
                $('#gestor-header').hide();
                $('#gestor-error').removeClass('d-none').html(`<i class="fas fa-exclamation-circle me-2"></i>${resp.message}`);
                return;
            }

            const isEn = localStorage.getItem('idiomaSite') === 'en';
            const s = resp.stats;
            const minutos = Math.round((s.total_segundos || 0) / 60);

            // 1. Header Dinámico
            $('#gestor-header').html(`
                <div class="col-12 text-center text-md-start d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-warning text-dark text-uppercase px-3 py-2 rounded-pill mb-2 d-inline-block" style="font-size:0.7rem; letter-spacing:1px; font-weight:800;">
                            Portal de Administración
                        </span>
                        <h2 class="fw-bold text-dark mb-0 display-6">Centro del Artista: <span class="text-primary">${s.nombre_artistico || 'General'}</span></h2>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <button class="btn btn-white shadow-sm rounded-pill px-4 fw-bold border" onclick="location.reload();">
                            <i class="fas fa-sync-alt me-2 text-primary"></i> Sincronizar
                        </button>
                    </div>
                </div>
            `);

            // 2. KPIs
            $('#kpi-reps-validas').text(Number(s.total_reps_validas).toLocaleString('es'));
            $('#kpi-minutos').html(minutos.toLocaleString('es') + ' <small class="fs-6 opacity-75">min</small>');
            $('#kpi-regalia').text('$' + parseFloat(s.regalia_usd).toLocaleString('es', {minimumFractionDigits:2, maximumFractionDigits:2}));
            $('#gestor-kpis').removeClass('d-none');

            // 3. Tabla Canciones
            let htmlCan = '';
            if (!s.canciones || s.canciones.length === 0) {
                htmlCan = `<tr><td colspan="4" class="text-center py-5 text-muted opacity-50">
                            <div class="py-4">
                                <i class="fas fa-compact-disc fa-3x mb-3 d-block"></i>
                                <span>Aún no hay actividad monetizada para tus temas.</span>
                            </div>
                           </td></tr>`;
            } else {
                s.canciones.forEach((c) => {
                    const isDest = parseInt(c.destacada) === 1;
                    const starBtn = isDest ? 'btn-warning border-0' : 'btn-outline-secondary opacity-50 border-0';
                    const winMsg = isEn ? 'Featured' : 'Destacada';

                    htmlCan += `
                        <tr class="py-3">
                            <td class="ps-4">
                                <div class="fw-bold text-dark">${c.titulo}</div>
                                <div class="text-muted small">${c.album}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold me-2">${Number(c.reps_validas).toLocaleString('es')}</span>
                                    <span class="badge bg-light text-primary border rounded-pill py-1" style="font-size:0.65rem;">VALID</span>
                                </div>
                            </td>
                            <td class="text-end fw-bold text-success pe-4">
                                <span class="badge bg-success-soft text-success px-2 py-1">$${parseFloat(c.regalia_cancion).toFixed(2)}</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm ${starBtn} rounded-circle shadow-sm btn-destacar" 
                                        onclick="alternarDestacadaGestor(${c.PK_id_cancion}, this)">
                                    <i class="fas fa-star"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
            $('#tabla-canciones-gestor').html(htmlCan);

            // 4. Países
            let htmlPais = '';
            if (!resp.paises || resp.paises.length === 0) {
                htmlPais = `
                    <div class="text-center py-5 px-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px; height:70px;">
                            <i class="fas fa-globe-africa fa-2x text-muted opacity-50"></i>
                        </div>
                        <h6 class="fw-bold text-secondary mb-2">Sin datos geográficos</h6>
                        <p class="text-muted small mb-0">Estamos recolectando métricas globales. Asegura que los perfiles de usuario tengan el campo <strong>codigo_pais</strong> completo.</p>
                    </div>`;
                $('#tabla-paises-gestor-container').html(htmlPais);
            } else {
                const maxReps = resp.paises[0].reps;
                resp.paises.forEach((p) => {
                    const pct = Math.round((p.reps / maxReps) * 100);
                    htmlPais += `
                        <tr class="py-3">
                            <td class="ps-4 py-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-bold text-dark">${p.pais}</span>
                                    <span class="text-muted small">${pct}%</span>
                                </div>
                                <div class="progress rounded-pill" style="height: 6px;">
                                    <div class="progress-bar bg-success rounded-pill" style="width: ${pct}%"></div>
                                </div>
                            </td>
                            <td class="text-center fw-bold text-dark">${Number(p.reps).toLocaleString('es')}</td>
                            <td class="text-end pe-4 text-muted small">${p.minutos}m</td>
                        </tr>
                    `;
                });
                $('#tabla-paises-gestor').html(htmlPais);
            }

            $('#gestor-tablas').removeClass('d-none');
        },
        error: function() {
            $('#gestor-header').hide();
            $('#gestor-error').removeClass('d-none').text('Fallo crítico en la comunicación con el servidor.');
        }
    });

    window.alternarDestacadaGestor = function(id, btn) {
        $(btn).prop('disabled', true);
        $.ajax({
            url: 'php/queries.php?caso=alternar_destacada',
            type: 'POST',
            data: { id_cancion: id },
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success') {
                    const isDest = parseInt(resp.destacada) === 1;
                    $(btn).removeClass('btn-warning btn-outline-secondary opacity-50')
                          .addClass(isDest ? 'btn-warning' : 'btn-outline-secondary opacity-50');
                    Swal.fire({ icon: 'success', title: resp.message, timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });
                } else {
                    Swal.fire('Atención', resp.message, 'warning');
                }
            },
            complete: () => { $(btn).prop('disabled', false); }
        });
    };

})();
</script>
<style>
    .bg-success-soft { background-color: rgba(56, 161, 105, 0.1); }
</style>
