<div class="container-fluid pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 data-key="adm_gen_titulo">Gestión de Géneros Musicales</h2>
        <button class="btn btn-primary" onclick="abrirModalGenero()">
            <i class="fas fa-plus"></i> <span data-key="adm_gen_nuevo">Nuevo Género</span>
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th data-key="adm_gen_th_nombre">Nombre del Género</th>
                            <th class="text-center" data-key="adm_usr_th_acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaGeneros">
                        <tr><td colspan="3" class="text-center">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal formulario Género -->
<div class="modal fade" id="modalGenero" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formularioGenero" onsubmit="guardarGenero(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalGenero">Nuevo Género</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id_genero" name="id_genero" value="0">
                    <div class="mb-3">
                        <label for="nombre_genero" class="form-label">Nombre del Género <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre_genero" name="nombre_genero" required maxlength="50">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-key="btn_cancelar">Cancelar</button>
                    <button type="submit" class="btn btn-primary" data-key="btn_guardar_simple">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Inicializar la tabla al cargar la vista
cargarGeneros();
</script>
