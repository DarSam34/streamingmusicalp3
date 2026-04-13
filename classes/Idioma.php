<?php
/**
 * CLASE: Idioma
 * PROPÓSITO: Gestionar el sistema multilenguaje del sistema.
 * CUMPLE: RF-01 y Norma de Multilenguaje Interno.
 * Las traducciones residen en la tabla Idioma_Traduccion de la BD.
 * No se permite dependencia de plugins externos.
 */
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/Utilidades.php';

class Idioma {

    /**
     * Carga todas las traducciones para un idioma dado.
     * @param string $codigo_idioma  'es' para Español, 'en' para Inglés.
     * @return array Diccionario ['clave_etiqueta' => 'texto_traducido', ...]
     */
    public function cargarTraducciones($codigo_idioma = 'es') {
        $db = null;
        try {
            $db = (new Conexion())->conectar();
            $sql = "SELECT etiqueta_llave, texto_traduccion 
                    FROM Idioma_Traduccion 
                    WHERE codigo_iso = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$codigo_idioma]);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convertir el array de filas en un diccionario plano key=>value
            $diccionario = [];
            foreach ($filas as $fila) {
                $diccionario[$fila['etiqueta_llave']] = $fila['texto_traduccion'];
            }
            return $diccionario;
        } catch (PDOException $e) {
            Utilidades::registrarLog('idioma_error', 'cargarTraducciones(): ' . $e->getMessage());
            return []; // Si falla, la interfaz se mantiene en el idioma actual
        } finally {
            $db = null;
        }
    }

    /**
     * Verifica que las traducciones base existen en la BD.
     * Si no, las inserta (inicialización automática).
     */
    public function inicializarTraducciones() {
        $db = null;
        try {
            $db = (new Conexion())->conectar();
            $stmt = $db->prepare("SELECT COUNT(*) FROM Idioma_Traduccion");
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) return; // Ya existen traducciones

            $traducciones = [
                // Español
                ['es', 'login_titulo',             'Iniciar Sesión'],
                ['es', 'login_correo',             'Correo Electrónico'],
                ['es', 'login_contrasena',         'Contraseña'],
                ['es', 'login_btn',                'Entrar'],
                ['es', 'login_sin_cuenta',         '¿No tienes cuenta?'],
                ['es', 'registro_titulo',          'Crear Cuenta Gratis'],
                ['es', 'registro_nombre',          'Nombre Completo'],
                ['es', 'registro_correo',          'Correo Electrónico'],
                ['es', 'registro_contrasena',      'Contraseña'],
                ['es', 'registro_btn',             'Registrarme'],
                ['es', 'registro_ya_cuenta',       '¿Ya tienes cuenta?'],
                ['es', 'nav_catalogo',             'Catálogo'],
                ['es', 'nav_playlists',            'Mis Playlists'],
                ['es', 'nav_estadisticas',         'Mis Estadísticas'],
                ['es', 'nav_descubrimiento',       'Descubrimiento Semanal'],
                ['es', 'nav_planes',               'Planes / Premium'],
                ['es', 'nav_perfil',               'Mi Perfil'],
                ['es', 'nav_historial',            'Mi Historial'],
                ['es', 'nav_salir',                'Cerrar Sesión'],
                ['es', 'slogan',                   'Tu universo musical conectado'],
                ['es', 'tab_iniciar_sesion',       'Iniciar Sesión'],
                ['es', 'tab_crear_cuenta',         'Crear Cuenta'],
                ['es', 'registro_contrasena2',     'Confirmar Contraseña'],
                ['es', 'nav_dashboard',            'Dashboard'],
                ['es', 'nav_usuarios',             'Gestión de Usuarios'],
                ['es', 'nav_artistas',             'Artistas'],
                ['es', 'nav_albumes',              'Álbumes'],
                ['es', 'nav_generos',              'Géneros'],
                ['es', 'dash_titulo',              'Dashboard Global — KPIs'],
                ['es', 'dash_subtitulo',           'Estadísticas en tiempo real'],
                ['es', 'dash_usuarios_totales',    'Usuarios Totales'],
                ['es', 'dash_activos_hoy',         'Activos Hoy (DAU)'],
                ['es', 'dash_activos_mes',         'Activos Mes (MAU)'],
                ['es', 'dash_reproducciones_hoy',  'Reproducciones Hoy'],
                ['es', 'dash_premium',             'Suscripciones Premium'],
                ['es', 'dash_conversion',          'Tasa Conversión Free→Pro'],
                ['es', 'dash_retencion',           'Tasa Retención (30d)'],
                ['es', 'dash_ingresos',            'Ingresos Premium'],
                ['es', 'dash_trending',            'Canciones Trending (Top 10)'],
                ['es', 'dash_actualizar',          'Actualizar'],
                ['es', 'welcome_user',             'Bienvenido'],
                ['es', 'welcome_msg',              'Selecciona una opción del menú para comenzar a escuchar música.'],
                ['es', 'player_no_playing',        'No reproduciendo'],
                ['es', 'admin_panel',              'Panel de Administración'],
                ['es', 'admin_login',              'Acceso Administrativo'],
                ['es', 'pl_titulo',                'Mis Playlists'],
                ['es', 'pl_nueva',                 'Nueva Playlist'],
                ['es', 'pl_cargando',              'Cargando playlists...'],
                ['es', 'pl_nombre',                'Nombre'],
                ['es', 'pl_visibilidad',           'Visibilidad'],
                ['es', 'pl_publica',               'Pública'],
                ['es', 'pl_privada',               'Privada'],
                ['es', 'pl_cancelar',              'Cancelar'],
                ['es', 'pl_guardar',               'Guardar'],
                ['es', 'pl_ver',                   'Ver'],
                ['es', 'pl_eliminar',              'Eliminar'],
                ['es', 'pl_vacia',                 'No tienes playlists. ¡Crea una!'],
                ['es', 'st_titulo',                'Mis Estadísticas'],
                ['es', 'st_subtitulo',             'Resumen de tu actividad en Soundverse.'],
                ['es', 'st_resumen',               'Resumen de escucha'],
                ['es', 'st_total_minutos',         'Total minutos escuchados'],
                ['es', 'st_total_canciones',       'Canciones reproducidas'],
                ['es', 'st_promedio',              'Promedio diario (30 días)'],
                ['es', 'st_genero',                'Género favorito'],
                ['es', 'st_top_canciones',         'Top 5 canciones más escuchadas'],
                ['es', 'st_top_artistas',          'Top 5 artistas más escuchados'],
                ['es', 'ds_titulo',                'Descubrimiento Semanal'],
                ['es', 'ds_subtitulo',             'Música nueva recomendada basada en tus géneros favoritos.'],
                ['es', 'ds_vacio',                 'No hay sugerencias en este momento. Sigue escuchando música para conocer tus gustos.'],
                ['es', 'ds_escuchar',              'Escuchar Pista'],
                ['es', 'sub_titulo',               'Suscripción Premium'],
                ['es', 'sub_subtitulo',            'Descubre la música sin límites y apoya a tus artistas favoritos.'],
                ['es', 'sub_miembro',              '¡Eres miembro Premium!'],
                ['es', 'sub_beneficios',           'Estás disfrutando de Soundverse con todos los beneficios desbloqueados.'],
                ['es', 'sub_historial',            'Historial de Facturación'],
                ['es', 'sub_no_facturas',          'No tienes facturas generadas.'],
                ['es', 'sub_pasate',               'Pásate a Premium'],
                ['es', 'sub_mejorar',              'Mejorar Cuenta Ahora'],
                ['es', 'reg_fortaleza',            'Escribe tu contraseña'],
                ['es', 'reg_minimo8',              'Mínimo 8 caracteres'],
                ['es', 'reg_mayuscula',            'Al menos una mayúscula'],
                ['es', 'reg_numero',               'Al menos un número'],
                // === PERFIL USUARIO (ES) ===
                ['es', 'prf_subtitulo',            'Gestiona tu información personal y seguridad de tu cuenta.'],
                ['es', 'prf_datos',                'Datos Personales'],
                ['es', 'prf_nivel',                'Nivel de Suscripción'],
                ['es', 'prf_mejorar',              'Para mejorar tu cuenta, ve a la sección Premium.'],
                ['es', 'prf_seguridad',            'Seguridad'],
                ['es', 'prf_pass_actual',          'Contraseña Actual'],
                ['es', 'prf_pass_nueva',           'Nueva Contraseña'],
                ['es', 'prf_pass_hint',            'Debe contener 8 caracteres, números y mayúsculas.'],
                ['es', 'prf_pass_btn',             'Actualizar Contraseña'],
                // English
                ['en', 'login_titulo',             'Sign In'],
                ['en', 'login_correo',             'Email Address'],
                ['en', 'login_contrasena',         'Password'],
                ['en', 'login_btn',                'Login'],
                ['en', 'login_sin_cuenta',         "Don't have an account?"],
                ['en', 'registro_titulo',          'Create Free Account'],
                ['en', 'registro_nombre',          'Full Name'],
                ['en', 'registro_correo',          'Email Address'],
                ['en', 'registro_contrasena',      'Password'],
                ['en', 'registro_btn',             'Sign Up'],
                ['en', 'registro_ya_cuenta',       'Already have an account?'],
                ['en', 'nav_catalogo',             'Catalog'],
                ['en', 'nav_playlists',            'My Playlists'],
                ['en', 'nav_estadisticas',         'My Statistics'],
                ['en', 'nav_descubrimiento',       'Weekly Discovery'],
                ['en', 'nav_planes',               'Plans / Premium'],
                ['en', 'nav_perfil',               'My Profile'],
                ['en', 'nav_historial',            'My History'],
                ['en', 'nav_salir',                'Sign Out'],
                ['en', 'slogan',                   'Your connected musical universe'],
                ['en', 'tab_iniciar_sesion',       'Sign In'],
                ['en', 'tab_crear_cuenta',         'Create Account'],
                ['en', 'registro_contrasena2',     'Confirm Password'],
                ['en', 'nav_dashboard',            'Dashboard'],
                ['en', 'nav_usuarios',             'User Management'],
                ['en', 'nav_artistas',             'Artists'],
                ['en', 'nav_albumes',              'Albums'],
                ['en', 'nav_generos',              'Genres'],
                ['en', 'dash_titulo',              'Global Dashboard — KPIs'],
                ['en', 'dash_subtitulo',           'Real-time stats'],
                ['en', 'dash_usuarios_totales',    'Total Users'],
                ['en', 'dash_activos_hoy',         'Today Active (DAU)'],
                ['en', 'dash_activos_mes',         'Monthly Active (MAU)'],
                ['en', 'dash_reproducciones_hoy',  'Plays Today'],
                ['en', 'dash_premium',             'Premium Subscriptions'],
                ['en', 'dash_conversion',          'Free→Pro Conversion Rate'],
                ['en', 'dash_retencion',           'Retention Rate (30d)'],
                ['en', 'dash_ingresos',            'Premium Revenue'],
                ['en', 'dash_trending',            'Trending Songs (Top 10)'],
                ['en', 'dash_actualizar',          'Update'],
                ['en', 'welcome_user',             'Welcome'],
                ['en', 'welcome_msg',              'Select an option from the menu to start listening to music.'],
                ['en', 'player_no_playing',        'Not playing'],
                ['en', 'admin_panel',              'Administration Panel'],
                ['en', 'admin_login',              'Administrative Access'],
                ['en', 'pl_titulo',                'My Playlists'],
                ['en', 'pl_nueva',                 'New Playlist'],
                ['en', 'pl_cargando',              'Loading playlists...'],
                ['en', 'pl_nombre',                'Name'],
                ['en', 'pl_visibilidad',           'Visibility'],
                ['en', 'pl_publica',               'Public'],
                ['en', 'pl_privada',               'Private'],
                ['en', 'pl_cancelar',              'Cancel'],
                ['en', 'pl_guardar',               'Save'],
                ['en', 'pl_ver',                   'View'],
                ['en', 'pl_eliminar',              'Delete'],
                ['en', 'pl_vacia',                 "You don't have playlists. Create one!"],
                ['en', 'st_titulo',                'My Statistics'],
                ['en', 'st_subtitulo',             'Summary of your activity on Soundverse.'],
                ['en', 'st_resumen',               'Listening Summary'],
                ['en', 'st_total_minutos',         'Total minutes listened'],
                ['en', 'st_total_canciones',       'Songs played'],
                ['en', 'st_promedio',              'Daily average (30 days)'],
                ['en', 'st_genero',                'Favorite genre'],
                ['en', 'st_top_canciones',         'Top 5 most played songs'],
                ['en', 'st_top_artistas',          'Top 5 most played artists'],
                ['en', 'ds_titulo',                'Weekly Discovery'],
                ['en', 'ds_subtitulo',             'New recommended music based on your favorite genres.'],
                ['en', 'ds_vacio',                 'No suggestions at this time. Keep listening to music to learn your tastes.'],
                ['en', 'ds_escuchar',              'Listen Track'],
                ['en', 'sub_titulo',               'Premium Subscription'],
                ['en', 'sub_subtitulo',            'Discover music without limits and support your favorite artists.'],
                ['en', 'sub_miembro',              "You're a Premium member!"],
                ['en', 'sub_beneficios',           'You are enjoying Soundverse with all benefits unlocked.'],
                ['en', 'sub_historial',            'Billing History'],
                ['en', 'sub_no_facturas',          'No invoices generated.'],
                ['en', 'sub_pasate',               'Go Premium'],
                ['en', 'sub_mejorar',              'Upgrade Account Now'],
                ['en', 'reg_fortaleza',            'Type your password'],
                ['en', 'reg_minimo8',              'Minimum 8 characters'],
                ['en', 'reg_mayuscula',            'At least one uppercase'],
                ['en', 'reg_numero',               'At least one number'],
                // === PERFIL USUARIO (EN) ===
                ['en', 'prf_subtitulo',            'Manage your personal information and account security.'],
                ['en', 'prf_datos',                'Personal Data'],
                ['en', 'prf_nivel',                'Subscription Level'],
                ['en', 'prf_mejorar',              'To upgrade your account, go to the Premium section.'],
                ['en', 'prf_seguridad',            'Security'],
                ['en', 'prf_pass_actual',          'Current Password'],
                ['en', 'prf_pass_nueva',           'New Password'],
                ['en', 'prf_pass_hint',            'Must contain 8 characters, numbers and uppercase letters.'],
                ['en', 'prf_pass_btn',             'Update Password'],
                // === ADMIN INTERNAL VIEWS (ES) ===
                ['es', 'adm_bienvenida',           'Bienvenido a Soundverse'],
                ['es', 'adm_bienvenida_desc',      'El sistema está funcionando correctamente. Selecciona un módulo en el menú lateral izquierdo para comenzar a administrar la plataforma.'],
                ['es', 'adm_usr_titulo',            'Administración de Usuarios'],
                ['es', 'adm_usr_subtitulo',         'Módulo para el registro, edición y control de acceso de la plataforma Soundverse.'],
                ['es', 'adm_usr_nuevo',             'Nuevo Registro'],
                ['es', 'adm_usr_tipo',              'Tipo de Suscripción'],
                ['es', 'adm_usr_admin',             'Otorgar privilegios de Administrador'],
                ['es', 'adm_usr_pass',              'Contraseña Temporal'],
                ['es', 'adm_usr_pass_hint',         'Mínimo 8 caracteres. En edición, déjalo vacío para no cambiar la clave.'],
                ['es', 'adm_usr_registrar',         'Registrar Usuario'],
                ['es', 'adm_usr_cancelar',          'Cancelar Edición'],
                ['es', 'adm_usr_lista',             'Lista de Usuarios'],
                ['es', 'adm_usr_th_usuario',        'Usuario'],
                ['es', 'adm_usr_th_correo',         'Correo'],
                ['es', 'adm_usr_th_plan',           'Plan'],
                ['es', 'adm_usr_th_acciones',       'Acciones'],
                ['es', 'adm_art_titulo',            'Gestión de Artistas'],
                ['es', 'adm_art_nuevo',             'Nuevo Artista'],
                ['es', 'adm_art_th_foto',           'Foto'],
                ['es', 'adm_art_th_nombre',         'Nombre Artístico'],
                ['es', 'adm_art_th_bio',            'Biografía'],
                ['es', 'adm_art_th_estado',         'Estado'],
                ['es', 'adm_art_foto',              'Foto de Perfil'],
                ['es', 'adm_art_verificado',        'Cuenta Verificada'],
                ['es', 'adm_alb_titulo',            'Gestión de Álbumes'],
                ['es', 'adm_alb_nuevo',             'Nuevo Álbum'],
                ['es', 'adm_alb_th_portada',        'Portada'],
                ['es', 'adm_alb_th_titulo',         'Título'],
                ['es', 'adm_alb_th_fecha',          'Fecha de Lanzamiento'],
                ['es', 'adm_cat_titulo',            'Catálogo Musical'],
                ['es', 'adm_cat_nueva',             'Nueva Canción'],
                ['es', 'adm_cat_th_genero',         'Género'],
                ['es', 'adm_cat_th_duracion',       'Duración'],
                ['es', 'adm_cat_guardar',           'Guardar Canción'],
                ['es', 'adm_gen_titulo',            'Gestión de Géneros Musicales'],
                ['es', 'adm_gen_nuevo',             'Nuevo Género'],
                ['es', 'adm_gen_th_nombre',         'Nombre del Género'],
                ['es', 'btn_cancelar',              'Cancelar'],
                ['es', 'btn_guardar',               'Guardar Cambios'],
                ['es', 'btn_guardar_simple',        'Guardar'],
                // === ADMIN INTERNAL VIEWS (EN) ===
                ['en', 'adm_bienvenida',           'Welcome to Soundverse'],
                ['en', 'adm_bienvenida_desc',      'The system is running correctly. Select a module from the left sidebar to start managing the platform.'],
                ['en', 'adm_usr_titulo',            'User Administration'],
                ['en', 'adm_usr_subtitulo',         'Module for registration, editing and access control of the Soundverse platform.'],
                ['en', 'adm_usr_nuevo',             'New Registration'],
                ['en', 'adm_usr_tipo',              'Subscription Type'],
                ['en', 'adm_usr_admin',             'Grant Administrator Privileges'],
                ['en', 'adm_usr_pass',              'Temporary Password'],
                ['en', 'adm_usr_pass_hint',         'Minimum 8 characters. When editing, leave empty to keep the current password.'],
                ['en', 'adm_usr_registrar',         'Register User'],
                ['en', 'adm_usr_cancelar',          'Cancel Edit'],
                ['en', 'adm_usr_lista',             'User List'],
                ['en', 'adm_usr_th_usuario',        'User'],
                ['en', 'adm_usr_th_correo',         'Email'],
                ['en', 'adm_usr_th_plan',           'Plan'],
                ['en', 'adm_usr_th_acciones',       'Actions'],
                ['en', 'adm_art_titulo',            'Artist Management'],
                ['en', 'adm_art_nuevo',             'New Artist'],
                ['en', 'adm_art_th_foto',           'Photo'],
                ['en', 'adm_art_th_nombre',         'Stage Name'],
                ['en', 'adm_art_th_bio',            'Biography'],
                ['en', 'adm_art_th_estado',         'Status'],
                ['en', 'adm_art_foto',              'Profile Photo'],
                ['en', 'adm_art_verificado',        'Verified Account'],
                ['en', 'adm_alb_titulo',            'Album Management'],
                ['en', 'adm_alb_nuevo',             'New Album'],
                ['en', 'adm_alb_th_portada',        'Cover'],
                ['en', 'adm_alb_th_titulo',         'Title'],
                ['en', 'adm_alb_th_fecha',          'Release Date'],
                ['en', 'adm_cat_titulo',            'Music Catalog'],
                ['en', 'adm_cat_nueva',             'New Song'],
                ['en', 'adm_cat_th_genero',         'Genre'],
                ['en', 'adm_cat_th_duracion',       'Duration'],
                ['en', 'adm_cat_guardar',           'Save Song'],
                ['en', 'adm_gen_titulo',            'Music Genre Management'],
                ['en', 'adm_gen_nuevo',             'New Genre'],
                ['en', 'adm_gen_th_nombre',         'Genre Name'],
                ['en', 'btn_cancelar',              'Cancel'],
                ['en', 'btn_guardar',               'Save Changes'],
                ['en', 'btn_guardar_simple',        'Save'],
            ];

            $sql_ins = "INSERT INTO Idioma_Traduccion (codigo_iso, etiqueta_llave, texto_traduccion) VALUES (?, ?, ?)";
            $stmt_ins = $db->prepare($sql_ins);
            foreach ($traducciones as $t) {
                $stmt_ins->execute($t);
            }
        } catch (PDOException $e) {
            Utilidades::registrarLog('idioma_error', 'inicializarTraducciones(): ' . $e->getMessage());
        } finally {
            $db = null;
        }
    }
}
?>
