<?php
/**
 * ARCHIVO: admin/php/queries.php
 * Controlador único para el panel de administración.
 */
session_name('SOUNDVERSE_ADMIN');
session_start();
header('Content-Type: application/json');
// ===== HEADERS DE SEGURIDAD HTTP =====
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Errores al log del servidor, nunca al cliente
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Carga de clases necesarias
require_once dirname(__DIR__, 2) . '/classes/Conexion.php';
require_once dirname(__DIR__, 2) . '/classes/Usuario.php';
require_once dirname(__DIR__, 2) . '/classes/Artista.php';
require_once dirname(__DIR__, 2) . '/classes/Album.php';
require_once dirname(__DIR__, 2) . '/classes/Cancion.php';
require_once dirname(__DIR__, 2) . '/classes/Dashboard.php';
require_once dirname(__DIR__, 2) . '/classes/Genero.php';
require_once dirname(__DIR__, 2) . '/classes/Utilidades.php';
require_once dirname(__DIR__, 2) . '/classes/GestorSeguimiento.php';

$caso = $_POST['caso'] ?? $_GET['caso'] ?? '';

switch ($caso) {
    case 'iniciarSesion':
        iniciarSesion();
        break;
    case 'registrarUsuario':
        verificarSesionAjax();
        registrarUsuario();
        break;
    case 'actualizarUsuario':
        verificarSesionAjax();
        actualizarUsuario();
        break;
    case 'eliminarUsuario':
        verificarSesionAjax();
        eliminarUsuario();
        break;
    case 'listar_usuarios':
        verificarSesionAjax();
        listarUsuarios();
        break;
    case 'cargar_dashboard':
        verificarSesionAjax();
        cargarDatosDashboard();
        break;
    case 'listar_canciones':
        verificarSesionAjax();
        listarCanciones();
        break;
    case 'datos_selects_cancion':
        verificarSesionAjax();
        datosSelectsCancion();
        break;
    case 'guardar_cancion':
        verificarSesionAjax();
        guardarCancion();
        break;
    case 'obtener_cancion':
        verificarSesionAjax();
        obtenerCancion();
        break;
    case 'eliminar_cancion':
        verificarSesionAjax();
        eliminarCancion();
        break;
    case 'listar_generos':
        verificarSesionAjax();
        listarGeneros();
        break;
    case 'crear_genero':
        verificarSesionAjax();
        crearGenero();
        break;
    case 'actualizar_genero':
        verificarSesionAjax();
        actualizarGenero();
        break;
    case 'eliminar_genero':
        verificarSesionAjax();
        eliminarGenero();
        break;
    case 'listar_artistas':
        verificarSesionAjax();
        listarArtistas();
        break;
    case 'guardar_artista':
        verificarSesionAjax();
        guardarArtista();
        break;
    case 'obtener_artista':
        verificarSesionAjax();
        obtenerArtista();
        break;
    case 'eliminar_artista':
        verificarSesionAjax();
        eliminarArtista();
        break;
    case 'listar_albumes':
        verificarSesionAjax();
        listarAlbumes();
        break;
    case 'guardar_album':
        verificarSesionAjax();
        guardarAlbum();
        break;
    case 'obtener_album':
        verificarSesionAjax();
        obtenerAlbum();
        break;
    case 'eliminar_album':
        verificarSesionAjax();
        eliminarAlbum();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Caso no válido.']);
        break;
}

function listarAlbumes() {
    $obj = new Album();
    $albumes = $obj->listarAlbumes();
    echo json_encode(['status' => 'success', 'data' => $albumes]);
}

// ==================== FUNCIONES DE AUTENTICACIÓN Y CRUD ====================

function iniciarSesion() {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $usuarioObj = new Usuario();
    $usuario    = $usuarioObj->login($email, $password);

    if ($usuario !== false) {
        if ((int)$usuario['es_admin'] !== 1) {
            Utilidades::registrarLog('auditoria', "[ACCESO_DENEGADO] Email: {$email} - No es administrador.");
            echo json_encode([
                'status'  => 'error',
                'message' => 'Acceso denegado: No tienes permisos de administrador.'
            ]);
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuario['PK_id_usuario'];
        $_SESSION['nombre']     = $usuario['nombre_completo'];
        $_SESSION['rol']        = $usuario['FK_id_tipo'];
        $_SESSION['es_admin']   = 1;
        $_SESSION['time']       = time();

        Utilidades::registrarLog('auditoria', "[LOGIN_EXITOSO] Admin '{$usuario['nombre_completo']}' (ID: {$usuario['PK_id_usuario']}) ha iniciado sesión.");

        echo json_encode(['status' => 'success', 'message' => 'Acceso concedido.', 'es_admin' => 1]);
    } else {
        Utilidades::registrarLog('auditoria', "[LOGIN_FALLIDO] Intento con email: '{$email}'.");
        echo json_encode(['status' => 'error', 'message' => 'Correo o contraseña incorrectos.']);
    }
}

function verificarSesionAjax() {
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['es_admin'])) {
        echo json_encode(['status' => 'error', 'message' => 'Sesión no válida o sin permisos.']);
        exit;
    }
}

