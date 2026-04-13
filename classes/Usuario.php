<?php
/**
 * CLASE: Usuario
 * PROPÓSITO:
 * Gestionar todas las operaciones de base de datos relacionadas con los
 * usuarios de la plataforma Soundverse (login, registro, borrado lógico).
 * PATRÓN: MVC - Capa Modelo
 * 1. El constructor instancia la conexión PDO a través de la clase Conexion.
 * 2. Método privado logError() registra excepciones en logs/errores.log.
 * 3. Todas las consultas usan try-catch capturando PDOException.
 * 4. Borrado LÓGICO (UPDATE estado_disponible = 0) — DELETE está prohibido.
 * 5. La conexión se destruye al final de cada método público ($this->db = null).
 */

// CORRECCIÓN: 'C' mayúscula en Conexion.php para que funcione en Linux/servidor
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/Utilidades.php';

class Usuario
{
    /** @var PDO|null Objeto de conexión a la base de datos */
    private $db;

    /**
     * Establece la conexión a la base de datos al instanciar la clase.
     */
    public function __construct()
    {
        $this->db = (new Conexion())->conectar();
        $this->ensureSesionColumn();
    }

    /**
     * [AUTO-MIGRACIÓN] Asegura que la columna id_sesion_activa exista para el control de sesiones.
     * Útil para cuando el proyecto se comparte entre compañeros con diferentes bases de datos.
     */
    private function ensureSesionColumn() {
        if (!$this->db) return;
        try {
            $this->db->query("SELECT id_sesion_activa FROM Usuario LIMIT 1");
        } catch (PDOException $e) {
            // Si falla, es probable que la columna no exista
            try {
                $this->db->exec("ALTER TABLE Usuario ADD id_sesion_activa VARCHAR(255) NULL");
            } catch (PDOException $e2) {
                // Silencioso: si falla el ALTER, no bloqueamos el sistema
            }
        }
    }

    /**
     * Registra un mensaje de error en el archivo de log del sistema.
     * Los errores NO se muestran al usuario, solo se guardan en el archivo.
     * @param string $mensaje Descripción del error a registrar.
     */
    private function logError($mensaje)
    {
        $log_file = __DIR__ . '/../logs/errores.log';
        $entrada = '[' . date('Y-m-d H:i:s') . '] [Usuario] ' . $mensaje . PHP_EOL;
        file_put_contents($log_file, $entrada, FILE_APPEND);
    }

