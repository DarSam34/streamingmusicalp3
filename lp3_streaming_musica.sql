-- ==============================================================================
-- PLATAFORMA DE STREAMING DE MÚSICA "SOUNDVERSE"
-- Script completo para construcción parte #2

-- Compatible: MySQL 5.7+ / MariaDB 10.3+ / XAMPP cualquier versión
-- CONTRASEÑA BD: dejar en blanco (root sin password) — estándar XAMPP local
-- ==============================================================================

-- Encoding correcto para tildes, eñes y caracteres especiales en cualquier cliente
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ==============================================================================
-- LIMPIEZA AUTOMÁTICA PARA EVITAR ERRORES DE IMPORTACIÓN (ESCUDO ANTI-ERRORES)
-- ==============================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS `lp3_streaming_musica`;
CREATE DATABASE `lp3_streaming_musica` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lp3_streaming_musica`;
-- ==============================================================================

-- ==============================================================================
-- NIVEL 1: CONFIGURACIÓN Y SOPORTE
-- ==============================================================================

CREATE TABLE `Tipo_Suscripcion` (
  `PK_id_tipo`          INT           AUTO_INCREMENT PRIMARY KEY,
  `nombre_plan`         VARCHAR(30)   NOT NULL,
  `precio_mensual`      DECIMAL(10,2) NOT NULL DEFAULT 0,
  `calidad_kbps`        INT           NOT NULL DEFAULT 128,
  `limite_playlists`    INT           NOT NULL DEFAULT 15,
  `limite_skips_hora`   INT           NOT NULL DEFAULT 6,
  `puede_descargar`     TINYINT(1)    DEFAULT 0,
  `tiene_anuncios`      TINYINT(1)    DEFAULT 1,
  `estado_disponible`   TINYINT(1)    DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE `Metodo_Pago` (
  `PK_id_metodo`        INT           AUTO_INCREMENT PRIMARY KEY,
  `nombre_metodo`       VARCHAR(50)   NOT NULL,
  `estado_disponible`   TINYINT(1)    DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE `Genero_Musical` (
  `PK_id_genero`        INT           AUTO_INCREMENT PRIMARY KEY,
  `nombre_genero`       VARCHAR(50)   NOT NULL UNIQUE,
  `estado_disponible`   TINYINT(1)    DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE `Idioma_Traduccion` (
  `PK_id_idioma`        INT           AUTO_INCREMENT PRIMARY KEY,
  `codigo_iso`          VARCHAR(5)    NOT NULL,
  `etiqueta_llave`      VARCHAR(100)  NOT NULL,
  `texto_traduccion`    TEXT          NOT NULL,
  `estado_disponible`   TINYINT(1)    DEFAULT 1,
  UNIQUE KEY `unique_etiqueta_idioma` (`codigo_iso`, `etiqueta_llave`)
) ENGINE=InnoDB;

-- ==============================================================================
-- NIVEL 2: ACTORES DEL SISTEMA
-- ==============================================================================

CREATE TABLE `Usuario` (
  `PK_id_usuario`       INT           AUTO_INCREMENT PRIMARY KEY,
  `FK_id_tipo`          INT           NOT NULL,
  `nombre_completo`     VARCHAR(100)  NOT NULL,
  `correo`              VARCHAR(100)  UNIQUE NOT NULL,
  `codigo_pais`         VARCHAR(2)    DEFAULT 'MX',
  `clave_hash`          VARCHAR(255)  NOT NULL,
  `fecha_registro`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `estado_disponible`   TINYINT(1)    DEFAULT 1,
  `es_admin`            TINYINT(1)    DEFAULT 0,
  `id_sesion_activa`    VARCHAR(255)  NULL,
  CONSTRAINT `FK_usuario_tipo`
    FOREIGN KEY (`FK_id_tipo`) REFERENCES `Tipo_Suscripcion`(`PK_id_tipo`)
) ENGINE=InnoDB;

CREATE TABLE `Artista` (
  `PK_id_artista`       INT           AUTO_INCREMENT PRIMARY KEY,
  `nombre_artistico`    VARCHAR(100)  NOT NULL,
  `biografia`           TEXT,
  `ruta_foto_perfil`    VARCHAR(255),
  `verificado`          TINYINT(1)    DEFAULT 0,
  `estado_disponible`   TINYINT(1)    DEFAULT 1,
  `FK_id_usuario_gestor` INT          NULL,
  CONSTRAINT `FK_artista_usuario_gestor`
    FOREIGN KEY (`FK_id_usuario_gestor`) REFERENCES `Usuario`(`PK_id_usuario`)
) ENGINE=InnoDB;

-- ==============================================================================
-- NIVEL 3: CATÁLOGO MULTIMEDIA
-- ==============================================================================

CREATE TABLE `Album` (
  `PK_id_album`         INT           AUTO_INCREMENT PRIMARY KEY,
  `FK_id_artista`       INT           NOT NULL,
  `titulo`              VARCHAR(100)  NOT NULL,
  `fecha_lanzamiento`   DATE          NOT NULL,
  `ruta_portada`        VARCHAR(255),
  `discografica`        VARCHAR(100)  DEFAULT NULL,
  `estado_disponible`   TINYINT(1)    DEFAULT 1,
  CONSTRAINT `FK_album_artista`
    FOREIGN KEY (`FK_id_artista`) REFERENCES `Artista`(`PK_id_artista`)
) ENGINE=InnoDB;

CREATE TABLE `Cancion` (
  `PK_id_cancion`           INT           AUTO_INCREMENT PRIMARY KEY,
  `FK_id_album`             INT           NOT NULL,
  `FK_id_genero`            INT           NOT NULL,
  `titulo`                  VARCHAR(150)  NOT NULL,
  `numero_pista`            INT           DEFAULT 1,
  `duracion_segundos`       INT           NOT NULL,
  `ruta_archivo_audio`      VARCHAR(255)  NOT NULL,
  `letra_sincronizada`      TEXT,
  `contador_reproducciones` INT           DEFAULT 0,
  `destacada`               TINYINT(1)    DEFAULT 0,
  `estado_disponible`       TINYINT(1)    DEFAULT 1,
  CONSTRAINT `FK_cancion_album`
    FOREIGN KEY (`FK_id_album`)   REFERENCES `Album`(`PK_id_album`),
  CONSTRAINT `FK_cancion_genero`
    FOREIGN KEY (`FK_id_genero`)  REFERENCES `Genero_Musical`(`PK_id_genero`)
) ENGINE=InnoDB;

-- ==============================================================================
-- NIVEL 4: INTERACCIONES
-- ==============================================================================

CREATE TABLE `Playlist` (
  `PK_id_playlist`      INT           AUTO_INCREMENT PRIMARY KEY,
  `FK_id_usuario`       INT           NOT NULL,
  `nombre_playlist`     VARCHAR(100)  NOT NULL,
  `visibilidad`         ENUM('Publica','Privada','Colaborativa') DEFAULT 'Publica',
  `fecha_creacion`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `estado_disponible`   TINYINT(1)    DEFAULT 1,
  UNIQUE KEY `unique_playlist_usuario` (`FK_id_usuario`, `nombre_playlist`),
  CONSTRAINT `FK_playlist_usuario`
    FOREIGN KEY (`FK_id_usuario`) REFERENCES `Usuario`(`PK_id_usuario`)
) ENGINE=InnoDB;

CREATE TABLE `Detalle_Playlist` (
  `FK_id_playlist`      INT           NOT NULL,
  `FK_id_cancion`       INT           NOT NULL,
  `fecha_agregada`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `orden_reproduccion`  INT,
  PRIMARY KEY (`FK_id_playlist`, `FK_id_cancion`),
  CONSTRAINT `FK_detalle_playlist`
    FOREIGN KEY (`FK_id_playlist`) REFERENCES `Playlist`(`PK_id_playlist`) ON DELETE CASCADE,
  CONSTRAINT `FK_detalle_cancion`
    FOREIGN KEY (`FK_id_cancion`)  REFERENCES `Cancion`(`PK_id_cancion`)
) ENGINE=InnoDB;

CREATE TABLE `Historial_Reproduccion` (
  `PK_id_historial`         INT           AUTO_INCREMENT PRIMARY KEY,
  `FK_id_usuario`           INT           NOT NULL,
  `FK_id_cancion`           INT           NOT NULL,
  `segundos_escuchados`     INT           NOT NULL,
  `es_valida_regalia`       TINYINT(1)    DEFAULT 0,
  `fecha_hora_reproduccion` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `FK_historial_usuario`
    FOREIGN KEY (`FK_id_usuario`) REFERENCES `Usuario`(`PK_id_usuario`),
  CONSTRAINT `FK_historial_cancion`
    FOREIGN KEY (`FK_id_cancion`) REFERENCES `Cancion`(`PK_id_cancion`)
) ENGINE=InnoDB;

-- ==============================================================================
-- NIVEL 5: FACTURACIÓN
-- ==============================================================================

CREATE TABLE `Factura` (
  `PK_id_factura`       INT           AUTO_INCREMENT PRIMARY KEY,
  `FK_id_usuario`       INT           NOT NULL,
  `FK_id_metodo`        INT           NOT NULL,
  `fecha_emision`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `monto_total`         DECIMAL(10,2) NOT NULL,
  `estado_disponible`   TINYINT(1)    DEFAULT 1,
  CONSTRAINT `FK_factura_usuario`
    FOREIGN KEY (`FK_id_usuario`) REFERENCES `Usuario`(`PK_id_usuario`),
  CONSTRAINT `FK_factura_metodo`
    FOREIGN KEY (`FK_id_metodo`)  REFERENCES `Metodo_Pago`(`PK_id_metodo`)
) ENGINE=InnoDB;

CREATE TABLE `Detalle_Factura` (
  `PK_id_detalle`           INT           AUTO_INCREMENT PRIMARY KEY,
  `FK_id_factura`           INT           NOT NULL,
  `FK_id_tipo_suscripcion`  INT           NOT NULL,
  `precio_aplicado`         DECIMAL(10,2) NOT NULL,
  CONSTRAINT `FK_detalle_factura`
    FOREIGN KEY (`FK_id_factura`)          REFERENCES `Factura`(`PK_id_factura`) ON DELETE CASCADE,
  CONSTRAINT `FK_detalle_tipo_suscripcion`
    FOREIGN KEY (`FK_id_tipo_suscripcion`) REFERENCES `Tipo_Suscripcion`(`PK_id_tipo`)
) ENGINE=InnoDB;

-- ==============================================================================
-- NIVEL 6: SEGUIMIENTO Y NOTIFICACIONES
-- ==============================================================================

CREATE TABLE `Seguimiento_Artista` (
  `FK_id_usuario`       INT           NOT NULL,
  `FK_id_artista`       INT           NOT NULL,
  `fecha_seguimiento`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`FK_id_usuario`, `FK_id_artista`),
  CONSTRAINT `FK_seguimiento_usuario`
    FOREIGN KEY (`FK_id_usuario`) REFERENCES `Usuario`(`PK_id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `FK_seguimiento_artista`
    FOREIGN KEY (`FK_id_artista`) REFERENCES `Artista`(`PK_id_artista`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `Notificacion` (
  `PK_id_notificacion`  INT           AUTO_INCREMENT PRIMARY KEY,
  `FK_id_usuario`       INT           NOT NULL,
  `mensaje`             TEXT          NOT NULL,
  `leida`               TINYINT(1)    DEFAULT 0,
  `estado_disponible`   TINYINT(1)    DEFAULT 1,
  `fecha_creacion`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `tipo`                VARCHAR(50),
  `referencia_id`       INT           NULL,
  CONSTRAINT `FK_notificacion_usuario`
    FOREIGN KEY (`FK_id_usuario`) REFERENCES `Usuario`(`PK_id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==============================================================================
-- DATOS DE PRUEBA Y DICCIONARIO DE IDIOMAS
-- ==============================================================================

-- Tipos de suscripción
INSERT INTO `Tipo_Suscripcion` (`nombre_plan`, `precio_mensual`, `calidad_kbps`, `limite_playlists`, `limite_skips_hora`, `puede_descargar`, `tiene_anuncios`) VALUES
('Free',    0.00,   128, 15,   6,    0, 1),
('Premium', 149.00, 320, 9999, 9999, 1, 0);

-- Métodos de pago
INSERT INTO `Metodo_Pago` (`nombre_metodo`) VALUES
('Tarjeta de Crédito'),
('PayPal'),
('Transferencia Bancaria');

-- Géneros musicales
INSERT INTO `Genero_Musical` (`PK_id_genero`, `nombre_genero`) VALUES
(1, 'Rock'), (2, 'Pop'), (3, 'Hip Hop'), (4, 'Electrónica'), (5, 'Clásica'), (6, 'Jazz'), (7, 'Reggaetón'), (8, 'Salsa'), (9, 'Disco');

-- DICCIONARIO COMPLETO DE TRADUCCIONES (ESPAÑOL E INGLÉS)
INSERT IGNORE INTO `Idioma_Traduccion` (`codigo_iso`, `etiqueta_llave`, `texto_traduccion`) VALUES
-- Menú Principal y Reproductor
('es', 'nav_catalogo', 'Catálogo'), ('en', 'nav_catalogo', 'Catalog'),
('es', 'nav_playlists', 'Mis Playlists'), ('en', 'nav_playlists', 'My Playlists'),
('es', 'nav_estadisticas', 'Mis Estadísticas'), ('en', 'nav_estadisticas', 'My Statistics'),
('es', 'nav_descubrimiento', 'Descubrimiento Semanal'), ('en', 'nav_descubrimiento', 'Weekly Discovery'),
('es', 'nav_planes', 'Planes / Premium'), ('en', 'nav_planes', 'Plans / Premium'),
('es', 'nav_perfil', 'Mi Perfil'), ('en', 'nav_perfil', 'My Profile'),
('es', 'nav_historial', 'Mi Historial'), ('en', 'nav_historial', 'My History'),
('es', 'nav_salir', 'Cerrar Sesión'), ('en', 'nav_salir', 'Log Out'),
('es', 'nav_dashboard_artista', 'Dashboard Artista'), ('en', 'nav_dashboard_artista', 'Artist Dashboard'),
('es', 'nav_notificaciones', 'Notificaciones'), ('en', 'nav_notificaciones', 'Notifications'),
('es', 'notif_titulo', 'Notificaciones'), ('en', 'notif_titulo', 'Notifications'),
('es', 'notif_marcar', 'Marcar todas leídas'), ('en', 'notif_marcar', 'Mark all as read'),
('es', 'notif_cargando', 'Cargando...'), ('en', 'notif_cargando', 'Loading...'),
('es', 'welcome_user', 'Bienvenido'), ('en', 'welcome_user', 'Welcome'),
('es', 'welcome_msg', 'Selecciona una opción del menú para comenzar a escuchar música.'), ('en', 'welcome_msg', 'Select a menu option to start listening to music.'),
('es', 'player_no_playing', 'No reproduciendo'), ('en', 'player_no_playing', 'Not playing'),
('es', 'player_vol', 'Vol'), ('en', 'player_vol', 'Vol'),
('es', 'welcome_main_title', 'Bienvenidos Usuarios de Soundverse'), ('en', 'welcome_main_title', 'Welcome Soundverse Users'),

-- Catálogo
('es', 'cat_titulo', 'Catálogo Musical'), ('en', 'cat_titulo', 'Music Catalog'),
('es', 'cat_subtitulo', 'Explora los álbumes o descubre todas las canciones disponibles.'), ('en', 'cat_subtitulo', 'Explore albums or discover all available songs.'),
('es', 'cat_tab_albumes', 'Álbumes'), ('en', 'cat_tab_albumes', 'Albums'),
('es', 'cat_tab_canciones', 'Todas las Canciones'), ('en', 'cat_tab_canciones', 'All Songs'),
('es', 'cat_cargando_albumes', 'Cargando álbumes...'), ('en', 'cat_cargando_albumes', 'Loading albums...'),
('es', 'cat_canciones_album', 'Canciones del álbum'), ('en', 'cat_canciones_album', 'Album songs'),
('es', 'cat_th_num', '#'), ('en', 'cat_th_num', '#'),
('es', 'cat_th_titulo', 'Título'), ('en', 'cat_th_titulo', 'Title'),
('es', 'cat_th_artista', 'Artista'), ('en', 'cat_th_artista', 'Artist'),
('es', 'cat_th_album_artista', 'Álbum / Artista'), ('en', 'cat_th_album_artista', 'Album / Artist'),
('es', 'cat_th_duracion', 'Duración'), ('en', 'cat_th_duracion', 'Duration'),
('es', 'cat_th_accion', 'Acción'), ('en', 'cat_th_accion', 'Action'),
('es', 'cat_selecciona_album', 'Selecciona un álbum'), ('en', 'cat_selecciona_album', 'Select an album'),
('es', 'cat_btn_aleatorio', 'Aleatorio Global'), ('en', 'cat_btn_aleatorio', 'Global Shuffle'),
('es', 'cat_cargando_pistas', 'Cargando pistas...'), ('en', 'cat_cargando_pistas', 'Loading tracks...'),
('es', 'cat_no_albumes', 'No hay álbumes disponibles.'), ('en', 'cat_no_albumes', 'No albums available.'),
('es', 'cat_sin_canciones', 'Este álbum no tiene canciones disponibles.'), ('en', 'cat_sin_canciones', 'This album has no available songs.'),
('es', 'cat_sin_canciones_global', 'No se encontraron canciones en el catálogo.'), ('en', 'cat_sin_canciones_global', 'No songs found in the catalog.'),
('es', 'cat_err_catalogo', 'Error al cargar el catálogo completo.'), ('en', 'cat_err_catalogo', 'Error loading full catalog.'),
('es', 'cat_err_servidor', 'Error al conectar con el servidor.'), ('en', 'cat_err_servidor', 'Error connecting to server.'),
('es', 'cat_btn_reproducir', 'Reproducir'), ('en', 'cat_btn_reproducir', 'Play'),
('es', 'cat_btn_artista', 'Artista'), ('en', 'cat_btn_artista', 'Artist'),
('es', 'cat_err_canciones', 'Error al cargar canciones.'), ('en', 'cat_err_canciones', 'Error loading songs.'),

-- Suscripción
('es', 'sub_feat_1', 'Música sin anuncios'), ('en', 'sub_feat_1', 'Ad-free music'),
('es', 'sub_feat_2', 'Saltos de canción ilimitados'), ('en', 'sub_feat_2', 'Unlimited song skips'),
('es', 'sub_feat_3', 'Calidad de audio Máxima (320kbps)'), ('en', 'sub_feat_3', 'Maximum audio quality (320kbps)'),
('es', 'sub_feat_4', 'Playlists ilimitadas'), ('en', 'sub_feat_4', 'Unlimited playlists'),
('es', 'sub_mes', '/ mes'), ('en', 'sub_mes', '/ month'),
('es', 'sub_select_pago', 'Selecciona Método de Pago...'), ('en', 'sub_select_pago', 'Select Payment Method...'),
('es', 'sub_btn_cambiar_pago', 'Cambiar Método de Pago'), ('en', 'sub_btn_cambiar_pago', 'Change Payment Method'),
('es', 'sub_btn_cancelar', 'Cancelar Suscripción'), ('en', 'sub_btn_cancelar', 'Cancel Subscription'),
('es', 'sub_th_factura', 'Nº Factura'), ('en', 'sub_th_factura', 'Invoice No.'),
('es', 'sub_th_fecha', 'Fecha'), ('en', 'sub_th_fecha', 'Date'),
('es', 'sub_th_plan', 'Plan'), ('en', 'sub_th_plan', 'Plan'),
('es', 'sub_th_metodo', 'Método de Pago'), ('en', 'sub_th_metodo', 'Payment Method'),
('es', 'sub_th_total', 'Total'), ('en', 'sub_th_total', 'Total'),
('es', 'sub_error_facturas', 'Error al cargar facturas'), ('en', 'sub_error_facturas', 'Error loading invoices'),

-- Playlists
('es', 'pl_titulo', 'Mis Playlists'), ('en', 'pl_titulo', 'My Playlists'),
('es', 'pl_nueva', 'Nueva Playlist'), ('en', 'pl_nueva', 'New Playlist'),
('es', 'pl_explorar', 'Explorar Playlists Públicas'), ('en', 'pl_explorar', 'Explore Public Playlists'),
('es', 'pl_buscar_ph', 'Buscar por nombre...'), ('en', 'pl_buscar_ph', 'Search by name...'),
('es', 'pl_escribe_buscar', 'Escribe para buscar playlists públicas de otros usuarios.'), ('en', 'pl_escribe_buscar', 'Type to search for public playlists from other users.'),
('es', 'pl_colaborativa', 'Colaborativa (Pública y otros pueden añadir)'), ('en', 'pl_colaborativa', 'Collaborative (Public & others can add)'),
('es', 'pl_canciones_titulo', 'Canciones de la playlist'), ('en', 'pl_canciones_titulo', 'Playlist songs'),
('es', 'pl_crea_primera', 'Crea tu primera lista o explora las de otros usuarios.'), ('en', 'pl_crea_primera', 'Create your first list or explore others.'),
('es', 'pl_btn_crear_primera', 'Crear mi primera Playlist'), ('en', 'pl_btn_crear_primera', 'Create my first Playlist'),
('es', 'pl_vis_publica', 'Publica'), ('en', 'pl_vis_publica', 'Public'),
('es', 'pl_vis_privada', 'Privada'), ('en', 'pl_vis_privada', 'Private'),
('es', 'pl_vis_colaborativa', 'Colaborativa'), ('en', 'pl_vis_colaborativa', 'Collaborative'),
('es', 'pl_err_cargar', 'Error al cargar playlists.'), ('en', 'pl_err_cargar', 'Error loading playlists.'),
('es', 'pl_err_conexion', 'Error de conexión.'), ('en', 'pl_err_conexion', 'Connection error.'),
('es', 'pl_cargando_pistas', 'Cargando pistas...'), ('en', 'pl_cargando_pistas', 'Loading tracks...'),
('es', 'pl_vacia_catalogo', 'Esta playlist aún está vacía. Ve al catálogo para agregar canciones.'), ('en', 'pl_vacia_catalogo', 'This playlist is empty. Go to the catalog to add songs.'),
('es', 'pl_reproducir_toda', 'Reproducir Toda la Playlist'), ('en', 'pl_reproducir_toda', 'Play Entire Playlist'),
('es', 'pl_buscando', 'Buscando playlists...'), ('en', 'pl_buscando', 'Searching playlists...'),
('es', 'pl_no_encontrada', 'No se encontraron playlists públicas.'), ('en', 'pl_no_encontrada', 'No public playlists found.'),
('es', 'pl_de', 'De:'), ('en', 'pl_de', 'By:'),
('es', 'pl_canciones', 'canciones'), ('en', 'pl_canciones', 'songs'),
('es', 'pl_btn_copiar_mis', 'Copiar a mis playlists'), ('en', 'pl_btn_copiar_mis', 'Copy to my playlists'),
('es', 'pl_err_conexion_pub', 'Error de conexión al cargar las playlists.'), ('en', 'pl_err_conexion_pub', 'Connection error while loading playlists.'),
('es', 'pl_por', 'por'), ('en', 'pl_por', 'by'),
('es', 'pl_btn_reproducir', 'Reproducir'), ('en', 'pl_btn_reproducir', 'Play'),
('es', 'pl_btn_aleatorio', 'Aleatorio'), ('en', 'pl_btn_aleatorio', 'Shuffle'),
('es', 'pl_btn_guardar', 'Guardar'), ('en', 'pl_btn_guardar', 'Save'),
('es', 'pl_err_buscar_pub', 'Error al buscar playlists públicas.'), ('en', 'pl_err_buscar_pub', 'Error searching public playlists.'),
('es', 'pl_nombre', 'Nombre de la lista'), ('en', 'pl_nombre', 'Playlist Name'),
('es', 'pl_visibilidad', 'Privacidad'), ('en', 'pl_visibilidad', 'Privacy'),
('es', 'pl_publica', 'Pública (Todos pueden verla)'), ('en', 'pl_publica', 'Public (Everyone can see it)'),
('es', 'pl_privada', 'Privada (Solo tú)'), ('en', 'pl_privada', 'Private (Only you)'),
('es', 'pl_cancelar', 'Cancelar'), ('en', 'pl_cancelar', 'Cancel'),
('es', 'pl_guardar', 'Guardar'), ('en', 'pl_guardar', 'Save'),
('es', 'pl_ver', 'Ver'), ('en', 'pl_ver', 'View'),
('es', 'pl_vacia', 'Aún no tienes playlists'), ('en', 'pl_vacia', 'You have no playlists yet'),
('es', 'pl_cargando', 'Cargando playlists...'), ('en', 'pl_cargando', 'Loading playlists...'),

-- Estadísticas
('es', 'st_titulo', 'Mis Estadísticas'), ('en', 'st_titulo', 'My Statistics'),
('es', 'st_cargando_periodo', 'Cargando periodo...'), ('en', 'st_cargando_periodo', 'Loading period...'),
('es', 'st_filtro_mes', 'Este mes'), ('en', 'st_filtro_mes', 'This month'),
('es', 'st_filtro_anio', 'Este año'), ('en', 'st_filtro_anio', 'This year'),
('es', 'st_filtro_historico', 'Histórico'), ('en', 'st_filtro_historico', 'Historical'),
('es', 'st_playlists_activas', 'Mis Playlists Activas'), ('en', 'st_playlists_activas', 'My Active Playlists'),
('es', 'st_grafico', 'Reproducciones por Género'), ('en', 'st_grafico', 'Plays by Genre'),
('es', 'st_evolucion', 'Evolución de gustos por mes'), ('en', 'st_evolucion', 'Taste evolution by month'),
('es', 'st_evolucion_sub', '— géneros más escuchados'), ('en', 'st_evolucion_sub', '— top genres listened'),
('es', 'st_calc_evolucion', 'Calculando evolución...'), ('en', 'st_calc_evolucion', 'Calculating evolution...'),
('es', 'st_no_datos', 'No hay datos suficientes todavía.'), ('en', 'st_no_datos', 'Not enough data yet.'),
('es', 'st_sin_canciones', 'Aún no tienes canciones reproducidas.'), ('en', 'st_sin_canciones', 'You haven\'t played any songs yet.'),
('es', 'st_sin_playlists_stats', 'Reproduce canciones desde tus playlists para ver datos aquí.'), ('en', 'st_sin_playlists_stats', 'Play songs from your playlists to see data here.'),
('es', 'st_sin_datos_grafico', 'Aún no hay datos suficientes para el gráfico.'), ('en', 'st_sin_datos_grafico', 'Not enough data for the chart yet.'),
('es', 'st_sin_evolucion', 'Escucha más música para ver tu evolución de gustos.'), ('en', 'st_sin_evolucion', 'Listen to more music to see your taste evolution.'),
('es', 'st_sin_datos_periodo', 'Sin datos para este periodo.'), ('en', 'st_sin_datos_periodo', 'No data for this period.'),
('es', 'st_th_reprod', 'Reprod.'), ('en', 'st_th_reprod', 'Plays'),
('es', 'st_th_min', 'Min.'), ('en', 'st_th_min', 'Min.'),
('es', 'st_err_artistas', 'Error al cargar artistas.'), ('en', 'st_err_artistas', 'Error loading artists.'),
('es', 'st_sin_evolucion_2', 'Sin datos de evolución aún.'), ('en', 'st_sin_evolucion_2', 'No evolution data yet.'),
('es', 'st_total_minutos', 'Total minutos'), ('en', 'st_total_minutos', 'Total minutes'),
('es', 'st_total_canciones', 'Canciones únicas'), ('en', 'st_total_canciones', 'Unique songs'),
('es', 'st_promedio', 'Min/día (30d)'), ('en', 'st_promedio', 'Min/day (30d)'),
('es', 'st_genero', 'Género favorito'), ('en', 'st_genero', 'Favorite genre'),
('es', 'st_top_canciones', 'Top 5 canciones más escuchadas'), ('en', 'st_top_canciones', 'Top 5 most played songs'),
('es', 'st_top_artistas', 'Top 5 Artistas'), ('en', 'st_top_artistas', 'Top 5 Artists'),

-- Descubrimiento Semanal
('es', 'ds_titulo', 'Descubrimiento Semanal'), ('en', 'ds_titulo', 'Weekly Discovery'),
('es', 'ds_subtitulo', 'Música nueva recomendada basada en tus géneros favoritos.'), ('en', 'ds_subtitulo', 'New recommended music based on your favorite genres.'),
('es', 'ds_cargando', 'Afinando los algoritmos de recomendación...'), ('en', 'ds_cargando', 'Tuning recommendation algorithms...'),
('es', 'ds_basado_gustos', 'Basado en tus gustos'), ('en', 'ds_basado_gustos', 'Based on your taste'),
('es', 'ds_basado_gustos_sub', 'Canciones de artistas que sigues'), ('en', 'ds_basado_gustos_sub', 'Songs from artists you follow'),
('es', 'ds_personalizando', 'Personalizando...'), ('en', 'ds_personalizando', 'Personalizing...'),
('es', 'ds_explorar', 'Explorar Playlists'), ('en', 'ds_explorar', 'Explore Playlists'),
('es', 'explorar_desc', 'Descubre la música que otros usuarios de Soundverse están compartiendo. Copia sus listas públicas directamente a tus playlists.'), ('en', 'explorar_desc', 'Discover music that other Soundverse users are sharing. Copy their public lists directly to your playlists.'),
('es', 'ds_buscar_ph', 'Buscar por nombre de playlist...'), ('en', 'ds_buscar_ph', 'Search by playlist name...'),
('es', 'ds_buscar', 'Buscar'), ('en', 'ds_buscar', 'Search'),
('es', 'ds_err_cargar', 'Error al cargar el descubrimiento semanal.'), ('en', 'ds_err_cargar', 'Error loading weekly discovery.'),
('es', 'ds_err_conexion', 'Error de conexión con el servidor.'), ('en', 'ds_err_conexion', 'Server connection error.'),
('es', 'ds_sigue_para_recom', 'Sigue a artistas desde el catálogo para ver recomendaciones personalizadas aquí.'), ('en', 'ds_sigue_para_recom', 'Follow artists from the catalog to see personalized recommendations here.'),
('es', 'ds_btn_escuchar', 'Escuchar'), ('en', 'ds_btn_escuchar', 'Listen'),
('es', 'ds_err_recom', 'Error al cargar recomendaciones.'), ('en', 'ds_err_recom', 'Error loading recommendations.'),
('es', 'ds_siguiendo', 'Siguiendo'), ('en', 'ds_siguiendo', 'Following'),
('es', 'ds_seguir', 'Seguir'), ('en', 'ds_seguir', 'Follow'),
('es', 'ds_vacio', 'No hay sugerencias en este momento. Sigue escuchando música para conocer tus gustos.'), ('en', 'ds_vacio', 'No suggestions at this time. Keep listening to music so we can learn your tastes.'),
('es', 'ds_escuchar', 'Escuchar Pista'), ('en', 'ds_escuchar', 'Listen to Track'),

-- Historial
('es', 'hi_desc', 'Aquí verás tu actividad reciente y las canciones que has reproducido (Para analíticas de monetización mayores a 30s).'), ('en', 'hi_desc', 'Here you will see your recent activity and played songs (For monetization analytics > 30s).'),
('es', 'hi_nota', 'Se muestran únicamente tus últimas <strong>50 reproducciones</strong> por rendimiento.'), ('en', 'hi_nota', 'Only your last <strong>50 plays</strong> are shown for performance.'),
('es', 'hi_th_cancion', '🎵 Canción'), ('en', 'hi_th_cancion', '🎵 Song'),
('es', 'hi_th_artista', '🎤 Artista'), ('en', 'hi_th_artista', '🎤 Artist'),
('es', 'hi_th_album', '💿 Álbum'), ('en', 'hi_th_album', '💿 Album'),
('es', 'hi_th_escuchado', '⏱️ Escuchado'), ('en', 'hi_th_escuchado', '⏱️ Listened'),
('es', 'hi_th_fecha', '📅 Fecha'), ('en', 'hi_th_fecha', '📅 Date'),
('es', 'hi_vacio', 'Aún no has escuchado ninguna canción. ¡Empieza ahora!'), ('en', 'hi_vacio', 'You haven\'t listened to any songs yet. Start now!'),
('es', 'hi_err_cargar', 'Error al cargar historial'), ('en', 'hi_err_cargar', 'Error loading history'),

-- Extras del Panel de Administrador / Generales
('es', 'adm_cat_th_duracion', 'Duración'), ('en', 'adm_cat_th_duracion', 'Duration'),
('es', 'adm_usr_th_acciones', 'Acciones'), ('en', 'adm_usr_th_acciones', 'Actions'),
('es', 'adm_cat_th_artista', 'Artista'), ('en', 'adm_cat_th_artista', 'Artist'),

-- Nuevos Artistas y Explorar (Agregados al BD)
('es', 'nav_artistas', 'Explorar Artistas'), ('en', 'nav_artistas', 'Explore Artists'),
('es', 'quick_artistas', 'Artistas'), ('en', 'quick_artistas', 'Artists'),
('es', 'ae_titulo', 'Descubre Nuevos Talentos'), ('en', 'ae_titulo', 'Discover New Talent'),
('es', 'ae_subtitulo', 'Explora la lista completa de artistas disponibles en Soundverse.'), ('en', 'ae_subtitulo', 'Explore the full list of artists available on Soundverse.'),
('es', 'ae_buscar_ph', 'Buscar artista por nombre...'), ('en', 'ae_buscar_ph', 'Search artist by name...'),
('es', 'ae_sin_resultados', 'No se encontraron artistas que coincidan con tu búsqueda.'), ('en', 'ae_sin_resultados', 'No artists were found that match your search.'),
('es', 'ae_ver_perfil', 'Ver Perfil'), ('en', 'ae_ver_perfil', 'View Profile'),
('es', 'pa_loading_perfil', 'Cargando perfil del artista...'), ('en', 'pa_loading_perfil', 'Loading artist profile...'),
('es', 'pa_verificado', 'Verificado'), ('en', 'pa_verificado', 'Verified'),
('es', 'pa_reproducciones', 'Reproducciones'), ('en', 'pa_reproducciones', 'Plays'),
('es', 'pa_albumes', 'Álbumes'), ('en', 'pa_albumes', 'Albums'),
('es', 'pa_canciones', 'Canciones'), ('en', 'pa_canciones', 'Songs'),
('es', 'pa_acerca_de', 'Acerca del artista'), ('en', 'pa_acerca_de', 'About the artist'),
('es', 'pa_destacadas_titulo', 'Canciones Destacadas'), ('en', 'pa_destacadas_titulo', 'Featured Songs'),
('es', 'pa_destacadas_sub', '— selección del artista'), ('en', 'pa_destacadas_sub', '— artist selection'),
('es', 'pa_discografia', 'Discografía'), ('en', 'pa_discografia', 'Discography'),
('es', 'pa_err_cargar', 'No se pudo cargar el perfil del artista. Intente nuevamente.'), ('en', 'pa_err_cargar', 'Could not load artist profile. Try again.');


-- ==============================================================================
-- DATOS PRÁCTICOS DE PRUEBA
-- ==============================================================================

-- Usuarios (Hash = 'Lp3.2026' para todos — consistencia en pruebas de equipo)
INSERT INTO `Usuario` (`FK_id_tipo`, `nombre_completo`, `correo`, `codigo_pais`, `clave_hash`, `estado_disponible`, `es_admin`) VALUES
(1, 'Usuario Free',            'free@test.com',          'MX', '$2y$10$MDuw3m.jxWbYA/rZypiQs.Z2i.emuobdF4BeAAqBOC1r/a/A0XcHu', 1, 0),
(2, 'Usuario Premium',         'premium@test.com',        'GT', '$2y$10$MDuw3m.jxWbYA/rZypiQs.Z2i.emuobdF4BeAAqBOC1r/a/A0XcHu', 1, 0),
(2, 'Administrador Soundverse','admin@soundverse.com',    'MX', '$2y$10$MDuw3m.jxWbYA/rZypiQs.Z2i.emuobdF4BeAAqBOC1r/a/A0XcHu', 1, 1),
(1, 'Rogelio',                 'rogelio@test.com',        'SV', '$2y$10$MDuw3m.jxWbYA/rZypiQs.Z2i.emuobdF4BeAAqBOC1r/a/A0XcHu', 1, 0);

-- Artistas
INSERT INTO `Artista` (`PK_id_artista`, `nombre_artistico`, `biografia`, `ruta_foto_perfil`, `verificado`, `estado_disponible`, `FK_id_usuario_gestor`) VALUES
(1, 'The Beatles', 'Banda británica de rock formada en Liverpool en 1960.', 'assets/img/artistas/ART_69cd4ebb99f44.jpg', 1, 1, NULL),
(2, 'Adele', 'Cantante y compositora británica.', 'assets/img/artistas/ART_69cd4eb15f8f8.jpg', 1, 1, NULL),
(3, 'Daft Punk', 'Dúo francés de música electrónica.', 'assets/img/artistas/ART_69cd4ea3685e3.jpg', 1, 1, NULL),
(4, 'Shakira', 'Cantautora, productora y bailarina colombiana, reconocida como la "Reina del pop latino". Con más de 75 millones de discos vendidos, fusiona ritmos latinos, pop y rock.', 'assets/img/artistas/ART_69d9c6ffaa8c2.jpg', 1, 1, 3),
(5, 'Bee Gees', 'Fue un grupo musical manés-australiano formado por los hermanos Barry, Robin y Maurice Gibb en Redcliffe, Queensland, Australia. El trío fue popular a finales de la década de 1960 y principios de la de 1970, con varios temas donde destaca el característico falsete del grupo.', 'assets/img/artistas/ART_69d9cd2fdaaa2.jpg', 0, 1, 3),
(6, 'Bon Jovi', 'Bon Jovi es una banda estadounidense de rock formada en 1983 en Nueva Jersey por su líder y vocalista, Jon Bon Jovi. La formación actual la completan el teclista David Bryan, el batería Tico Torres, el bajista Hugh McDonald, los guitarristas Phil X y John Shanks, y el percusionista Everett Bradley.', 'assets/img/artistas/ART_69d9ce807e2e9.jpg', 0, 1, 3);

-- Álbumes
INSERT INTO `Album` (`PK_id_album`, `FK_id_artista`, `titulo`, `fecha_lanzamiento`, `ruta_portada`, `discografica`) VALUES
(1, 1, 'Abbey Road',            '1969-09-26', 'assets/img/portadas/ALB_69cd4dd0e804a.jpeg', NULL),
(2, 1, 'Let It Be',             '1970-05-08', 'assets/img/portadas/ALB_69cd4dc4d03e1.jpeg', NULL),
(3, 2, '21',                    '2011-01-24', 'assets/img/portadas/ALB_69cd4db86f7df.jpeg', NULL),
(4, 3, 'Random Access Memories','2013-05-17', 'assets/img/portadas/ALB_69cd4d9eb8196.jpeg', NULL),
(5, 4, 'Fijación Oral, Vol. 1',  '2005-06-03', 'assets/img/portadas/ALB_69d9c7db5558a.webp', 'Epic Records'),
(6, 4, 'Donde Estan Los Ladrones','1998-09-29', 'assets/img/portadas/ALB_69d9cac8cf7ef.jpeg', 'Sony Music Latin'),
(7, 5, 'Spirits Having Flown',  '1979-01-01', 'assets/img/portadas/ALB_69d9cd5d7dccb.jpg', 'RSO Records'),
(8, 6, 'Slippery When Wet',     '1986-08-18', 'assets/img/portadas/ALB_69d9cece085ab.jpeg', 'Mercury Records'),
(9, 5, 'Children of the World', '1976-09-01', 'assets/img/portadas/ALB_69d9cfaee5486.jpg', 'Epic Records');

-- Canciones
INSERT INTO `Cancion` (`PK_id_cancion`, `FK_id_album`, `FK_id_genero`, `titulo`, `numero_pista`, `duracion_segundos`, `ruta_archivo_audio`, `letra_sincronizada`, `contador_reproducciones`) VALUES
(1, 1, 1, 'Come Together', 1, 259, 'assets/musica/TRACK_69cd71c9785ad.mp3', '[00:10.00] Here come old flat top\r\n[00:14.00] He come groovin up slowly\r\n[00:18.00] He got joo joo eyeball\r\n[00:21.00] He one holy roller\r\n[00:24.00] He got hair down to his knee\r\n[00:28.00] Got to be a joker\r\n[00:30.00] He just do what he please\r\n[00:34.00] Come together, right now\r\n[00:37.00] Over me\r\n[00:40.00] He bag production\r\n[00:43.00] He got walrus gumboot\r\n[00:46.00] He got Ono sideboard\r\n[00:49.00] He one spinal cracker\r\n[00:52.00] He got feet down below his knee\r\n[00:55.00] Hold you in his armchair\r\n[00:58.00] You can feel his disease\r\n[01:01.00] Come together, right now\r\n[01:04.00] Over me', 0),
(2, 1, 1, 'Something', 2, 182, 'assets/musica/TRACK_69cd737741d57.mp3', NULL, 1),
(3, 2, 1, 'Let It Be', 1, 243, 'assets/musica/TRACK_69cd738735e05.mp3', '[00:05.00] When I find myself in times of trouble\r\n[00:11.00] Mother Mary comes to me\r\n[00:15.00] Speaking words of wisdom\r\n[00:18.00] Let it be\r\n[00:21.00] And in my hour of darkness\r\n[00:25.00] She is standing right in front of me\r\n[00:30.00] Speaking words of wisdom\r\n[00:33.00] Let it be\r\n[00:36.00] Let it be, let it be\r\n[00:40.00] Let it be, let it be\r\n[00:43.00] Whisper words of wisdom\r\n[00:46.00] Let it be\r\n[00:49.00] And when the brokenhearted people\r\n[00:53.00] Living in the world agree\r\n[00:57.00] There will be an answer\r\n[01:00.00] Let it be\r\n[01:03.00] For though they may be parted\r\n[01:06.00] There is still a chance that they will see\r\n[01:10.00] There will be an answer\r\n[01:13.00] Let it be\r\n[01:16.00] Let it be, let it be\r\n[01:19.00] Let it be, let it be\r\n[01:22.00] Yeah, there will be an answer\r\n[01:25.00] Let it be\r\n[01:28.00] Let it be, let it be\r\n[01:31.00] Let it be, let it be\r\n[01:34.00] Whisper words of wisdom\r\n[01:37.00] Let it be\r\n[01:53.00] And when the night is cloudy\r\n[01:56.00] There is still a light that shines on me\r\n[02:00.00] Shine on till tomorrow\r\n[02:03.00] Let it be\r\n[02:06.00] I wake up to the sound of music\r\n[02:10.00] Mother Mary comes to me\r\n[02:13.00] Speaking words of wisdom\r\n[02:16.00] Let it be\r\n[02:19.00] Let it be, let it be\r\n[02:22.00] Let it be, yeah, let it be\r\n[02:25.00] Oh, there will be an answer\r\n[02:28.00] Let it be\r\n[02:31.00] Let it be, let it be\r\n[02:34.00] Let it be, yeah, let it be\r\n[02:37.00] Oh, there will be an answer\r\n[02:40.00] Let it be\r\n[02:43.00] Let it be, let it be\r\n[02:46.00] Let it be, yeah, let it be\r\n[02:49.00] Whisper words of wisdom\r\n[02:52.00] Let it be', 2),
(4, 3, 2, 'Rolling in the Deep', 1, 228, 'assets/musica/TRACK_69cc79e25b63b.mp3', NULL, 2),
(5, 3, 2, 'Someone Like You', 2, 285, 'assets/musica/TRACK_69cd73a37f1a9.mp3', NULL, 0),
(6, 4, 4, 'Get Lucky', 1, 369, 'assets/musica/TRACK_69cd73afa7578.mp3', NULL, 2),
(7, 5, 1, 'Escondite Ingles', 1, 189, 'assets/musica/TRACK_69d9c8d08fa5d.mp3', '[00:12.00] Bienvenido, has llegado\r\n[00:14.00] En el momento justo\r\n[00:16.00] Y al lugar indicado\r\n\r\n[00:18.00] No todo lo rico engorda\r\n[00:21.00] No todo lo bueno es pecado\r\n\r\n[00:24.00] Yo seré tus deseos hechos piernas\r\n[00:28.00] Una idea recurrente de fascinación\r\n[00:35.00] Yo seré un complot para tu mente\r\n[00:40.00] El objeto y la causa de tu perdición\r\n[00:45.00] Y tu buena suerte\r\n\r\n[00:47.00] Cuenta del uno al diez\r\n[00:49.00] Y me escondo donde puedas verme\r\n[00:52.00] Es mi forma de jugar al escondite inglés\r\n\r\n[00:58.00] Bésame de una vez\r\n[01:01.00] Y te amarro a mi sofá burgués\r\n[01:04.00] Es mi forma de jugar al escondite\r\n[01:07.00] Es mi forma de jugar al escondite\r\n[01:10.00] Es mi forma de jugar al escondite inglés\r\n\r\n[01:24.00] No me juzgues, no soy de esas\r\n[01:26.00] Solo porque tengo una mente traviesa\r\n[01:31.00] No todo lo rico engorda\r\n[01:34.00] Y no él que peca empata si reza\r\n\r\n[01:36.00] Yo seré tus deseos hechos piernas\r\n[01:41.00] La que dicte una sentencia a tu imaginación\r\n[01:48.00] Yo seré un complot para tu mente\r\n[01:53.00] El objeto y la causa de tu perdición\r\n[01:58.00] Y tu buena suerte\r\n\r\n[02:00.00] Cuenta del uno al diez\r\n[02:02.00] Y me escondo donde puedas verme\r\n[02:05.00] Es mi forma de jugar el escondite inglés\r\n\r\n[02:11.00] Bésame de una vez\r\n[02:13.00] Y te amarro a mi sofá burgués\r\n[02:17.00] Es mi forma de jugar al escondite\r\n[02:19.00] Es mi forma de jugar al escondite\r\n[02:22.00] Es mi forma de jugar al escondite inglés\r\n\r\n[02:37.00] Cuenta del uno al diez\r\n[02:40.00] Y me escondo donde puedas verme\r\n\r\n[02:49.00] Cuenta del uno al diez\r\n[02:51.00] Y me escondo donde puedas verme\r\n\r\n[02:55.00] Uno, dos. Uno, dos. Uno, dos.\r\n[02:59.00] Uno,dos. Dos por cinco, diez.', 7),
(8, 5, 7, 'La Tortura', 2, 214, 'assets/musica/TRACK_69d9c9978c83e.mp3', 'Ay, panita mía, guárdate la poesía\r\nGuárdate la alegría pa\' ti\r\n\r\n¡Ven, dame, dámelo!\r\n\r\nNo pido que todos los días sean de Sol\r\nNo pido que todos los viernes sean de fiesta\r\nY tampoco te pido que vuelvas rogando perdón\r\nSi lloras con dos ojos secos y hablando de ella\r\n\r\nAy, amor, me duele tanto\r\nMe duele tanto\r\nQue te fueras sin decir adónde\r\nAy, amor, fue una tortura perderte\r\n\r\nYo sé que no he sido un santo\r\nPero lo puedo arreglar, amor\r\nNo solo de pan vive el hombre\r\nY no de excusas vivo yo\r\n\r\nSolo de errores se aprende\r\nY hoy sé que es tuyo mi corazón\r\nMejor te guardas todo eso\r\nA otro perro con ese hueso\r\nY nos decimos adiós\r\n\r\nEsto es otra vez y esto es otra vez\r\nEsto es otra vez, esto es otra vez\r\n\r\nNo puedo pedir que el invierno perdone a un rosal\r\nNo puedo pedir a los olmos que entreguen peras\r\nNo puedo pedirle lo eterno a un simple mortal\r\nY andar arrojando a los cerdos miles de perlas\r\n\r\nAy, amor, me duele tanto\r\nMe duele tanto\r\nQue no creas más en mis promesas\r\nAy, amor, es una tortura perderte\r\n\r\nYo sé que no he sido un santo\r\nPero lo puedo arreglar, amor\r\nNo solo de pan vive el hombre\r\nY no de excusas vivo yo\r\n\r\nSolo de errores se aprende\r\nY hoy sé que es tuyo mi corazón\r\nMejor te guardas todo eso\r\nA otro perro con ese hueso\r\nY nos decimos adiós\r\n\r\n¡Ven, dame, dámelo!\r\n\r\nNo te bajes, no te bajes\r\nOye, negrita, mira, no te rajes\r\nDe lunes a viernes, tienes mi amor\r\nDéjame el sábado a mí, que es mejor\r\n\r\nOye, mi negra, no me castigues más\r\nPorque allá afuera, sin ti no tengo paz\r\nYo solo soy un hombre arrepentido\r\nSoy como el ave que vuelve a su nido\r\n\r\nYo sé que no he sido un santo\r\nY es que no estoy hecho de cartón\r\nNo solo de pan vive el hombre\r\nY no de excusas vivo yo\r\n\r\nSolo de errores se aprende\r\nY hoy sé que es tuyo mi corazón\r\nAy, ay\r\nAy, ay, ay\r\n\r\nAy, todo lo que he hecho por ti\r\nFue una tortura perderte\r\nY me duele tanto que sea así\r\nSigues llorando perdón\r\nYo, yo no voy a llorar hoy por ti', 2),
(9, 6, 1, 'Dónde Están los Ladrones?', 3, 194, 'assets/musica/TRACK_69d9cb5965ed7.mp3', 'Los han visto por ahí\r\nLos han visto en los tejados\r\nDando vueltas en París\r\nCondenando en los juzgados\r\n\r\nCon la nariz empolvada\r\nDe corbata o de blue jeans\r\nLos han visto en las portadas todas\r\nSin más nada que decir\r\n\r\n¿Dónde están los ladrones?\r\n¿Dónde está el asesino?\r\nQuizá allá revolcándose\r\nEn el patio del vecino\r\n\r\n¿Y qué pasa si son ellos?\r\n¿Y qué pasa si soy yo?\r\nEl que toca esta guitarra\r\nO la que canta esta canción\r\nLa que canta esta canción\r\n\r\nLos han visto de rodillas\r\nSentados o de cuclillas\r\nParados dando lecciones\r\nEn todas las posiciones\r\n\r\nPredicando en las iglesias\r\nHasta ofreciendo conciertos\r\nLos han visto en los cócteles todos\r\nRepartiendo ministerios\r\n\r\n¿Dónde están los ladrones?\r\n¿Dónde está el asesino?\r\nQuizá allá revolcándose\r\nEn el patio del vecino\r\n\r\n¿Y qué pasa si son ellos?\r\n¿Y qué pasa si soy yo?\r\nEl que toca esta guitarra\r\nO la que canta esta canción\r\nLa que canta esta canción', 1),
(10, 6, 2, 'Octavo Día', 2, 195, 'assets/musica/TRACK_69d9cbe072afb.mp3', 'El octavo día, Dios, después de tanto trabajar\r\nPara liberar tensiones luego ya de revisar\r\nDijo: Todo está muy bien, es hora de descansar\r\nY se fue a dar un paseo por el espacio sideral\r\n\r\nQuién se iba a imaginar que el mismo Dios al regresar\r\nIba a encontrarlo todo en un desorden infernal\r\nY que se iba a convertir en un desempleado más\r\nDe la tasa que anualmente está creciendo sin parar\r\n\r\nDesde ese entonces, hay quienes lo han visto\r\nSolo en las calles, transitar\r\nAnda esperando paciente por alguien\r\nCon quien, al menos tranquilo, pueda conversar\r\n\r\nMientras tanto, este mundo gira y gira\r\nSin poderlo detener\r\nY aquí abajo, unos cuantos nos manejan\r\nComo fichas de ajedrez\r\nNo soy la clase de idiota que se deja convencer\r\nPero digo la verdad\r\nY hasta un ciego lo puede ver\r\n\r\nSi a falta de ocupación o de excesiva soledad\r\nDios no resistiera más y se marchara a otro lugar\r\nSería nuestra perdición, no habría otro remedio más\r\nQue adorar a Michael Jackson, a Bill Clinton o a Tarzán\r\n\r\nEs más difícil ser rey sin corona\r\nQue una persona más normal\r\nPobre de Dios que no sale en revistas\r\nNo es modelo ni artista, o de familia real\r\n\r\nMientras tanto, este mundo gira y gira\r\nSin poderlo detener\r\nY aquí abajo, unos cuantos nos manejan\r\nComo fichas de ajedrez\r\nNo soy la clase de idiota que se deja convencer\r\nPero digo la verdad\r\nY hasta un ciego lo puede ver\r\n\r\nMientras tanto, este mundo gira y gira\r\nSin poderlo detener\r\nY aquí abajo, unos cuantos nos manejan\r\nComo fichas de ajedrez\r\nNo soy la clase de idiota que se deja convencer\r\nPero digo la verdad\r\nY hasta un ciego lo puede ver', 0),
(11, 7, 9, 'Tragedy', 1, 210, 'assets/musica/TRACK_69d9cd945559d.mp3', '[00:28.00] Here I lie, in a lost and lonely part of town\n[00:36.00] Held in time, in a world of tears I slowly drown\n[00:44.00] Going home, I just cant make it all alone\n[00:50.00] I really should be holding you, holding you\n[00:56.00] Loving you, loving you\n\n[01:04.00] Tragedy\n[01:06.00] When the feelings gone and you cant go on, its tragedy\n[01:11.00] When the morning cries and you dont know why, its hard to bear\n[01:14.00] With no one to love you, youre going nowhere\n[01:21.00] Tragedy\n[01:22.50] When you lose control and you got no soul, its tragedy\n[01:27.00] When the morning cries and you dont know why, its hard to bear\n[01:30.50] With no one beside you, youre going nowhere\n\n[02:06.00] Night and day, theres a burning down inside of me\n[02:14.00] Oh, burning love with a yearning that wont let me be\n[02:22.00] Down I go and I just cant take it all alone\n[02:28.10] I really should be holding you, holding you\n[02:34.00] Loving you, loving you\n\n[02:41.90] Tragedy\n[02:44.00] When the feelings gone and you cant go on, its tragedy\n[02:48.50] When the morning cries and you dont know why, its hard to bear\n[02:52.50] With no one to love you, youre going nowhere\n[02:59.00] Tragedy\n[03:00.80] When you lose control, and you got no soul, its tragedy\n[03:04.80] When the morning cries and you dont know why, its hard to bear\n[03:08.80] With no one beside you, youre going nowhere\n\n[03:37.50] Tragedy\n[03:39.00] When the feelings gone and you cant go on, its tragedy\n[03:43.30] When the morning cries and you dont know why, its hard to bear\n[03:47.00] With no one to love you, youre going nowhere\n[03:53.90] Tragedy\n[03:55.00] When you lose control and you got no soul, its tragedy\n[03:58.80] When the morning cries and you dont know why, its hard to bear\n[04:03.00] With no one beside you, youre going nowhere\n\n[04:11.00] Tragedy\n[04:13.10] When the feelings gone and you cant go on, its tragedy\n[04:17.00] When the morning cries and you dont know why, its hard to bear\n[04:21.00] With no one to love you, youre going nowhere\n[04:27.50] Tragedy\n[04:29.00] When you lose control and you got no soul, its tragedy\n[04:33.80] When the morning cries and you dont know why, its hard to bear\n[04:37.00] With no one beside you, youre going nowhere', 1),
(12, 8, 1, 'Livin\' On A Prayer', 1, 298, 'assets/musica/TRACK_69d9cf0a496b5.mp3', '(Once upon a time, not so long ago)\r\n\r\nTommy used to work on the docks\r\nUnion\'s been on strike, he\'s down on his luck\r\nIt\'s tough, so tough\r\nGina works the diner all day\r\nWorking for her man, she brings home her pay\r\nFor love, for love\r\n\r\nShe says: We\'ve got to hold on to what we\'ve got\r\nIt doesn\'t make a difference if we make it or not\r\nWe\'ve got each other and that\'s a lot\r\nFor love, we\'ll give it a shot\r\n\r\nWhoa, we\'re halfway there\r\nOh-oh, living on a prayer\r\nTake my hand, we\'ll make it, I swear\r\nOh-oh, living on a prayer\r\n\r\nTommy\'s got his six string in hock\r\nNow he\'s holding in what he used to make it talk\r\nSo tough, it\'s tough\r\nGina dreams of running away\r\nWhen she cries in the night, Tommy whispers\r\nBaby, it\'s okay, someday\r\n\r\nWe\'ve got to hold on to what we\'ve got\r\nIt doesn\'t make a difference if we make it or not\r\nWe\'ve got each other and that\'s a lot\r\nFor love, we\'ll give it a shot\r\n\r\nWhoa, we\'re halfway there\r\nOh-oh, living on a prayer\r\nTake my hand, we\'ll make it, I swear\r\nOh-oh, living on a prayer\r\nLiving on a prayer\r\n\r\nOoh, we\'ve got to hold on, ready or not\r\nYou live for the fight when that\'s all that you\'ve got\r\n\r\nWhoa, we\'re halfway there\r\nOh-oh, living on a prayer\r\nTake my hand and we\'ll make it, I swear\r\nOh-oh, living on a prayer\r\n\r\nWhoa, we\'re halfway there\r\nOh-oh, living on a prayer\r\nTake my hand and we\'ll make it, I swear\r\nOh-oh, living on a prayer\r\n\r\nWhoa, we\'re halfway there\r\nOh-oh, living on a prayer\r\nTake my hand', 0),
(13, 9, 9, 'You Should Be Dancing', 1, 310, 'assets/musica/TRACK_69d9cff981a40.mp3', 'My baby moves at midnight\r\nGoes right on till the dawn\r\nMy woman takes me higher\r\nMy woman keeps me warm\r\n\r\nWhat you doin\' on your back\r\nWhat you doin\'on your back?\r\nYou should be dancing, yeah\r\nDancing, yeah\r\n\r\nShe\'s juicy and she\'s trouble\r\nShe gets it to me good\r\nMy woman gives me power\r\nGoes right down to my blood\r\n\r\nWhat you doin\' on your back\r\nWhat you doin\'on your back?\r\nYou should be dancing, yeah\r\nDancing, yeah\r\n\r\nMy baby moves at midnight\r\nGoes right on till the dawn\r\nMy woman takes me higher\r\nMy woman keeps me warm\r\n\r\nWhat you doin\' on your back\r\nWhat you doin\'on your back?\r\nYou should be dancing, yeah\r\nDancing, yeah\r\nYou should be dancing, yeah\r\nYou should be dancing, yeah\r\nYou should be dancing, yeah\r\nYou should be dancing, yeah\r\nYou should be dancing, yeah', 3);

-- Playlists
INSERT INTO `Playlist` (`FK_id_usuario`, `nombre_playlist`, `visibilidad`) VALUES
(1, 'Mis favoritas', 'Publica'),
(2, 'Para entrenar', 'Privada'),
(1, 'mario',         'Publica');

-- Detalle de playlists
INSERT INTO `Detalle_Playlist` (`FK_id_playlist`, `FK_id_cancion`, `orden_reproduccion`) VALUES
(1, 1, 1),
(1, 4, 2),
(2, 2, 1),
(2, 6, 2),
(3, 1, 1),
(3, 2, 2),
(3, 3, 3),
(3, 4, 4),
(3, 5, 5),
(3, 6, 6);

-- Historial de reproducciones
INSERT INTO `Historial_Reproduccion` (`FK_id_usuario`, `FK_id_cancion`, `segundos_escuchados`, `es_valida_regalia`) VALUES
(1, 1,  45,  1), 
(1, 2, 120,  1), 
(1, 3,  30,  1), 
(2, 4, 200,  1), 
(2, 5,  60,  1), 
(2, 6,   5,  0); 

-- Seguimientos de artistas
INSERT INTO `Seguimiento_Artista` (`FK_id_usuario`, `FK_id_artista`) VALUES
(1, 1), (1, 2),
(2, 2), (2, 3);

-- ==============================================================================
-- RESTAURAR LA SEGURIDAD DE LA BASE DE DATOS
-- ==============================================================================
SET FOREIGN_KEY_CHECKS = 1;