function registrarUsuario() {
    $nombre  = trim($_POST['nombre']   ?? '');
    $email   = trim($_POST['email']    ?? '');
    $pass    = $_POST['password'] ?? '';
    $id_tipo = (isset($_POST['rol']) && $_POST['rol'] == 2) ? 2 : 1;
    $es_admin = (isset($_POST['es_admin']) && $_POST['es_admin'] == 1) ? 1 : 0;

    if (empty($nombre) || empty($email)) {
        echo json_encode(['codigo' => 2, 'status' => 'error', 'message' => 'Nombre y correo son obligatorios.']);
        return;
    }
    if (strlen($pass) < 8) {
        echo json_encode(['codigo' => 2, 'status' => 'error', 'message' => 'La contraseña debe tener al menos 8 caracteres.']);
        return;
    }

    $usuarioObj = new Usuario();
    if ($usuarioObj->verificarCorreoExistente($email)) {
        echo json_encode(['codigo' => 2, 'status' => 'error', 'message' => 'El correo ya está registrado.']);
    } else {
        $usuarioObj2 = new Usuario();
        $resultado = $usuarioObj2->guardarUsuario($id_tipo, $nombre, $email, $pass, $es_admin);
        echo json_encode($resultado
            ? ['codigo' => 1, 'status' => 'success', 'message' => 'Usuario registrado correctamente.']
            : ['codigo' => 3, 'status' => 'error',   'message' => 'Error al guardar en base de datos.']);
    }
}

function actualizarUsuario() {
    $id_usuario = (int) ($_POST['id_usuario'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $id_tipo = (isset($_POST['rol']) && $_POST['rol'] == 2) ? 2 : 1;
    $es_admin = (isset($_POST['es_admin']) && $_POST['es_admin'] == 1) ? 1 : 0;

    if ($id_usuario <= 0) {
        echo json_encode(['codigo' => 2, 'status' => 'error', 'message' => 'ID de usuario no válido.']);
        return;
    }
    if (empty($nombre) || empty($email)) {
        echo json_encode(['codigo' => 2, 'status' => 'error', 'message' => 'El nombre y el correo son obligatorios.']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['codigo' => 2, 'status' => 'error', 'message' => 'El correo electrónico no tiene un formato válido.']);
        return;
    }
    if (!empty($pass) && strlen($pass) < 8) {
        echo json_encode(['codigo' => 2, 'status' => 'error', 'message' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
        return;
    }

    $usuarioObj = new Usuario();
    $resultado = $usuarioObj->actualizarUsuario($id_usuario, $id_tipo, $nombre, $email, $pass, $es_admin);
    echo json_encode($resultado !== false
        ? ['codigo' => 1, 'status' => 'success', 'message' => 'Actualizado correctamente.']
        : ['codigo' => 3, 'status' => 'error',   'message' => 'Error al actualizar en base de datos.']);
}

function eliminarUsuario() {
    $id_usuario = (int) ($_POST['id_usuario'] ?? 0);
    if ($id_usuario <= 0) {
        echo json_encode(['codigo' => 2, 'status' => 'error', 'message' => 'ID inválido.']);
        return;
    }
    $usuarioObj = new Usuario();
    $resultado = $usuarioObj->eliminarUsuarioLogico($id_usuario);
    echo json_encode($resultado
        ? ['codigo' => 1, 'status' => 'success', 'message' => 'Usuario desactivado correctamente.']
        : ['codigo' => 3, 'status' => 'error',   'message' => 'No se pudo desactivar.']);
}

function listarUsuarios() {
    $usuarioObj = new Usuario();
    echo json_encode($usuarioObj->listarUsuarios());
}

function listarCanciones() {
    $cancionObj = new Cancion();
    echo json_encode($cancionObj->listarCanciones());
}

function listarArtistas() {
    $obj = new Artista();
    echo json_encode($obj->listarArtistas());
}

function guardarArtista() {
    $id = $_POST['id_artista'] ?? '';
    $nombre = trim($_POST['nombre_artistico'] ?? '');
    $biografia = trim($_POST['biografia'] ?? '');
    $verificado = isset($_POST['verificado']) ? 1 : 0;
    
    if (empty($nombre)) {
        echo json_encode(['status' => 'error', 'message' => 'El nombre artístico es obligatorio.']);
        return;
    }

    $rutaFinal = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $mimePermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $_FILES['foto']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeReal, $mimePermitidos)) {
            echo json_encode(['status' => 'error', 'message' => 'Solo se permiten imágenes JPG, PNG, GIF o WebP.']);
            return;
        }

        $directorio = dirname(__DIR__, 2) . "/assets/img/artistas/";
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nuevoNombre = "ART_" . uniqid() . "." . strtolower($extension);
        $rutaFisica = $directorio . $nuevoNombre;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaFisica)) {
            $rutaFinal = "assets/img/artistas/" . $nuevoNombre;
        }
    }

    $obj = new Artista();
    $adminId = $_SESSION['usuario_id'];
    if (empty($id) || $id == '0') {
        $id_gestor = $adminId;
        $res = $obj->registrarArtista($nombre, $biografia, $rutaFinal, $verificado, $id_gestor);
        if ($res == 1) {
            Utilidades::registrarLog('auditoria', "[CRUD] Admin ID:{$adminId} CREO artista '{$nombre}'.");
        }
        echo json_encode(['status' => ($res == 1) ? 'success' : 'error', 'message' => ($res == 1) ? 'Artista guardado.' : 'Error al registrar.']);
    } else {
        $res = $obj->actualizarArtista($id, $nombre, $biografia, $rutaFinal, $verificado);
        if ($res == 1) {
            Utilidades::registrarLog('auditoria', "[CRUD] Admin ID:{$adminId} ACTUALIZO artista ID:{$id} '{$nombre}'.");
        }
        echo json_encode(['status' => ($res == 1) ? 'success' : 'error', 'message' => ($res == 1) ? 'Artista actualizado.' : 'Error al actualizar.']);
    }
}

