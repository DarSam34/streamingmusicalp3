<?php
/**
 * CLASE: Conexion
 * * PROPÓSITO:
 * Establecer y gestionar la conexión a la base de datos MySQL usando PDO.
 * 1. Atributos PRIVATE para encapsular credenciales
 * 2. Uso de PDO con sentencias preparadas
 * 3. DSN con charset=utf8mb4 (soporte tildes y eñes)
 * 4. Manejo de excepciones con try-catch
 * 5. Logs de error en archivo .txt (NO en BD)
 * 6. Método conectar() retorna el objeto PDO
 * 7. La destrucción de la conexión ($db = null) se hará en quien use esta clase
 */

class Conexion
{
    // Atributos privados - Nadie fuera de la clase puede ver las credenciales
    private $host = "localhost";
    private $user = "root";      // En producción, cambiar a usuario específico
    private $pass = "";          // En producción, poner contraseña real
    private $db = "lp3_streaming_musica";

    /**
     * MÉTODO: conectar()
     * * Establece la conexión con la base de datos usando PDO.
     * * @return PDO|null Retorna objeto PDO si éxito, null si error
     * * @throws PDOException Se captura internamente y se registra en log
     */
    public function conectar()
    {
        try {
            // 1. Construir el DSN (Data Source Name)
            // Importante: incluir charset=utf8mb4 para caracteres especiales
            $dsn = "mysql:host=" . $this->host .
                ";dbname=" . $this->db .
                ";charset=utf8mb4";

            // 2. Crear instancia de PDO
            $pdo = new PDO($dsn, $this->user, $this->pass);

            // 3. Configurar PDO para que lance excepciones en errores
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Configuración de Fetch Mode por defecto
            // Esto permite que al hacer SELECT, los datos se manejen como arreglos asociativos.
            // Es necesario para la integración con el módulo de administración.
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // 4. Retornar el objeto de conexión
            return $pdo;

        } catch (PDOException $e) {
            // 5. Registrar el error en archivo de log
            // Usando __DIR__ para obtener ruta absoluta
            $log_file = __DIR__ . "/../logs/errores.log";

            // Formato: [fecha] Error: mensaje
            $mensaje = "[" . date('Y-m-d H:i:s') . "] Error de conexión: " .
                $e->getMessage() . " en " . $e->getFile() .
                " línea " . $e->getLine() . PHP_EOL;

            // Escribir en archivo (3 = FILE_APPEND)
            file_put_contents($log_file, $mensaje, FILE_APPEND);

            // 6. Retornar null para indicar que no hay conexión
            return null;
        }
    }
}