<?php
/**
 * CLASE: GestorSeguimiento
 * PROPÓSITO:
 * Manejar la relación de Seguimiento (Follow) entre Usuarios y Artistas.
 * Requisito indispensable del Proyecto 6.
 */

require_once __DIR__ . '/Conexion.php';

class GestorSeguimiento {

    /**
     * Alterna el estado de seguimiento (Follow / Unfollow)
     * Si no lo sigue, lo inserta. Si ya lo sigue, lo elimina.
     */
    public function alternarSeguimiento($id_usuario, $id_artista) {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return ['status' => 'error', 'message' => 'Error BD'];

        try {
            // Verificar si ya lo sigue
            $sqlCheck = "SELECT COUNT(*) as total FROM Seguimiento_Artista WHERE FK_id_usuario = ? AND FK_id_artista = ?";
            $stmtCheck = $db->prepare($sqlCheck);
            $stmtCheck->execute([$id_usuario, $id_artista]);
            $sigue = $stmtCheck->fetch(PDO::FETCH_ASSOC)['total'] > 0;

            if ($sigue) {
                // Unfollow (DELETE físico permitido porque es una tabla pivote transaccional)
                $sqlDel = "DELETE FROM Seguimiento_Artista WHERE FK_id_usuario = ? AND FK_id_artista = ?";
                $stmtDel = $db->prepare($sqlDel);
                $stmtDel->execute([$id_usuario, $id_artista]);
                return ['status' => 'success', 'accion' => 'unfollow', 'message' => 'Has dejado de seguir al artista.'];
            } else {
                // Follow
                $sqlIns = "INSERT INTO Seguimiento_Artista (FK_id_usuario, FK_id_artista) VALUES (?, ?)";
                $stmtIns = $db->prepare($sqlIns);
                $stmtIns->execute([$id_usuario, $id_artista]);
                return ['status' => 'success', 'accion' => 'follow', 'message' => 'Ahora sigues a este artista.'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        } finally {
            $db = null;
        }
    }

    /**
     * Comprueba si un usuario sigue a un artista
     */
    public function verificarSeguimiento($id_usuario, $id_artista) {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return false;

        try {
            $sql = "SELECT COUNT(*) as total FROM Seguimiento_Artista WHERE FK_id_usuario = ? AND FK_id_artista = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id_usuario, $id_artista]);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
        } catch (PDOException $e) {
            return false;
        } finally {
            $db = null;
        }
    }

    /**
     * Obtiene los artistas que un usuario sigue actualmente
     */
    public function obtenerArtistasSeguidos($id_usuario) {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return [];

        try {
            // BUGFIX: la columna real es ruta_foto_perfil, usamos alias para no romper el frontend
            $sql = "SELECT ar.PK_id_artista, ar.nombre_artistico, ar.ruta_foto_perfil AS foto_perfil, ar.biografia 
                    FROM Artista ar 
                    INNER JOIN Seguimiento_Artista sa ON ar.PK_id_artista = sa.FK_id_artista 
                    WHERE sa.FK_id_usuario = ? AND ar.estado_disponible = 1
                    ORDER BY sa.fecha_seguimiento DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id_usuario]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        } finally {
            $db = null;
        }
    }

    /**
     * Genera una notificación en la tabla Notificacion para cada seguidor
     * del artista indicado. Se invoca al registrar una canción nueva.
     *
     * @param int    $id_artista      ID del artista que sube contenido
     * @param string $nombre_artista  Nombre artístico (para el mensaje)
     * @param string $titulo_cancion  Título de la canción nueva
     * @param int    $id_cancion      referencia_id a la canción
     * @return int   Número de notificaciones insertadas
     */
    public function notificarSeguidores($id_artista, $nombre_artista, $titulo_cancion, $id_cancion) {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return 0;

        try {
            // Obtener todos los seguidores del artista
            $sqlSeg = "SELECT FK_id_usuario FROM Seguimiento_Artista WHERE FK_id_artista = ?";
            $stmtSeg = $db->prepare($sqlSeg);
            $stmtSeg->execute([$id_artista]);
            $seguidores = $stmtSeg->fetchAll(PDO::FETCH_COLUMN);

            if (empty($seguidores)) return 0;

            $mensaje  = "🎵 {$nombre_artista} ha publicado una nueva canción: \"{$titulo_cancion}\"";
            $sqlNotif = "INSERT INTO Notificacion (FK_id_usuario, mensaje, tipo, referencia_id)
                         VALUES (?, ?, 'nueva_cancion', ?)";
            $stmtNotif = $db->prepare($sqlNotif);

            $insertados = 0;
            foreach ($seguidores as $id_usuario) {
                $stmtNotif->execute([$id_usuario, $mensaje, $id_cancion]);
                $insertados++;
            }
            return $insertados;

        } catch (PDOException $e) {
            // Fallo silencioso: las notificaciones no deben bloquear el guardado de la canción
            return 0;
        } finally {
            $db = null;
        }
    }

    /**
     * Genera una notificación en la tabla Notificacion para cada seguidor
     * del artista indicado. Se invoca al registrar un álbum nuevo.
     *
     * @param int    $id_artista      ID del artista que sube contenido
     * @param string $nombre_artista  Nombre artístico (para el mensaje)
     * @param string $titulo_album    Título del álbum nuevo
     * @param int    $id_album        referencia_id al álbum
     * @return int   Número de notificaciones insertadas
     */
    public function notificarSeguidoresAlbum($id_artista, $nombre_artista, $titulo_album, $id_album) {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return 0;

        try {
            // Obtener todos los seguidores del artista
            $sqlSeg = "SELECT FK_id_usuario FROM Seguimiento_Artista WHERE FK_id_artista = ?";
            $stmtSeg = $db->prepare($sqlSeg);
            $stmtSeg->execute([$id_artista]);
            $seguidores = $stmtSeg->fetchAll(PDO::FETCH_COLUMN);

            if (empty($seguidores)) return 0;

            $mensaje  = "💿 {$nombre_artista} ha lanzado un nuevo álbum: \"{$titulo_album}\"";
            $sqlNotif = "INSERT INTO Notificacion (FK_id_usuario, mensaje, tipo, referencia_id)
                         VALUES (?, ?, 'nuevo_album', ?)";
            $stmtNotif = $db->prepare($sqlNotif);

            $insertados = 0;
            foreach ($seguidores as $id_usuario) {
                $stmtNotif->execute([$id_usuario, $mensaje, $id_album]);
                $insertados++;
            }
            return $insertados;

        } catch (PDOException $e) {
            // Fallo silencioso
            return 0;
        } finally {
            $db = null;
        }
    }

    /**
     * Obtiene las notificaciones no leídas de un usuario (máx 20).
     * @param int $id_usuario
     * @return array
     */
    public function obtenerNotificaciones($id_usuario) {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return [];

        try {
            $sql = "SELECT PK_id_notificacion, mensaje, leida, tipo, referencia_id,
                           fecha_creacion
                    FROM Notificacion
                    WHERE FK_id_usuario = ? AND estado_disponible = 1
                    ORDER BY leida ASC, fecha_creacion DESC
                    LIMIT 20";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id_usuario]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        } finally {
            $db = null;
        }
    }

    /**
     * Marca una notificación como leída (o todas si id = 0).
     * @param int $id_usuario
     * @param int $id_notificacion  0 = marcar todas
     * @return bool
     */
    public function marcarLeida($id_usuario, $id_notificacion = 0) {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return false;

        try {
            if ($id_notificacion == 0) {
                $sql  = "UPDATE Notificacion SET leida = 1 WHERE FK_id_usuario = ? AND estado_disponible = 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([$id_usuario]);
            } else {
                $sql  = "UPDATE Notificacion SET leida = 1 WHERE PK_id_notificacion = ? AND FK_id_usuario = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$id_notificacion, $id_usuario]);
            }
            return true;
        } catch (PDOException $e) {
            return false;
        } finally {
            $db = null;
        }
    }
}
?>