function obtenerArtista() {
    $obj = new Artista();
    echo json_encode($obj->obtenerArtista($_POST['id'] ?? ''));
}

function eliminarArtista() {
    $id = $_POST['id'] ?? '';
    $obj = new Artista();
    $res = $obj->eliminarArtista($id);
    if ($res == 1) {
        Utilidades::registrarLog('auditoria', "[CRUD] Admin ID:{$_SESSION['usuario_id']} ELIMINO (logico) artista ID:{$id}.");
    }
    echo json_encode(['status' => ($res == 1) ? 'success' : 'error', 'message' => ($res == 1) ? 'Artista desactivado.' : 'Error al eliminar.']);
}

function cargarDatosDashboard() {
    $dashObj = new Dashboard();
    echo json_encode($dashObj->obtenerEstadisticas());
}

function guardarAlbum() {
    $id = $_POST['id_album'] ?? '';
    $id_artista = $_POST['fk_artista'] ?? '';
    $titulo = trim($_POST['titulo'] ?? '');
    $fecha = $_POST['fecha_lanzamiento'] ?? '';
    $discografica = trim($_POST['discografica'] ?? '');
    
    if (empty($titulo) || empty($id_artista) || empty($fecha)) {
        echo json_encode(['status' => 'error', 'message' => 'Llene todos los campos obligatorios.']);
        return;
    }

    $rutaFinal = "";
    if (isset($_FILES['portada']) && $_FILES['portada']['error'] == 0) {
        $mimePermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $_FILES['portada']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeReal, $mimePermitidos)) {
            echo json_encode(['status' => 'error', 'message' => 'Solo se permiten imágenes JPG, PNG, GIF o WebP.']);
            return;
        }

        $directorio = dirname(__DIR__, 2) . "/assets/img/portadas/";
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        $extension = pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION);
        $nuevoNombre = "ALB_" . uniqid() . "." . strtolower($extension);
        $rutaFisica = $directorio . $nuevoNombre;

        if (move_uploaded_file($_FILES['portada']['tmp_name'], $rutaFisica)) {
            $rutaFinal = "assets/img/portadas/" . $nuevoNombre;
        }
    }

    $obj = new Album();
    $adminId = $_SESSION['usuario_id'];
    if (empty($id) || $id == '0') {
        $nuevaId = $obj->registrarAlbum($id_artista, $titulo, $fecha, $rutaFinal, $discografica);
        if ($nuevaId > 0) {
            Utilidades::registrarLog('auditoria', "[CRUD] Admin ID:{$adminId} CREO album '{$titulo}' (ID:{$nuevaId}).");

            // ── Notificar a los seguidores del artista ───────────────────────────
            $artistaData = (new Artista())->obtenerArtista($id_artista);
            if ($artistaData) {
                $gestor = new GestorSeguimiento();
                $notificados = $gestor->notificarSeguidoresAlbum(
                    (int)$id_artista,
                    $artistaData['nombre_artistico'],
                    $titulo,
                    $nuevaId
                );
                if ($notificados > 0) {
                    Utilidades::registrarLog('notificaciones',
                        "[NUEVO_ALBUM] '{$titulo}' (ID:{$nuevaId}) — Notificados: {$notificados} seguidores del artista ID:{$id_artista}.");
                }
            }
            // ────────────────────────────────────────────────────────────────────
        }
        echo json_encode(['status' => ($nuevaId > 0) ? 'success' : 'error', 'message' => ($nuevaId > 0) ? 'Álbum guardado.' : 'Error interno.']);
    } else {
        $res = $obj->actualizarAlbum($id, $id_artista, $titulo, $fecha, $rutaFinal, $discografica);
        if ($res == 1) {
            Utilidades::registrarLog('auditoria', "[CRUD] Admin ID:{$adminId} ACTUALIZO album ID:{$id} '{$titulo}'.");
        }
        echo json_encode(['status' => ($res == 1) ? 'success' : 'error', 'message' => ($res == 1) ? 'Álbum actualizado.' : 'Error interno.']);
    }
}

