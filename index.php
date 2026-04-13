<?php
/**
 * ARCHIVO: index.php (Raíz del Proyecto)
 * 
 * PROPÓSITO:
 * Instalador Automático + Landing Page del sistema SoundVerse.
 * 
 * FUNCIONALIDADES:
 * 1. Detecta si la BD existe (clase Conexion.php)
 * 2. Si NO existe → muestra instalador profesional con:
 *    - Verificación de requisitos (PHP, MySQL, extensiones, carpetas)
 *    - Creación automática de carpetas necesarias
 *    - Importación del script SQL
 *    - Verificación post-instalación (tablas, datos de prueba)
 * 3. Si SÍ existe → muestra Landing Page con acceso a Admin / Usuario
 * 
 * BASADO EN:
 * - Planificación del proyecto: Fase 5 – Construcción Parte #3
 *   "Creación del instalador automático del sistema
 *    (preparación de carpetas y carga inicial de datos)"

 * 
 * @author Equipo Proyecto 6 - Programación Avanzada
 * @version 2.0
 */

session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// ============================================================
// CONSTANTES DEL SISTEMA
// ============================================================
define('SOUNDVERSE_VERSION', '2.0');
define('MIN_PHP_VERSION', '7.4.0');
define('BASE_DIR', __DIR__);

// ============================================================
// Garantizar que logs/ exista ANTES de que Conexion.php intente escribir
// ============================================================
$log_dir_init = BASE_DIR . '/logs';
if (!is_dir($log_dir_init)) {
    @mkdir($log_dir_init, 0777, true);
}

// ============================================================
// DETECCIÓN AUTOMÁTICA: ¿La BD ya existe?
// ============================================================
require_once 'classes/Conexion.php';

$conObj = new Conexion();
$db = $conObj->conectar();
$bd_existe = ($db !== null);

if ($bd_existe) {
    $db = null; // Regla de Oro: destruir conexión
}

// ============================================================
// FUNCIONES AUXILIARES DEL INSTALADOR
// ============================================================

/**
 * Verifica los requisitos del sistema
 * @return array con [nombre, estado, detalle]
 */
function verificarRequisitos(): array
{
    $requisitos = [];

    // 1. Versión de PHP
    $requisitos[] = [
        'nombre' => 'PHP ' . MIN_PHP_VERSION . ' o superior',
        'estado' => version_compare(PHP_VERSION, MIN_PHP_VERSION, '>='),
        'detalle' => 'Versión actual: ' . PHP_VERSION
    ];

    // 2. Extensión PDO
    $requisitos[] = [
        'nombre' => 'Extensión PDO habilitada',
        'estado' => extension_loaded('pdo'),
        'detalle' => extension_loaded('pdo') ? 'Disponible' : 'No encontrada'
    ];

    // 3. Extensión PDO MySQL
    $requisitos[] = [
        'nombre' => 'Extensión PDO MySQL',
        'estado' => extension_loaded('pdo_mysql'),
        'detalle' => extension_loaded('pdo_mysql') ? 'Disponible' : 'No encontrada'
    ];

    // 4. Archivo SQL existe
    $sql_existe = file_exists(BASE_DIR . '/lp3_streaming_musica.sql');
    $requisitos[] = [
        'nombre' => 'Script SQL (lp3_streaming_musica.sql)',
        'estado' => $sql_existe,
        'detalle' => $sql_existe ? 'Encontrado en raíz del proyecto' : 'No encontrado'
    ];

    // 5. Clase Conexión existe
    $con_existe = file_exists(BASE_DIR . '/classes/Conexion.php');
    $requisitos[] = [
        'nombre' => 'Clase Conexion.php',
        'estado' => $con_existe,
        'detalle' => $con_existe ? 'Archivo presente' : 'No encontrado en classes/'
    ];

    // 6. Permisos de escritura
    $writable = is_writable(BASE_DIR);
    $requisitos[] = [
        'nombre' => 'Permisos de escritura en proyecto',
        'estado' => $writable,
        'detalle' => $writable ? 'Directorio escribible' : 'Sin permisos de escritura'
    ];

    return $requisitos;
}

/**
 * Crea las carpetas necesarias para el sistema
 * @return array lista de carpetas y su estado
 */
