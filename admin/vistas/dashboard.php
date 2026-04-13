<div class="container-fluid pt-3 pb-5">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-0" data-key="dash_titulo">Dashboard Global — KPIs de la Plataforma</h2>
            <p class="text-muted mb-0 small" data-key="dash_subtitulo">Estadísticas en tiempo real de Soundverse.</p>
        </div>
        <button class="btn btn-outline-secondary btn-sm" onclick="cargarDatosDashboardVista()">
            <i class="fas fa-sync-alt me-1"></i> <span data-key="dash_actualizar">Actualizar</span>
        </button>
    </div>

    <!-- ===== FILA 1: USUARIOS Y ACTIVIDAD ===== -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card text-white shadow-sm border-0 h-100" style="background:#6B46C1;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1 small opacity-75" data-key="dash_usuarios_totales">Usuarios Totales</h6>
                        <h2 class="mb-0 fw-bold" id="stat-usuarios">0</h2>
                    </div>
                    <i class="fas fa-users fa-2x opacity-40"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-info text-white shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1 small opacity-75" data-key="dash_activos_hoy">DAU (Activos Hoy)</h6>
                        <h2 class="mb-0 fw-bold" id="stat-dau">0</h2>
                    </div>
                    <i class="fas fa-calendar-day fa-2x opacity-40"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-primary text-white shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1 small opacity-75" data-key="dash_activos_mes">MAU (30 días)</h6>
                        <h2 class="mb-0 fw-bold" id="stat-mau">0</h2>
                    </div>
                    <i class="fas fa-calendar-alt fa-2x opacity-40"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-secondary text-white shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1 small opacity-75" data-key="dash_reproducciones_hoy">Reproducciones Hoy</h6>
                        <h2 class="mb-0 fw-bold" id="stat-rep-hoy">0</h2>
                    </div>
                    <i class="fas fa-play-circle fa-2x opacity-40"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FILA 2: MONETIZACIÓN, STREAMING Y RETENCIÓN ===== -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-2">
            <div class="card bg-warning text-dark shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1 small opacity-75" data-key="dash_premium">Premium</h6>
                        <h2 class="mb-0 fw-bold" id="stat-pro">0</h2>
                    </div>
                    <i class="fas fa-star fa-2x opacity-40"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-white shadow-sm border-0 h-100" style="background:#38a169;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1 small opacity-75">Free</h6>
                        <h2 class="mb-0 fw-bold" id="stat-free">0</h2>
                    </div>
                    <i class="fas fa-user fa-2x opacity-40"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-white shadow-sm border-0 h-100" style="background:#2D3748;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1 small opacity-75" data-key="dash_porcentaje_premium">% Premium</h6>
                        <h2 class="mb-0 fw-bold" id="stat-ratio">0%</h2>
                        <small class="opacity-75" style="font-size:.7rem;">del total de usuarios</small>
                    </div>
                    <i class="fas fa-chart-pie fa-2x opacity-40"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-white shadow-sm border-0 h-100" style="background:#553C9A;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1 small opacity-75" data-key="dash_conversion">Conversión (mes)</h6>
                        <h2 class="mb-0 fw-bold" id="stat-conversion-real">0%</h2>
                        <small class="opacity-75" id="stat-convertidos-mes" style="font-size:.7rem;">0 nuevos Premium</small>
                    </div>
                    <i class="fas fa-exchange-alt fa-2x opacity-40"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-white shadow-sm border-0 h-100" style="background:#c05621;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1 small opacity-75" data-key="dash_retencion">Retención</h6>
                        <h2 class="mb-0 fw-bold" id="stat-retencion">0%</h2>
                    </div>
                    <i class="fas fa-redo fa-2x opacity-40"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card bg-dark text-white shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1 small opacity-75" data-key="dash_horas">Hrs. Streaming</h6>
                        <h2 class="mb-0 fw-bold" id="stat-horas">0</h2>
                    </div>
                    <i class="fas fa-headphones-alt fa-2x opacity-40"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-white shadow-sm border-0 h-100" style="background:#1a7f64;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1 small opacity-75" data-key="dash_ingresos">Ingresos (mes)</h6>
                        <h2 class="mb-0 fw-bold fs-5" id="stat-ingresos">L.0</h2>
                    </div>
                    <i class="fas fa-dollar-sign fa-2x opacity-40"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FILA 3: CATÁLOGO ===== -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center" style="border-radius:12px; border-top: 3px solid #6B46C1 !important;">
                <div class="card-body py-2">
                    <div class="text-primary fw-bold fs-4" id="stat-canciones">—</div>
                    <div class="small text-muted">Canciones activas</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center" style="border-radius:12px; border-top: 3px solid #0bc5ea !important;">
                <div class="card-body py-2">
                    <div class="text-info fw-bold fs-4" id="stat-artistas">—</div>
                    <div class="small text-muted">Artistas activos</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center" style="border-radius:12px; border-top: 3px solid #38a169 !important;">
                <div class="card-body py-2">
                    <div class="text-success fw-bold fs-4" id="stat-albumes">—</div>
                    <div class="small text-muted">Álbumes activos</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FILA 4: GRÁFICOS ===== -->
    <div class="row g-3 mb-4">
        <!-- Donut Free vs Premium -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-header fw-bold" style="background:#6B46C1; color:#fff; border-radius:12px 12px 0 0;">
                    <i class="fas fa-chart-pie me-2"></i> Distribución Free / Premium
                </div>
                <div class="card-body p-2">
                    <div id="chart-ratio" style="width:100%; height:220px;"></div>
                </div>
            </div>
        </div>
        <!-- Área: Actividad semanal -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-header fw-bold bg-dark text-white" style="border-radius:12px 12px 0 0;">
                    <i class="fas fa-chart-area me-2 text-primary"></i> Reproducciones — Últimos 7 días
                </div>
                <div class="card-body p-2">
                    <div id="chart-actividad-semanal" style="width:100%; height:220px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FILA 5: TOP ARTISTAS + TRENDING TOP 10 ===== -->
    <div class="row g-3">
        <!-- Top 5 Artistas -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-header bg-info text-white fw-bold" style="border-radius:12px 12px 0 0;">
                    <i class="fas fa-star me-2"></i> Top 5 Artistas (Globales)
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Artista</th>
                                    <th class="text-center">Repr.</th>
                                    <th class="text-center">Min.</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-top-artistas">
                                <tr><td colspan="4" class="text-center py-3 text-muted">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trending Top 10 -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-header bg-dark text-white fw-bold" style="border-radius:12px 12px 0 0;" data-key="dash_trending">
                    <i class="fas fa-fire me-2 text-warning"></i> Trending Top 50
                    <button class="btn btn-sm btn-outline-warning float-end py-0" style="font-size:.75rem;"
                            onclick="this.closest('.card-body').style.maxHeight='none'; this.style.display='none';">
                        Ver todas
                    </button>
                </div>
                <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th>#</th>
                                    <th>Título</th>
                                    <th><i class="fas fa-headphones"></i></th>
                                </tr>
                            </thead>
                            <tbody id="tabla-trending">
                                <tr><td colspan="3" class="text-center py-3 text-muted">Cargando datos...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Países -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-header bg-primary text-white fw-bold" style="border-radius:12px 12px 0 0;">
                    <i class="fas fa-globe-americas me-2"></i> Uso por Países
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>País</th>
                                    <th class="text-center">Actividad</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-paises">
                                <tr><td colspan="2" class="text-center py-3 text-muted">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar el dashboard al cargar la vista
cargarDatosDashboardVista();
</script>