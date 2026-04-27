<?php
class ColeccionModelo {
    private $db;

    public function __construct() { $this->db = new Base; }

    public function crear($datos) {
        $id = 'col_' . bin2hex(random_bytes(8));
        $this->db->query("INSERT INTO colecciones (id, usuario_id, nombre, descripcion, privada) VALUES (:id,:uid,:nom,:des,:priv)");
        $this->db->bind(':id',   $id);
        $this->db->bind(':uid',  $datos['usuario_id']);
        $this->db->bind(':nom',  $datos['nombre']);
        $this->db->bind(':des',  $datos['descripcion'] ?? '');
        $this->db->bind(':priv', $datos['privada'] ?? 0, PDO::PARAM_INT);
        return $this->db->execute() ? $id : false;
    }

    public function obtenerDeUsuario($usuario_id) {
        $this->db->query("SELECT c.*, (SELECT COUNT(*) FROM coleccion_prompts cp WHERE cp.coleccion_id = c.id) AS num_prompts
                          FROM colecciones c WHERE c.usuario_id = :uid ORDER BY c.fecha_creacion DESC");
        $this->db->bind(':uid', $usuario_id);
        return $this->db->registros();
    }

    public function obtenerPorId($id) {
        $this->db->query("SELECT c.*, u.nombre AS usuario_nombre FROM colecciones c
                          JOIN usuarios u ON c.usuario_id = u.id WHERE c.id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        return $this->db->registro();
    }

    public function obtenerPrompts($coleccion_id) {
        $this->db->query("SELECT p.*, u.nombre AS usuario_nombre, u.avatar
                          FROM coleccion_prompts cp
                          JOIN prompts p   ON cp.prompt_id = p.id
                          JOIN usuarios u  ON p.usuario_id = u.id
                          WHERE cp.coleccion_id = :cid AND p.activo = 1
                          ORDER BY cp.orden ASC, cp.fecha_agregado DESC");
        $this->db->bind(':cid', $coleccion_id);
        return $this->db->registros();
    }

    public function agregarPrompt($coleccion_id, $prompt_id) {
        try {
            $id = 'cp_' . bin2hex(random_bytes(8));
            $this->db->query("INSERT INTO coleccion_prompts (id, coleccion_id, prompt_id) VALUES (:id,:cid,:pid)");
            $this->db->bind(':id',  $id);
            $this->db->bind(':cid', $coleccion_id);
            $this->db->bind(':pid', $prompt_id);
            return $this->db->execute();
        } catch (Exception $e) {
            return false; // Duplicate key
        }
    }

    public function quitarPrompt($coleccion_id, $prompt_id) {
        $this->db->query("DELETE FROM coleccion_prompts WHERE coleccion_id = :cid AND prompt_id = :pid");
        $this->db->bind(':cid', $coleccion_id);
        $this->db->bind(':pid', $prompt_id);
        return $this->db->execute();
    }

    public function eliminar($id, $usuario_id) {
        $this->db->query("DELETE FROM colecciones WHERE id = :id AND usuario_id = :uid");
        $this->db->bind(':id',  $id);
        $this->db->bind(':uid', $usuario_id);
        return $this->db->execute();
    }
}
