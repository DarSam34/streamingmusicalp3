<?php
session_name('SOUNDVERSE_USER');
session_start();
header('Content-Type: application/json');
// ===== HEADERS DE SEGURIDAD HTTP =====
header('X-Content-Type-Options: nosniff');           // Evita MIME-type sniffing
header('X-Frame-Options: DENY');                     // Evita clickjacking
header('Referrer-Policy: strict-origin-when-cross-origin');

// En producción SIEMPRE 0 (nunca exponer errores al cliente)
error_reporting(E_ALL);
ini_set('display_errors', 0);  // CORREGIDO: era 1, expone rutas y stack traces al usario
ini_set('log_errors', 1);      // Los errores se guardan en el log del servidor, no en pantalla

// Rutas absolutas a las clases
$ruta_conexion = dirname(__DIR__, 2) . '/classes/Conexion.php';
$ruta_usuario  = dirname(__DIR__, 2) . '/classes/Usuario.php';
$ruta_cancion  = dirname(__DIR__, 2) . '/classes/Cancion.php';
$ruta_playlist = dirname(__DIR__, 2) . '/classes/Playlist.php';

// Verificar existencia de archivos críticos
if (!file_exists($ruta_conexion) || !file_exists($ruta_usuario)) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan archivos de clases.']);
    exit;
}

require_once $ruta_conexion;
require_once $ruta_usuario;
require_once $ruta_cancion;
require_once $ruta_playlist;
require_once dirname(__DIR__, 2) . '/classes/Artista.php'; // Perfil artista
require_once dirname(__DIR__, 2) . '/classes/Album.php';   // Discografía
require_once dirname(__DIR__, 2) . '/classes/Historial.php';
require_once dirname(__DIR__, 2) . '/classes/Utilidades.php'; // Logging centralizado
require_once dirname(__DIR__, 2) . '/classes/GestorSeguimiento.php';
require_once dirname(__DIR__, 2) . '/classes/MotorRecomendacion.php';
require_once dirname(__DIR__, 2) . '/classes/Facturacion.php';
require_once dirname(__DIR__, 2) . '/classes/Idioma.php';

$caso = $_GET['caso'] ?? ($_POST['caso'] ?? '');