    /**
     * Comprueba si un correo electrónico ya está registrado en la BD.
     * @param string $email Correo a verificar.
     * @return bool true si el correo ya existe, false si está disponible.
     */
    public function verificarCorreoExistente($email)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM Usuario WHERE correo = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return ((int) $resultado['total']) > 0;
        } catch (PDOException $e) {
            $this->logError('verificarCorreoExistente(): ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }

    /**
     * Registra un nuevo usuario en la BD con la contraseña encriptada.
     * @param int    $id_tipo      ID del tipo de suscripción (1=Free, 2=Premium).
     * @param string $nombre       Nombre completo del usuario.
     * @param string $email        Correo electrónico único.
     * @param string $password     Contraseña en texto plano (se encripta con BCRYPT).
     * @param int    $es_admin     0 = usuario normal, 1 = administrador.
     * @param string $codigo_pais  Código ISO 3166-1 alpha-2 del país (ej: 'MX', 'GT', 'SV').
     * @return bool true si el INSERT fue exitoso, false si falló.
     */
    public function guardarUsuario($id_tipo, $nombre, $email, $password, $es_admin = 0, $codigo_pais = 'MX')
    {
        try {
            // Reconectar porque verificarCorreoExistente() pudo cerrar $this->db
            if (!$this->db) {
                $this->db = (new Conexion())->conectar();
            }
            $clave_hash = password_hash($password, PASSWORD_BCRYPT);
            // Sanitizar codigo_pais: solo letras mayúsculas, exactamente 2 caracteres
            $codigo_pais = strtoupper(preg_replace('/[^A-Za-z]/', '', $codigo_pais));
            if (strlen($codigo_pais) !== 2) $codigo_pais = 'MX';

            $sql = "INSERT INTO Usuario (FK_id_tipo, nombre_completo, correo, codigo_pais, clave_hash, es_admin)
                    VALUES (:tipo, :nombre, :email, :pais, :hash, :admin)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':tipo',   $id_tipo,     PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':email',  $email);
            $stmt->bindParam(':pais',   $codigo_pais);
            $stmt->bindParam(':hash',   $clave_hash);
            $stmt->bindParam(':admin',  $es_admin,    PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError('guardarUsuario(): ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }

    /**
     * Desactiva un usuario cambiando su campo estado_disponible a 0.
     * REGLA ESTRICTA: Está PROHIBIDO usar DELETE.
     * @param int $id_usuario ID del usuario a desactivar.
     * @return bool true si se actualizó al menos una fila, false si falló.
     */
    public function eliminarUsuarioLogico($id_usuario)
    {
        try {
            if (!$this->db) {
                $this->db = (new Conexion())->conectar();
            }
            $sql = "UPDATE Usuario SET estado_disponible = 0 WHERE PK_id_usuario = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError('eliminarUsuarioLogico(): ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }

    /**
     * Valida las credenciales del usuario contra la base de datos.
     * Usa password_verify() para comparar contra el hash almacenado.
     * Solo permite el acceso a usuarios con estado_disponible = 1 (activos).
     * @param string $email    Correo del usuario.
     * @param string $password Contraseña en texto plano.
     * @return array|false Arreglo asociativo con los datos del usuario si las
     *                     credenciales son correctas, false si fallan.
     */
    public function login($email, $password)
    {
        try {
            if (!$this->db) {
                $this->db = (new Conexion())->conectar();
            }
            $sql = "SELECT PK_id_usuario, nombre_completo, clave_hash, FK_id_tipo, es_admin, id_sesion_activa
                    FROM Usuario
                    WHERE correo = :email AND estado_disponible = 1
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $usuario['clave_hash'])) {
                    return $usuario;
                }
            }
            return false;
        } catch (PDOException $e) {
            $this->logError('login(): ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }

    /**
     * Retorna todos los usuarios activos con su tipo de suscripción.
     * @return array Arreglo de usuarios activos, o arreglo vacío si hay error.
     */
    public function listarUsuarios()
    {
        try {
            if (!$this->db) {
                $this->db = (new Conexion())->conectar();
            }
            $sql = "SELECT u.PK_id_usuario, u.nombre_completo, u.correo,
                           u.FK_id_tipo, t.nombre_plan, u.es_admin
                    FROM Usuario u
                    INNER JOIN Tipo_Suscripcion t ON u.FK_id_tipo = t.PK_id_tipo
                    WHERE u.estado_disponible = 1
                    ORDER BY u.PK_id_usuario DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('listarUsuarios(): ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }

    /**
     * Actualiza los datos de un usuario existente.
     * Si el password viene vacío, NO se modifica la clave actual (por seguridad).
     * @param int    $id_usuario ID del usuario a modificar.
     * @param int    $id_tipo    Nuevo tipo de suscripción (1=Free, 2=Premium).
     * @param string $nombre     Nuevo nombre completo.
     * @param string $email      Nuevo correo electrónico.
     * @param string $password   Nueva contraseña (vacío = no cambiar).
     * @return bool true si la operación fue exitosa, false si falló.
     */
    public function actualizarUsuario($id_usuario, $id_tipo, $nombre, $email, $password, $es_admin = 0)
    {
        try {
            if (!$this->db) {
                $this->db = (new Conexion())->conectar();
            }
            if (empty($password)) {
                $sql = "UPDATE Usuario
                        SET FK_id_tipo = :tipo, nombre_completo = :nombre, correo = :email, es_admin = :admin
                        WHERE PK_id_usuario = :id";
                $stmt = $this->db->prepare($sql);
            } else {
                $sql = "UPDATE Usuario
                        SET FK_id_tipo = :tipo, nombre_completo = :nombre,
                            correo = :email, clave_hash = :hash, es_admin = :admin
                        WHERE PK_id_usuario = :id";
                $stmt = $this->db->prepare($sql);
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt->bindParam(':hash', $hash);
            }
            $stmt->bindParam(':tipo',   $id_tipo,    PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':email',  $email);
            $stmt->bindParam(':admin',  $es_admin,   PDO::PARAM_INT);
            $stmt->bindParam(':id',     $id_usuario, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError('actualizarUsuario(): ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }

    /**
     * ACTUALIZAR PERFIL (CU-03)
     */
    public function actualizarPerfil($id_usuario, $nombre, $email) {
        try {
            if (!$this->db) $this->db = (new Conexion())->conectar();
            $sql = "UPDATE Usuario SET nombre_completo = ?, correo = ? WHERE PK_id_usuario = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nombre, $email, $id_usuario]);
            return true;
        } catch (PDOException $e) {
            $this->logError('actualizarPerfil(): ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }

    /**
     * ACTUALIZAR SEGURIDAD (CU-03)
     */
    public function actualizarPassword($id_usuario, $antigua, $nueva) {
        try {
            if (!$this->db) $this->db = (new Conexion())->conectar();
            
            // Verificar password actual
            $sql = "SELECT clave_hash FROM Usuario WHERE PK_id_usuario = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario]);
            $hash = $stmt->fetchColumn();
            
            if (password_verify($antigua, $hash)) {
                $nuevo_hash = password_hash($nueva, PASSWORD_BCRYPT);
                $sql_upd = "UPDATE Usuario SET clave_hash = ? WHERE PK_id_usuario = ?";
                $stmt_upd = $this->db->prepare($sql_upd);
                $stmt_upd->execute([$nuevo_hash, $id_usuario]);
                return true;
            }
            return false; // Contraseña antigua no coincide
        } catch (PDOException $e) {
            $this->logError('actualizarPassword(): ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }

    /**
     * Obtener un solo usuario
     */
    public function obtenerUsuario($id_usuario) {
        try {
            if (!$this->db) $this->db = (new Conexion())->conectar();
            $sql = "SELECT PK_id_usuario, nombre_completo, correo, FK_id_tipo FROM Usuario WHERE PK_id_usuario = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('obtenerUsuario(): ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }

    /**
     * Verifica si un correo ya está siendo usado por OTRO usuario distinto al indicado.
     * Se usa al editar perfil para detectar colisiones sin afectar al usuario actual.
     * @param string $email       Correo a verificar
     * @param int    $id_excluir  ID del usuario que NO debe contar (el que está editando)
     * @return bool true si el correo está en uso por otra cuenta, false si está libre
     */
    public function verificarCorreoEnOtroUsuario($email, $id_excluir) {
        try {
            if (!$this->db) $this->db = (new Conexion())->conectar();
            $sql  = "SELECT COUNT(*) AS total FROM Usuario WHERE correo = ? AND PK_id_usuario != ? AND estado_disponible = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email, $id_excluir]);
            $res  = $stmt->fetch(PDO::FETCH_ASSOC);
            return ((int) $res['total']) > 0;
        } catch (PDOException $e) {
            $this->logError('verificarCorreoEnOtroUsuario(): ' . $e->getMessage());
            return false; // En caso de error, permitir continuar (fail-open controlado)
        } finally {
            $this->db = null;
        }
    }
    /**
     * Actualiza el identificador de sesión activa para control de dispositivos concurrentes.
     * @param int    $id_usuario
     * @param string|null $session_id  ID de la sesión actual o null para cerrar.
     * @return bool
     */
    public function actualizarSesionActiva($id_usuario, $session_id) {
        try {
            if (!$this->db) $this->db = (new Conexion())->conectar();
            $sql = "UPDATE Usuario SET id_sesion_activa = ? WHERE PK_id_usuario = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$session_id, $id_usuario]);
        } catch (PDOException $e) {
            $this->logError('actualizarSesionActiva(): ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }

    public function obtenerLimitesSuscripcion($id_tipo) {
        try {
            if (!$this->db) $this->db = (new Conexion())->conectar();
            $stmt = $this->db->prepare("SELECT limite_playlists FROM Tipo_Suscripcion WHERE PK_id_tipo = ?");
            $stmt->execute([$id_tipo]);
            return $stmt->fetchColumn() ?: 15; // 15 como fallback seguro
        } catch (PDOException $e) { return 15; }
    }

    public function cancelarSuscripcion($id_usuario) {
        try {
            if (!$this->db) $this->db = (new Conexion())->conectar();
            // Degrada bruscamente al usuario y reconecta políticas free
            $sql = "UPDATE Usuario SET FK_id_tipo = 1 WHERE PK_id_usuario = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario]);
            
            if($stmt->rowCount() > 0){
                 Utilidades::registrarLog('pagos', "[DOWNGRADE] Usuario ID:{$id_usuario} regresó a plan Free");
                 return true;
            }
            return false;
        } catch (PDOException $e) {
            $this->logError('cancelarSuscripcion(): ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }
}
