<?php
/**
 * CLASE: Artista
 * PROPÓSITO: Gestionar el CRUD de Artistas y su información pública.
 */
require_once __DIR__ . '/Conexion.php';

class Artista {
    private $db;

    public function __construct() {
        $this->db = (new Conexion())->conectar();
    }

    private function reconectar() {
        if ($this->db === null) {
            $this->db = (new Conexion())->conectar();
        }
    }

    public function listarArtistas() {
        try {
            $sql = "SELECT PK_id_artista, nombre_artistico, biografia, ruta_foto_perfil, verificado, estado_disponible 
                    FROM Artista 
                    WHERE estado_disponible = 1 
                    ORDER BY PK_id_artista DESC";
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

    public function registrarArtista($nombre, $biografia, $ruta_foto, $verificado, $id_gestor) {
        try {
            $sql = "INSERT INTO Artista (nombre_artistico, biografia, ruta_foto_perfil, verificado, FK_id_usuario_gestor) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nombre, $biografia, $ruta_foto, $verificado, $id_gestor]);
            return $stmt->rowCount() > 0 ? 1 : 3;
        } catch (PDOException $e) {
            $this->logError('registrarArtista(): ' . $e->getMessage());
            return 3;
        } finally {
            $this->db = null;
        }
    }

    public function actualizarArtista($id, $nombre, $biografia, $ruta_foto, $verificado) {
        try {
            if (empty($ruta_foto)) {
                $sql = "UPDATE Artista SET nombre_artistico = ?, biografia = ?, verificado = ? WHERE PK_id_artista = ? AND estado_disponible = 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$nombre, $biografia, $verificado, $id]);
            } else {
                $sql = "UPDATE Artista SET nombre_artistico = ?, biografia = ?, ruta_foto_perfil = ?, verificado = ? WHERE PK_id_artista = ? AND estado_disponible = 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$nombre, $biografia, $ruta_foto, $verificado, $id]);
            }
            return $stmt->rowCount() > 0 ? 1 : 3;
        } catch (PDOException $e) {
            $this->logError('actualizarArtista(): ' . $e->getMessage());
            return 3;
        } finally {
            $this->db = null;
        }
    }

    public function eliminarArtista($id) {
        try {
            $sql = "UPDATE Artista SET estado_disponible = 0 WHERE PK_id_artista = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0 ? 1 : 3;
        } catch (PDOException $e) {
            $this->logError('eliminarArtista(): ' . $e->getMessage());
            return 3;
        } finally {
            $this->db = null;
        }
    }

    public function obtenerArtista($id) {
        $this->reconectar();
        try {
            $sql = "SELECT * FROM Artista WHERE PK_id_artista = ? AND estado_disponible = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('obtenerArtista(): ' . $e->getMessage());
            return null;
        } finally {
            $this->db = null;
        }
    }

    /**
     * Obtiene todos los álbumes (discografía) de un artista.
     * Incluye la duración total calculada sumando sus canciones.
     * @param int $id_artista
     * @return array
     */
    public function obtenerDiscografia($id_artista) {
        $this->reconectar();
        try {
            $sql = "SELECT a.PK_id_album, a.titulo, a.fecha_lanzamiento, a.ruta_portada, a.discografica,
                           COUNT(c.PK_id_cancion) AS total_canciones,
                           COALESCE(SUM(c.duracion_segundos), 0) AS duracion_total_segundos
                    FROM Album a
                    LEFT JOIN Cancion c ON a.PK_id_album = c.FK_id_album AND c.estado_disponible = 1
                    WHERE a.FK_id_artista = ? AND a.estado_disponible = 1
                    GROUP BY a.PK_id_album
                    ORDER BY a.fecha_lanzamiento DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_artista]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('obtenerDiscografia(): ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }

    /**
     * Extrae dinámicamente los géneros de un artista basándose en sus canciones.
     * @param int $id_artista
     * @return array  Lista de géneros únicos
     */
    public function obtenerGenerosArtista($id_artista) {
        $this->reconectar();
        try {
            $sql = "SELECT DISTINCT g.PK_id_genero, g.nombre_genero
                    FROM Genero_Musical g
                    INNER JOIN Cancion c ON g.PK_id_genero = c.FK_id_genero
                    INNER JOIN Album a ON c.FK_id_album = a.PK_id_album
                    WHERE a.FK_id_artista = ?
                      AND c.estado_disponible = 1
                      AND a.estado_disponible = 1
                      AND g.estado_disponible = 1
                    ORDER BY g.nombre_genero ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_artista]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('obtenerGenerosArtista(): ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }

    /**
     * Calcula la suma total de reproducciones válidas de todas las canciones del artista.
     * Solo cuenta reproducciones válidas (es_valida_regalia = 1).
     * @param int $id_artista
     * @return int
     */
    public function obtenerTotalReproducciones($id_artista) {
        $this->reconectar();
        try {
            $sql = "SELECT COALESCE(SUM(c.contador_reproducciones), 0) AS total
                    FROM Cancion c
                    INNER JOIN Album a ON c.FK_id_album = a.PK_id_album
                    WHERE a.FK_id_artista = ?
                      AND c.estado_disponible = 1
                      AND a.estado_disponible = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_artista]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($res['total'] ?? 0);
        } catch (PDOException $e) {
            $this->logError('obtenerTotalReproducciones(): ' . $e->getMessage());
            return 0;
        } finally {
            $this->db = null;
        }
    }

    /**
     * Calcula el total de usuarios que siguen a este artista.
     * @param int $id_artista
     * @return int
     */
    public function obtenerTotalSeguidores($id_artista) {
        $this->reconectar();
        try {
            $sql = "SELECT COUNT(*) AS total FROM Seguimiento_Artista WHERE FK_id_artista = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_artista]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($res['total'] ?? 0);
        } catch (PDOException $e) {
            $this->logError('obtenerTotalSeguidores(): ' . $e->getMessage());
            return 0;
        } finally {
            $this->db = null;
        }
    }


    /**
     * RF-Artista: Estadísticas completas del panel gestor.
     * Devuelve KPIs globales + top 10 canciones con reproducciones válidas
     * y regalías estimadas por canción (es_valida_regalia = 1).
     * @param int $id_artista
     * @return array
     */
    public function obtenerEstadisticasGestor(int $id_artista): array {
        $this->reconectar();
        $resultado = [
            'total_reps_validas' => 0,
            'total_segundos'     => 0,
            'regalia_usd'        => 0.0,
            'canciones'          => [],
        ];
        try {
            // KPI global: reproducciones válidas + tiempo total
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS total_reps,
                        COALESCE(SUM(h.segundos_escuchados), 0) AS tot_seg
                 FROM Historial_Reproduccion h
                 INNER JOIN Cancion c ON h.FK_id_cancion = c.PK_id_cancion
                 INNER JOIN Album   a ON c.FK_id_album   = a.PK_id_album
                 WHERE a.FK_id_artista = ? AND h.es_valida_regalia = 1"
            );
            $stmt->execute([$id_artista]);
            $global = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalReps = (int)($global['total_reps'] ?? 0);
            $resultado['total_reps_validas'] = $totalReps;
            $resultado['total_segundos']     = (int)($global['tot_seg'] ?? 0);
            $resultado['regalia_usd']        = round($totalReps * 0.005, 2); // $0.005 / rep válida

            // Top 10 canciones: reproducciones válidas del Historial + regalía estimada por pista
            $stmtTop = $this->db->prepare(
                "SELECT c.PK_id_cancion, c.titulo, alb.titulo AS album,
                        COUNT(h.PK_id_historial)          AS reps_validas,
                        ROUND(COUNT(h.PK_id_historial) * 0.005, 2) AS regalia_cancion,
                        c.contador_reproducciones,
                        c.destacada
                 FROM Historial_Reproduccion h
                 INNER JOIN Cancion c   ON h.FK_id_cancion  = c.PK_id_cancion
                 INNER JOIN Album   alb ON c.FK_id_album    = alb.PK_id_album
                 WHERE alb.FK_id_artista = ? AND h.es_valida_regalia = 1
                 GROUP BY c.PK_id_cancion
                 ORDER BY reps_validas DESC
                 LIMIT 10"
            );
            $stmtTop->execute([$id_artista]);
            $resultado['canciones'] = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

            return $resultado;
        } catch (PDOException $e) {
            $this->logError('obtenerEstadisticasGestor(): ' . $e->getMessage());
            return $resultado;
        } finally {
            $this->db = null;
        }
    }

    /**
     * Requisito u: Obtiene las canciones marcadas como destacadas de un artista para su perfil público.
     * @param int $id_artista
     * @return array  [{PK_id_cancion, titulo, album, artista, duracion_segundos, contador_reproducciones}, ...]
     */
    public function obtenerCancionesDestacadas(int $id_artista): array {
        $this->reconectar();
        try {
            $sql = "SELECT c.PK_id_cancion, c.titulo, c.duracion_segundos,
                           c.contador_reproducciones, alb.titulo AS album,
                           a.nombre_artistico AS artista
                    FROM Cancion c
                    INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                    INNER JOIN Artista a ON alb.FK_id_artista = a.PK_id_artista
                    WHERE alb.FK_id_artista = ?
                      AND c.destacada = 1
                      AND c.estado_disponible = 1
                    ORDER BY c.contador_reproducciones DESC
                    LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_artista]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('obtenerCancionesDestacadas(): ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }

    /**
     * Requisito u: Alterna la marca "destacada" de una canción en el perfil del artista.
     * Solo el gestor del artista puede activarla. Máximo 5 canciones destacadas.
     * @param int $id_cancion
     * @param int $id_artista   ID del artista propietario (para validar pertenencia)
     * @return array  ['status' => 'success'|'error', 'message' => '...', 'destacada' => 0|1]
     */
    public function alternarDestacada(int $id_cancion, int $id_artista): array {
        $this->reconectar();
        try {
            // Verificar que la canción pertenece al artista
            $stmtVerif = $this->db->prepare(
                "SELECT c.PK_id_cancion, c.destacada
                 FROM Cancion c
                 INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                 WHERE c.PK_id_cancion = ?
                   AND alb.FK_id_artista = ?
                   AND c.estado_disponible = 1"
            );
            $stmtVerif->execute([$id_cancion, $id_artista]);
            $cancion = $stmtVerif->fetch(PDO::FETCH_ASSOC);

            if (!$cancion) {
                return ['status' => 'error', 'message' => 'La canción no pertenece a este artista.'];
            }

            $nuevo_estado = $cancion['destacada'] == 1 ? 0 : 1;

            // Si se quiere activar, verificar que no haya ya 5 destacadas
            if ($nuevo_estado === 1) {
                $stmtCount = $this->db->prepare(
                    "SELECT COUNT(*) AS total
                     FROM Cancion c
                     INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                     WHERE alb.FK_id_artista = ? AND c.destacada = 1 AND c.estado_disponible = 1"
                );
                $stmtCount->execute([$id_artista]);
                if ($stmtCount->fetch(PDO::FETCH_ASSOC)['total'] >= 5) {
                    return ['status' => 'error', 'message' => 'Máximo 5 canciones destacadas por artista. Quita una antes de agregar otra.'];
                }
            }

            $stmtUpd = $this->db->prepare(
                "UPDATE Cancion SET destacada = ? WHERE PK_id_cancion = ?"
            );
            $stmtUpd->execute([$nuevo_estado, $id_cancion]);

            $accion = $nuevo_estado ? 'destacada' : 'quitada de destacados';
            return [
                'status'    => 'success',
                'message'   => "Canción {$accion} correctamente.",
                'destacada' => $nuevo_estado
            ];

        } catch (PDOException $e) {
            $this->logError('alternarDestacada(): ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al actualizar.'];
        } finally {
            $this->db = null;
        }
    }


    /**
     * RF-Artista (países): Desglose de reproducciones válidas por país del oyente.
     * Usa el campo codigo_pais de la tabla Usuario.
     * @param int $id_artista
     * @return array  [['pais'=>'MX','reps'=>N,'minutos'=>N], ...]
     */
    public function obtenerActividadPorPais(int $id_artista): array {
        $this->reconectar();
        try {
            $sql = "SELECT COALESCE(u.codigo_pais, 'Desconocido') AS pais,
                           COUNT(h.PK_id_historial)                    AS reps,
                           ROUND(SUM(h.segundos_escuchados) / 60, 1)   AS minutos
                    FROM Historial_Reproduccion h
                    INNER JOIN Cancion c   ON h.FK_id_cancion  = c.PK_id_cancion
                    INNER JOIN Album   alb ON c.FK_id_album    = alb.PK_id_album
                    INNER JOIN Usuario u   ON h.FK_id_usuario  = u.PK_id_usuario
                    WHERE alb.FK_id_artista = ?
                      AND h.es_valida_regalia = 1
                    GROUP BY u.codigo_pais
                    ORDER BY reps DESC
                    LIMIT 15";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_artista]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('obtenerActividadPorPais(): ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }

    private function logError($mensaje) {
        $log_file = __DIR__ . '/../logs/errores.log';
        $entrada = '[' . date('Y-m-d H:i:s') . '] [Artista] ' . $mensaje . PHP_EOL;
        file_put_contents($log_file, $entrada, FILE_APPEND);
    }
}
?>