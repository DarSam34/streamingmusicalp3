<?php
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/Usuario.php';

class Playlist {
    private $db;

    public function __construct() {
        // Inicializada aquí para compatibilidad con código existente, pero se renovará en cada método.
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
            // REGLA FF: Calcular total de canciones y duración total de cada playlist
            $sql = "SELECT p.*,
                           COUNT(dp.FK_id_cancion) AS total_canciones,
                           COALESCE(SUM(c.duracion_segundos), 0) AS duracion_total
                    FROM Playlist p
                    LEFT JOIN Detalle_Playlist dp ON p.PK_id_playlist = dp.FK_id_playlist
                    LEFT JOIN Cancion c ON dp.FK_id_cancion = c.PK_id_cancion AND c.estado_disponible = 1
                    WHERE p.FK_id_usuario = ? AND p.estado_disponible = 1
                    GROUP BY p.PK_id_playlist
                    ORDER BY p.fecha_creacion DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        } finally {
            $this->db = null;
        }
    }

    public function crearPlaylist($id_usuario, $nombre_lista, $visibilidad, $tipo_suscripcion = 1) {
        $this->reconectar();
        try {
            if ($tipo_suscripcion == 1) {
                $sqlCount = "SELECT COUNT(*) as total FROM Playlist WHERE FK_id_usuario = ? AND estado_disponible = 1";
                $stmtCount = $this->db->prepare($sqlCount);
                $stmtCount->execute([$id_usuario]);
                $total_playlists = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
                
                $limite = (new Usuario())->obtenerLimitesSuscripcion($tipo_suscripcion);
                if ($total_playlists >= $limite) {
                    return ['success' => false, 'message' => "Límite de {$limite} playlists alcanzado. Mejora a Premium para crear listas ilimitadas."];
                }
            }

            // Regla: Los nombres deben ser únicos por usuario.
            $sqlCheck = "SELECT COUNT(*) as total FROM Playlist WHERE FK_id_usuario = ? AND nombre_playlist = ? AND estado_disponible = 1";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$id_usuario, $nombre_lista]);
            $total_nombres = $stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

            if ($total_nombres > 0) {
                return ['success' => false, 'message' => 'El usuario ya tiene una playlist con ese nombre.'];
            }

            $sql = "INSERT INTO Playlist (FK_id_usuario, nombre_playlist, visibilidad) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario, $nombre_lista, $visibilidad]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al crear la playlist: ' . $e->getMessage()];
        } finally {
            $this->db = null;
        }
    }

    public function eliminarPlaylist($id_playlist, $id_usuario) {
        $this->reconectar();
        try {
            // Borrado lógico (NO DELETE fisico)
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

    public function agregarCancion($id_playlist, $id_cancion, $orden = null) {
        $this->reconectar();
        try {
            // Regla: Máximo 10,000 canciones por playlist.
            $sqlMax = "SELECT COUNT(*) as total FROM Detalle_Playlist WHERE FK_id_playlist = ?";
            $stmtMax = $this->db->prepare($sqlMax);
            $stmtMax->execute([$id_playlist]);
            $total_canciones = $stmtMax->fetch(PDO::FETCH_ASSOC)['total'];

            if ($total_canciones >= 10000) {
                return false; // Límite de 10,000 canciones alcanzado
            }

            // Verificar que la canción no esté ya en la playlist
            $sqlCheck = "SELECT COUNT(*) as total FROM Detalle_Playlist WHERE FK_id_playlist = ? AND FK_id_cancion = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$id_playlist, $id_cancion]);
            if ($stmtCheck->fetch(PDO::FETCH_ASSOC)['total'] > 0) {
                return false; // Ya está
            }
            // Calcular el siguiente número de orden (MAX + 1) para que el reordenamiento funcione
            $sqlOrden = "SELECT COALESCE(MAX(orden_reproduccion), 0) + 1 AS sig_orden FROM Detalle_Playlist WHERE FK_id_playlist = ?";
            $stmtOrden = $this->db->prepare($sqlOrden);
            $stmtOrden->execute([$id_playlist]);
            $sig_orden = (int)($stmtOrden->fetch(PDO::FETCH_ASSOC)['sig_orden'] ?? 1);

            $sql = "INSERT INTO Detalle_Playlist (FK_id_playlist, FK_id_cancion, orden_reproduccion) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_playlist, $id_cancion, $sig_orden]);
            return true;
        } catch (PDOException $e) {
            return false;
        } finally {
            $this->db = null;
        }
    }

    public function removerCancion($id_playlist, $id_cancion) {
        $this->reconectar();
        try {
            // Borrado físico permitido en tabla pivote transaccional (Detalle_Playlist),

            $sql = "DELETE FROM Detalle_Playlist WHERE FK_id_playlist = ? AND FK_id_cancion = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_playlist, $id_cancion]);
            $eliminado = $stmt->rowCount() > 0;
            if ($eliminado) {
                Utilidades::registrarLog('operaciones',
                    "[DETALLE_PLAYLIST] Canción ID:{$id_cancion} removida de Playlist ID:{$id_playlist}");
            }
            return $eliminado;
        } catch (PDOException $e) {
            Utilidades::registrarLog('errores', '[removerCancion] ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }

    public function obtenerCancionesPlaylist($id_playlist) {
        $this->reconectar();
        try {
            // Regla: Si una canción es borrada lógicamente (estado_disponible = 0), desaparecerá de las playlists.
            $sql = "SELECT c.PK_id_cancion, c.titulo, c.duracion_segundos, c.ruta_archivo_audio,
                           a.nombre_artistico as artista
                    FROM Detalle_Playlist dp
                    INNER JOIN Cancion c ON dp.FK_id_cancion = c.PK_id_cancion
                    INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                    INNER JOIN Artista a ON alb.FK_id_artista = a.PK_id_artista
                    WHERE dp.FK_id_playlist = ? AND c.estado_disponible = 1
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

    public function listarPlaylistsParaAgregar($id_usuario) {
        $this->reconectar();
        try {
            $sql = "SELECT p.PK_id_playlist, p.nombre_playlist, p.visibilidad, u.nombre_completo AS propietario,
                           CASE WHEN p.FK_id_usuario = ? THEN 1 ELSE 0 END AS es_mia
                    FROM Playlist p
                    INNER JOIN Usuario u ON p.FK_id_usuario = u.PK_id_usuario
                    WHERE p.estado_disponible = 1 
                      AND (p.FK_id_usuario = ? OR p.visibilidad = 'Colaborativa')
                    ORDER BY es_mia DESC, p.nombre_playlist ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario, $id_usuario]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('listarPlaylistsParaAgregar(): ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }

    /**
     * REGLA CC: Retorna las playlists públicas de OTROS usuarios.
     * Permite filtrar por nombre de playlist (búsqueda).
     * @param int    $id_usuario ID del usuario actual (para excluirlo)
     * @param string $busqueda   Término de búsqueda (vacío = todas)
     * @return array
     */
    public function listarPlaylistsPublicas($id_usuario, $busqueda = '') {
        $this->reconectar();
        try {
            $like = '%' . $busqueda . '%';
            $sql  = "SELECT p.PK_id_playlist, p.nombre_playlist, p.visibilidad,
                            u.nombre_completo AS propietario,
                            COUNT(dp.FK_id_cancion) AS total_canciones,
                            COALESCE(SUM(c.duracion_segundos), 0) AS duracion_total
                     FROM Playlist p
                     INNER JOIN Usuario u ON p.FK_id_usuario = u.PK_id_usuario
                     LEFT JOIN Detalle_Playlist dp ON p.PK_id_playlist = dp.FK_id_playlist
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

    /**
     * REGLA DD: Duplica una playlist pública de otro usuario a la biblioteca del usuario actual.
     * Respeta el límite de 15 playlists para usuarios Free.
     * Usa transacción para garantizar atomicidad del INSERT doble.
     * @param int $id_playlist_origen   ID de la playlist a copiar
     * @param int $id_usuario_destino   ID del usuario que recibe la copia
     * @param int $tipo_suscripcion     1=Free (máximo 15), 2=Premium (sin límite)
     * @return array ['status' => 'success'|'error', 'message' => '...']
     */
    public function duplicarPlaylist($id_playlist_origen, $id_usuario_destino, $tipo_suscripcion = 1) {
        $this->reconectar();
        try {
            $this->db->beginTransaction();

            // Verificar límite Free
            if ($tipo_suscripcion == 1) {
                $stmtCount = $this->db->prepare(
                    "SELECT COUNT(*) AS total FROM Playlist WHERE FK_id_usuario = ? AND estado_disponible = 1"
                );
                $stmtCount->execute([$id_usuario_destino]);
                $limite = (new Usuario())->obtenerLimitesSuscripcion($tipo_suscripcion);
                if ($stmtCount->fetch(PDO::FETCH_ASSOC)['total'] >= $limite) {
                    $this->db->rollBack();
                    return ['status' => 'error', 'message' => "Límite de {$limite} playlists alcanzado. Mejora a Premium."];
                }
            }

            // Obtener datos de la playlist original (solo si es pública o colaborativa y activa)
            $stmtOrig = $this->db->prepare(
                "SELECT nombre_playlist FROM Playlist WHERE PK_id_playlist = ? AND visibilidad IN ('Publica', 'Colaborativa') AND estado_disponible = 1"
            );
            $stmtOrig->execute([$id_playlist_origen]);
            $orig = $stmtOrig->fetch(PDO::FETCH_ASSOC);

            if (!$orig) {
                $this->db->rollBack();
                return ['status' => 'error', 'message' => 'Playlist no encontrada o no es pública/colaborativa.'];
            }

            // Crear nueva playlist en la biblioteca del usuario destino
            $nombre_nuevo = 'Copia de ' . $orig['nombre_playlist'];
            
            // Regla: Evitar fallo por llave única (UNIQUE KEY) si ya existe una "Copia de..."
            $stmtCheckName = $this->db->prepare("SELECT COUNT(*) FROM Playlist WHERE FK_id_usuario = ? AND nombre_playlist = ? AND estado_disponible = 1");
            $stmtCheckName->execute([$id_usuario_destino, $nombre_nuevo]);
            if ($stmtCheckName->fetchColumn() > 0) {
                $nombre_nuevo .= ' (' . rand(100, 999) . ')';
            }

            $stmtIns = $this->db->prepare(
                "INSERT INTO Playlist (FK_id_usuario, nombre_playlist, visibilidad) VALUES (?, ?, 'Privada')"
            );
            $stmtIns->execute([$id_usuario_destino, $nombre_nuevo]);
            $nuevo_id = $this->db->lastInsertId();

            // Copiar todas las canciones de la playlist original a la nueva
            $stmtCopy = $this->db->prepare(
                "INSERT INTO Detalle_Playlist (FK_id_playlist, FK_id_cancion, orden_reproduccion)
                 SELECT ?, dp.FK_id_cancion, dp.orden_reproduccion
                 FROM Detalle_Playlist dp
                 INNER JOIN Cancion c ON dp.FK_id_cancion = c.PK_id_cancion
                 WHERE dp.FK_id_playlist = ? AND c.estado_disponible = 1"
            );
            $stmtCopy->execute([$nuevo_id, $id_playlist_origen]);

            $this->db->commit();
            return ['status' => 'success', 'message' => "Playlist duplicada como \"{$nombre_nuevo}\" en tu biblioteca."];

        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->logError('duplicarPlaylist(): ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al duplicar la playlist.'];
        } finally {
            $this->db = null;
        }
    }

    /**
     * Reordena una canción dentro de una playlist (la sube o la baja una posición).
     * Intercambia el orden_reproduccion de la canción seleccionada con su vecina.
     * @param int    $id_playlist
     * @param int    $id_cancion   La canción a mover
     * @param string $direccion    'up' | 'down'
     * @return bool
     */
    public function reordenarCancion($id_playlist, $id_cancion, $direccion) {
        $this->reconectar();
        try {
            // Obtener el orden actual de la canción a mover
            $sqlOri = "SELECT orden_reproduccion FROM Detalle_Playlist WHERE FK_id_playlist = ? AND FK_id_cancion = ?";
            $stmtOri = $this->db->prepare($sqlOri);
            $stmtOri->execute([$id_playlist, $id_cancion]);
            $fila = $stmtOri->fetch(PDO::FETCH_ASSOC);
            if (!$fila) return false;
            $orden_actual = (int)$fila['orden_reproduccion'];

            // Buscar la canción vecina (inmediatamente superior o inferior)
            if ($direccion === 'up') {
                $sqlVec = "SELECT FK_id_cancion, orden_reproduccion FROM Detalle_Playlist
                           WHERE FK_id_playlist = ? AND orden_reproduccion < ?
                           ORDER BY orden_reproduccion DESC LIMIT 1";
            } else {
                $sqlVec = "SELECT FK_id_cancion, orden_reproduccion FROM Detalle_Playlist
                           WHERE FK_id_playlist = ? AND orden_reproduccion > ?
                           ORDER BY orden_reproduccion ASC LIMIT 1";
            }
            $stmtVec = $this->db->prepare($sqlVec);
            $stmtVec->execute([$id_playlist, $orden_actual]);
            $vecina = $stmtVec->fetch(PDO::FETCH_ASSOC);
            if (!$vecina) return false; // Ya está al inicio o al final

            // Intercambiar los valores de orden_reproduccion
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
            $this->logError('reordenarCancion(): ' . $e->getMessage());
            return false;
        } finally {
            $this->db = null;
        }
    }

    /**
     * Permite que un usuario distinto al dueño agregue canciones a una Playlist Colaborativa.
     * Si la playlist no es Colaborativa, solo el dueño puede agregar.
     * @param int $id_playlist
     * @param int $id_cancion
     * @param int $id_usuario_solicitante   ID del usuario que desea agregar
     * @return array ['status' => 'success'|'error', 'message' => '...']
     */
    public function agregarCancionColaborativa($id_playlist, $id_cancion, $id_usuario_solicitante) {
        $this->reconectar();
        try {
            // Verificar que la playlist existe y es Colaborativa (o pertenece al usuario)
            $sqlPl = "SELECT FK_id_usuario, visibilidad FROM Playlist WHERE PK_id_playlist = ? AND estado_disponible = 1";
            $stmtPl = $this->db->prepare($sqlPl);
            $stmtPl->execute([$id_playlist]);
            $playlist = $stmtPl->fetch(PDO::FETCH_ASSOC);

            if (!$playlist) {
                return ['status' => 'error', 'message' => 'Playlist no encontrada.'];
            }

            $es_dueno      = ((int)$playlist['FK_id_usuario'] === (int)$id_usuario_solicitante);
            $es_colaborativa = ($playlist['visibilidad'] === 'Colaborativa');

            if (!$es_dueno && !$es_colaborativa) {
                return ['status' => 'error', 'message' => 'Solo puedes agregar canciones a playlists colaborativas o a las tuyas.'];
            }

            // Verificar límite de 10,000 canciones
            $sqlMax = "SELECT COUNT(*) as total FROM Detalle_Playlist WHERE FK_id_playlist = ?";
            $stmtMax = $this->db->prepare($sqlMax);
            $stmtMax->execute([$id_playlist]);
            if ($stmtMax->fetch(PDO::FETCH_ASSOC)['total'] >= 10000) {
                return ['status' => 'error', 'message' => 'Límite de 10,000 canciones por playlist alcanzado.'];
            }

            // Verificar que no esté ya en la playlist
            $sqlChk = "SELECT COUNT(*) as total FROM Detalle_Playlist WHERE FK_id_playlist = ? AND FK_id_cancion = ?";
            $stmtChk = $this->db->prepare($sqlChk);
            $stmtChk->execute([$id_playlist, $id_cancion]);
            if ($stmtChk->fetch(PDO::FETCH_ASSOC)['total'] > 0) {
                return ['status' => 'error', 'message' => 'La canción ya está en la playlist.'];
            }

            // Calcular el siguiente orden
            $sqlOrden = "SELECT COALESCE(MAX(orden_reproduccion), 0) + 1 AS siguiente FROM Detalle_Playlist WHERE FK_id_playlist = ?";
            $stmtOrden = $this->db->prepare($sqlOrden);
            $stmtOrden->execute([$id_playlist]);
            $orden = $stmtOrden->fetch(PDO::FETCH_ASSOC)['siguiente'];

            $sql = "INSERT INTO Detalle_Playlist (FK_id_playlist, FK_id_cancion, orden_reproduccion) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_playlist, $id_cancion, $orden]);

            return ['status' => 'success', 'message' => 'Canción agregada a la playlist colaborativa.'];
        } catch (PDOException $e) {
            $this->logError('agregarCancionColaborativa(): ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al agregar la canción.'];
        } finally {
            $this->db = null;
        }
    }

    private function logError($mensaje) {
        $log_file = __DIR__ . '/../logs/errores.log';
        $entrada  = '[' . date('Y-m-d H:i:s') . '] [Playlist] ' . $mensaje . PHP_EOL;
        file_put_contents($log_file, $entrada, FILE_APPEND);
    }
}
?>