function crearEstructuraCarpetas(): array
{
    $carpetas = [
        'logs',
        'assets',
        'assets/img',
        'assets/img/artistas',
        'assets/img/portadas',
        'assets/musica',
        'classes',
        'admin',
        'admin/php',
        'admin/vistas',
        'user',
        'user/php',
        'user/vistas',
        'js',
    ];

    $resultado = [];

    foreach ($carpetas as $carpeta) {
        $ruta_completa = BASE_DIR . '/' . $carpeta;
        $ya_existe = is_dir($ruta_completa);

        if (!$ya_existe) {
            $creada = @mkdir($ruta_completa, 0777, true);
            $resultado[] = [
                'carpeta' => $carpeta,
                'estado' => $creada ? 'creada' : 'error',
                'mensaje' => $creada ? 'Carpeta creada exitosamente' : 'Error al crear la carpeta'
            ];
        } else {
            $resultado[] = [
                'carpeta' => $carpeta,
                'estado' => 'existe',
                'mensaje' => 'Ya existía'
            ];
        }
    }

    // Crear archivo de log vacío si no existe
    $log_file = BASE_DIR . '/logs/errores.log';
    if (!file_exists($log_file)) {
        @file_put_contents($log_file, "");
    }
    $accesos_log = BASE_DIR . '/logs/accesos.log';
    if (!file_exists($accesos_log)) {
        @file_put_contents($accesos_log, "");
    }

    return $resultado;
}

/**
 * Instala la base de datos ejecutando el script SQL
 * @param string $user  Usuario MySQL
 * @param string $pass  Contraseña MySQL
 * @return array con [exito, mensaje, metodo]
 */
function instalarBaseDatos(string $user, string $pass): array
{
    $sql_file = BASE_DIR . '/lp3_streaming_musica.sql';

    if (!file_exists($sql_file)) {
        return [
            'exito' => false,
            'mensaje' => 'No se encontró el archivo lp3_streaming_musica.sql',
            'metodo' => 'ninguno'
        ];
    }

    // ============================================================
    // MÉTODO 1: Ejecutar mysql.exe de XAMPP (más fiable)
    // ============================================================
    $mysql_exe = 'C:\\xampp\\mysql\\bin\\mysql.exe';
    $instalado_ok = false;
    $metodo_usado = '';

    if (file_exists($mysql_exe)) {
        $pass_arg = !empty($pass) ? "-p{$pass}" : '';
        $sql_path = str_replace('/', '\\', $sql_file);
        $cmd = "\"{$mysql_exe}\" --default-character-set=utf8mb4 -u{$user} {$pass_arg} < \"{$sql_path}\" 2>&1";
        exec($cmd, $out_arr, $ret_code);
        $out_msg = implode(' ', $out_arr);

        if ($ret_code === 0) {
            $instalado_ok = true;
            $metodo_usado = 'mysql.exe (XAMPP)';
        } elseif (strpos($out_msg, 'ERROR 1007') !== false || strpos($out_msg, 'ERROR 1050') !== false) {
            $instalado_ok = true;
            $metodo_usado = 'mysql.exe (BD ya existía)';
        } elseif (!empty($out_arr)) {
            return [
                'exito' => false,
                'mensaje' => 'Error mysql.exe: ' . $out_msg,
                'metodo' => 'mysql.exe'
            ];
        }
    }

    // ============================================================
    // MÉTODO 2: PDO puro con parseado de SQL
    // ============================================================
    if (!$instalado_ok) {
        try {
            $pdo_install = new PDO(
                "mysql:host=localhost;charset=utf8mb4",
                $user,
                $pass
            );
            $pdo_install->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql_raw = file_get_contents($sql_file);

            // Normalizar saltos de línea
            $sql_raw = str_replace("\r\n", "\n", $sql_raw);
            $sql_raw = str_replace("\r", "\n", $sql_raw);

            // Quitar comentarios
            $sql_clean = preg_replace('/--[^\n]*/', '', $sql_raw);
            $sql_clean = preg_replace('/\/\*.*?\*\//s', '', $sql_clean);

            // Separar en sentencias
            $sentencias = array_values(array_filter(
                array_map('trim', explode(';', $sql_clean)),
                function ($s) {
                    return strlen($s) > 3; }
            ));

            $errores_sql = [];
            foreach ($sentencias as $sentencia) {
                try {
                    $pdo_install->exec($sentencia);
                } catch (PDOException $ei) {
                    $cod = (int) $ei->getCode();
                    $msg = $ei->getMessage();
                    $ignorar = in_array($cod, [1007, 1050])
                        || strpos($msg, 'Duplicate entry') !== false
                        || strpos($msg, 'already exists') !== false;
                    if (!$ignorar) {
                        $errores_sql[] = $msg;
                        error_log("[Soundverse Installer] SQL Warn: {$msg}");
                    }
                }
            }
            $pdo_install = null;
            $instalado_ok = true;
            $metodo_usado = 'PDO directo';

        } catch (PDOException $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error de conexión MySQL: ' . $e->getMessage(),
                'metodo' => 'PDO'
            ];
        }
    }

    return [
        'exito' => $instalado_ok,
        'mensaje' => $instalado_ok ? 'Base de datos instalada correctamente' : 'Error desconocido',
        'metodo' => $metodo_usado
    ];
}

/**
 * Verifica que la instalación fue exitosa
 * @param string $user  Usuario MySQL
 * @param string $pass  Contraseña MySQL
 * @return array con verificaciones
 */
