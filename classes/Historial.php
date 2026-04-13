<?php
/**
 * CLASE: Historial
 * PROPÓSITO:
 * Gestionar las reproducciones y analítica de usuario individual.
 * Creado por IA y refactorizado ppara cumplir directivas de POO estricto
 * y SQL encapsulado.
 */

require_once __DIR__ . '/Conexion.php';

class Historial {

    /**
     * Registra una escucha aplicando reglas de anti-bot y validación de regalías.
     * @param int $id_usuario
     * @param int $id_cancion
     * @param int $segundos
     * @return bool
     */
    public function registrarEscucha($id_usuario, $id_cancion, $segundos) {
        // Regla 1: Anti-Bots (< 10 segundos no cuenta)
        if ($segundos < 10) {
            return true; 
        }

        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) {
            return false;
        }

        // Regla 2: Anti-Bots Avanzado (Más de 5 repeticiones de la misma canción en la última hora)
        try {
            $stmtBot = $db->prepare("SELECT COUNT(*) AS conteo FROM Historial_Reproduccion WHERE FK_id_usuario = ? AND FK_id_cancion = ? AND fecha_hora_reproduccion >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $stmtBot->execute([$id_usuario, $id_cancion]);
            $reps_recientes = (int)($stmtBot->fetch(PDO::FETCH_ASSOC)['conteo'] ?? 0);
            
            if ($reps_recientes >= 5) {
                // Ya la escuchó demasiado, asume comportamiento no humano o abuso de granja
                return true; // Mentimos y decimos que se registró
            }
        } catch(PDOException $e) {} // Ignoramos si falla el check y seguimos normal

        // Regla: Regalías
        $es_valida_regalia = ($segundos >= 30) ? 1 : 0;

        try {
            $sql = "INSERT INTO Historial_Reproduccion (FK_id_usuario, FK_id_cancion, segundos_escuchados, es_valida_regalia) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id_usuario, $id_cancion, $segundos, $es_valida_regalia]);

            // Si es válida para regalías, actualizamos también el contador oficial de la Canción
            if ($es_valida_regalia) {
                $sql2 = "UPDATE Cancion SET contador_reproducciones = contador_reproducciones + 1 WHERE PK_id_cancion = ?";
                $stmt2 = $db->prepare($sql2);
                $stmt2->execute([$id_cancion]);
            }

            return true;
        } catch (PDOException $e) {
            $this->logError("Error registrarEscucha: " . $e->getMessage());
            return false;
        } finally {
            $db = null; // Cierre innegociable
        }
    }

    /**
     * Obtiene todas las estadísticas personales de un usuario (Top canciones, artistas, etc.)
     * REGLA Y: Historial se filtra a 90 días para Free, ilimitado para Premium.
     * @param int $id_usuario
     * @param int $tipo_suscripcion  1 = Free (90 días), 2 = Premium (sin límite)
     * @return array
     */
    public function obtenerEstadisticasPersonales($id_usuario, $tipo_suscripcion = 1) {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) {
            return [];
        }

        // REGLA Y: Si es Free, limitar a los últimos 90 días
        $filtro_fecha = ($tipo_suscripcion == 1)
            ? "AND DATE(h.fecha_hora_reproduccion) >= CURDATE() - INTERVAL 90 DAY"
            : ""; // Premium: sin restricción de fecha

        $datos = [
            'total_minutos'   => 0,
            'total_canciones' => 0,
            'promedio_diario' => 0,
            'genero_favorito' => 'N/A',
            'top_canciones'   => [],
            'top_artistas'    => [],
            'top_playlists'   => [],
            'tipo_historial'  => ($tipo_suscripcion == 1) ? '90 días (Free)' : 'Sin límite (Premium)'
        ];

