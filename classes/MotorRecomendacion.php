<?php
/**
 * CLASE: MotorRecomendacion
 * PROPÓSITO:
 * Centralizar la lógica de recomendaciones algorítmicas de la plataforma.
 * 1. Radio (Basado en una canción semilla).
 * 2. Descubrimiento Semanal (Canciones nuevas afines al género principal).
 */

require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/Historial.php';

class MotorRecomendacion {

    /**
     * Requisito: "Radio" automática genera playlist basada en una canción seed.
     * Busca hasta 20 canciones del mismo género o artista, excluyendo la semilla.
     */
    public function generarRadio($id_cancion_seed) {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return [];

        try {
            // Obtener metadata semilla
            $sqlSeed = "SELECT FK_id_genero, FK_id_album FROM Cancion WHERE PK_id_cancion = ?";
            $stmtSeed = $db->prepare($sqlSeed);
            $stmtSeed->execute([$id_cancion_seed]);
            $seed = $stmtSeed->fetch(PDO::FETCH_ASSOC);

            if (!$seed) return [];

            // Buscar el artista del album
            $sqlArtist = "SELECT FK_id_artista FROM Album WHERE PK_id_album = ?";
            $stmtArt = $db->prepare($sqlArtist);
            $stmtArt->execute([$seed['FK_id_album']]);
            $artista_seed = $stmtArt->fetch(PDO::FETCH_ASSOC)['FK_id_artista'] ?? 0;

            // Consultar recomendaciones mixtas
            $sql = "SELECT c.PK_id_cancion, c.titulo, c.ruta_archivo_audio, a.nombre_artistico as artista, a.PK_id_artista
                    FROM Cancion c
                    INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                    INNER JOIN Artista a ON alb.FK_id_artista = a.PK_id_artista
                    WHERE c.estado_disponible = 1 
                      AND c.PK_id_cancion != ?
                      AND (c.FK_id_genero = ? OR a.PK_id_artista = ?)
                    ORDER BY RAND() LIMIT 20";
                    
            $stmt = $db->prepare($sql);
            $stmt->execute([$id_cancion_seed, $seed['FK_id_genero'], $artista_seed]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        } finally {
            $db = null;
        }
    }

    /**
     * Requisito: Descubrimiento semanal con canciones nuevas similares a gustos.
     * Encuentra canciones del género favorito que NUNCA ha escuchado el usuario.
     */
    public function descubrimientoSemanal($id_usuario) {
        // En lugar de instanciar un controlador completo, hacemos la consulta simplificada aquí
        // para tener performance.
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return [];

        try {
            // 1. Obtener género favorito (basado en escuchas)
            $sqlFav = "SELECT g.PK_id_genero
                       FROM Historial_Reproduccion h
                       INNER JOIN Cancion c ON h.FK_id_cancion = c.PK_id_cancion
                       INNER JOIN Genero_Musical g ON c.FK_id_genero = g.PK_id_genero
                       WHERE h.FK_id_usuario = ?
                       GROUP BY g.PK_id_genero
                       ORDER BY COUNT(*) DESC LIMIT 1";
            $stmtFav = $db->prepare($sqlFav);
            $stmtFav->execute([$id_usuario]);
            $genero_fav = $stmtFav->fetch(PDO::FETCH_ASSOC);
            $id_genero_favorito = $genero_fav ? $genero_fav['PK_id_genero'] : 0;

            // 2. Traer 20 canciones de ese género que NO estén en su historial
            $sqlDescubrimiento = "
                    SELECT c.PK_id_cancion, c.titulo, c.ruta_archivo_audio, a.nombre_artistico as artista
                    FROM Cancion c
                    INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                    INNER JOIN Artista a ON alb.FK_id_artista = a.PK_id_artista
                    WHERE c.estado_disponible = 1 
                      AND c.FK_id_genero = ?
                      AND c.PK_id_cancion NOT IN (
                          SELECT FK_id_cancion FROM Historial_Reproduccion WHERE FK_id_usuario = ?
                      )
                    ORDER BY c.contador_reproducciones DESC 
                    LIMIT 20";
                    
            $stmt = $db->prepare($sqlDescubrimiento);
            // Si no tiene género favorito (usuario nuevo), se sugiere global top 20
            if ($id_genero_favorito == 0) {
                 $sqlTop = "SELECT c.PK_id_cancion, c.titulo, c.ruta_archivo_audio, a.nombre_artistico as artista
                            FROM Cancion c
                            INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                            INNER JOIN Artista a ON alb.FK_id_artista = a.PK_id_artista
                            WHERE c.estado_disponible = 1
                            ORDER BY c.contador_reproducciones DESC LIMIT 20";
                 $stmt = $db->prepare($sqlTop);
                 $stmt->execute();
            } else {
                 $stmt->execute([$id_genero_favorito, $id_usuario]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        } finally {
            $db = null;
        }
    }

    /**
     * Requisito: "Basado en tus gustos" / artistas seguidos.
     * Busca canciones de artistas que el usuario sigue y que aún no ha escuchado.
     * Si no sigue a nadie, cae en el top global.
     *
     * @param int $id_usuario
     * @return array
     */
    public function recomendarPorArtistasSeguidos($id_usuario) {
        $conObj = new Conexion();
        $db = $conObj->conectar();
        if ($db === null) return [];

        try {
            // 1. ¿Sigue algún artista?
            $sqlChk = "SELECT FK_id_artista FROM Seguimiento_Artista WHERE FK_id_usuario = ? LIMIT 1";
            $stmtChk = $db->prepare($sqlChk);
            $stmtChk->execute([$id_usuario]);
            $hay_seguidos = $stmtChk->rowCount() > 0;

            if (!$hay_seguidos) {
                // Sin seguidos: devolver top 10 global
                $sql = "SELECT c.PK_id_cancion, c.titulo, c.ruta_archivo_audio,
                               a.nombre_artistico AS artista, a.PK_id_artista,
                               alb.titulo AS album,
                               (SELECT COUNT(*) FROM Seguimiento_Artista sa2 
                                WHERE sa2.FK_id_artista = a.PK_id_artista AND sa2.FK_id_usuario = ?) AS sigue_artista
                        FROM Cancion c
                        INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                        INNER JOIN Artista a ON alb.FK_id_artista = a.PK_id_artista
                        WHERE c.estado_disponible = 1
                        ORDER BY c.contador_reproducciones DESC LIMIT 10";
                $stmt = $db->prepare($sql);
                $stmt->execute([$id_usuario]);
            } else {
                // Con seguidos: canciones de artistas seguidos que no ha escuchado
                $sql = "SELECT c.PK_id_cancion, c.titulo, c.ruta_archivo_audio,
                               a.nombre_artistico AS artista, a.PK_id_artista,
                               alb.titulo AS album, 1 AS sigue_artista
                        FROM Cancion c
                        INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                        INNER JOIN Artista a ON alb.FK_id_artista = a.PK_id_artista
                        INNER JOIN Seguimiento_Artista sa ON sa.FK_id_artista = a.PK_id_artista
                        WHERE sa.FK_id_usuario = ?
                          AND c.estado_disponible = 1
                          AND c.PK_id_cancion NOT IN (
                               SELECT FK_id_cancion FROM Historial_Reproduccion WHERE FK_id_usuario = ?
                          )
                        ORDER BY RAND() LIMIT 15";
                $stmt = $db->prepare($sql);
                $stmt->execute([$id_usuario, $id_usuario]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        } finally {
            $db = null;
        }
    }
}
?>