switch ($caso) {
    case 'iniciarSesion':
        iniciarSesion();
        break;
    case 'verificar_email':        // Público: no requiere sesión
        verificarEmail();
        break;
    case 'cargar_idioma':          // Público: RF-01 Multilenguaje
        cargarIdioma();
        break;
    case 'registrarUsuario':       // Público: auto-registro de cuenta Free
        registrarUsuario();
        break;
    case 'listar_canciones':
        verificarSesionAjax();
        listarCanciones();
        break;
    case 'calidad_usuario':           // RF-Freemium: calidad de bitrate leída de BD
        verificarSesionAjax();
        obtenerCalidadKbps();
        break;
    case 'listar_albumes':
        verificarSesionAjax();
        listarAlbumes();
        break;
    case 'canciones_por_album':
        verificarSesionAjax();
        cancionesPorAlbum();
        break;
    case 'registrar_escucha':
        verificarSesionAjax();
        registrarEscucha();
        break;
    case 'listar_playlists':
        verificarSesionAjax();
        listarPlaylists();
        break;
    case 'listar_playlists_para_agregar':
        verificarSesionAjax();
        listarPlaylistsParaAgregar();
        break;
    case 'crear_playlist':
        verificarSesionAjax();
        crearPlaylist();
        break;
    case 'eliminar_playlist':
        verificarSesionAjax();
        eliminarPlaylist();
        break;
    case 'agregar_cancion_playlist':
        verificarSesionAjax();
        agregarCancionPlaylist();
        break;
    case 'validar_skip':
        verificarSesionAjax();
        validarSkip();
        break;
    case 'remover_cancion_playlist':
        verificarSesionAjax();
        removerCancionPlaylist();
        break;
    case 'obtener_canciones_playlist':
        verificarSesionAjax();
        obtenerCancionesPlaylist();
        break;
    case 'estadisticas_personales':
        verificarSesionAjax();
        estadisticasPersonales();
        break;
    case 'playlists_publicas':              // Regla CC: explorar playlists públicas
    case 'listar_playlists_publicas':       // Alias usado por el frontend (Playlists.php)
        verificarSesionAjax();
        listarPlaylistsPublicas();
        break;
    case 'duplicar_playlist':               // Regla DD: copiar playlist a tu biblioteca
        verificarSesionAjax();
        duplicarPlaylist();
        break;
    case 'historial_usuario':               // Vista de historial personal
        verificarSesionAjax();
        historialUsuario();
        break;
    case 'artistas_seguidos':
        verificarSesionAjax();
        artistasSeguidos();
        break;
    case 'radio_cancion':
        verificarSesionAjax();
        radioCancion();
        break;
    case 'descubrimiento_semanal':
        verificarSesionAjax();
        descubrimientoSemanal();
        break;
    case 'alternar_seguimiento':
        verificarSesionAjax();
        alternarSeguimiento();
        break;
    case 'obtener_perfil':
        verificarSesionAjax();
        obtenerPerfil();
        break;
    case 'actualizar_perfil':
        verificarSesionAjax();
        actualizarPerfil();
        break;
    case 'actualizar_password':
        verificarSesionAjax();
        actualizarPassword();
        break;
    case 'metodos_pago':
        verificarSesionAjax();
        obtenerMetodosPago();
        break;
    case 'procesar_upgrade':
        verificarSesionAjax();
        procesarUpgrade();
        break;
    case 'cancelar_suscripcion':
        verificarSesionAjax();
        cancelarSuscripcion();
        break;
    case 'historial_facturas':
        verificarSesionAjax();
        obtenerFacturas();
        break;
    case 'obtener_notificaciones':
        verificarSesionAjax();
        obtenerNotificaciones();
        break;
    case 'marcar_notificacion_leida':
        verificarSesionAjax();
        marcarNotificacionLeida();
        break;
    case 'recomendaciones_artistas_seguidos':
        verificarSesionAjax();
        recomendacionesPorArtistasSeguidos();
        break;
    case 'top_artistas_filtrado':
        verificarSesionAjax();
        topArtistasFiltrado();
        break;
    case 'evolucion_generos':
        verificarSesionAjax();
        evolucionGeneros();
        break;
    case 'perfil_artista':              // Perfil público del artista (Fase 2)
        verificarSesionAjax();
        perfilArtista();
        break;
    case 'reordenar_cancion_playlist':  // Reordenar canciones ↑↓ en playlist
        verificarSesionAjax();
        reordenarCancionPlaylist();
        break;
    case 'agregar_cancion_colaborativa': // Playlists Colaborativas
        verificarSesionAjax();
        agregarCancionColaborativa();
        break;
    case 'obtener_letra':
        verificarSesionAjax();
        obtenerLetraCancion();
        break;
    case 'estadisticas_artista_gestor':   // RF-Artista: panel gestor de canciones
        verificarSesionAjax();
        estadisticasArtistaGestor();
        break;
    case 'destacadas_artista':           // Requisito u: canciones destacadas en perfil público
        verificarSesionAjax();
        obtenerCancionesDestacadasArtista();
        break;
    case 'alternar_destacada':           // Requisito u: gestor activa/desactiva destacada
        verificarSesionAjax();
        alternarCancionDestacada();
        break;

    case 'validar_sesion_concurrente':
        verificarSesionAjax();
        validarSesionConcurrente();
        break;

    case 'listar_todos_artistas':
        verificarSesionAjax();
        try {
            $db = (new Conexion())->conectar();
            $stmt = $db->prepare("
                SELECT 
                    PK_id_artista, 
                    nombre_artistico, 
                    biografia, 
                    ruta_foto_perfil, 
                    verificado
                FROM Artista 
                WHERE estado_disponible = 1 
                ORDER BY nombre_artistico ASC
            ");
            $stmt->execute();
            $artistas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $artistas]);
        } catch (PDOException $e) {
            Utilidades::registrarLog('errores', '[listar_todos_artistas] ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error de base de datos al cargar artistas.']);
        }
        $db = null;
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Caso no válido.']);
        break;
}

// ==================== FUNCIONES AUXILIARES ====================

function verificarSesionAjax() {
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Sesión no válida.']);
        exit;
    }
}

function iniciarSesion() {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    $usuarioObj = new Usuario();
    $usuario    = $usuarioObj->login($email, $password);

    if ($usuario !== false) {
        session_regenerate_id(true);
        $_SESSION['usuario_id']       = $usuario['PK_id_usuario'];
        $_SESSION['nombre']           = $usuario['nombre_completo'];
        $_SESSION['tipo_suscripcion'] = $usuario['FK_id_tipo'];
        $_SESSION['es_admin']         = (int)$usuario['es_admin'];
        $_SESSION['time']             = time();

        // [PREMIUM-O] Persistir ID de sesión para control de dispositivos concurrentes
        $usuarioObj->actualizarSesionActiva($usuario['PK_id_usuario'], session_id());

        echo json_encode(['status' => 'success', 'message' => 'Acceso concedido.']);
    } else {
        // LOG: intento de login fallido
        Utilidades::registrarLog('auditoria', "[LOGIN_FALLIDO] Intento fallido con email: '{$email}'.");
        echo json_encode(['status' => 'error', 'message' => 'Correo o contraseña incorrectos.']);
    }
}

function listarCanciones() {
    $uid  = $_SESSION['usuario_id'] ?? 0;
    $tipo = (int)($_SESSION['tipo_suscripcion'] ?? 1);

    // RF-Freemium: leer calidad_kbps real desde Tipo_Suscripcion (no hardcodeado)
    $kbps = 128; // valor de respaldo si falla la consulta
    try {
        $db   = (new Conexion())->conectar();
        $stmt = $db->prepare(
            "SELECT calidad_kbps FROM Tipo_Suscripcion WHERE PK_id_tipo = ? LIMIT 1"
        );
        $stmt->execute([$tipo]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) $kbps = (int)$fila['calidad_kbps'];
        $db = null;
    } catch (PDOException $e) {
        Utilidades::registrarLog('errores', '[listarCanciones] Error leyendo calidad_kbps: ' . $e->getMessage());
    }

    $cancionObj = new Cancion();
    $canciones  = $cancionObj->listarCanciones($uid);

    echo json_encode([
        'status'       => 'success',
        'data'         => $canciones,
        'calidad_kbps' => $kbps,   // Expuesto para que el reproductor muestre el valor real
    ]);
}

