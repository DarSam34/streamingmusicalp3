<?php
/**
 * CLASE: Cancion
 * PROPÓSITO: Gestionar el CRUD del catálogo musical y archivos de audio.
 */
require_once __DIR__ . '/Conexion.php';

class Cancion {
    private $db;

    public function __construct() {
        $this->db = (new Conexion())->conectar();
    }

    public function listarCanciones($id_usuario = 0) {
        try {
            $sql = "SELECT c.PK_id_cancion, c.titulo, c.duracion_segundos, c.ruta_archivo_audio,
                           a.titulo AS album, ar.nombre_artistico AS artista, ar.PK_id_artista, g.nombre_genero AS genero,
                           (SELECT COUNT(*) FROM Seguimiento_Artista sa 
                            WHERE sa.FK_id_artista = ar.PK_id_artista AND sa.FK_id_usuario = ?) AS sigue_artista
                    FROM Cancion c
                    INNER JOIN Album a ON c.FK_id_album = a.PK_id_album
                    INNER JOIN Artista ar ON a.FK_id_artista = ar.PK_id_artista
                    INNER JOIN Genero_Musical g ON c.FK_id_genero = g.PK_id_genero
                    WHERE c.estado_disponible = 1
                    ORDER BY c.PK_id_cancion DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        } finally {
            $this->db = null;
        }
    }

    public function listarAlbumes() {
        try {
            // CORRECCIÓN: Se agregaron ruta_portada y fecha_lanzamiento a la consulta
            $sql = "SELECT PK_id_album, titulo, ruta_portada, fecha_lanzamiento 
                    FROM Album 
                    WHERE estado_disponible = 1 
                    ORDER BY titulo ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        } finally {
            $this->db = null;
        }
    }

    public function listarGeneros() {
        try {
            $sql = "SELECT PK_id_genero, nombre_genero FROM Genero_Musical WHERE estado_disponible = 1 ORDER BY nombre_genero ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        } finally {
            $this->db = null;
        }
    }

    /**
     * @param int $numero_pista  Número de pista dentro del álbum
     * @return int  ID de la canción insirtada (> 0) o 0 si falla.
     *              Se devuelve int en lugar de bool para permitir pasar
     *              el id_cancion al sistema de notificaciones.
     */
    public function registrarCancion($id_album, $id_genero, $titulo, $duracion, $ruta, $letra, $numero_pista = 1) {
        try {
            $sql = "INSERT INTO Cancion (FK_id_album, FK_id_genero, titulo, numero_pista, duracion_segundos, ruta_archivo_audio, letra_sincronizada)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $ok   = $stmt->execute([$id_album, $id_genero, $titulo, $numero_pista, $duracion, $ruta, $letra]);
            return $ok ? (int)$this->db->lastInsertId() : 0;
        } catch (PDOException $e) {
            return 0;
        } finally {
            $this->db = null;
        }
    }

