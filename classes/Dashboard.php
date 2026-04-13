<?php
/**
 * CLASE: Dashboard
 * PROPÓSITO: KPIs globales para el panel de administración.
 * REGLAS: PDO preparadas, $db = null al terminar, sin SQL en el controlador.

 */
require_once __DIR__ . '/Conexion.php';

class Dashboard {
    private $db;

    public function __construct() {
        $this->db = (new Conexion())->conectar();
    }

    public function obtenerEstadisticas() {
        $stats = [
            // Usuarios
            'dau'                => 0,
            'mau'                => 0,
            'totalUsuarios'      => 0,
            'totalPro'           => 0,
            'totalFree'          => 0,
            // Actividad
            'horas_streaming'    => 0,
            'reproducciones_hoy' => 0,
            // Negocio
            'ratio_convertidos'  => 0,   // % del total que son Premium
            'tasa_conversion'    => 0,   // % de Free que se convirtió a Premium este mes (vía Factura)
            'convertidos_mes'    => 0,   // Cantidad de usuarios que pagaron por primera vez este mes
            'tasa_retencion'     => 0,   // % de usuarios activos mes actual vs mes anterior
            'ingresos_mes'       => 0,   // SUM(Factura.monto_total) del mes actual
            // Catálogo
            'totalCanciones'     => 0,
            'totalArtistas'      => 0,
            'totalAlbumes'       => 0,
            // Listas
            'top_canciones'      => [],  // trending última semana
            'top_artistas'       => [],  // top 5 global
            'paises_actividad'   => [],  // top países
            // Gráficos
            'ratio_chart'        => [],  // [{tipo:'Free', total:N}, {tipo:'Premium', total:N}]
            'trending_semanal'   => [],  // [{dia:'Lun', reproducciones:N}, ...]
        ];

        try {
            // =========================================================
            // 1. DAU — Usuarios distintos con reproducción HOY
            // =========================================================
            $stmt = $this->db->prepare(
                "SELECT COUNT(DISTINCT FK_id_usuario) AS total
                 FROM Historial_Reproduccion
                 WHERE DATE(fecha_hora_reproduccion) = CURDATE()"
            );
            $stmt->execute();
            $stats['dau'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // =========================================================
            // 2. Reproducciones totales HOY
            // =========================================================
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS total
                 FROM Historial_Reproduccion
                 WHERE DATE(fecha_hora_reproduccion) = CURDATE()"
            );
            $stmt->execute();
            $stats['reproducciones_hoy'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // =========================================================
            // 3. MAU — Usuarios distintos en los últimos 30 días
            // =========================================================
            $stmt = $this->db->prepare(
                "SELECT COUNT(DISTINCT FK_id_usuario) AS total
                 FROM Historial_Reproduccion
                 WHERE fecha_hora_reproduccion >= CURDATE() - INTERVAL 30 DAY"
            );
            $stmt->execute();
            $stats['mau'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // =========================================================
            // 4. Horas Totales de Streaming
            // =========================================================
            $stmt = $this->db->prepare(
                "SELECT ROUND(SUM(segundos_escuchados)/3600, 2) AS total
                 FROM Historial_Reproduccion"
            );
            $stmt->execute();
            $stats['horas_streaming'] = (float)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // =========================================================
            // 5. Ratio Free vs Premium + Tasa conversión correcta
            //    ratio_convertidos = % del total de usuarios que son Premium
            // =========================================================
            $stmt = $this->db->prepare(
                "SELECT FK_id_tipo, COUNT(*) AS total
                 FROM Usuario
                 WHERE estado_disponible = 1
                 GROUP BY FK_id_tipo"
            );
            $stmt->execute();
            $grupos       = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalFree    = 0;
            $totalPremium = 0;
            $ratioChart   = [];
            foreach ($grupos as $g) {
                if ($g['FK_id_tipo'] == 1) { $totalFree    = (int)$g['total']; $ratioChart[] = ['tipo' => 'Free',    'total' => (int)$g['total']]; }
                if ($g['FK_id_tipo'] == 2) { $totalPremium = (int)$g['total']; $ratioChart[] = ['tipo' => 'Premium', 'total' => (int)$g['total']]; }
            }
            $totalUsuarios = $totalFree + $totalPremium ?: 1;
            $stats['totalFree']         = $totalFree;
            $stats['totalPro']          = $totalPremium;
            $stats['totalUsuarios']     = $totalUsuarios;
            $stats['ratio_convertidos'] = round(($totalPremium / $totalUsuarios) * 100, 1);
            $stats['ratio_chart']       = $ratioChart;

            // =========================================================
            // 6. Tasa de Retención real: 
            //    Usuarios activos este mes / Usuarios activos mes anterior × 100
            // =========================================================
            $stmt = $this->db->prepare(
                "SELECT COUNT(DISTINCT FK_id_usuario) AS activos_mes_actual
                 FROM Historial_Reproduccion
                 WHERE MONTH(fecha_hora_reproduccion) = MONTH(CURDATE())
                   AND YEAR(fecha_hora_reproduccion)  = YEAR(CURDATE())"
            );
            $stmt->execute();
            $activosMes = (int)($stmt->fetch(PDO::FETCH_ASSOC)['activos_mes_actual'] ?? 0);

            $stmt = $this->db->prepare(
                "SELECT COUNT(DISTINCT FK_id_usuario) AS activos_mes_anterior
                 FROM Historial_Reproduccion
                 WHERE MONTH(fecha_hora_reproduccion) = MONTH(CURDATE() - INTERVAL 1 MONTH)
                   AND YEAR(fecha_hora_reproduccion)  = YEAR(CURDATE() - INTERVAL 1 MONTH)"
            );
            $stmt->execute();
            $activosAnterior = (int)($stmt->fetch(PDO::FETCH_ASSOC)['activos_mes_anterior'] ?? 0);

            $stats['tasa_retencion'] = ($activosAnterior > 0)
                ? round(($activosMes / $activosAnterior) * 100, 1)
                : 0;

            // =========================================================
            // 7. Ingresos del MES ACTUAL — SUM real de Factura
            // =========================================================
            $stmt = $this->db->prepare(
                "SELECT COALESCE(SUM(monto_total), 0) AS ingresos
                 FROM Factura
                 WHERE MONTH(fecha_emision) = MONTH(CURDATE())
                   AND YEAR(fecha_emision)  = YEAR(CURDATE())
                   AND estado_disponible = 1"
            );
            $stmt->execute();
            $stats['ingresos_mes'] = round((float)($stmt->fetch(PDO::FETCH_ASSOC)['ingresos'] ?? 0), 2);
            // Retrocompatibilidad con el JS que usa 'ingresos_premium'
            $stats['ingresos_premium'] = $stats['ingresos_mes'];

            // =========================================================
            // 7b. Tasa de Conversión Free → Premium (mes actual)
            //     Proxy: usuarios distintos con factura pagada este mes
            //     dividido entre (Free activos + convertidos este mes)
            // =========================================================
            $stmtConv = $this->db->prepare(
                "SELECT COUNT(DISTINCT FK_id_usuario) AS convertidos
                 FROM Factura
                 WHERE MONTH(fecha_emision) = MONTH(CURDATE())
                   AND YEAR(fecha_emision)  = YEAR(CURDATE())
                   AND estado_disponible = 1"
            );
            $stmtConv->execute();
            $convertidos = (int)($stmtConv->fetch(PDO::FETCH_ASSOC)['convertidos'] ?? 0);

            $stmtFree = $this->db->prepare(
                "SELECT COUNT(*) AS total_free
                 FROM Usuario
                 WHERE FK_id_tipo = 1 AND estado_disponible = 1"
            );
            $stmtFree->execute();
            $totalFreeActivos = (int)($stmtFree->fetch(PDO::FETCH_ASSOC)['total_free'] ?? 1);

            $base = $totalFreeActivos + $convertidos;
            $stats['convertidos_mes']  = $convertidos;
            $stats['tasa_conversion']  = ($base > 0)
                ? round(($convertidos / $base) * 100, 2)
                : 0;

            // =========================================================
            // 8. Canciones TRENDING — últimos 7 días (Historial real)
            // =========================================================
            $stmtTrend = $this->db->prepare(
                "SELECT c.titulo, a.nombre_artistico AS artista,
                        COUNT(*) AS reproducciones_semana,
                        c.contador_reproducciones
                 FROM Historial_Reproduccion h
                 INNER JOIN Cancion c   ON h.FK_id_cancion   = c.PK_id_cancion
                 INNER JOIN Album   alb ON c.FK_id_album      = alb.PK_id_album
                 INNER JOIN Artista a   ON alb.FK_id_artista  = a.PK_id_artista
                 WHERE h.fecha_hora_reproduccion >= CURDATE() - INTERVAL 7 DAY
                   AND c.estado_disponible = 1
                 GROUP BY c.PK_id_cancion
                 ORDER BY reproducciones_semana DESC
                 LIMIT 50"
            );
            $stmtTrend->execute();
            $stats['top_canciones'] = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);

            // Fallback: si no hay historial semanal, usar contador global
            if (empty($stats['top_canciones'])) {
                $stmtFb = $this->db->prepare(
                    "SELECT c.titulo, a.nombre_artistico AS artista,
                            c.contador_reproducciones, c.contador_reproducciones AS reproducciones_semana
                     FROM Cancion c
                     INNER JOIN Album alb ON c.FK_id_album = alb.PK_id_album
                     INNER JOIN Artista a ON alb.FK_id_artista = a.PK_id_artista
                     WHERE c.estado_disponible = 1
                     ORDER BY c.contador_reproducciones DESC LIMIT 50"
                );
                $stmtFb->execute();
                $stats['top_canciones'] = $stmtFb->fetchAll(PDO::FETCH_ASSOC);
            }

            // =========================================================
            // 9. TOP 5 Artistas globales (por reproducciones validadas)
            // =========================================================
            $stmtArt = $this->db->prepare(
                "SELECT a.nombre_artistico AS artista,
                        COUNT(h.PK_id_historial) AS reproducciones,
                        ROUND(SUM(h.segundos_escuchados)/60, 1) AS minutos
                 FROM Historial_Reproduccion h
                 INNER JOIN Cancion c   ON h.FK_id_cancion   = c.PK_id_cancion
                 INNER JOIN Album   alb ON c.FK_id_album      = alb.PK_id_album
                 INNER JOIN Artista a   ON alb.FK_id_artista  = a.PK_id_artista
                 WHERE c.estado_disponible = 1
                 GROUP BY a.PK_id_artista
                 ORDER BY reproducciones DESC
                 LIMIT 5"
            );
            $stmtArt->execute();
            $stats['top_artistas'] = $stmtArt->fetchAll(PDO::FETCH_ASSOC);

            // =========================================================
            // 10. Trending SEMANAL por día (para gráfico de área)
            //     Reproducciones de los últimos 7 días agrupadas por día
            // =========================================================
            $stmtTrnd = $this->db->prepare(
                "SELECT DATE_FORMAT(fecha_hora_reproduccion, '%a %d/%m') AS dia,
                        COUNT(*) AS reproducciones
                 FROM Historial_Reproduccion
                 WHERE fecha_hora_reproduccion >= CURDATE() - INTERVAL 7 DAY
                 GROUP BY DATE(fecha_hora_reproduccion)
                 ORDER BY DATE(fecha_hora_reproduccion) ASC"
            );
            $stmtTrnd->execute();
            $stats['trending_semanal'] = $stmtTrnd->fetchAll(PDO::FETCH_ASSOC);

            // =========================================================
            // 11. Totales de catálogo
            // =========================================================
            $stmtC = $this->db->prepare("SELECT COUNT(*) AS t FROM Cancion WHERE estado_disponible = 1");
            $stmtC->execute();
            $stats['totalCanciones'] = (int)($stmtC->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);

            $stmtA = $this->db->prepare("SELECT COUNT(*) AS t FROM Artista WHERE estado_disponible = 1");
            $stmtA->execute();
            $stats['totalArtistas'] = (int)($stmtA->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);

            $stmtAlb = $this->db->prepare("SELECT COUNT(*) AS t FROM Album WHERE estado_disponible = 1");
            $stmtAlb->execute();
            $stats['totalAlbumes'] = (int)($stmtAlb->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);

            // =========================================================
            // 12. Países con más actividad (Mapeo de nombres completos)
            // =========================================================
            $stmtPais = $this->db->prepare(
                "SELECT codigo_pais AS codigo, COUNT(PK_id_usuario) AS actividad
                 FROM Usuario
                 WHERE estado_disponible = 1 AND FK_id_tipo = 1 AND codigo_pais IS NOT NULL
                 GROUP BY codigo_pais
                 ORDER BY actividad DESC
                 LIMIT 5"
            );
            $stmtPais->execute();
            $paisesRaw = $stmtPais->fetchAll(PDO::FETCH_ASSOC);

            // Mapeo manual de códigos ISO a nombres (Req: Dashboard Profesional)
            $mapaPaises = [
                'HN' => 'Honduras', 'MX' => 'México', 'ES' => 'España', 'US' => 'Estados Unidos',
                'GT' => 'Guatemala', 'SV' => 'El Salvador', 'NI' => 'Nicaragua', 'CR' => 'Costa Rica',
                'PA' => 'Panamá', 'CO' => 'Colombia', 'AR' => 'Argentina', 'CL' => 'Chile', 
                'PE' => 'Perú', 'BR' => 'Brasil', 'UY' => 'Uruguay', 'VE' => 'Venezuela'
            ];

            $stats['paises_actividad'] = array_map(function($p) use ($mapaPaises) {
                return [
                    'pais' => $mapaPaises[strtoupper($p['codigo'])] ?? $p['codigo'],
                    'actividad' => (int)$p['actividad']
                ];
            }, $paisesRaw);

            return $stats;

        } catch (PDOException $e) {
            $this->logError('Dashboard::obtenerEstadisticas() — ' . $e->getMessage());
            return $stats;
        } finally {
            $this->db = null; // Regla innegociable
        }
    }

    /**
     * Método independiente para consultar solo la tasa de conversión.
     * Útil si en el futuro el dashboard se fragmenta en endpoints separados.
     */
    public function obtenerTasaConversion(): array {
        try {
            if (!$this->db) $this->db = (new Conexion())->conectar();

            $stmt1 = $this->db->prepare(
                "SELECT COUNT(DISTINCT FK_id_usuario) AS convertidos
                 FROM Factura
                 WHERE MONTH(fecha_emision) = MONTH(CURDATE())
                   AND YEAR(fecha_emision)  = YEAR(CURDATE())
                   AND estado_disponible = 1"
            );
            $stmt1->execute();
            $convertidos = (int)($stmt1->fetch(PDO::FETCH_ASSOC)['convertidos'] ?? 0);

            $stmt2 = $this->db->prepare(
                "SELECT COUNT(*) AS total_free
                 FROM Usuario
                 WHERE FK_id_tipo = 1 AND estado_disponible = 1"
            );
            $stmt2->execute();
            $totalFree = (int)($stmt2->fetch(PDO::FETCH_ASSOC)['total_free'] ?? 1);

            $base = $totalFree + $convertidos;
            return [
                'convertidos_mes' => $convertidos,
                'base_free'       => $totalFree,
                'tasa_conversion' => ($base > 0) ? round(($convertidos / $base) * 100, 2) : 0,
            ];
        } catch (PDOException $e) {
            $this->logError('obtenerTasaConversion(): ' . $e->getMessage());
            return ['convertidos_mes' => 0, 'base_free' => 0, 'tasa_conversion' => 0];
        } finally {
            $this->db = null;
        }
    }

    private function logError(string $msg): void {
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($dir . '/errores.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
    }
}
?>