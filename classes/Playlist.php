<?php
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/Usuario.php';
require_once __DIR__ . '/Utilidades.php';

class Playlist {
    private $db;

    public function __construct() {
        $this->db = (new Conexion())->conectar();
    }

    private function reconectar() {
        if ($this->db === null) {
            $this->db = (new Conexion())->conectar();
        }
    }

    public function listarPlaylistsUsuario($id_usuario) {
        $this->reconectar();
        try {
            $sql = "SELECT p.*,
                           COUNT(dp.FK_id_cancion) AS total_canciones,
                           COALESCE(SUM(c.duracion_segundos), 0) AS duracion_total
                    FROM Playlist p
                    LEFT JOIN Detalle_Playlist dp ON p.PK_id_playlist = dp.FK_id_playlist AND dp.estado_disponible = 1
                    LEFT JOIN Cancion c ON dp.FK_id_cancion = c.PK_id_cancion AND c.estado_disponible = 1
                    WHERE p.FK_id_usuario = ? AND p.estado_disponible = 1
                    GROUP BY p.PK_id_playlist
                    ORDER BY p.fecha_creacion DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('listarPlaylistsUsuario: ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }

    public function crearPlaylist($id_usuario, $nombre_lista, $visibilidad, $tipo_suscripcion = 1) {
        $this->reconectar();
        try {
            // Corrección de formato para Strict Mode en ENUM
            $visibilidad = ucfirst(strtolower(trim($visibilidad)));
            if (!in_array($visibilidad, ['Publica', 'Privada', 'Colaborativa'])) {
                $visibilidad = 'Publica';
            }

            if ($tipo_suscripcion == 1) {
                $sqlCount = "SELECT COUNT(*) as total FROM Playlist WHERE FK_id_usuario = ? AND estado_disponible = 1";
                $stmtCount = $this->db->prepare($sqlCount);
                $stmtCount->execute([$id_usuario]);
                $total_playlists = (int)$stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
                
                $limite = (int)(new Usuario())->obtenerLimitesSuscripcion($tipo_suscripcion);
                if ($total_playlists >= $limite) {
                    return ['success' => false, 'message' => "Límite de {$limite} playlists alcanzado. Mejora a Premium."];
                }
            }

            $sqlCheck = "SELECT COUNT(*) as total FROM Playlist WHERE FK_id_usuario = ? AND nombre_playlist = ? AND estado_disponible = 1";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$id_usuario, $nombre_lista]);
            if ($stmtCheck->fetch(PDO::FETCH_ASSOC)['total'] > 0) {
                return ['success' => false, 'message' => 'Ya tienes una playlist con ese nombre.'];
            }

            $sql = "INSERT INTO Playlist (FK_id_usuario, nombre_playlist, visibilidad) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario, $nombre_lista, $visibilidad]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            $this->logError('crearPlaylist(): ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno al crear la playlist.'];
        } finally {
            $this->db = null;
        }
    }

    public function eliminarPlaylist($id_playlist, $id_usuario) {
        $this->reconectar();
        try {
            $sql = "UPDATE Playlist SET estado_disponible = 0 WHERE PK_id_playlist = ? AND FK_id_usuario = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_playlist, $id_usuario]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        } finally {
            $this->db = null;
        }
    }

    // Prevención de IDOR: Se exige $id_usuario para validar la propiedad de la playlist
    public function removerCancion($id_playlist, $id_cancion, $id_usuario) {
        $this->reconectar();
        try {
            $sqlPl = "SELECT FK_id_usuario FROM Playlist WHERE PK_id_playlist = ? AND estado_disponible = 1";
            $stmtPl = $this->db->prepare($sqlPl);
            $stmtPl->execute([$id_playlist]);
            $playlist = $stmtPl->fetch(PDO::FETCH_ASSOC);

            if (!$playlist || ((int)$playlist['FK_id_usuario'] !== (int)$id_usuario)) {
                return false; 
            }

            // Borrado lógico en tabla pivote
            $sql = "UPDATE Detalle_Playlist SET estado_disponible = 0 WHERE FK_id_playlist = ? AND FK_id_cancion = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_playlist, $id_cancion]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError('[removerCancion] ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }

    public function obtenerCancionesPlaylist($id_playlist) {
        $this->reconectar();
        try {
            $sql = "SELECT c.PK_id_cancion, c.titulo, c.duracion_segundos, c.ruta_archivo_audio,
                           a.nombre_artistico as artista
                    FROM Detalle_Playlist dp
                    INNER JOIN Cancion c ON dp.FK_id_cancion = c.PK_id_cancion
                    INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                    INNER JOIN Artista a ON alb.FK_id_artista = a.PK_id_artista
                    WHERE dp.FK_id_playlist = ? AND c.estado_disponible = 1 AND dp.estado_disponible = 1
                    ORDER BY dp.orden_reproduccion ASC, dp.fecha_agregada ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_playlist]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        } finally {
            $this->db = null;
        }
    }

    public function reordenarCancion($id_playlist, $id_cancion, $direccion, $id_usuario) {
        $this->reconectar();
        try {
            // Prevención de IDOR
            $stmtPl = $this->db->prepare("SELECT FK_id_usuario FROM Playlist WHERE PK_id_playlist = ?");
            $stmtPl->execute([$id_playlist]);
            if ((int)$stmtPl->fetchColumn() !== (int)$id_usuario) return false;

            $sqlOri = "SELECT orden_reproduccion FROM Detalle_Playlist WHERE FK_id_playlist = ? AND FK_id_cancion = ? AND estado_disponible = 1";
            $stmtOri = $this->db->prepare($sqlOri);
            $stmtOri->execute([$id_playlist, $id_cancion]);
            $fila = $stmtOri->fetch(PDO::FETCH_ASSOC);
            if (!$fila) return false;
            $orden_actual = (int)$fila['orden_reproduccion'];

            if ($direccion === 'up') {
                $sqlVec = "SELECT FK_id_cancion, orden_reproduccion FROM Detalle_Playlist WHERE FK_id_playlist = ? AND orden_reproduccion < ? AND estado_disponible = 1 ORDER BY orden_reproduccion DESC LIMIT 1";
            } else {
                $sqlVec = "SELECT FK_id_cancion, orden_reproduccion FROM Detalle_Playlist WHERE FK_id_playlist = ? AND orden_reproduccion > ? AND estado_disponible = 1 ORDER BY orden_reproduccion ASC LIMIT 1";
            }
            
            $stmtVec = $this->db->prepare($sqlVec);
            $stmtVec->execute([$id_playlist, $orden_actual]);
            $vecina = $stmtVec->fetch(PDO::FETCH_ASSOC);
            if (!$vecina) return false;

            $this->db->beginTransaction();
            $sqlUpd1 = "UPDATE Detalle_Playlist SET orden_reproduccion = ? WHERE FK_id_playlist = ? AND FK_id_cancion = ?";
            $stmtUpd1 = $this->db->prepare($sqlUpd1);
            $stmtUpd1->execute([$vecina['orden_reproduccion'], $id_playlist, $id_cancion]);

            $stmtUpd2 = $this->db->prepare($sqlUpd1);
            $stmtUpd2->execute([$orden_actual, $id_playlist, $vecina['FK_id_cancion']]);
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        } finally {
            $this->db = null;
        }
    }

    public function agregarCancionColaborativa($id_playlist, $id_cancion, $id_usuario_solicitante) {
        $this->reconectar();
        try {
            $sqlPl = "SELECT FK_id_usuario, visibilidad FROM Playlist WHERE PK_id_playlist = ? AND estado_disponible = 1";
            $stmtPl = $this->db->prepare($sqlPl);
            $stmtPl->execute([$id_playlist]);
            $playlist = $stmtPl->fetch(PDO::FETCH_ASSOC);

            if (!$playlist) return ['status' => 'error', 'message' => 'Playlist no encontrada.'];

            $es_dueno = ((int)$playlist['FK_id_usuario'] === (int)$id_usuario_solicitante);
            $es_colaborativa = ($playlist['visibilidad'] === 'Colaborativa');

            if (!$es_dueno && !$es_colaborativa) {
                return ['status' => 'error', 'message' => 'Solo puedes agregar canciones a playlists colaborativas o a las tuyas.'];
            }

            $sqlMax = "SELECT COUNT(*) as total FROM Detalle_Playlist WHERE FK_id_playlist = ? AND estado_disponible = 1";
            $stmtMax = $this->db->prepare($sqlMax);
            $stmtMax->execute([$id_playlist]);
            if ($stmtMax->fetch(PDO::FETCH_ASSOC)['total'] >= 10000) {
                return ['status' => 'error', 'message' => 'Límite de 10,000 canciones alcanzado.'];
            }

            // Manejo de reactivación si fue borrada lógicamente
            $sqlChk = "SELECT estado_disponible FROM Detalle_Playlist WHERE FK_id_playlist = ? AND FK_id_cancion = ?";
            $stmtChk = $this->db->prepare($sqlChk);
            $stmtChk->execute([$id_playlist, $id_cancion]);
            $registro = $stmtChk->fetch(PDO::FETCH_ASSOC);

            if ($registro) {
                if ((int)$registro['estado_disponible'] === 1) {
                    return ['status' => 'error', 'message' => 'La canción ya está en la playlist.'];
                } else {
                    $sqlReact = "UPDATE Detalle_Playlist SET estado_disponible = 1 WHERE FK_id_playlist = ? AND FK_id_cancion = ?";
                    $this->db->prepare($sqlReact)->execute([$id_playlist, $id_cancion]);
                    return ['status' => 'success', 'message' => 'Canción agregada de nuevo.'];
                }
            }

            $sqlOrden = "SELECT COALESCE(MAX(orden_reproduccion), 0) + 1 AS siguiente FROM Detalle_Playlist WHERE FK_id_playlist = ?";
            $stmtOrden = $this->db->prepare($sqlOrden);
            $stmtOrden->execute([$id_playlist]);
            $orden = $stmtOrden->fetch(PDO::FETCH_ASSOC)['siguiente'];

            $sql = "INSERT INTO Detalle_Playlist (FK_id_playlist, FK_id_cancion, orden_reproduccion, estado_disponible) VALUES (?, ?, ?, 1)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_playlist, $id_cancion, $orden]);

            return ['status' => 'success', 'message' => 'Canción agregada a la playlist.'];
        } catch (PDOException $e) {
            $this->logError('agregarCancionColaborativa(): ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al agregar la canción.'];
        } finally {
            $this->db = null;
        }
    }

    public function listarPlaylistsPublicas($id_usuario, $busqueda = '') {
        $this->reconectar();
        try {
            $like = '%' . $busqueda . '%';
            $sql  = "SELECT p.PK_id_playlist, p.nombre_playlist, p.visibilidad, p.FK_id_usuario,
                            u.nombre_completo AS propietario,
                            COUNT(dp.FK_id_cancion) AS total_canciones,
                            COALESCE(SUM(c.duracion_segundos), 0) AS duracion_total
                     FROM Playlist p
                     INNER JOIN Usuario u ON p.FK_id_usuario = u.PK_id_usuario
                     LEFT JOIN Detalle_Playlist dp ON p.PK_id_playlist = dp.FK_id_playlist AND dp.estado_disponible = 1
                     LEFT JOIN Cancion c ON dp.FK_id_cancion = c.PK_id_cancion AND c.estado_disponible = 1
                     WHERE p.visibilidad IN ('Publica', 'Colaborativa')
                       AND p.estado_disponible = 1
                       AND p.FK_id_usuario != ?
                       AND p.nombre_playlist LIKE ?
                     GROUP BY p.PK_id_playlist
                     ORDER BY total_canciones DESC
                     LIMIT 50";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario, $like]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('listarPlaylistsPublicas(): ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }

    public function duplicarPlaylist($id_playlist_origen, $id_usuario_destino, $tipo_suscripcion = 1) {
        $this->reconectar();
        try {
            $this->db->beginTransaction();

            if ($tipo_suscripcion == 1) {
                $stmtCount = $this->db->prepare("SELECT COUNT(*) AS total FROM Playlist WHERE FK_id_usuario = ? AND estado_disponible = 1");
                $stmtCount->execute([$id_usuario_destino]);
                $limite = (new Usuario())->obtenerLimitesSuscripcion($tipo_suscripcion);
                if ($stmtCount->fetch(PDO::FETCH_ASSOC)['total'] >= $limite) {
                    $this->db->rollBack();
                    return ['status' => 'error', 'message' => "Límite de {$limite} playlists alcanzado."];
                }
            }

            $stmtOrig = $this->db->prepare("SELECT nombre_playlist FROM Playlist WHERE PK_id_playlist = ? AND visibilidad IN ('Publica', 'Colaborativa') AND estado_disponible = 1");
            $stmtOrig->execute([$id_playlist_origen]);
            $orig = $stmtOrig->fetch(PDO::FETCH_ASSOC);

            if (!$orig) {
                $this->db->rollBack();
                return ['status' => 'error', 'message' => 'Playlist no encontrada.'];
            }

            $nombre_nuevo = 'Copia de ' . $orig['nombre_playlist'];
            $stmtCheckName = $this->db->prepare("SELECT COUNT(*) FROM Playlist WHERE FK_id_usuario = ? AND nombre_playlist = ? AND estado_disponible = 1");
            $stmtCheckName->execute([$id_usuario_destino, $nombre_nuevo]);
            if ($stmtCheckName->fetchColumn() > 0) {
                $nombre_nuevo .= ' (' . rand(100, 999) . ')';
            }

            $stmtIns = $this->db->prepare("INSERT INTO Playlist (FK_id_usuario, nombre_playlist, visibilidad) VALUES (?, ?, 'Privada')");
            $stmtIns->execute([$id_usuario_destino, $nombre_nuevo]);
            $nuevo_id = $this->db->lastInsertId();

            $stmtCopy = $this->db->prepare(
                "INSERT INTO Detalle_Playlist (FK_id_playlist, FK_id_cancion, orden_reproduccion, estado_disponible)
                 SELECT ?, dp.FK_id_cancion, dp.orden_reproduccion, 1
                 FROM Detalle_Playlist dp
                 INNER JOIN Cancion c ON dp.FK_id_cancion = c.PK_id_cancion
                 WHERE dp.FK_id_playlist = ? AND c.estado_disponible = 1 AND dp.estado_disponible = 1"
            );
            $stmtCopy->execute([$nuevo_id, $id_playlist_origen]);

            $this->db->commit();
            return ['status' => 'success', 'message' => "Duplicada exitosamente."];
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->logError('duplicarPlaylist(): ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al duplicar la playlist.'];
        } finally {
            $this->db = null;
        }
    }

    private function logError($mensaje) {
        $log_file = __DIR__ . '/../logs/errores.log';
        @file_put_contents($log_file, '[' . date('Y-m-d H:i:s') . '] [Playlist] ' . $mensaje . PHP_EOL, FILE_APPEND);
    }
}
?>