function listarAlbumes() {
    $albumObj = new Album();
    $albumes = $albumObj->listarAlbumes();
    echo json_encode(['status' => 'success', 'data' => $albumes]);
}

function cancionesPorAlbum() {
    $id_album   = $_POST['id_album']   ?? 0;
    $uid        = $_SESSION['usuario_id'] ?? 0;
    $cancionObj = new Cancion();
    $canciones  = $cancionObj->listarCancionesPorAlbum($id_album, $uid);
    echo json_encode(['status' => 'success', 'data' => $canciones]);
}

function registrarEscucha() {
    $id_cancion = $_POST['id_cancion'] ?? 0;
    $segundos   = (int)($_POST['segundos'] ?? 0);
    $id_usuario = $_SESSION['usuario_id'];

    if ($id_cancion > 0 && $segundos > 0) {
        $historialObj = new Historial();
        $exito = $historialObj->registrarEscucha($id_usuario, $id_cancion, $segundos);
        
        if ($exito) {
            echo json_encode(['status' => 'success', 'message' => 'Escucha procesada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Fallo al guardar escucha en base de datos.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Datos de escucha incompletos o inválidos.']);
    }
}

function listarPlaylists() {
    $playlistObj = new Playlist();
    $list        = $playlistObj->listarPlaylistsUsuario($_SESSION['usuario_id']);
    echo json_encode(['status' => 'success', 'data' => $list]);
}

function listarPlaylistsParaAgregar() {
    $playlistObj = new Playlist();
    $list = $playlistObj->listarPlaylistsParaAgregar($_SESSION['usuario_id']);
    echo json_encode(['status' => 'success', 'data' => $list]);
}

function crearPlaylist() {
    $nombre      = trim($_POST['nombre']      ?? '');
    $visibilidad = $_POST['visibilidad'] ?? 'Publica';
    $tipo_suscripcion = $_SESSION['tipo_suscripcion'] ?? 1; // 1 = Free, 2 = Premium

    if (empty($nombre)) {
        echo json_encode(['status' => 'error', 'message' => 'El nombre es requerido.']);
        return;
    }

    $playlistObj = new Playlist();
    $result      = $playlistObj->crearPlaylist($_SESSION['usuario_id'], $nombre, $visibilidad, $tipo_suscripcion);


    if (isset($result['success']) && $result['success']) {
        Utilidades::registrarLog('operaciones',
            "[PLAYLIST_CREADA] Usuario ID:{$_SESSION['usuario_id']} creó playlist '{$nombre}' ({$visibilidad}), plan: " .
            ($tipo_suscripcion == 2 ? 'Premium' : 'Free'));
    } elseif (isset($result['success']) && !$result['success'] && $tipo_suscripcion == 1) {
        Utilidades::registrarLog('auditoria',
            "[LIMITE_PLAYLIST_FREE] Usuario ID:{$_SESSION['usuario_id']} intentó crear playlist '{$nombre}' pero alcanzó el límite de 15.");
    }

    // Normalizar la respuesta para que siempre tenga 'status'
    if (isset($result['success'])) {
        $result['status'] = $result['success'] ? 'success' : 'error';
    }
    echo json_encode($result);
}

function eliminarPlaylist() {
    $id_playlist = $_POST['id_playlist'] ?? 0;
    $playlistObj = new Playlist();
    $result      = $playlistObj->eliminarPlaylist($id_playlist, $_SESSION['usuario_id']);
    echo json_encode(['status' => $result ? 'success' : 'error']);
}

function agregarCancionPlaylist() {
    $id_playlist = (int)($_POST['id_playlist'] ?? 0);
    $id_cancion  = (int)($_POST['id_cancion']  ?? 0);

    if ($id_playlist <= 0 || $id_cancion <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Parámetros inválidos.']);
        return;
    }

    $playlistObj = new Playlist();

    // Nueva regla (PARCIAL-02 Completado): Usar función que valida si es dueño o si la playlist es Colaborativa
    $result = $playlistObj->agregarCancionColaborativa($id_playlist, $id_cancion, (int)$_SESSION['usuario_id']);

    if (isset($result['status']) && $result['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'Canción agregada a la playlist.']);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => $result['message'] ?? 'No se pudo agregar a la playlist.'
        ]);
    }
}


function removerCancionPlaylist() {
    $id_playlist = (int)($_POST['id_playlist'] ?? 0);
    $id_cancion  = (int)($_POST['id_cancion']  ?? 0);
    $id_usuario  = (int)$_SESSION['usuario_id']; // Prevención IDOR
    
    $playlistObj = new Playlist();
    $result      = $playlistObj->removerCancion($id_playlist, $id_cancion, $id_usuario);
    echo json_encode(['status' => $result ? 'success' : 'error']);
}

