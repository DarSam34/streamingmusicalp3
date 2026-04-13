<?php
/**
 * CLASE: Utilidades
 * PROPÓSITO:
 * Centralizar herramientas comunes del sistema como el Logging.

 * base de datos, sino en archivos de texto (.txt / .log).
 */

class Utilidades {

    /**
     * Registra un evento en el log especificado.
     * @param string $tipo_log Nombre del archivo de log (ej. 'accesos', 'errores', 'operaciones')
     * @param string $mensaje El mensaje descriptivo de la acción a registrar.
     */
    public static function registrarLog($tipo_log, $mensaje) {
        // La ruta absoluta usando __DIR__ para asegurar que funcione desde cualquier controlador
        $log_dir = dirname(__DIR__) . '/logs';
        
        // Verifica si la carpeta logs existe, si no, la crea (con permisos)
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        
        $archivo_log = $log_dir . '/' . $tipo_log . '.log';
        
        // Estructura del mensaje: [Fecha/Hora] [IP del Usuario] Mensaje
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
        $fecha = date('Y-m-d H:i:s');
        $linea = "[$fecha] [IP: $ip] $mensaje" . PHP_EOL;
        
        // FILE_APPEND garantiza que no se sobrescriba el archivo, sino que se agregue al final
        file_put_contents($archivo_log, $linea, FILE_APPEND);
    }
}
?>