    /**
     * @param int $numero_pista  Número de pista dentro del álbum
     */
    public function actualizarCancion($id, $id_album, $id_genero, $titulo, $duracion, $ruta, $letra, $numero_pista = 1) {
        try {
            if (empty($ruta)) {
                $sql = "UPDATE Cancion SET FK_id_album = ?, FK_id_genero = ?, titulo = ?, numero_pista = ?, duracion_segundos = ?, letra_sincronizada = ?
                        WHERE PK_id_cancion = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$id_album, $id_genero, $titulo, $numero_pista, $duracion, $letra, $id]);
            } else {
                $sql = "UPDATE Cancion SET FK_id_album = ?, FK_id_genero = ?, titulo = ?, numero_pista = ?, duracion_segundos = ?, ruta_archivo_audio = ?, letra_sincronizada = ?
                        WHERE PK_id_cancion = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$id_album, $id_genero, $titulo, $numero_pista, $duracion, $ruta, $letra, $id]);
            }
        } catch (PDOException $e) {
            return false;
        } finally {
            $this->db = null;
        }
    }

    public function obtenerCancion($id) {
        try {
            $sql = "SELECT c.*, a.titulo AS album_nombre 
                    FROM Cancion c 
                    LEFT JOIN Album a ON c.FK_id_album = a.PK_id_album 
                    WHERE c.PK_id_cancion = ? AND c.estado_disponible = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        } finally {
            $this->db = null;
        }
    }

    public function eliminarCancion($id) {
        $this->reconectar();
        try {
            $this->db->beginTransaction();

            // Borrado lógico de la canción (estado_disponible = 0)
            $sqlCancion = "UPDATE Cancion SET estado_disponible = 0 WHERE PK_id_cancion = ?";
            $stmtCancion = $this->db->prepare($sqlCancion);
            $stmtCancion->execute([$id]);

            // DELETE físico en tabla pivote transaccional (Detalle_Playlist).
            // No aplica borrado lógico porque los detalles de playlist son efimeros;
            // la canción desaparece de las playlists por fuerza una vez es desactivada.
            $sqlDetalle = "DELETE FROM Detalle_Playlist WHERE FK_id_cancion = ?";
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            $stmtDetalle->execute([$id]);

            $this->db->commit();


            require_once __DIR__ . '/Utilidades.php';
            Utilidades::registrarLog('operaciones',
                "[CANCION_ELIMINADA] Canción ID:{$id} desactivada (borrado lógico). Removida de Detalle_Playlist.");

            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        } finally {
            $this->db = null;
        }
    }

    private function reconectar() {
        if ($this->db === null) {
            $this->db = (new Conexion())->conectar();
        }
    }

    public function listarCancionesPorAlbum($id_album, $id_usuario = 0) {
        $this->reconectar();
        try {
            $sql = "SELECT c.PK_id_cancion, c.titulo, c.numero_pista, c.duracion_segundos, c.ruta_archivo_audio,
                           a.titulo AS album, ar.nombre_artistico AS artista, ar.PK_id_artista,
                           (SELECT COUNT(*) FROM Seguimiento_Artista sa 
                            WHERE sa.FK_id_artista = ar.PK_id_artista AND sa.FK_id_usuario = ?) AS sigue_artista
                    FROM Cancion c
                    INNER JOIN Album a ON c.FK_id_album = a.PK_id_album
                    INNER JOIN Artista ar ON a.FK_id_artista = ar.PK_id_artista
                    WHERE c.FK_id_album = ? AND c.estado_disponible = 1
                    ORDER BY c.numero_pista ASC, c.PK_id_cancion ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario, $id_album]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        } finally {
            $this->db = null;
        }
    }

    public function obtenerRecomendacionesRadio($id_cancion) {
        $this->reconectar();
        try {
            $sql_genero = "SELECT FK_id_genero FROM Cancion WHERE PK_id_cancion = ?";
            $stmt_g = $this->db->prepare($sql_genero);
            $stmt_g->execute([$id_cancion]);
            $res = $stmt_g->fetch(PDO::FETCH_ASSOC);
            if (!$res) return [];

            $id_genero = $res['FK_id_genero'];

            $sql = "SELECT c.PK_id_cancion, c.titulo, c.duracion_segundos, c.ruta_archivo_audio,
                           a.titulo AS album, ar.nombre_artistico AS artista
                    FROM Cancion c
                    INNER JOIN Album a ON c.FK_id_album = a.PK_id_album
                    INNER JOIN Artista ar ON a.FK_id_artista = ar.PK_id_artista
                    WHERE c.FK_id_genero = ? AND c.PK_id_cancion != ? AND c.estado_disponible = 1
                    ORDER BY RAND() LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_genero, $id_cancion]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        } finally {
            $this->db = null;
        }
    }

    public function obtenerDescubrimientoSemanal($id_usuario) {
        $this->reconectar();
        try {
            $sql_fav = "SELECT c.FK_id_genero
                        FROM Historial_Reproduccion h
                        INNER JOIN Cancion c ON h.FK_id_cancion = c.PK_id_cancion
                        WHERE h.FK_id_usuario = ? AND h.es_valida_regalia = 1
                        GROUP BY c.FK_id_genero
                        ORDER BY COUNT(*) DESC LIMIT 3";
            $stmt_fav = $this->db->prepare($sql_fav);
            $stmt_fav->execute([$id_usuario]);
            $generos_fav = $stmt_fav->fetchAll(PDO::FETCH_COLUMN);

            if (empty($generos_fav)) {
                $sql = "SELECT c.PK_id_cancion, c.titulo, c.duracion_segundos, c.ruta_archivo_audio,
                               a.titulo AS album, ar.nombre_artistico AS artista, ar.PK_id_artista,
                               (SELECT COUNT(*) FROM Seguimiento_Artista sa 
                                WHERE sa.FK_id_artista = ar.PK_id_artista AND sa.FK_id_usuario = ?) AS sigue_artista
                        FROM Cancion c
                        INNER JOIN Album a ON c.FK_id_album = a.PK_id_album
                        INNER JOIN Artista ar ON a.FK_id_artista = ar.PK_id_artista
                        WHERE c.estado_disponible = 1
                        ORDER BY RAND() LIMIT 20";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id_usuario]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $inQuery = implode(',', array_fill(0, count($generos_fav), '?'));
            
            $sql = "SELECT c.PK_id_cancion, c.titulo, c.duracion_segundos, c.ruta_archivo_audio,
                           a.titulo AS album, ar.nombre_artistico AS artista, ar.PK_id_artista,
                           (SELECT COUNT(*) FROM Seguimiento_Artista sa 
                            WHERE sa.FK_id_artista = ar.PK_id_artista AND sa.FK_id_usuario = ?) AS sigue_artista
                    FROM Cancion c
                    INNER JOIN Album a ON c.FK_id_album = a.PK_id_album
                    INNER JOIN Artista ar ON a.FK_id_artista = ar.PK_id_artista
                    WHERE c.FK_id_genero IN ($inQuery) 
                      AND c.estado_disponible = 1
                      AND c.PK_id_cancion NOT IN (
                          SELECT FK_id_cancion FROM Historial_Reproduccion WHERE FK_id_usuario = ?
                      )
                    ORDER BY RAND() LIMIT 20";
            
            $params = array_merge([$id_usuario], $generos_fav, [$id_usuario]);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($resultados) < 5) {
                $sql_fill = "SELECT c.PK_id_cancion, c.titulo, c.duracion_segundos, c.ruta_archivo_audio,
                                     a.titulo AS album, ar.nombre_artistico AS artista, ar.PK_id_artista,
                                     (SELECT COUNT(*) FROM Seguimiento_Artista sa 
                                      WHERE sa.FK_id_artista = ar.PK_id_artista AND sa.FK_id_usuario = ?) AS sigue_artista
                              FROM Cancion c
                              INNER JOIN Album a ON c.FK_id_album = a.PK_id_album
                              INNER JOIN Artista ar ON a.FK_id_artista = ar.PK_id_artista
                              WHERE c.estado_disponible = 1
                                AND c.PK_id_cancion NOT IN (
                                    SELECT FK_id_cancion FROM Historial_Reproduccion WHERE FK_id_usuario = ?
                                )
                              ORDER BY RAND() LIMIT 15";
                $stmt_fill = $this->db->prepare($sql_fill);
                $stmt_fill->execute([$id_usuario, $id_usuario]);
                $fill = $stmt_fill->fetchAll(PDO::FETCH_ASSOC);
                
                $ids_existentes = array_column($resultados, 'PK_id_cancion');
                foreach ($fill as $f) {
                    if (!in_array($f['PK_id_cancion'], $ids_existentes)) {
                        $resultados[] = $f;
                    }
                }
            }

            return $resultados;

        } catch (PDOException $e) {
            return [];
        } finally {
            $this->db = null;
        }
    }
}
?>