function obtenerCancionesPlaylist() {
    $id_playlist = $_POST['id_playlist'] ?? 0;
    $playlistObj = new Playlist();
    $canciones   = $playlistObj->obtenerCancionesPlaylist($id_playlist);
    echo json_encode(['status' => 'success', 'data' => $canciones]);
}

function estadisticasPersonales() {
    $id_usuario      = $_SESSION['usuario_id'];
    $tipo_suscripcion = (int)($_SESSION['tipo_suscripcion'] ?? 1);
    $historialObj = new Historial();
    $datos = $historialObj->obtenerEstadisticasPersonales($id_usuario, $tipo_suscripcion);

    // Top géneros para Google Charts (gráfico de barras por género)
    try {
        $db = (new Conexion())->conectar();
        $ventana = ($tipo_suscripcion == 2) ? '' : 'AND h.fecha_hora_reproduccion >= DATE_SUB(NOW(), INTERVAL 90 DAY)';
        $sqlGen = "SELECT g.nombre_genero AS genero, COUNT(*) AS reproducciones
                   FROM Historial_Reproduccion h
                   INNER JOIN Cancion c ON h.FK_id_cancion = c.PK_id_cancion
                   INNER JOIN Genero_Musical g ON c.FK_id_genero = g.PK_id_genero
                   WHERE h.FK_id_usuario = ? {$ventana}
                   GROUP BY g.PK_id_genero
                   ORDER BY reproducciones DESC
                   LIMIT 6";
        $stmt = $db->prepare($sqlGen);
        $stmt->execute([$id_usuario]);
        $datos['top_generos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
    } catch (PDOException $e) {
        $datos['top_generos'] = [];
    }

    // Evolución mensual de géneros (gráfico de línea)
    $historialObj2 = new Historial();
    $datos['evolucion_generos'] = $historialObj2->obtenerEvolucionGenerosMensual($id_usuario, $tipo_suscripcion);

    echo json_encode(['status' => 'success', 'data' => $datos]);
}

function radioCancion() {
    $id_cancion = $_GET['id_cancion'] ?? 0;
    $motorObj = new MotorRecomendacion();
    $canciones  = $motorObj->generarRadio($id_cancion);
    echo json_encode(['status' => 'success', 'data' => $canciones]);
}

function descubrimientoSemanal() {
    $id_usuario = $_SESSION['usuario_id'];
    $motorObj = new MotorRecomendacion();
    $canciones  = $motorObj->descubrimientoSemanal($id_usuario);
    echo json_encode(['status' => 'success', 'data' => $canciones]);
}

function alternarSeguimiento() {
    $id_usuario = $_SESSION['usuario_id'];
    $id_artista = $_POST['id_artista'] ?? 0;
    
    if($id_artista == 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID de artista inválido']);
        return;
    }

    $segObj = new GestorSeguimiento();
    $result = $segObj->alternarSeguimiento($id_usuario, $id_artista);
    echo json_encode($result);
}

// ==================== PLAYLISTS PÚBLICAS / EXPLORAR ====================

/**
 * REGLA CC: Playlists públicas aparecen en búsqueda.
 * Devuelve playlists de otros usuarios que son Publica.
 * [SQL movido a Playlist::listarPlaylistsPublicas() — POO estricta]
 */
function listarPlaylistsPublicas() {
    $id_usuario = $_SESSION['usuario_id'];
    $busqueda   = trim($_GET['q'] ?? '');
    $playlistObj = new Playlist();
    $playlists   = $playlistObj->listarPlaylistsPublicas($id_usuario, $busqueda);
    echo json_encode(['status' => 'success', 'data' => $playlists]);
}

/**
 * REGLA DD: Duplicar playlist de otro usuario a su biblioteca.
 * [SQL movido a Playlist::duplicarPlaylist() — POO estricta]
 */
function duplicarPlaylist() {
    $id_playlist_origen = (int)($_POST['id_playlist'] ?? 0);
    $id_usuario_destino = $_SESSION['usuario_id'];
    $tipo_suscripcion   = (int)($_SESSION['tipo_suscripcion'] ?? 1);

    if ($id_playlist_origen <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Playlist inválida.']);
        return;
    }

    $playlistObj = new Playlist();
    $res = $playlistObj->duplicarPlaylist($id_playlist_origen, $id_usuario_destino, $tipo_suscripcion);

    if ($res['status'] === 'success') {
        // Actualizar contador de playlists en sesión si es necesario (opcional)
    }
    echo json_encode($res);
}

/**
 * Devuelve las últimas 50 escuchas del usuario para la vista de historial.
 * [SQL movido a Historial::obtenerHistorialReciente() — POO estricta]
 */
function historialUsuario() {
    $id_usuario = $_SESSION['usuario_id'];
    $historialObj = new Historial();
    $registros    = $historialObj->obtenerHistorialReciente($id_usuario, 50);
    echo json_encode(['status' => 'success', 'data' => $registros]);
}

// ==================== GESTIÓN DE PERFIL ====================

function obtenerPerfil() {
    $id_usuario = $_SESSION['usuario_id'];
    $usuarioObj = new Usuario();
    $datos = $usuarioObj->obtenerUsuario($id_usuario);
    if ($datos) {
        echo json_encode(['status' => 'success', 'data' => $datos]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontraron datos del perfil.']);
    }
}

function actualizarPerfil() {
    $id_usuario = $_SESSION['usuario_id'];
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email']  ?? '');

    // DOBLE VALIDACIÓN: el servidor siempre re-valida
    if (empty($nombre) || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'El nombre y correo son obligatorios.']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'El correo electrónico no tiene un formato válido.']);
        return;
    }

    $usuarioObj = new Usuario();

    // Verificación de colisión de correo delegada a la clase Usuario
    if ($usuarioObj->verificarCorreoEnOtroUsuario($email, $id_usuario)) {
        echo json_encode(['status' => 'error', 'message' => 'Este correo ya está siendo usado por otra cuenta.']);
        return;
    }

    $usuarioObj2 = new Usuario();
    $resultado   = $usuarioObj2->actualizarPerfil($id_usuario, $nombre, $email);
    if ($resultado) {
        $_SESSION['nombre'] = $nombre;
        echo json_encode(['status' => 'success', 'message' => 'Perfil actualizado correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el perfil.']);
    }
}

