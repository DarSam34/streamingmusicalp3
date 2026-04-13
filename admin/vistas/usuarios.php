<!-- 
    NOTA: Este fragmento NO contiene PHP ni SQL.
    Los datos se cargan vía AJAX desde queries.php?caso=listarUsuarios

-->

<div class="container-fluid animate__animated animate__fadeIn">

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold"><i class="fas fa-users-cog me-2 text-primary"></i> <span data-key="adm_usr_titulo">Administración de Usuarios</span></h2>
            <p class="text-muted" data-key="adm_usr_subtitulo">Módulo para el registro, edición y control de acceso de la plataforma Soundverse.</p>
            <hr>
        </div>
    </div>

    <div class="row">

        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-user-plus me-2"></i> <span data-key="adm_usr_nuevo">Nuevo Registro</span></h5>
                </div>

                <div class="card-body">
                    <form id="formNuevoUsuario">
                        <input type="hidden" id="id_usuario" value="0">
                        <div class="mb-3">
                            <label class="form-label fw-bold" data-key="registro_nombre">Nombre Completo</label>
                            <input type="text" id="nombre" class="form-control" placeholder="Ej. Mario Mejía" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" data-key="login_correo">Correo Electrónico</label>
                            <input type="email" id="email" class="form-control" placeholder="mario@example.com"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" data-key="adm_usr_tipo">Tipo de Suscripción</label>
                            <select id="rol" class="form-select">
                                <option value="1">Free</option>
                                <option value="2">Premium</option>
                            </select>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="es_admin">
                            <label class="form-check-label fw-bold" for="es_admin" data-key="adm_usr_admin">Otorgar privilegios de Administrador</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" data-key="adm_usr_pass">Contraseña Temporal</label>
                            <input type="password" id="password" class="form-control" minlength="8">
                            <div class="form-text" data-key="adm_usr_pass_hint">Mínimo 8 caracteres. En edición, déjalo vacío para no cambiar la
                                clave.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" id="btn-submit-usuario" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><span data-key="adm_usr_registrar">Registrar Usuario</span>
                            </button>
                            <button type="button" id="btn-cancelar-usuario" class="btn btn-outline-secondary"
                                style="display:none;" onclick="cancelarEdicionUsuario()">
                                <i class="fas fa-times me-2"></i><span data-key="adm_usr_cancelar">Cancelar Edición</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 text-dark"><i class="fas fa-list me-2 text-success"></i> <span data-key="adm_usr_lista">Lista de Usuarios</span></h5>
                    <span class="badge bg-secondary" id="badge-total-usuarios">Total: 0</span>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th data-key="adm_usr_th_usuario">Usuario</th>
                                    <th data-key="adm_usr_th_correo">Correo</th>
                                    <th data-key="adm_usr_th_plan">Plan</th>
                                    <th class="text-center" data-key="adm_usr_th_acciones">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-usuarios">
                                <!-- La tabla se llena vía AJAX al cargar la vista -->
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        <span class="ms-2 text-muted">Cargando usuarios...</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>