        try {
            // Total minutos escuchados (con filtro de fecha según suscripción)
            $stmt = $db->prepare("SELECT SUM(segundos_escuchados) / 60 AS total
                                  FROM Historial_Reproduccion h
                                  WHERE h.FK_id_usuario = ? {$filtro_fecha}");
            $stmt->execute([$id_usuario]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $datos['total_minutos'] = round($res['total'] ?? 0, 2);

            // Total canciones distintas reproducidas
            $stmt = $db->prepare("SELECT COUNT(DISTINCT h.FK_id_cancion) AS total
                                  FROM Historial_Reproduccion h
                                  WHERE h.FK_id_usuario = ? {$filtro_fecha}");
            $stmt->execute([$id_usuario]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $datos['total_canciones'] = $res['total'] ?? 0;

            // Promedio diario últimos 30 días (en minutos)
            $stmt = $db->prepare("SELECT (SUM(h.segundos_escuchados)/60) / 30 AS promedio
                                  FROM Historial_Reproduccion h
                                  WHERE h.FK_id_usuario = ? AND DATE(h.fecha_hora_reproduccion) >= CURDATE() - INTERVAL 30 DAY {$filtro_fecha}");
            $stmt->execute([$id_usuario]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $datos['promedio_diario'] = round($res['promedio'] ?? 0, 2);

            // Género favorito
            $stmt = $db->prepare("SELECT g.nombre_genero, COUNT(*) AS total
                                  FROM Historial_Reproduccion h
                                  INNER JOIN Cancion c ON h.FK_id_cancion = c.PK_id_cancion
                                  INNER JOIN Genero_Musical g ON c.FK_id_genero = g.PK_id_genero
                                  WHERE h.FK_id_usuario = ? {$filtro_fecha}
                                  GROUP BY g.nombre_genero
                                  ORDER BY total DESC LIMIT 1");
            $stmt->execute([$id_usuario]);
            $genero = $stmt->fetch(PDO::FETCH_ASSOC);
            $datos['genero_favorito'] = $genero ? $genero['nombre_genero'] : 'N/A';

            // Top 5 canciones más reproducidas
            $stmt = $db->prepare("SELECT c.titulo, a.nombre_artistico AS artista, COUNT(*) AS reproducciones
                                  FROM Historial_Reproduccion h
                                  INNER JOIN Cancion c ON h.FK_id_cancion = c.PK_id_cancion
                                  INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                                  INNER JOIN Artista a ON alb.FK_id_artista = a.PK_id_artista
                                  WHERE h.FK_id_usuario = ? {$filtro_fecha}
                                  GROUP BY c.PK_id_cancion
                                  ORDER BY reproducciones DESC LIMIT 5");
            $stmt->execute([$id_usuario]);
            $datos['top_canciones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Top 5 artistas más reproducidos
            $stmt = $db->prepare("SELECT a.nombre_artistico AS artista, COUNT(*) AS reproducciones
                                  FROM Historial_Reproduccion h
                                  INNER JOIN Cancion c ON h.FK_id_cancion = c.PK_id_cancion
                                  INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                                  INNER JOIN Artista a ON alb.FK_id_artista = a.PK_id_artista
                                  WHERE h.FK_id_usuario = ? {$filtro_fecha}
                                  GROUP BY a.PK_id_artista
                                  ORDER BY reproducciones DESC LIMIT 5");
            $stmt->execute([$id_usuario]);
            $datos['top_artistas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Playlists más reproducidas del usuario
            $stmt = $db->prepare("SELECT p.nombre_playlist, COUNT(h.PK_id_historial) as reproducciones
                                  FROM Playlist p
                                  INNER JOIN Detalle_Playlist dp ON p.PK_id_playlist = dp.FK_id_playlist
                                  INNER JOIN Historial_Reproduccion h ON dp.FK_id_cancion = h.FK_id_cancion AND h.FK_id_usuario = p.FK_id_usuario
                                  WHERE p.FK_id_usuario = ? AND p.estado_disponible = 1
                                  GROUP BY p.PK_id_playlist
                                  ORDER BY reproducciones DESC LIMIT 3");
            $stmt->execute([$id_usuario]);
            $datos['top_playlists'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $datos;
        } catch (PDOException $e) {
            $this->logError("Error obtenerEstadisticasPersonales: " . $e->getMessage());
            return $datos;
        } finally {
            $db = null; // Cierre innegociable
        }
    }

    /**
     * Retorna las últimas N escuchas de un usuario con detalle de canción, álbum y artista.
     * Usado en la vista de historial personal del frontend.
     * Soporta paginación mediante límite y offset.
     * @param int $id_usuario  ID del usuario
     * @param int $limite      Número máximo de registros a retornar (default 50)
     * @param int $offset      Número de registros a saltar (default 0)
     * @return array
     */
    public function obtenerHistorialReciente($id_usuario, $limite = 50, $offset = 0) {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return [];
        try {
            $sql = "SELECT h.fecha_hora_reproduccion, h.segundos_escuchados,
                           c.titulo, c.duracion_segundos,
                           a.nombre_artistico AS artista,
                           alb.titulo AS album
                    FROM Historial_Reproduccion h
                    INNER JOIN Cancion c   ON h.FK_id_cancion   = c.PK_id_cancion
                    INNER JOIN Album   alb ON c.FK_id_album      = alb.PK_id_album
                    INNER JOIN Artista a   ON alb.FK_id_artista  = a.PK_id_artista
                    WHERE h.FK_id_usuario = ?
                    ORDER BY h.fecha_hora_reproduccion DESC
                    LIMIT ? OFFSET ?";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(1, $id_usuario, PDO::PARAM_INT);
            $stmt->bindParam(2, $limite,     PDO::PARAM_INT);
            $stmt->bindParam(3, $offset,     PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('obtenerHistorialReciente(): ' . $e->getMessage());
            return [];
        } finally {
            $db = null; // Cierre innegociable
        }
    }

    /**
     * Top 5 artistas con filtro temporal dinámico.
     * Requisito: "Top 5 artistas del mes/año" con selector en el frontend.
     *
     * @param int    $id_usuario
     * @param int    $tipo_suscripcion  1=Free(90d max), 2=Premium
     * @param string $filtro            'mes_actual' | 'anio_actual' | 'todo'
     * @return array
     */
    public function obtenerTopArtistasFiltrado($id_usuario, $tipo_suscripcion = 1, $filtro = 'mes_actual') {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return [];

        // Construir cláusula de fecha según filtro elegido
        switch ($filtro) {
            case 'mes_actual':
                $clausula_fecha = "AND MONTH(h.fecha_hora_reproduccion) = MONTH(CURDATE())
                                   AND YEAR(h.fecha_hora_reproduccion)  = YEAR(CURDATE())";
                break;
            case 'anio_actual':
                $clausula_fecha = "AND YEAR(h.fecha_hora_reproduccion) = YEAR(CURDATE())";
                break;
            default: // 'todo'
                $clausula_fecha = ($tipo_suscripcion == 1)
                    ? "AND DATE(h.fecha_hora_reproduccion) >= CURDATE() - INTERVAL 90 DAY"
                    : "";
        }

        try {
            $sql = "SELECT a.nombre_artistico AS artista,
                           COUNT(*) AS reproducciones,
                           ROUND(SUM(h.segundos_escuchados)/60, 1) AS minutos
                    FROM Historial_Reproduccion h
                    INNER JOIN Cancion c   ON h.FK_id_cancion  = c.PK_id_cancion
                    INNER JOIN Album   alb ON c.FK_id_album     = alb.PK_id_album
                    INNER JOIN Artista a   ON alb.FK_id_artista = a.PK_id_artista
                    WHERE h.FK_id_usuario = ? {$clausula_fecha}
                    GROUP BY a.PK_id_artista
                    ORDER BY reproducciones DESC
                    LIMIT 5";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id_usuario]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("obtenerTopArtistasFiltrado: " . $e->getMessage());
            return [];
        } finally {
            $db = null;
        }
    }

    /**
     * Evolución de géneros favoritos por mes (últimos 6 meses).
     * Requisito 7: gráfico de líneas en Google Charts.
     * Devuelve filas: [mes_label, genero1_repros, genero2_repros, ...]
     *
     * @param int $id_usuario
     * @param int $tipo_suscripcion
     * @return array  ['meses'=>[], 'series'=>[genero=>[mes=>count]]]
     */
    public function obtenerEvolucionGenerosMensual($id_usuario, $tipo_suscripcion = 1) {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return ['meses' => [], 'series' => []];

        // Free: máx 3 meses de historia; Premium: 6 meses
        $meses_atras = ($tipo_suscripcion == 1) ? 3 : 6;

        try {
            $sql = "SELECT DATE_FORMAT(h.fecha_hora_reproduccion, '%Y-%m') AS mes,
                           g.nombre_genero AS genero,
                           COUNT(*) AS reproducciones
                    FROM Historial_Reproduccion h
                    INNER JOIN Cancion c ON h.FK_id_cancion = c.PK_id_cancion
                    INNER JOIN Genero_Musical g ON c.FK_id_genero = g.PK_id_genero
                    WHERE h.FK_id_usuario = ?
                      AND h.fecha_hora_reproduccion >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                    GROUP BY mes, g.PK_id_genero
                    ORDER BY mes ASC, reproducciones DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id_usuario, $meses_atras]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Pivotar resultado para Google Charts LineChart
            $meses  = [];
            $series = []; // [genero => [mes => count]]

            foreach ($rows as $r) {
                $mes    = $r['mes'];    // '2026-03'
                $genero = $r['genero'];
                $rep    = (int)$r['reproducciones'];

                if (!in_array($mes, $meses))    $meses[] = $mes;
                if (!isset($series[$genero]))   $series[$genero] = [];
                $series[$genero][$mes] = $rep;
            }

            // Conservar solo los top 4 géneros del periodo (menos líneas = más legible)
            $totales = [];
            foreach ($series as $g => $datos) {
                $totales[$g] = array_sum($datos);
            }
            arsort($totales);
            $series = array_intersect_key($series, array_slice($totales, 0, 4, true));

            return ['meses' => $meses, 'series' => $series];

        } catch (PDOException $e) {
            $this->logError("obtenerEvolucionGenerosMensual: " . $e->getMessage());
            return ['meses' => [], 'series' => []];
        } finally {
            $db = null;
        }
    }

    /**
     * Evita guardar errores en base de datos.
     */
    private function logError($mensaje) {
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        file_put_contents($log_dir . '/errores_historial.log', "[" . date('Y-m-d H:i:s') . "] " . $mensaje . "\n", FILE_APPEND);
    }
}
?>
