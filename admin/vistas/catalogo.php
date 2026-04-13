<div class="container-fluid pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 data-key="adm_cat_titulo">Catálogo Musical</h2>
        <button class="btn btn-primary" onclick="abrirModalCancion()">
            <i class="fas fa-plus"></i> <span data-key="adm_cat_nueva">Nueva Canción</span>
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th data-key="adm_alb_th_titulo">Título</th>
                            <th data-key="nav_artistas">Artista</th>
                            <th data-key="nav_albumes">Álbum</th>
                            <th data-key="adm_cat_th_genero">Género</th>
                            <th data-key="adm_cat_th_duracion">Duración</th>
                            <th class="text-center">Audio</th>
                            <th class="text-center" data-key="adm_usr_th_acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-canciones">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCancion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formularioCancion" onsubmit="guardarCancionForm(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalCancion">Registrar Nueva Canción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id_cancion" name="id_cancion" value="0">
                    <input type="hidden" name="caso" value="guardar_cancion">
                    
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="titulo" class="form-label">Título de la Canción <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <!-- NUEVO: Número de pista dentro del álbum -->
                            <label for="numero_pista" class="form-label"># Pista</label>
                            <input type="number" class="form-control" id="numero_pista" name="numero_pista"
                                   min="1" max="999" value="1" title="Orden de la canción dentro del álbum">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="duracion_segundos" class="form-label">Duración (seg) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="duracion_segundos" name="duracion_segundos" placeholder="Ej: 210" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="genero" class="form-label">Género <span class="text-danger">*</span></label>
                            <select class="form-select" id="genero" name="genero" required>
                                </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="album" class="form-label">Álbum <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="album" name="album" list="lista-albumes" placeholder="Escriba el nombre exacto del álbum..." autocomplete="off" required>
                            <datalist id="lista-albumes"></datalist>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="archivo_audio" class="form-label">Archivo de Audio (MP3/WAV) <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="archivo_audio" name="archivo_audio" accept="audio/*">
                            <small class="text-muted">Si editas una canción, déjalo vacío para conservar el audio actual.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="letra_sincronizada" class="form-label">Letra (Opcional)</label>
                        <textarea class="form-control" id="letra_sincronizada" name="letra_sincronizada" rows="4" placeholder="[00:15.00] Primera línea de la canción..."></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-key="btn_cancelar">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-cancion" data-key="adm_cat_guardar">Guardar Canción</button>
                </div>
            </form>
        </div>
    </div>
</div>