function artistasSeguidos() {
    $id_usuario = $_SESSION['usuario_id'];
    $gestor = new GestorSeguimiento();
    $lista = $gestor->obtenerArtistasSeguidos($id_usuario);
    
    // Devolver un array vacío si false para mantener una respuesta estándar
    if ($lista === false) { $lista = []; }
    
    echo json_encode(['status' => 'success', 'data' => $lista]);
}



function actualizarPassword() {
    $id_usuario = $_SESSION['usuario_id'];
    $antigua = $_POST['antigua'] ?? '';
    $nueva = $_POST['nueva'] ?? '';

    if (empty($antigua) || empty($nueva)) {
        echo json_encode(['status' => 'error', 'message' => 'Ambas contraseñas son obligatorias.']);
        return;
    }
    if (strlen($nueva) < 8 || !preg_match('/[A-Z]/', $nueva) || !preg_match('/[0-9]/', $nueva)) {
         echo json_encode(['status' => 'error', 'message' => 'La nueva contraseña no cumple con los requisitos de seguridad.']);
         return;
    }

    $usuarioObj = new Usuario();
    $resultado = $usuarioObj->actualizarPassword($id_usuario, $antigua, $nueva);
    
    if ($resultado) {
        echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'La contraseña antigua es incorrecta o hubo un error.']);
    }
}

// ==================== SUSCRIPCIONES Y FACTURACIÓN ====================

function obtenerMetodosPago() {
    $factObj = new Facturacion();
    echo json_encode(['status' => 'success', 'data' => $factObj->obtenerMetodosPago()]);
}

function procesarUpgrade() {
    $id_usuario = $_SESSION['usuario_id'];
    $id_metodo = (int)($_POST['id_metodo'] ?? 0);
    
    // Validar si ya es premium
    if ($_SESSION['tipo_suscripcion'] == 2) {
        echo json_encode(['status' => 'error', 'message' => 'Tu cuenta ya es Premium.']);
        return;
    }
    
    if ($id_metodo <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Debes seleccionar un método de pago.']);
        return;
    }

    $factObj = new Facturacion();
    $res = $factObj->procesarUpgrade($id_usuario, $id_metodo);
    
    if ($res['status'] === 'success') {
        // Actualizar privilegios en la sesión actual
        $_SESSION['tipo_suscripcion'] = 2; 
    }
    
    echo json_encode($res);
}

function cancelarSuscripcion() {
    $conObj = new Conexion();
    $db = $conObj->conectar();
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'Error de conexión']);
        return;
    }
    try {
        $id_usuario = $_SESSION['usuario_id'];
        $sql = "UPDATE Usuario SET FK_id_tipo = 1 WHERE PK_id_usuario = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id_usuario]);
        $_SESSION['tipo_suscripcion'] = 1;
        echo json_encode(['status' => 'success', 'message' => 'Tu suscripción ha sido cancelada. Volviste al plan Free.']);
    } catch (\PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al cancelar.']);
    }
    $db = null;
}

function obtenerFacturas() {
    $id_usuario = $_SESSION['usuario_id'];
    $factObj = new Facturacion();
    echo json_encode(['status' => 'success', 'data' => $factObj->obtenerFacturasUsuario($id_usuario)]);
}

// ==================== NOTIFICACIONES ====================

function obtenerNotificaciones() {
    $id_usuario = $_SESSION['usuario_id'];
    $gestorSeg  = new GestorSeguimiento();
    $lista      = $gestorSeg->obtenerNotificaciones($id_usuario);
    $no_leidas  = array_filter($lista, fn($n) => $n['leida'] == 0);
    echo json_encode([
        'status'    => 'success',
        'data'      => $lista,
        'no_leidas' => count($no_leidas)
    ]);
}

function marcarNotificacionLeida() {
    $id_usuario      = $_SESSION['usuario_id'];
    $id_notificacion = (int)($_POST['id_notificacion'] ?? 0);
    $gestorSeg       = new GestorSeguimiento();
    $ok = $gestorSeg->marcarLeida($id_usuario, $id_notificacion);
    echo json_encode(['status' => $ok ? 'success' : 'error']);
}

