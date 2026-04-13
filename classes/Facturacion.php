<?php
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/Utilidades.php';

class Facturacion {
    private $db;

    public function __construct() {
    }

    private function logError($mensaje) {
        Utilidades::registrarLog('facturacion_error', $mensaje);
    }

    public function obtenerMetodosPago() {
        try {
            $this->db = (new Conexion())->conectar();
            // Filtra solo métodos activos (borrado lógico)
            $sql = "SELECT PK_id_metodo, nombre_metodo FROM Metodo_Pago WHERE estado_disponible = 1 ORDER BY nombre_metodo ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('obtenerMetodosPago(): ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }

    /**
     * Procesa el upgrade a Premium de manera transaccional.
     * CU-05: Actualizar a Premium.
     */
    public function procesarUpgrade($id_usuario, $id_metodo_pago) {
        $conObj = new Conexion();
        $this->db = $conObj->conectar();
        if (!$this->db) return ['status' => 'error', 'message' => 'Error de conexión'];

        try {
            $this->db->beginTransaction();

            // 1. Obtener precio del plan Premium (FK_id_tipo = 2)
            $stmt = $this->db->prepare("SELECT precio_mensual FROM Tipo_Suscripcion WHERE PK_id_tipo = 2");
            $stmt->execute();
            $precio = $stmt->fetchColumn();
            if ($precio === false) {
                throw new Exception("Plan Premium no encontrado.");
            }

            // 2. Insertar Factura
            $stmt = $this->db->prepare("INSERT INTO Factura (FK_id_usuario, FK_id_metodo, monto_total) VALUES (?, ?, ?)");
            $stmt->execute([$id_usuario, $id_metodo_pago, $precio]);
            $id_factura = $this->db->lastInsertId();

            // 3. Insertar Detalle_Factura
            $stmt = $this->db->prepare("INSERT INTO Detalle_Factura (FK_id_factura, FK_id_tipo_suscripcion, precio_aplicado) VALUES (?, 2, ?)");
            $stmt->execute([$id_factura, $precio]);

            // 4. Actualizar estado del usuario a Premium
            $stmt = $this->db->prepare("UPDATE Usuario SET FK_id_tipo = 2 WHERE PK_id_usuario = ?");
            $stmt->execute([$id_usuario]);

            $this->db->commit();

            // Log obligatorio: upgrade a Premium es acción financiera crítica
            Utilidades::registrarLog('suscripciones',
                "[UPGRADE_PREMIUM] Usuario ID:{$id_usuario} actualizó a Premium. Factura ID:{$id_factura}. Monto: $".number_format($precio, 2));

            return ['status' => 'success', 'message' => '¡Felicidades! Ahora eres Premium.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->logError('procesarUpgrade(): ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Hubo un error al procesar el pago: ' . $e->getMessage()];
        } finally {
            $this->db = null;
        }
    }

    /**
     * Devuelve las facturas de un usuario.
     * CU-07: Ver facturas.
     */
    public function obtenerFacturasUsuario($id_usuario) {
        try {
            $this->db = (new Conexion())->conectar();
            $sql = "SELECT f.PK_id_factura, f.fecha_emision, f.monto_total, m.nombre_metodo, df.FK_id_tipo_suscripcion
                    FROM Factura f
                    INNER JOIN Metodo_Pago m ON f.FK_id_metodo = m.PK_id_metodo
                    LEFT JOIN Detalle_Factura df ON f.PK_id_factura = df.FK_id_factura
                    WHERE f.FK_id_usuario = ?
                    ORDER BY f.fecha_emision DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('obtenerFacturasUsuario(): ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }
}
?>
