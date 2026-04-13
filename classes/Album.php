<?php
/**
 * CLASE: Album
 * PROPÓSITO: Gestionar el CRUD de Álbumes Musicales.
 */
require_once __DIR__ . '/Conexion.php';

class Album {
    private $db;

    public function __construct() {
        $this->db = (new Conexion())->conectar();
    }

    private function reconectar() {
        if ($this->db === null) {
            $this->db = (new Conexion())->conectar();
        }
    }

    public function listarAlbumes() {
        try {
            $sql = "SELECT a.PK_id_album, a.titulo, a.fecha_lanzamiento, a.ruta_portada, a.discografica, ar.nombre_artistico 
                    FROM Album a
                    INNER JOIN Artista ar ON a.FK_id_artista = ar.PK_id_artista
                    WHERE a.estado_disponible = 1 
                    ORDER BY a.PK_id_album DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $albumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agregar duración formateada a cada álbum
            foreach ($albumes as &$album) {
                $album['duracion_formateada'] = $this->obtenerDuracionFormateada($album['PK_id_album']);
            }
            
            return $albumes;
        } catch (PDOException $e) {
            $this->logError('listarAlbumes(): ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }

    /**
     * @param string $discografica Sello discográfico (nuevo campo)
     */
    public function registrarAlbum($id_artista, $titulo, $fecha_lanzamiento, $ruta_portada, $discografica = '') {
        try {
            $sql = "INSERT INTO Album (FK_id_artista, titulo, fecha_lanzamiento, ruta_portada, discografica) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_artista, $titulo, $fecha_lanzamiento, $ruta_portada, $discografica]);
            if ($stmt->rowCount() > 0) {
                return (int)$this->db->lastInsertId();
            }
            return 0;
        } catch (PDOException $e) {
            $this->logError('registrarAlbum(): ' . $e->getMessage());
            return 0;
        } finally {
            $this->db = null;
        }
    }

    /**
     * @param string $discografica Sello discográfico (nuevo campo)
     */
    public function actualizarAlbum($id, $id_artista, $titulo, $fecha_lanzamiento, $ruta_portada, $discografica = '') {
        try {
            if (empty($ruta_portada)) {
                $sql = "UPDATE Album SET FK_id_artista = ?, titulo = ?, fecha_lanzamiento = ?, discografica = ? WHERE PK_id_album = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id_artista, $titulo, $fecha_lanzamiento, $discografica, $id]);
            } else {
                $sql = "UPDATE Album SET FK_id_artista = ?, titulo = ?, fecha_lanzamiento = ?, ruta_portada = ?, discografica = ? WHERE PK_id_album = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id_artista, $titulo, $fecha_lanzamiento, $ruta_portada, $discografica, $id]);
            }
            return $stmt->rowCount() > 0 ? 1 : 3;
        } catch (PDOException $e) {
            $this->logError('actualizarAlbum(): ' . $e->getMessage());
            return 3;
        } finally {
            $this->db = null;
        }
    }

    public function eliminarAlbum($id) {
        try {
            $sql = "UPDATE Album SET estado_disponible = 0 WHERE PK_id_album = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0 ? 1 : 3;
        } catch (PDOException $e) {
            $this->logError('eliminarAlbum(): ' . $e->getMessage());
            return 3;
        } finally {
            $this->db = null;
        }
    }

    public function obtenerAlbum($id) {
        $this->reconectar();
        try {
            $sql = "SELECT * FROM Album WHERE PK_id_album = ? AND estado_disponible = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('obtenerAlbum(): ' . $e->getMessage());
            return null;
        } finally {
            $this->db = null;
        }
    }

    public function obtenerIdPorNombre($nombre) {
        $nombre = trim($nombre);
        $this->reconectar();
        try {
            $sql = "SELECT PK_id_album FROM Album WHERE titulo = ? AND estado_disponible = 1 LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nombre]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? $res['PK_id_album'] : null;
        } catch (PDOException $e) {
            $this->logError('obtenerIdPorNombre(): ' . $e->getMessage());
            return null;
        } finally {
            $this->db = null;
        }
    }

    /**
     * Calcula la duración total de un álbum sumando los segundos de sus canciones.
     * @param int $id_album
     * @return int Segundos totales
     */
    public function obtenerDuracionTotal($id_album) {
        $this->reconectar();
        try {
            $sql = "SELECT COALESCE(SUM(duracion_segundos), 0) AS total
                    FROM Cancion
                    WHERE FK_id_album = :id_album AND estado_disponible = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_album' => $id_album]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($res['total'] ?? 0);
        } catch (PDOException $e) {
            $this->logError('obtenerDuracionTotal(): ' . $e->getMessage());
            return 0;
        } finally {
            $this->db = null;
        }
    }

    /**
     * Obtiene la duración total formateada (HH:MM:SS o MM:SS)
     * @param int $id_album
     * @return string Duración formateada
     */
    public function obtenerDuracionFormateada($id_album) {
        $segundos = $this->obtenerDuracionTotal($id_album);
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $segundos_rest = $segundos % 60;
        
        if ($horas > 0) {
            return sprintf("%d:%02d:%02d", $horas, $minutos, $segundos_rest);
        }
        return sprintf("%d:%02d", $minutos, $segundos_rest);
    }

    /**
     * Lista todos los artistas disponibles (para el select del formulario de álbumes).
     * @return array
     */
    public function listarArtistas() {
        $this->reconectar();
        try {
            $sql = "SELECT PK_id_artista, nombre_artistico FROM Artista WHERE estado_disponible = 1 ORDER BY nombre_artistico ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('listarArtistas(): ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }

    private function logError($mensaje) {
        $log_file = __DIR__ . '/../logs/errores.log';
        $entrada = '[' . date('Y-m-d H:i:s') . '] [Album] ' . $mensaje . PHP_EOL;
        file_put_contents($log_file, $entrada, FILE_APPEND);
    }
}
?>