// ==================== RECOMENDACIONES / ARTISTAS SEGUIDOS ====================

function recomendacionesPorArtistasSeguidos() {
    $id_usuario = $_SESSION['usuario_id'];
    $motor      = new MotorRecomendacion();
    $canciones  = $motor->recomendarPorArtistasSeguidos($id_usuario);
    echo json_encode(['status' => 'success', 'data' => $canciones]);
}

// ==================== REGISTRO PÚBLICO ====================

function verificarEmail() {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        echo json_encode(['disponible' => false]);
        return;
    }
    $usuarioObj = new Usuario();
    $existe = $usuarioObj->verificarCorreoExistente($email);
    echo json_encode(['disponible' => !$existe]);
}

function registrarUsuario() {
    $nombre   = trim($_POST['nombre']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    // --- Validaciones Backend (independientes del front-end) ---
    if (strlen($nombre) < 3) {
        echo json_encode(['status' => 'error', 'message' => 'El nombre debe tener al mínimo 3 caracteres.']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'El correo electrónico no tiene un formato válido.']);
        return;
    }
    if (strlen($password) < 8) {
        echo json_encode(['status' => 'error', 'message' => 'La contraseña debe tener al menos 8 caracteres.']);
        return;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        echo json_encode(['status' => 'error', 'message' => 'La contraseña debe contener al menos una mayúscula.']);
        return;
    }
    if (!preg_match('/[0-9]/', $password)) {
        echo json_encode(['status' => 'error', 'message' => 'La contraseña debe contener al menos un número.']);
        return;
    }

    // Verificar correo duplicado
    $usuarioObj = new Usuario();
    if ($usuarioObj->verificarCorreoExistente($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Este correo ya está registrado. \u00bfOlvidaste tu contraseña?']);
        return;
    }

    // Registrar como Free (FK_id_tipo = 1)
    $usuarioObj2 = new Usuario();
    $resultado   = $usuarioObj2->guardarUsuario(1, $nombre, $email, $password);
    if ($resultado) {
        echo json_encode(['status' => 'success', 'message' => 'Cuenta creada exitosamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error interno al crear la cuenta. Inténtalo más tarde.']);
    }
}

// ==================== MULTILENGUAJE (RF-01) ====================

function cargarIdioma() {
    $idioma = $_POST['idioma'] ?? $_GET['idioma'] ?? 'es';
    $idioma = in_array($idioma, ['es', 'en']) ? $idioma : 'es';
    $idiomaObj = new Idioma();
    $idiomaObj->inicializarTraducciones();
    $traducciones = $idiomaObj->cargarTraducciones($idioma);
    echo json_encode(['status' => 'success', 'idioma' => $idioma, 'data' => $traducciones]);
}

// ==================== ESTADÍSTICAS: TOP ARTISTAS CON FILTRO ====================

/**
 * Devuelve top 5 artistas filtrados por mes_actual / anio_actual / todo.
 * Parámetro GET: filtro = 'mes_actual' | 'anio_actual' | 'todo'
 */
function topArtistasFiltrado() {
    $id_usuario       = $_SESSION['usuario_id'];
    $tipo_suscripcion = (int)($_SESSION['tipo_suscripcion'] ?? 1);
    $filtro           = $_GET['filtro'] ?? $_POST['filtro'] ?? 'mes_actual';
    $filtros_validos  = ['mes_actual', 'anio_actual', 'todo'];
    if (!in_array($filtro, $filtros_validos)) $filtro = 'mes_actual';

    $historialObj = new Historial();
    $data = $historialObj->obtenerTopArtistasFiltrado($id_usuario, $tipo_suscripcion, $filtro);
    echo json_encode(['status' => 'success', 'data' => $data, 'filtro' => $filtro]);
}

/**
 * Devuelve la evolución mensual de géneros para el gráfico de líneas.
 */
function evolucionGeneros() {
    $id_usuario       = $_SESSION['usuario_id'];
    $tipo_suscripcion = (int)($_SESSION['tipo_suscripcion'] ?? 1);
    $historialObj     = new Historial();
    $data = $historialObj->obtenerEvolucionGenerosMensual($id_usuario, $tipo_suscripcion);
    echo json_encode(['status' => 'success', 'data' => $data]);
}
/**
 * Caso validar_sesion_concurrente
 * Verifica si el ID de sesión en el servidor coincide con el de la Base de Datos.
 * Si no coincide, significa que el usuario abrió sesión en otro dispositivo.
 */
function validarSesionConcurrente() {
    $id_usuario = $_SESSION['usuario_id'];
    $session_actual = session_id();

    try {
        $db = (new Conexion())->conectar();
        $stmt = $db->prepare("SELECT id_sesion_activa FROM Usuario WHERE PK_id_usuario = ?");
        $stmt->execute([$id_usuario]);
        $db_session = $stmt->fetchColumn();
        
        if ($db_session && $db_session !== $session_actual) {
            echo json_encode(['status' => 'conflict', 'message' => 'Sesión activa en otro dispositivo']);
        } else {
            echo json_encode(['status' => 'success']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'success']); // Fail-open para no molestar al usuario si falla la red
    }
}

// ==================== PERFIL PÚBLICO DE ARTISTA ====================

/**
 * Devuelve toda la info de un artista para su perfil público:
 * datos base, géneros dinámicos, total reproducciones y discografía.
 */
function perfilArtista() {
    $id_artista = (int)($_GET['id_artista'] ?? $_POST['id_artista'] ?? 0);
    if ($id_artista <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID de artista inválido.']);
        return;
    }

    $artistaObj = new Artista();
    $datos      = $artistaObj->obtenerArtista($id_artista);

    if (!$datos) {
        echo json_encode(['status' => 'error', 'message' => 'Artista no encontrado.']);
        return;
    }

    $artistaObj2  = new Artista();
    $discografia  = $artistaObj2->obtenerDiscografia($id_artista);

    $artistaObj3  = new Artista();
    $generos      = $artistaObj3->obtenerGenerosArtista($id_artista);

    $artistaObj4  = new Artista();
    $reproducciones = $artistaObj4->obtenerTotalReproducciones($id_artista);

    // Canciones destacadas del artista (requisito u)
    $artistaObj5  = new Artista();
    $destacadas   = $artistaObj5->obtenerCancionesDestacadas($id_artista);

    // NUEVO: Total de seguidores
    $artistaObj6  = new Artista();
    $seguidores   = $artistaObj6->obtenerTotalSeguidores($id_artista);

    // ¿El usuario actual sigue a este artista?
    $id_usuario   = $_SESSION['usuario_id'];
    $gestorSeg    = new GestorSeguimiento();
    $sigue        = $gestorSeg->verificarSeguimiento($id_usuario, $id_artista);

    echo json_encode([
        'status' => 'success',
        'data'   => array_merge($datos, [
            'discografia'    => $discografia,
            'generos'        => $generos,
            'reproducciones' => $reproducciones,
            'destacadas'     => $destacadas,   // NUEVO: requisito u
            'seguidores'     => $seguidores,   // NUEVO: seguidores totales
            'sigue'          => $sigue
        ])
    ]);
}

// ==================== REORDENAR CANCELAR CANCIONES EN PLAYLIST ====================

/**
 * Mueve una canción una posición arriba o abajo dentro de una playlist.
 */
function reordenarCancionPlaylist() {
    $id_playlist = (int)($_POST['id_playlist'] ?? 0);
    $id_cancion  = (int)($_POST['id_cancion']  ?? 0);
    $direccion   = $_POST['direccion'] ?? 'up';
    $id_usuario  = (int)$_SESSION['usuario_id']; // Prevención IDOR

    if ($id_playlist <= 0 || $id_cancion <= 0 || !in_array($direccion, ['up', 'down'])) {
        echo json_encode(['status' => 'error', 'message' => 'Parámetros inválidos.']);
        return;
    }

    $playlistObj = new Playlist();
    $ok = $playlistObj->reordenarCancion($id_playlist, $id_cancion, $direccion, $id_usuario);
    echo json_encode([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Orden actualizado.' : 'No se pudo reordenar.'
    ]);
}

// NUEVA FUNCIÓN: Lógica estricta de saltos para cuentas Free
function validarSkip() {
    $tipo = (int)($_SESSION['tipo_suscripcion'] ?? 1);
    if ($tipo == 2) { // Premium
        echo json_encode(['status' => 'success', 'permitido' => true]);
        return;
    }

    if (!isset($_SESSION['skips'])) {
        $_SESSION['skips'] = [];
    }
    
    $hora_actual = time();
    // Limpiar saltos que tienen más de 1 hora (3600 segundos)
    $_SESSION['skips'] = array_filter($_SESSION['skips'], function($timestamp) use ($hora_actual) {
        return ($hora_actual - $timestamp) <= 3600;
    });

    if (count($_SESSION['skips']) >= 6) {
        echo json_encode([
            'status' => 'error', 
            'permitido' => false, 
            'message' => 'Límite de 6 saltos por hora alcanzado. Mejora a Premium para saltos ilimitados.'
        ]);
        return;
    }

    // Registrar nuevo salto
    $_SESSION['skips'][] = $hora_actual;
    echo json_encode(['status' => 'success', 'permitido' => true]);
}

// ==================== PLAYLISTS COLABORATIVAS ====================

/**
 * Permite que un usuario no-dueño agregue una canción a una playlist colaborativa.
 */
function agregarCancionColaborativa() {
    $id_playlist = (int)($_POST['id_playlist'] ?? 0);
    $id_cancion  = (int)($_POST['id_cancion']  ?? 0);
    $id_usuario  = $_SESSION['usuario_id'];

    if ($id_playlist <= 0 || $id_cancion <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Parámetros inválidos.']);
        return;
    }

    $playlistObj = new Playlist();
    $res = $playlistObj->agregarCancionColaborativa($id_playlist, $id_cancion, $id_usuario);
    echo json_encode($res);
}

// ==================== REQUISITOS ADICIONALES ====================

/**
 * RF-Freemium (bitrate): Lee calidad_kbps directamente de Tipo_Suscripcion.
 * El frontend lo usa para actualizar el badge del reproductor con el valor real de BD.
 */
function obtenerCalidadKbps() {
    $tipo = (int)($_SESSION['tipo_suscripcion'] ?? 1);
    try {
        $db   = (new Conexion())->conectar();
        $stmt = $db->prepare(
            "SELECT calidad_kbps, nombre_plan FROM Tipo_Suscripcion WHERE PK_id_tipo = ? LIMIT 1"
        );
        $stmt->execute([$tipo]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $db   = null;
        if ($fila) {
            echo json_encode([
                'status'      => 'success',
                'calidad_kbps' => (int)$fila['calidad_kbps'],
                'nombre_plan' => $fila['nombre_plan'],
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Plan no encontrado.']);
        }
    } catch (PDOException $e) {
        Utilidades::registrarLog('errores', '[obtenerCalidadKbps] ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error al obtener calidad.']);
    }
}

function obtenerLetraCancion() {
    $id_cancion = (int)($_GET['id_cancion'] ?? 0);
    
    if ($id_cancion <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
        return;
    }
    
    $cancionObj = new Cancion();
    $detalle = $cancionObj->obtenerCancion($id_cancion);
    
    if ($detalle) {
        // Enviar la letra si existe o un aviso si no tiene
        $letra = (!empty($detalle['letra_sincronizada'])) ? $detalle['letra_sincronizada'] : '';
        echo json_encode(['status' => 'success', 'data' => trim($letra)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Canción no encontrada.']);
    }
}

// ==================== PANEL ARTISTA GESTOR ====================

/**
 * RF-Artista: Devuelve KPIs + top canciones con regalías + desglose por país.
 * Todo el SQL está encapsulado en Artista::obtenerEstadisticasGestor()
 * y Artista::obtenerActividadPorPais() — sin SQL suelto aquí.
 */
function estadisticasArtistaGestor() {
    $id_usuario = $_SESSION['usuario_id'];

    // Obtener el id_artista asociado al gestor (no hay clase para esto, usamos Artista::obtenerArtista indirectamente)
    try {
        $db    = (new Conexion())->conectar();
        $stmt  = $db->prepare("SELECT PK_id_artista FROM Artista WHERE FK_id_usuario_gestor = ? AND estado_disponible = 1 LIMIT 1");
        $stmt->execute([$id_usuario]);
        $fila  = $stmt->fetch(PDO::FETCH_ASSOC);
        $db    = null;
    } catch (PDOException $e) {
        Utilidades::registrarLog('errores', '[estadisticasArtistaGestor] ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error al buscar artista.']);
        return;
    }

    if (!$fila) {
        echo json_encode(['status' => 'error', 'message' => 'No tienes un perfil de artista asociado.']);
        return;
    }

    $id_artista  = (int)$fila['PK_id_artista'];
    $artistaObj  = new Artista();
    $stats       = $artistaObj->obtenerEstadisticasGestor($id_artista);

    $artistaObj2 = new Artista();
    $paises      = $artistaObj2->obtenerActividadPorPais($id_artista);

    echo json_encode([
        'status'      => 'success',
        'id_artista'  => $id_artista,
        'stats'       => $stats,
        'paises'      => $paises,
    ]);
}

// ==================== CANCIONES DESTACADAS (Requisito u) ====================

/**
 * Devuelve las canciones destacadas de un artista (perfil publico).
 */
function obtenerCancionesDestacadasArtista() {
    $id_artista = (int)($_GET['id_artista'] ?? 0);
    if ($id_artista <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID invalido.']);
        return;
    }
    $artistaObj = new Artista();
    echo json_encode(['status' => 'success', 'data' => $artistaObj->obtenerCancionesDestacadas($id_artista)]);
}

/**
 * Alterna la marca destacada de una cancion (solo para el gestor del artista).
 */
function alternarCancionDestacada() {
    $id_cancion = (int)($_POST['id_cancion'] ?? 0);
    $id_usuario = $_SESSION['usuario_id'];
    if ($id_cancion <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID de cancion invalido.']);
        return;
    }
    try {
        $db   = (new Conexion())->conectar();
        $stmt = $db->prepare("SELECT PK_id_artista FROM Artista WHERE FK_id_usuario_gestor = ? AND estado_disponible = 1 LIMIT 1");
        $stmt->execute([$id_usuario]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $db   = null;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error BD.']);
        return;
    }
    if (!$fila) {
        echo json_encode(['status' => 'error', 'message' => 'Sin perfil de artista.']);
        return;
    }
    $id_artista = (int)$fila['PK_id_artista'];
    $artistaObj = new Artista();
    $res = $artistaObj->alternarDestacada($id_cancion, $id_artista);
    if ($res['status'] === 'success') {
        $accion = $res['destacada'] ? 'DESTACAR' : 'QUITAR_DESTACADA';
        Utilidades::registrarLog('operaciones', "[{$accion}] Gestor:{$id_usuario} | Artista:{$id_artista} | Cancion:{$id_cancion}");
    }
    echo json_encode($res);
}
?>