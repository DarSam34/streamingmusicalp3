<?php
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/Utilidades.php';

class Genero {
    private $db;

    public function __construct() {}

    private function logError($mensaje) {
        Utilidades::registrarLog('generos_error', $mensaje);
    }

    public function listarGeneros() {
        try {
            $this->db = (new Conexion())->conectar();
            $sql = "SELECT PK_id_genero, nombre_genero FROM Genero_Musical WHERE estado_disponible = 1 ORDER BY nombre_genero ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('listarGeneros(): ' . $e->getMessage());
            return [];
        } finally {
            $this->db = null;
        }
    }

    public function crearGenero($nombre) {
        $nombre = trim($nombre);
        if (empty($nombre)) return ['status' => 'error', 'message' => 'El nombre no puede estar vacío.'];
        
        try {
            $this->db = (new Conexion())->conectar();
            
            // Verificar si existe el nombre (solo entre activos)
            $stmt = $this->db->prepare("SELECT PK_id_genero FROM Genero_Musical WHERE nombre_genero = ? AND estado_disponible = 1");
            $stmt->execute([$nombre]);
            if ($stmt->fetch()) {
                return ['status' => 'error', 'message' => 'El género ya existe.'];
            }

            $sql = "INSERT INTO Genero_Musical (nombre_genero) VALUES (?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nombre]);
            
            return ['status' => 'success', 'message' => 'Género creado correctamente.'];
        } catch (PDOException $e) {
            $this->logError('crearGenero(): ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al crear el género.'];
        } finally {
            $this->db = null;
        }
    }

    public function actualizarGenero($id_genero, $nombre) {
        $nombre = trim($nombre);
        if (empty($nombre)) return ['status' => 'error', 'message' => 'El nombre no puede estar vacío.'];
        
        try {
            $this->db = (new Conexion())->conectar();
            
            // Verificar colisión de nombre
            $stmt = $this->db->prepare("SELECT PK_id_genero FROM Genero_Musical WHERE nombre_genero = ? AND PK_id_genero != ?");
            $stmt->execute([$nombre, $id_genero]);
            if ($stmt->fetch()) {
                return ['status' => 'error', 'message' => 'Ya existe otro género con ese nombre.'];
            }

            $sql = "UPDATE Genero_Musical SET nombre_genero = ? WHERE PK_id_genero = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nombre, $id_genero]);
            
            return ['status' => 'success', 'message' => 'Género actualizado correctamente.'];
        } catch (PDOException $e) {
            $this->logError('actualizarGenero(): ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al actualizar el género.'];
        } finally {
            $this->db = null;
        }
    }

    /**
     * Borrado Lógico (Norma de Integridad Referencial).
     * Se prohíbe el DELETE físico. Se marca estado_disponible = 0.
     */
    public function eliminarGenero($id_genero) {
        try {
            $this->db = (new Conexion())->conectar();
            $sql = "UPDATE Genero_Musical SET estado_disponible = 0 WHERE PK_id_genero = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_genero]);
            return ['status' => 'success', 'message' => 'Género desactivado correctamente.'];
        } catch (PDOException $e) {
            $this->logError('eliminarGenero(): ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error al eliminar el género.'];
        } finally {
            $this->db = null;
        }
    }
}
?>
