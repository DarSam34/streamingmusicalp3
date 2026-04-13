<div class="container-fluid pb-5">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-0"><i class="fas fa-chart-line me-2 text-primary"></i> <span data-key="st_titulo">Mis Estadísticas</span></h2>
            <p class="text-muted mb-0 small" id="label-periodo-historial">Cargando periodo...</p>
        </div>
        <span class="badge bg-primary fs-6 px-3 py-2" id="badge-tipo-plan">...</span>
    </div>

    <!-- ===== FILA 1: MÉTRICAS RÁPIDAS (KPI CARDS) ===== -->
    <div class="row g-3 mb-4" id="kpi-cards">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center h-100" style="border-top: 4px solid #6B46C1 !important; border-radius:12px;">
                <div class="card-body py-3">
                    <i class="fas fa-stopwatch fa-2x text-primary mb-2"></i>
                    <div class="fs-4 fw-bold" id="kpi-minutos">—</div>
                    <div class="text-muted small" data-key="st_total_minutos">Total minutos</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center h-100" style="border-top: 4px solid #38a169 !important; border-radius:12px;">
                <div class="card-body py-3">
                    <i class="fas fa-music fa-2x text-success mb-2"></i>
                    <div class="fs-4 fw-bold" id="kpi-canciones">—</div>
                    <div class="text-muted small" data-key="st_total_canciones">Canciones únicas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center h-100" style="border-top: 4px solid #d69e2e !important; border-radius:12px;">
                <div class="card-body py-3">
                    <i class="fas fa-calendar-day fa-2x text-warning mb-2"></i>
                    <div class="fs-4 fw-bold" id="kpi-promedio">—</div>
                    <div class="text-muted small" data-key="st_promedio">Min/día (30d)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center h-100" style="border-top: 4px solid #0bc5ea !important; border-radius:12px;">
                <div class="card-body py-3">
                    <i class="fas fa-guitar fa-2x text-info mb-2"></i>
                    <div class="fs-4 fw-bold text-truncate px-1" id="kpi-genero">—</div>
                    <div class="text-muted small" data-key="st_genero">Género favorito</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FILA 2: TOP CANCIONES + TOP ARTISTAS ===== -->
    <div class="row g-3 mb-4">
        <!-- Top 5 Canciones -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-header bg-success text-white fw-bold d-flex align-items-center gap-2" style="border-radius:12px 12px 0 0;">
                    <i class="fas fa-trophy"></i>
                    <span data-key="st_top_canciones">Top 5 canciones más escuchadas</span>
                </div>
                <div class="card-body p-0" id="top-canciones">
                    <div class="text-center py-4"><div class="spinner-border text-success spinner-border-sm"></div></div>
                </div>
            </div>
        </div>

        <!-- Top 5 Artistas con selector -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-header bg-info text-white fw-bold d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-radius:12px 12px 0 0;">
                    <span><i class="fas fa-star me-2"></i><span data-key="st_top_artistas">Top 5 Artistas</span></span>
                    <div class="btn-group btn-group-sm" role="group" id="filtro-artistas">
                        <button type="button" class="btn btn-light btn-artistas-filtro active" data-filtro="mes_actual">Este mes</button>
                        <button type="button" class="btn btn-light btn-artistas-filtro" data-filtro="anio_actual">Este año</button>
                        <button type="button" class="btn btn-light btn-artistas-filtro" data-filtro="todo">Histórico</button>
                    </div>
                </div>
                <div class="card-body" id="top-artistas">
                    <div class="text-center py-4"><div class="spinner-border text-info spinner-border-sm"></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FILA 3: GÉNEROS (BARRAS) + PLAYLISTS ===== -->
    <div class="row g-3 mb-4">
        <!-- Gráfico de barras por género -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-header bg-dark text-white fw-bold" style="border-radius:12px 12px 0 0;">
                    <i class="fas fa-chart-bar me-2 text-warning"></i>
                    <span data-key="st_grafico">Reproducciones por Género</span>
                </div>
                <div class="card-body">
                    <div id="chart-generos" style="width:100%; height:260px;">
                        <div class="text-center py-5"><div class="spinner-border text-warning spinner-border-sm"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Playlists -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px; border-top: 4px solid #9f7aea !important;">
                <div class="card-header fw-bold" style="background: #f5f0ff; border-radius:12px 12px 0 0; color:#6B46C1;">
                    <i class="fas fa-list-music me-2"></i> Mis Playlists Activas
                </div>
                <div class="card-body p-0" id="top-playlists">
                    <div class="text-center py-4"><div class="spinner-border spinner-border-sm" style="color:#9f7aea"></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FILA 4: EVOLUCIÓN DE GÉNEROS (LÍNEA MENSUAL) ===== -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header bg-gradient fw-bold text-white" style="background: linear-gradient(135deg,#6B46C1,#3182ce); border-radius:12px 12px 0 0;">
                    <i class="fas fa-chart-area me-2"></i>
                    Evolución de gustos por mes
                    <small class="ms-2 fw-normal opacity-75">— géneros más escuchados</small>
                </div>
                <div class="card-body">
                    <div id="chart-evolucion" style="width:100%; height:280px;">
                        <div class="text-center py-5"><div class="spinner-border spinner-border-sm text-primary"></div><p class="mt-2 text-muted small">Calculando evolución...</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// =============================================================
// Función principal — carga todos los datos en un solo request
// =============================================================
function cargarEstadisticas() {
    $.ajax({
        url: 'php/queries.php?caso=estadisticas_personales',
        type: 'POST',
        dataType: 'json',
        success: function(resp) {
            if (resp.status !== 'success') {
                $('#estadisticas-resumen').html('<div class="alert alert-warning">No hay datos suficientes todavía.</div>');
                return;
            }
            let d = resp.data;

            // --- Subtítulo periodo ---
            $('#label-periodo-historial').text('Periodo: ' + d.tipo_historial);
            const esPremium = window.esPremium || false;
            $('#badge-tipo-plan').text(esPremium ? '👑 Premium' : '🎧 Free — últimos 90 días');

            // --- KPI Cards ---
            $('#kpi-minutos').text(parseFloat(d.total_minutos).toLocaleString('es'));
            $('#kpi-canciones').text(d.total_canciones);
            $('#kpi-promedio').text(d.promedio_diario);
            $('#kpi-genero').text(d.genero_favorito || 'N/A');

            // --- Top 5 Canciones (lista numerada) ---
            if (!d.top_canciones || d.top_canciones.length === 0) {
                $('#top-canciones').html('<div class="alert alert-info m-3">Aún no tienes canciones reproducidas.</div>');
            } else {
                let html = '<ol class="list-group list-group-numbered list-group-flush">';
                d.top_canciones.forEach(function(c) {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-start px-3">
                        <div class="ms-1 me-auto">
                            <div class="fw-semibold text-truncate" style="max-width:160px;" title="${c.titulo}">${c.titulo}</div>
                            <small class="text-muted">${c.artista}</small>
                        </div>
                        <span class="badge bg-success rounded-pill">${c.reproducciones}×</span>
                    </li>`;
                });
                html += '</ol>';
                $('#top-canciones').html(html);
            }

            // --- Top 5 Artistas (carga inicial con filtro mes_actual) ---
            renderTopArtistas(null); // usa AJAX propio con selector

            // --- Top Playlists ---
            if (d.top_playlists && d.top_playlists.length > 0) {
                let html = '<ul class="list-group list-group-flush">';
                d.top_playlists.forEach(function(p) {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center px-3">
                        <span><i class="fas fa-compact-disc me-2 text-primary"></i>${p.nombre_playlist}</span>
                        <span class="badge rounded-pill" style="background:#9f7aea">${p.reproducciones} repr.</span>
                    </li>`;
                });
                html += '</ul>';
                $('#top-playlists').html(html);
            } else {
                $('#top-playlists').html('<div class="text-center py-4 text-muted small px-3"><i class="fas fa-music-slash d-block fa-2x mb-2"></i>Reproduce canciones desde tus playlists para ver datos aquí.</div>');
            }

            // --- GOOGLE CHARTS: Géneros (barras) ---
            if (d.top_generos && d.top_generos.length > 0) {
                google.charts.load('current', { packages: ['corechart'] });
                google.charts.setOnLoadCallback(function() {
                    let chartData = [['Género', 'Reproducciones', { role: 'style' }]];
                    const colores = ['#6B46C1','#38a169','#d69e2e','#0bc5ea','#e53e3e','#ed8936'];
                    d.top_generos.forEach(function(g, i) {
                        chartData.push([g.genero, parseInt(g.reproducciones), colores[i % colores.length]]);
                    });
                    let dt = google.visualization.arrayToDataTable(chartData);
                    let opts = {
                        backgroundColor: 'transparent',
                        legend: { position: 'none' },
                        chartArea: { width: '88%', height: '78%' },
                        hAxis: { textStyle: { color: '#666', fontSize: 12 } },
                        vAxis: { minValue: 0, gridlines: { color: '#eee' }, textStyle: { color: '#666' } },
                        animation: { startup: true, duration: 700, easing: 'out' },
                        bar: { groupWidth: '60%' }
                    };
                    new google.visualization.ColumnChart(document.getElementById('chart-generos')).draw(dt, opts);
                });
            } else {
                document.getElementById('chart-generos').innerHTML = '<div class="alert alert-info text-center m-3">Aún no hay datos suficientes para el gráfico.</div>';
            }

            // --- GOOGLE CHARTS: Evolución mensual (líneas) ---
            if (d.evolucion_generos && d.evolucion_generos.meses && d.evolucion_generos.meses.length > 0) {
                renderEvolucionGeneros(d.evolucion_generos);
            } else {
                document.getElementById('chart-evolucion').innerHTML = '<div class="alert alert-info text-center m-3">Escucha más música para ver tu evolución de gustos.</div>';
            }

            // Multilenguaje
            const idioma = localStorage.getItem('idiomaSite') || 'es';
            if (typeof cambiarIdioma === 'function') cambiarIdioma(idioma);
        },
        error: function() {
            $('#kpi-minutos,#kpi-canciones,#kpi-promedio,#kpi-genero').text('Error');
        }
    });
}

// ================================================================
// TOP ARTISTAS CON FILTRO TEMPORAL
// ================================================================
function renderTopArtistas(filtro) {
    filtro = filtro || 'mes_actual';
    $('#top-artistas').html('<div class="text-center py-3"><div class="spinner-border text-info spinner-border-sm"></div></div>');

    $.ajax({
        url: 'php/queries.php?caso=top_artistas_filtrado&filtro=' + filtro,
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (!resp.data || resp.data.length === 0) {
                $('#top-artistas').html('<div class="alert alert-info m-3">Sin datos para este periodo.</div>');
                return;
            }
            let html = '<div class="table-responsive"><table class="table table-hover table-sm mb-0">';
            html += '<thead class="table-light"><tr><th>#</th><th>Artista</th><th class="text-center">Reprod.</th><th class="text-center">Min.</th></tr></thead><tbody>';
            resp.data.forEach(function(a, i) {
                const medal = ['🥇','🥈','🥉'][i] || (i+1);
                html += `<tr>
                    <td class="fw-bold">${medal}</td>
                    <td><i class="fas fa-microphone-alt text-info me-1"></i>${a.artista}</td>
                    <td class="text-center"><span class="badge bg-info text-dark">${a.reproducciones}</span></td>
                    <td class="text-center text-muted small">${a.minutos} min</td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            $('#top-artistas').html(html);
        },
        error: function() {
            $('#top-artistas').html('<div class="alert alert-danger m-3">Error al cargar artistas.</div>');
        }
    });
}

// Botones del filtro de artistas
$(document).on('click', '.btn-artistas-filtro', function() {
    $('.btn-artistas-filtro').removeClass('active btn-dark text-white').addClass('btn-light');
    $(this).removeClass('btn-light').addClass('active btn-dark text-white');
    renderTopArtistas($(this).data('filtro'));
});

// ================================================================
// GRÁFICO DE LÍNEAS: EVOLUCIÓN DE GÉNEROS POR MES
// ================================================================
function renderEvolucionGeneros(evolucion) {
    google.charts.load('current', { packages: ['corechart'] });
    google.charts.setOnLoadCallback(function() {
        const meses  = evolucion.meses;   // ['2026-02','2026-03',...]
        const series = evolucion.series;  // {Rock:{2026-02:3}, Pop:{...}}

        if (meses.length === 0 || Object.keys(series).length === 0) {
            document.getElementById('chart-evolucion').innerHTML = '<div class="alert alert-info m-3">Sin datos de evolución aún.</div>';
            return;
        }

        const generos = Object.keys(series);

        // Encabezado: ['Mes', 'Rock', 'Pop', ...]
        let header = ['Mes', ...generos];
        let rows   = [header];

        meses.forEach(function(mes) {
            // Formatear 'YYYY-MM' → 'Feb 26'
            const [yr, mo] = mes.split('-');
            const label = new Date(yr, parseInt(mo)-1, 1).toLocaleDateString('es', { month:'short', year:'2-digit' });
            let row = [label];
            generos.forEach(function(g) {
                row.push(series[g][mes] || 0);
            });
            rows.push(row);
        });

        let dt   = google.visualization.arrayToDataTable(rows);
        let opts = {
            backgroundColor: 'transparent',
            chartArea: { width: '88%', height: '75%' },
            legend: { position: 'bottom', textStyle: { color: '#555', fontSize: 12 } },
            hAxis: { textStyle: { color: '#666', fontSize: 12 } },
            vAxis: { minValue: 0, gridlines: { color: '#eee' }, textStyle: { color: '#666' } },
            colors: ['#6B46C1','#38a169','#d69e2e','#0bc5ea'],
            lineWidth: 3,
            pointSize: 6,
            animation: { startup: true, duration: 800, easing: 'out' },
            curveType: 'function'
        };
        new google.visualization.LineChart(document.getElementById('chart-evolucion')).draw(dt, opts);
    });
}

// Activar botón inicial de artistas
setTimeout(function() {
    const btnMes = document.querySelector('.btn-artistas-filtro[data-filtro="mes_actual"]');
    if (btnMes) btnMes.classList.add('btn-dark','text-white');
}, 100);

// Inicializar
cargarEstadisticas();
</script>