function obtenerAlbum() {
    $obj = new Album();
    echo json_encode($obj->obtenerAlbum($_POST['id'] ?? ''));
}

function eliminarAlbum() {
    $id = $_POST['id'] ?? '';
    $obj = new Album();
    $res = $obj->eliminarAlbum($id);
    if ($res == 1) {
        Utilidades::registrarLog('auditoria', "[CRUD] Admin ID:{$_SESSION['usuario_id']} ELIMINO (logico) album ID:{$id}.");
    }
    echo json_encode(['status' => ($res == 1) ? 'success' : 'error', 'message' => ($res == 1) ? 'Álbum desactivado.' : 'Error al eliminar.']);
}

function datosSelectsCancion() {
    $objAlbumes = new Cancion();
    $listaAlbumes = $objAlbumes->listarAlbumes();
    $objGeneros = new Cancion();
    $listaGeneros = $objGeneros->listarGeneros();
    echo json_encode(['albumes' => $listaAlbumes, 'generos' => $listaGeneros]);
}

function guardarCancion() {
    $cancionObj = new Cancion();
    $id = $_POST['id_cancion'] ?? '0';
    $albumTexto = $_POST['album'] ?? '';
    $genero = $_POST['genero'] ?? '';
    $titulo = trim($_POST['titulo'] ?? '');
    $duracion = $_POST['duracion_segundos'] ?? '0';
    $letra = trim($_POST['letra_sincronizada'] ?? '');
    $numero_pista = (int)($_POST['numero_pista'] ?? 1);
    if ($numero_pista < 1) $numero_pista = 1;

    if (empty($titulo) || empty($albumTexto) || empty($genero)) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan campos obligatorios.']);
        return;
    }

    $albumObj = new Album();
    $albumId = $albumObj->obtenerIdPorNombre($albumTexto);
    
    if (!$albumId) {
        echo json_encode(['status' => 'error', 'message' => 'El álbum escrito no existe.']);
        return;
    }

    $rutaFinal = "";
    if (isset($_FILES['archivo_audio']) && $_FILES['archivo_audio']['error'] == 0) {
        $uploadError = $_FILES['archivo_audio']['error'];
        if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
            $maxMB = ini_get('upload_max_filesize');
            echo json_encode(['status' => 'error', 'message' => "El archivo supera el límite ({$maxMB}). Máximo 15 MB."]);
            return;
        }
        if ($uploadError !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => "Error al subir el archivo (código: {$uploadError})."]);
            return;
        }

        $directorio = dirname(__DIR__, 2) . "/assets/musica/";
        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }
        $extension = pathinfo($_FILES['archivo_audio']['name'], PATHINFO_EXTENSION);
        
        $tamanoMaximo = 15 * 1024 * 1024;
        if ($_FILES['archivo_audio']['size'] > $tamanoMaximo) {
            $tamanoMB = round($_FILES['archivo_audio']['size'] / (1024 * 1024), 2);
            echo json_encode(['status' => 'error', 'message' => "El archivo pesa {$tamanoMB} MB. Máximo 15 MB."]);
            return;
        }
        
        if(strtolower($extension) != 'mp3' && strtolower($extension) != 'wav' && strtolower($extension) != 'ogg') {
            echo json_encode(['status' => 'error', 'message' => 'Solo MP3, WAV u OGG.']);
            return;
        }

        $nuevoNombre = "TRACK_" . uniqid() . "." . $extension;
        $rutaFisica = $directorio . $nuevoNombre;

        if (move_uploaded_file($_FILES['archivo_audio']['tmp_name'], $rutaFisica)) {
            $rutaFinal = "assets/musica/" . $nuevoNombre;
        }
    }

    $adminId = $_SESSION['usuario_id'];
    if (empty($id) || $id == '0') {
        if (empty($rutaFinal)) {
            echo json_encode(['status' => 'error', 'message' => 'Debe subir un archivo de audio.']);
            return;
        }
        // registrarCancion() ahora devuelve el ID insertado (int > 0) o 0 si falla
        $nuevaId = $cancionObj->registrarCancion($albumId, $genero, $titulo, $duracion, $rutaFinal, $letra, $numero_pista);
        if ($nuevaId > 0) {
            Utilidades::registrarLog('auditoria', "[CRUD] Admin ID:{$adminId} CREO cancion '{$titulo}' (ID:{$nuevaId}).");

            // ── Notificar a los seguidores del artista ───────────────────────────
            // Recuperar FK_id_artista del álbum para saber a quién notificar
            $albumData = (new Album())->obtenerAlbum($albumId);
            if ($albumData && !empty($albumData['FK_id_artista'])) {
                $artistaData = (new Artista())->obtenerArtista($albumData['FK_id_artista']);
                if ($artistaData) {
                    $gestor = new GestorSeguimiento();
                    $notificados = $gestor->notificarSeguidores(
                        (int)$albumData['FK_id_artista'],
                        $artistaData['nombre_artistico'],
                        $titulo,
                        $nuevaId
                    );
                    if ($notificados > 0) {
                        Utilidades::registrarLog('notificaciones',
                            "[NUEVA_CANCION] '{$titulo}' (ID:{$nuevaId}) — Notificados: {$notificados} seguidores del artista ID:{$albumData['FK_id_artista']}.");
                    }
                }
            }
            // ────────────────────────────────────────────────────────────────────
        }
        echo json_encode(['status' => $nuevaId > 0 ? 'success' : 'error', 'message' => $nuevaId > 0 ? 'Canción registrada.' : 'Error interno.']);
    } else {
        $res = $cancionObj->actualizarCancion($id, $albumId, $genero, $titulo, $duracion, $rutaFinal, $letra, $numero_pista);
        if ($res) {
            Utilidades::registrarLog('auditoria', "[CRUD] Admin ID:{$adminId} ACTUALIZO cancion ID:{$id}.");
        }
        echo json_encode(['status' => $res ? 'success' : 'error', 'message' => $res ? 'Canción actualizada.' : 'Error interno.']);
    }
}