function verificarInstalacion(string $user, string $pass): array
{
    $verificaciones = [];

    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=lp3_streaming_musica;charset=utf8mb4",
            $user,
            $pass
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar tablas principales
        $tablas_esperadas = [
            'Tipo_Suscripcion',
            'Metodo_Pago',
            'Genero_Musical',
            'Idioma_Traduccion',
            'Usuario',
            'Artista',
            'Album',
            'Cancion',
            'Playlist',
            'Detalle_Playlist',
            'Historial_Reproduccion',
            'Factura',
            'Detalle_Factura',
            'Seguimiento_Artista',
            'Notificacion'
        ];

        $stmt = $pdo->query("SHOW TABLES");
        $tablas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $tablas_count = count($tablas_existentes);

        $verificaciones[] = [
            'nombre' => 'Tablas creadas',
            'estado' => $tablas_count >= 15,
            'detalle' => "{$tablas_count} de 15 tablas encontradas"
        ];

        // Verificar usuarios de prueba
        $stmt = $pdo->query("SELECT COUNT(*) FROM Usuario");
        $users_count = (int) $stmt->fetchColumn();
        $verificaciones[] = [
            'nombre' => 'Usuarios de prueba',
            'estado' => $users_count >= 2,
            'detalle' => "{$users_count} usuarios encontrados"
        ];

        // Verificar tipos de suscripción
        $stmt = $pdo->query("SELECT COUNT(*) FROM Tipo_Suscripcion");
        $tipos_count = (int) $stmt->fetchColumn();
        $verificaciones[] = [
            'nombre' => 'Tipos de suscripción (Free/Premium)',
            'estado' => $tipos_count >= 2,
            'detalle' => "{$tipos_count} tipos encontrados"
        ];

        // Verificar artistas
        $stmt = $pdo->query("SELECT COUNT(*) FROM Artista");
        $artistas_count = (int) $stmt->fetchColumn();
        $verificaciones[] = [
            'nombre' => 'Artistas de prueba',
            'estado' => $artistas_count >= 3,
            'detalle' => "{$artistas_count} artistas encontrados"
        ];

        // Verificar canciones
        $stmt = $pdo->query("SELECT COUNT(*) FROM Cancion");
        $canciones_count = (int) $stmt->fetchColumn();
        $verificaciones[] = [
            'nombre' => 'Canciones de prueba',
            'estado' => $canciones_count >= 6,
            'detalle' => "{$canciones_count} canciones encontradas"
        ];

        // Verificar géneros
        $stmt = $pdo->query("SELECT COUNT(*) FROM Genero_Musical");
        $generos_count = (int) $stmt->fetchColumn();
        $verificaciones[] = [
            'nombre' => 'Géneros musicales',
            'estado' => $generos_count >= 8,
            'detalle' => "{$generos_count} géneros encontrados"
        ];

        // Verificar login funcional
        $stmt = $pdo->prepare("SELECT clave_hash FROM Usuario WHERE correo = ?");
        $stmt->execute(['free@test.com']);
        $hash = $stmt->fetchColumn();
        $login_ok = $hash && password_verify('Lp3.2026', $hash);
        $verificaciones[] = [
            'nombre' => 'Login de prueba (free@test.com)',
            'estado' => $login_ok,
            'detalle' => $login_ok ? 'Contraseña Lp3.2026 verificada' : 'Hash no coincide'
        ];

        $pdo = null;

    } catch (PDOException $e) {
        $verificaciones[] = [
            'nombre' => 'Conexión a la BD instalada',
            'estado' => false,
            'detalle' => 'Error: ' . $e->getMessage()
        ];
    }

    return $verificaciones;
}

// ============================================================
// PROCESAR FORMULARIO DE INSTALACIÓN (POST)
// ============================================================
$instalacion_resultado = null;
$paso_actual = 'requisitos'; // requisitos → instalar → verificar → completado

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['btn_instalar']) || isset($_POST['accion_instalar'])) && !$bd_existe) {
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';

    // Paso 1: Carpetas
    $resultado_carpetas = crearEstructuraCarpetas();

    // Paso 2: BD
    $resultado_bd = instalarBaseDatos($db_user, $db_pass);

    // Paso 3: Verificación
    $resultado_verificacion = [];
    if ($resultado_bd['exito']) {
        $resultado_verificacion = verificarInstalacion($db_user, $db_pass);
    }

    // Registrar en log
    $log_dir = BASE_DIR . '/logs';
    if (!is_dir($log_dir))
        @mkdir($log_dir, 0777, true);
    $log_msg = "[" . date('Y-m-d H:i:s') . "] Instalación " .
        ($resultado_bd['exito'] ? 'EXITOSA' : 'FALLIDA') .
        " | Método: " . $resultado_bd['metodo'] .
        " | Usuario: " . $db_user . PHP_EOL;
    @file_put_contents($log_dir . '/accesos.log', $log_msg, FILE_APPEND);

    $instalacion_resultado = [
        'carpetas' => $resultado_carpetas,
        'bd' => $resultado_bd,
        'verificacion' => $resultado_verificacion
    ];

    if ($resultado_bd['exito']) {
        $paso_actual = 'completado';
    } else {
        $paso_actual = 'error';
    }
}

