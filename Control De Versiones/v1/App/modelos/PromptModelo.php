<?php
class PromptModelo {
    private $db;

    public function __construct() {
        $this->db = new Base;
    }

    // 1. Obtener todos los prompts para el feed principal
    public function obtenerPrompts() {
        $this->db->query("SELECT p.*, u.nombre as usuario_nombre, u.avatar, 
                         (SELECT COUNT(*) FROM votos WHERE prompt_id = p.id AND tipo = 'positivo') as votos_positivos,
                         (SELECT COUNT(*) FROM comentarios WHERE prompt_id = p.id) as num_comentarios
                         FROM prompts p JOIN usuarios u ON p.usuario_id = u.id WHERE p.activo = 1 ORDER BY p.fecha_creacion DESC");
        return $this->db->registros();
    }

    // 2. LA FUNCIÓN QUE FALTABA: Obtener el top de creadores para el panel derecho
    public function obtenerTopCreadores() {
        $this->db->query("SELECT * FROM usuarios_estadisticas ORDER BY num_prompts DESC LIMIT 5");
        return $this->db->registros();
    }

    // 3. Agregar un nuevo prompt desde el Modal
    public function agregarPrompt($datos) {
        $this->db->query("INSERT INTO prompts (id, usuario_id, titulo, contenido, categoria, modelo, tags) 
                         VALUES (UUID(), :usuario_id, :titulo, :contenido, :categoria, :modelo, :tags)");
        $this->db->bind(':usuario_id', $datos['usuario_id']);
        $this->db->bind(':titulo', $datos['titulo']);
        $this->db->bind(':contenido', $datos['contenido']);
        $this->db->bind(':categoria', $datos['categoria']);
        $this->db->bind(':modelo', $datos['modelo']);
        $this->db->bind(':tags', $datos['tags']);
        return $this->db->execute();
    }

    // 4. Obtener un prompt específico para verlo en el Modal de Detalles
    public function obtenerPromptPorId($id) {
        $this->db->query("SELECT p.*, u.nombre as usuario_nombre FROM prompts p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->registro();
    }

    // 5. Obtener los comentarios de un prompt específico
    public function obtenerComentarios($id) {
        $this->db->query("SELECT c.*, u.nombre as usuario_nombre, u.avatar FROM comentarios c 
                         JOIN usuarios u ON c.usuario_id = u.id WHERE c.prompt_id = :id ORDER BY c.fecha_creacion DESC");
        $this->db->bind(':id', $id);
        return $this->db->registros();
    }
// --- AÑADIR ESTO AL FINAL DE PromptModelo.php ---

    public function registrarVoto($prompt_id, $usuario_id, $tipo) {
        // Evitar duplicados borrando el anterior si existe
        $this->db->query("DELETE FROM votos WHERE prompt_id = :prompt_id AND usuario_id = :usuario_id");
        $this->db->bind(':prompt_id', $prompt_id);
        $this->db->bind(':usuario_id', $usuario_id);
        $this->db->execute();

        // Insertar nuevo voto
        $this->db->query("INSERT INTO votos (id, prompt_id, usuario_id, tipo) VALUES (UUID(), :prompt_id, :usuario_id, :tipo)");
        $this->db->bind(':prompt_id', $prompt_id);
        $this->db->bind(':usuario_id', $usuario_id);
        $this->db->bind(':tipo', $tipo);
        return $this->db->execute();
    }

    public function agregarComentario($datos) {
        $this->db->query("INSERT INTO comentarios (id, prompt_id, usuario_id, contenido) VALUES (UUID(), :prompt_id, :usuario_id, :contenido)");
        $this->db->bind(':prompt_id', $datos['prompt_id']);
        $this->db->bind(':usuario_id', $datos['usuario_id']);
        $this->db->bind(':contenido', $datos['contenido']);
        return $this->db->execute();
    }
    }