function obtenerCancion() {
    $cancionObj = new Cancion();
    $id = $_POST['id'] ?? '';
    echo json_encode($cancionObj->obtenerCancion($id));
}

function eliminarCancion() {
    $cancionObj = new Cancion();
    $id = $_POST['id'] ?? '';
    $res = $cancionObj->eliminarCancion($id);
    if ($res) {
        Utilidades::registrarLog('auditoria', "[CRUD] Admin ID:{$_SESSION['usuario_id']} ELIMINO (logico) cancion ID:{$id}.");
    }
    echo json_encode(['status' => $res ? 'success' : 'error', 'message' => $res ? 'Canción desactivada.' : 'Error al eliminar.']);
}

function listarGeneros() {
    $generoObj = new Genero();
    echo json_encode(['status' => 'success', 'data' => $generoObj->listarGeneros()]);
}

function crearGenero() {
    $nombre = $_POST['nombre'] ?? '';
    $generoObj = new Genero();
    echo json_encode($generoObj->crearGenero($nombre));
}

function actualizarGenero() {
    $id = (int)($_POST['id_genero'] ?? 0);
    $nombre = $_POST['nombre'] ?? '';
    $generoObj = new Genero();
    echo json_encode($generoObj->actualizarGenero($id, $nombre));
}

function eliminarGenero() {
    $id = (int)($_POST['id_genero'] ?? 0);
    $generoObj = new Genero();
    echo json_encode($generoObj->eliminarGenero($id));
}
?>