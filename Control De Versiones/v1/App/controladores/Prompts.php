<?php
class Prompts extends Controlador {
    private $promptModelo;

    public function __construct() {
        $this->promptModelo = $this->modelo('PromptModelo');
    }

    // 1. Crear nuevo prompt
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos = [
                'usuario_id' => 'user_001', // Usuario fijo temporalmente
                'titulo' => trim($_POST['titulo']),
                'contenido' => trim($_POST['contenido']),
                'categoria' => trim($_POST['categoria']),
                'modelo' => trim($_POST['modelo']),
                'tags' => 'tag1, tag2' // Simplificado
            ];
            $this->promptModelo->agregarPrompt($datos);
            redireccionar('/inicio');
        }
    }

    // 2. Ver detalle por AJAX
    public function detalle($id) {
        $prompt = $this->promptModelo->obtenerPromptPorId($id);
        $comentarios = $this->promptModelo->obtenerComentarios($id);
        echo json_encode(['prompt' => $prompt, 'comentarios' => $comentarios]);
    }

    // 3. Votar por AJAX
    public function votar($prompt_id, $tipo) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->promptModelo->registrarVoto($prompt_id, 'user_001', $tipo);
            echo json_encode(['status' => 'ok']);
        }
    }

    // 4. Comentar por AJAX
    public function comentar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $datos = [
                'prompt_id' => trim($_POST['prompt_id']),
                'usuario_id' => 'user_001',
                'contenido' => trim($_POST['contenido'])
            ];
            $this->promptModelo->agregarComentario($datos);
            echo json_encode(['status' => 'ok']);
        }
    }
}