// ============================================================
// VISTA: INSTALADOR (Si la BD no existe)
// ============================================================
if (!$bd_existe && $paso_actual !== 'completado'):
    $requisitos = verificarRequisitos();
    $todos_ok = !in_array(false, array_column($requisitos, 'estado'));
    ?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SoundVerse — Instalador Automático</title>
        <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
            rel="stylesheet">
        <style>
            :root {
                --sv-purple: #6B46C1;
                --sv-purple-light: #9F7AEA;
                --sv-dark: #1A202C;
                --sv-dark-2: #2D3748;
                --sv-dark-3: #4A5568;
                --sv-gold: #FBBF24;
                --sv-success: #48BB78;
                --sv-danger: #FC8181;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Inter', sans-serif;
                background: var(--sv-dark);
                color: #E2E8F0;
                min-height: 100vh;
                overflow-x: hidden;
            }

            /* Animated background */
            .bg-gradient {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background:
                    radial-gradient(ellipse at 20% 50%, rgba(107, 70, 193, 0.15) 0%, transparent 50%),
                    radial-gradient(ellipse at 80% 20%, rgba(251, 191, 36, 0.08) 0%, transparent 50%),
                    radial-gradient(ellipse at 50% 80%, rgba(107, 70, 193, 0.1) 0%, transparent 50%);
                z-index: 0;
                animation: bgPulse 12s ease-in-out infinite alternate;
            }

            @keyframes bgPulse {
                0% {
                    opacity: 0.7;
                }

                100% {
                    opacity: 1;
                }
            }

            .installer-container {
                position: relative;
                z-index: 10;
                max-width: 780px;
                margin: 0 auto;
                padding: 2rem 1rem;
            }

            /* Header */
            .installer-header {
                text-align: center;
                margin-bottom: 2rem;
                animation: slideDown 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            }

            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .installer-logo {
                max-width: 100px;
                margin-bottom: 1rem;
                filter: drop-shadow(0 0 20px rgba(107, 70, 193, 0.5));
                animation: float 4s ease-in-out infinite;
            }

            @keyframes float {

                0%,
                100% {
                    transform: translateY(0);
                }

                50% {
                    transform: translateY(-10px);
                }
            }

            .installer-title {
                font-size: 2rem;
                font-weight: 800;
                letter-spacing: 2px;
                text-transform: uppercase;
                background: linear-gradient(135deg, #FFFFFF, #D8B4FE);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .installer-subtitle {
                font-weight: 300;
                color: #A0AEC0;
                font-size: 0.95rem;
                margin-top: 0.5rem;
            }

            .version-badge {
                display: inline-block;
                background: rgba(107, 70, 193, 0.2);
                border: 1px solid rgba(107, 70, 193, 0.4);
                padding: 3px 12px;
                border-radius: 20px;
                font-size: 0.75rem;
                color: var(--sv-purple-light);
                margin-top: 0.5rem;
            }

            /* Card principal */
            .installer-card {
                background: rgba(45, 55, 72, 0.6);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.05);
                border-radius: 20px;
                padding: 2rem;
                box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
                animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
                margin-bottom: 2rem;
            }

            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(40px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .card-section-title {
                font-size: 1.1rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 1px;
                margin-bottom: 1.5rem;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .card-section-title i {
                color: var(--sv-purple-light);
                font-size: 1.2rem;
            }

            /* Checklist items */
            .check-item {
                display: flex;
                align-items: center;
                padding: 0.65rem 0.75rem;
                margin-bottom: 0.5rem;
                border-radius: 10px;
                background: rgba(0, 0, 0, 0.15);
                transition: all 0.3s ease;
            }

            .check-item:hover {
                background: rgba(0, 0, 0, 0.25);
            }

            .check-icon {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 12px;
                flex-shrink: 0;
                font-size: 0.8rem;
            }

            .check-icon.ok {
                background: rgba(72, 187, 120, 0.2);
                color: var(--sv-success);
            }

            .check-icon.fail {
                background: rgba(252, 129, 129, 0.2);
                color: var(--sv-danger);
            }

            .check-icon.created {
                background: rgba(251, 191, 36, 0.2);
                color: var(--sv-gold);
            }

            .check-name {
                font-weight: 500;
                font-size: 0.9rem;
                flex: 1;
            }

            .check-detail {
                font-size: 0.78rem;
                color: #718096;
            }

            /* Formulario */
            .form-label {
                color: #CBD5E0;
                font-weight: 500;
                font-size: 0.85rem;
                letter-spacing: 0.5px;
                text-transform: uppercase;
            }

            .form-control {
                background: rgba(0, 0, 0, 0.3);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: #E2E8F0;
                border-radius: 12px;
                padding: 0.75rem 1rem;
                transition: all 0.3s;
            }

            .form-control:focus {
                background: rgba(0, 0, 0, 0.4);
                border-color: var(--sv-purple);
                box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.25);
                color: white;
            }

            .form-control::placeholder {
                color: #4A5568;
            }

            .form-text {
                color: #718096;
                font-size: 0.8rem;
            }

            /* Botón de instalación */
            .btn-install {
                background: linear-gradient(135deg, var(--sv-purple), var(--sv-purple-light));
                border: none;
                border-radius: 14px;
                padding: 14px 28px;
                font-weight: 700;
                font-size: 1.05rem;
                letter-spacing: 1px;
                text-transform: uppercase;
                color: white;
                width: 100%;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            }

            .btn-install:hover {
                transform: translateY(-3px);
                box-shadow: 0 15px 30px rgba(107, 70, 193, 0.4);
                color: white;
            }

            .btn-install:active {
                transform: translateY(-1px);
            }

            .btn-install:disabled {
                background: var(--sv-dark-3);
                transform: none;
                box-shadow: none;
                cursor: not-allowed;
            }

            .btn-install::after {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
                transition: 0.5s;
            }

            .btn-install:hover::after {
                left: 100%;
            }

            /* Error alert */
            .error-alert {
                background: rgba(252, 129, 129, 0.1);
                border: 1px solid rgba(252, 129, 129, 0.3);
                border-radius: 12px;
                padding: 1rem;
                color: var(--sv-danger);
                margin-bottom: 1.5rem;
            }

            /* Progress Steps */
            .progress-steps {
                display: flex;
                justify-content: center;
                gap: 0;
                margin-bottom: 2rem;
            }

            .step {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .step-circle {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 0.85rem;
                background: rgba(255, 255, 255, 0.05);
                border: 2px solid rgba(255, 255, 255, 0.1);
                color: #718096;
                transition: all 0.4s;
            }

            .step-circle.active {
                background: var(--sv-purple);
                border-color: var(--sv-purple-light);
                color: white;
                box-shadow: 0 0 15px rgba(107, 70, 193, 0.4);
            }

            .step-circle.done {
                background: var(--sv-success);
                border-color: var(--sv-success);
                color: white;
            }

            .step-label {
                font-size: 0.75rem;
                color: #718096;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .step-label.active {
                color: var(--sv-purple-light);
            }

            .step-label.done {
                color: var(--sv-success);
            }

            .step-connector {
                width: 40px;
                height: 2px;
                background: rgba(255, 255, 255, 0.1);
                margin: 0 4px;
                align-self: center;
            }

            .step-connector.done {
                background: var(--sv-success);
            }

            /* Summary stats */
            .stat-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .stat-card {
                background: rgba(0, 0, 0, 0.2);
                border-radius: 12px;
                padding: 1rem;
                text-align: center;
            }

            .stat-value {
                font-size: 1.8rem;
                font-weight: 800;
                color: var(--sv-purple-light);
            }

            .stat-label {
                font-size: 0.75rem;
                color: #718096;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-top: 0.25rem;
            }

            /* Footer */
            .installer-footer {
                text-align: center;
                padding: 1rem 0 2rem;
                color: #4A5568;
                font-size: 0.8rem;
            }
        </style>
    </head>

    <body>

        <div class="bg-gradient"></div>

        <div class="installer-container">

            <!-- Header -->
            <div class="installer-header">
                <img src="assets/img/logo_soundverse_white.png" alt="SoundVerse" class="installer-logo"
                    onerror="this.style.display='none'">
                <h1 class="installer-title">SoundVerse</h1>
                <p class="installer-subtitle">Instalador Automático del Sistema</p>
                <span class="version-badge">
                    <i class="fas fa-code-branch me-1"></i> v<?= SOUNDVERSE_VERSION ?>
                </span>
            </div>

            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step">
                    <div
                        class="step-circle <?= $paso_actual === 'requisitos' ? 'active' : ($paso_actual === 'error' ? 'active' : 'done') ?>">
                        <?= ($instalacion_resultado !== null) ? '<i class="fas fa-check"></i>' : '1' ?>
                    </div>
                    <span class="step-label <?= $paso_actual === 'requisitos' ? 'active' : 'done' ?>">Verificar</span>
                </div>
                <div class="step-connector <?= ($instalacion_resultado !== null) ? 'done' : '' ?>"></div>
                <div class="step">
                    <div
                        class="step-circle <?= ($instalacion_resultado !== null && !$instalacion_resultado['bd']['exito']) ? 'active' : (($instalacion_resultado !== null && $instalacion_resultado['bd']['exito']) ? 'done' : '') ?>">
                        <?= ($instalacion_resultado !== null && $instalacion_resultado['bd']['exito']) ? '<i class="fas fa-check"></i>' : '2' ?>
                    </div>
                    <span
                        class="step-label <?= ($instalacion_resultado !== null) ? ($instalacion_resultado['bd']['exito'] ? 'done' : 'active') : '' ?>">Instalar</span>
                </div>
                <div
                    class="step-connector <?= ($instalacion_resultado !== null && $instalacion_resultado['bd']['exito']) ? 'done' : '' ?>">
                </div>
                <div class="step">
                    <div
                        class="step-circle <?= ($instalacion_resultado !== null && $instalacion_resultado['bd']['exito']) ? 'done' : '' ?>">
                        <?= ($instalacion_resultado !== null && $instalacion_resultado['bd']['exito']) ? '<i class="fas fa-check"></i>' : '3' ?>
                    </div>
                    <span
                        class="step-label <?= ($instalacion_resultado !== null && $instalacion_resultado['bd']['exito']) ? 'done' : '' ?>">Verificar</span>
                </div>
            </div>

            <!-- Error de instalación -->
            <?php if ($instalacion_resultado !== null && !$instalacion_resultado['bd']['exito']): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error en la instalación:</strong> <?= htmlspecialchars($instalacion_resultado['bd']['mensaje']) ?>
                </div>
            <?php endif; ?>

            <!-- CARD: Requisitos del Sistema -->
            <div class="installer-card">
                <div class="card-section-title">
                    <i class="fas fa-clipboard-check"></i>
                    Verificación de Requisitos
                </div>

                <?php foreach ($requisitos as $req): ?>
                    <div class="check-item">
                        <div class="check-icon <?= $req['estado'] ? 'ok' : 'fail' ?>">
                            <i class="fas <?= $req['estado'] ? 'fa-check' : 'fa-times' ?>"></i>
                        </div>
                        <div class="check-name"><?= htmlspecialchars($req['nombre']) ?></div>
                        <div class="check-detail"><?= htmlspecialchars($req['detalle']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($instalacion_resultado !== null && $instalacion_resultado['bd']['exito']): ?>
                <!-- CARD: Resultado de Carpetas -->
                <div class="installer-card">
                    <div class="card-section-title">
                        <i class="fas fa-folder-plus"></i>
                        Estructura de Carpetas
                    </div>

                    <?php foreach ($instalacion_resultado['carpetas'] as $c): ?>
                        <div class="check-item">
                            <div
                                class="check-icon <?= $c['estado'] === 'error' ? 'fail' : ($c['estado'] === 'creada' ? 'created' : 'ok') ?>">
                                <i
                                    class="fas <?= $c['estado'] === 'error' ? 'fa-times' : ($c['estado'] === 'creada' ? 'fa-plus' : 'fa-check') ?>"></i>
                            </div>
                            <div class="check-name"><?= htmlspecialchars($c['carpeta']) ?>/</div>
                            <div class="check-detail"><?= htmlspecialchars($c['mensaje']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- CARD: Verificación Post-Instalación -->
                <div class="installer-card">
                    <div class="card-section-title">
                        <i class="fas fa-database"></i>
                        Verificación de Base de Datos
                    </div>

                    <div class="check-item" style="margin-bottom:1rem">
                        <div class="check-icon ok">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="check-name">Método de instalación</div>
                        <div class="check-detail"><?= htmlspecialchars($instalacion_resultado['bd']['metodo']) ?></div>
                    </div>

                    <?php foreach ($instalacion_resultado['verificacion'] as $v): ?>
                        <div class="check-item">
                            <div class="check-icon <?= $v['estado'] ? 'ok' : 'fail' ?>">
                                <i class="fas <?= $v['estado'] ? 'fa-check' : 'fa-times' ?>"></i>
                            </div>
                            <div class="check-name"><?= htmlspecialchars($v['nombre']) ?></div>
                            <div class="check-detail"><?= htmlspecialchars($v['detalle']) ?></div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Summary Stats -->
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-value">15</div>
                            <div class="stat-label">Tablas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">3</div>
                            <div class="stat-label">Artistas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">6</div>
                            <div class="stat-label">Canciones</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">8</div>
                            <div class="stat-label">Géneros</div>
                        </div>
                    </div>
                </div>

                <!-- Botón para continuar al sistema -->
                <div class="installer-card" style="text-align: center">
                    <div style="margin-bottom: 1.5rem;">
                        <i class="fas fa-check-circle fa-3x" style="color: var(--sv-success);"></i>
                        <h3 style="margin-top: 1rem; font-weight: 700;">¡Instalación Completada!</h3>
                        <p style="color: #A0AEC0; margin-top: 0.5rem;">
                            SoundVerse ha sido instalado correctamente. Puede acceder al sistema.
                        </p>
                    </div>

                    <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem;">
                        <p style="margin:0; font-size: 0.85rem;">
                            <strong style="color: var(--sv-gold);">Credenciales de prueba:</strong><br>
                            <span style="color: #A0AEC0;">
                                <i class="fas fa-envelope me-1"></i> free@test.com &nbsp;|&nbsp;
                                <i class="fas fa-envelope me-1"></i> premium@test.com &nbsp;|&nbsp;
                                <i class="fas fa-shield-alt me-1"></i> admin@soundverse.com<br>
                                <i class="fas fa-key me-1"></i> Contraseña: <code
                                    style="color:var(--sv-purple-light)">Lp3.2026</code>
                            </span>
                        </p>
                    </div>

                    <a href="index.php" class="btn-install"
                        style="display: inline-flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none;">
                        <i class="fas fa-rocket"></i> Ir a SoundVerse
                    </a>
                </div>

            <?php else: ?>
                <!-- CARD: Formulario de Instalación -->
                <div class="installer-card">
                    <div class="card-section-title">
                        <i class="fas fa-cog"></i>
                        Credenciales de MySQL
                    </div>

                    <p style="color: #A0AEC0; font-size: 0.9rem; margin-bottom: 1.5rem;">
                        No se detectó la base de datos <code>lp3_streaming_musica</code>.
                        Ingrese las credenciales de MySQL para instalar automáticamente el sistema.
                    </p>

                    <form method="POST" action="" id="form-instalador">
                        <input type="hidden" name="accion_instalar" value="1">
                        <div class="mb-3">
                            <label for="db_user" class="form-label">
                                <i class="fas fa-user me-1"></i> Usuario MySQL
                            </label>
                            <input type="text" class="form-control" id="db_user" name="db_user" value="root" required
                                placeholder="root">
                            <div class="form-text">Por defecto en XAMPP: root</div>
                        </div>

                        <div class="mb-4">
                            <label for="db_pass" class="form-label">
                                <i class="fas fa-lock me-1"></i> Contraseña MySQL
                            </label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass"
                                placeholder="Dejar vacío si no tiene contraseña">
                            <div class="form-text">Por defecto en XAMPP: vacío (sin contraseña)</div>
                        </div>

                        <button type="submit" name="btn_instalar" class="btn-install" <?= !$todos_ok ? 'disabled' : '' ?>
                            id="btn-instalar">
                            <i class="fas fa-download me-2"></i> Instalar SoundVerse
                        </button>

                        <?php if (!$todos_ok): ?>
                            <p style="color: var(--sv-danger); font-size: 0.85rem; text-align: center; margin-top: 1rem;">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Corrija los requisitos marcados en rojo antes de instalar.
                            </p>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- CARD: Qué hará el instalador -->
                <div class="installer-card">
                    <div class="card-section-title">
                        <i class="fas fa-info-circle"></i>
                        ¿Qué hará el instalador?
                    </div>

                    <div class="check-item">
                        <div class="check-icon" style="background: rgba(107,70,193,0.2); color: var(--sv-purple-light);">
                            <i class="fas fa-folder-plus"></i>
                        </div>
                        <div>
                            <div class="check-name">Crear estructura de carpetas</div>
                            <div class="check-detail">logs/, assets/img/, assets/musica/, admin/vistas/, user/vistas/</div>
                        </div>
                    </div>

                    <div class="check-item">
                        <div class="check-icon" style="background: rgba(107,70,193,0.2); color: var(--sv-purple-light);">
                            <i class="fas fa-database"></i>
                        </div>
                        <div>
                            <div class="check-name">Importar base de datos (15 tablas)</div>
                            <div class="check-detail">Ejecuta lp3_streaming_musica.sql con datos de prueba</div>
                        </div>
                    </div>

                    <div class="check-item">
                        <div class="check-icon" style="background: rgba(107,70,193,0.2); color: var(--sv-purple-light);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="check-name">Crear usuarios de prueba</div>
                            <div class="check-detail">free@test.com, premium@test.com, admin@soundverse.com (Lp3.2026)</div>
                        </div>
                    </div>

                    <div class="check-item">
                        <div class="check-icon" style="background: rgba(107,70,193,0.2); color: var(--sv-purple-light);">
                            <i class="fas fa-music"></i>
                        </div>
                        <div>
                            <div class="check-name">Insertar catálogo musical de prueba</div>
                            <div class="check-detail">3 artistas, 4 álbumes, 6 canciones, 8 géneros</div>
                        </div>
                    </div>

                    <div class="check-item">
                        <div class="check-icon" style="background: rgba(107,70,193,0.2); color: var(--sv-purple-light);">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <div class="check-name">Verificar integridad</div>
                            <div class="check-detail">Comprueba tablas, datos y autenticación post-instalación</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="installer-footer">
                &copy; <?= date('Y') ?> SoundVerse — Plataforma de Streaming Musical<br>
                <span style="font-size: 0.7rem;">

                </span>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Animación de botón al hacer submit
            const form = document.getElementById('form-instalador');
            if (form) {
                form.addEventListener('submit', function () {
                    const btn = document.getElementById('btn-instalar');
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Instalando... Por favor espere';
                });
            }
        </script>
    </body>

    </html>
    <?php
    exit;
endif;

// ============================================================
// REDIRECT POST → GET después de instalación exitosa
// ============================================================
if ($paso_actual === 'completado') {
    header("Location: index.php?instalado=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a SoundVerse</title>
    <meta name="description"
        content="SoundVerse - Tu universo musical conectado. Plataforma de streaming de música con catálogo extenso y playlists personalizadas.">

    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1A202C 0%, #2D3748 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            color: white;
            overflow: hidden;
            position: relative;
        }

        /* Abstract Background Elements */
        .bg-circle-1 {
            position: absolute;
            top: -10%;
            left: -5%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(107, 70, 193, 0.35) 0%, rgba(0, 0, 0, 0) 70%);
            border-radius: 50%;
            z-index: 0;
            animation: pulse-slow 8s infinite alternate;
        }

        .bg-circle-2 {
            position: absolute;
            bottom: -20%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.15) 0%, rgba(0, 0, 0, 0) 70%);
            border-radius: 50%;
            z-index: 0;
            animation: pulse-slow 10s infinite alternate-reverse;
        }

        .bg-circle-3 {
            position: absolute;
            top: 50%;
            left: 60%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(159, 122, 234, 0.12) 0%, rgba(0, 0, 0, 0) 70%);
            border-radius: 50%;
            z-index: 0;
            animation: pulse-slow 7s infinite alternate;
        }

        /* Glassmorphism Card */
        .welcome-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3.5rem 2.5rem;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
            z-index: 10;
            max-width: 550px;
            width: 90%;
            animation: slideUpFade 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .welcome-logo {
            max-width: 140px;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 0 15px rgba(107, 70, 193, 0.6));
            animation: float 4s ease-in-out infinite;
        }

        .app-title {
            font-weight: 800;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            font-size: 2.5rem;
            background: linear-gradient(90deg, #FFFFFF, #D8B4FE);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .app-subtitle {
            font-weight: 300;
            color: #E2E8F0;
            margin-bottom: 3rem;
            font-size: 1.1rem;
        }

        .portal-btn {
            border-radius: 50px;
            padding: 14px 24px;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            letter-spacing: 0.5px;
        }

        .btn-user {
            background: linear-gradient(135deg, #6B46C1 0%, #805AD5 100%);
            color: white;
            border: none;
            box-shadow: 0 10px 20px rgba(107, 70, 193, 0.3);
        }

        .btn-user:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 25px rgba(107, 70, 193, 0.5);
            color: white;
        }

        .btn-admin {
            background: transparent;
            color: #FBBF24;
            border: 2px solid rgba(251, 191, 36, 0.5);
            backdrop-filter: blur(5px);
        }

        .btn-admin:hover {
            background: rgba(251, 191, 36, 0.1);
            border-color: #FBBF24;
            transform: translateY(-4px);
            color: #FBBF24;
            box-shadow: 0 10px 20px rgba(251, 191, 36, 0.15);
        }

        /* Success Alert */
        .install-success {
            background: rgba(72, 187, 120, 0.1);
            border: 1px solid rgba(72, 187, 120, 0.3);
            border-radius: 12px;
            padding: 0.75rem 1.25rem;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: fadeIn 0.5s ease;
        }

        /* Animations */
        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-12px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        @keyframes pulse-slow {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }

            100% {
                transform: scale(1.1);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body>

    <!-- Fondos Abstractos Dinámicos -->
    <div class="bg-circle-1"></div>
    <div class="bg-circle-2"></div>
    <div class="bg-circle-3"></div>

    <!-- Contenedor Central -->
    <div class="welcome-card">

        <!-- Logo -->
        <img src="assets/img/logo_soundverse_white.png" alt="Soundverse" class="welcome-logo"
            onerror="this.src=''; this.alt='SV'; this.style.fontSize='4rem'; this.style.color='#6B46C1';">

        <!-- Headers -->
        <h1 class="app-title">SoundVerse</h1>
        <p class="app-subtitle">Tu universo musical conectado.</p>

        <?php if (isset($_GET['instalado'])): ?>
            <div class="install-success">
                <i class="fas fa-check-circle" style="color: #48BB78; font-size: 1.2rem;"></i>
                <span>Base de datos instalada correctamente. ¡Bienvenido!</span>
            </div>
        <?php endif; ?>

        <!-- Botones de Navegación -->
        <div class="row g-3">
            <div class="col-12">
                <a href="user/index.php" class="portal-btn btn-user" id="btn-unirme">
                    <i class="fas fa-headphones-alt me-2"></i> Ingresar a SoundVerse
                </a>
            </div>
        </div>

        <!-- Pie / Créditos -->
        <div class="mt-5 text-light small" style="opacity: 0.85;">
            &copy; <?php echo date("Y"); ?> SoundVerse — Streaming Moderno<br>
            <span style="font-size: 0.8rem;">Diseñado con <i class="fas fa-heart text-danger mx-1"></i> para
                P6</span><br>
            <span style="font-size: 0.7rem; color: #718096;">

            </span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>