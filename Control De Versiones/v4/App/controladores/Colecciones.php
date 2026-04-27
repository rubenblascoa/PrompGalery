<?php
class Colecciones extends Controlador {
    private $coleccionModelo;
    private $promptModelo;

    public function __construct() {
        $this->coleccionModelo = $this->modelo('ColeccionModelo');
        $this->promptModelo    = $this->modelo('PromptModelo');
    }

    // GET /colecciones  —  vista pública (mis colecciones o de usuario)
    public function index() {
        $usuario = usuarioActual();
        if (!$usuario) { $this->vista('colecciones/index', ['titulo' => 'Colecciones', 'colecciones' => [], 'usuario' => null]); return; }
        $colecciones = $this->coleccionModelo->obtenerDeUsuario($usuario['id']);
        $this->vista('colecciones/index', ['titulo' => 'Mis Colecciones — PromptVault', 'colecciones' => $colecciones, 'usuario' => $usuario]);
    }

    // GET /colecciones/mis  →  JSON (para SPA)
    public function mis() {
        requireLogin();
        $usuario     = usuarioActual();
        $colecciones = $this->coleccionModelo->obtenerDeUsuario($usuario['id']);
        jsonResponse(['colecciones' => $colecciones]);
    }

    // GET /colecciones/ver/:id  →  JSON
    public function ver($id = null) {
        $id = sanitizarUUID($id);
        if (!$id) jsonResponse(['error' => 'ID inválido'], 400);
        $col     = $this->coleccionModelo->obtenerPorId($id);
        if (!$col) jsonResponse(['error' => 'No encontrada'], 404);
        $usuario = usuarioActual();
        if ($col->privada && (!$usuario || $usuario['id'] !== $col->usuario_id)) jsonResponse(['error' => 'No autorizado'], 403);
        $prompts = $this->coleccionModelo->obtenerPrompts($id);
        jsonResponse(['coleccion' => $col, 'prompts' => $prompts]);
    }

    // POST /colecciones/crear
    public function crear() {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);
        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);
        if (!checkRateLimit('crear_coleccion', 20, 3600)) jsonResponse(['error' => 'Límite alcanzado'], 429);

        $usuario     = usuarioActual();
        $nombre      = sanitizarTexto($_POST['nombre']      ?? '');
        $descripcion = sanitizarTexto($_POST['descripcion'] ?? '');
        $privada     = (int)(!empty($_POST['privada']));

        if (strlen($nombre) < 2 || strlen($nombre) > 100) jsonResponse(['error' => 'El nombre debe tener entre 2 y 100 caracteres']);

        $id = $this->coleccionModelo->crear(['usuario_id' => $usuario['id'], 'nombre' => $nombre, 'descripcion' => $descripcion, 'privada' => $privada]);
        if ($id) jsonResponse(['ok' => true, 'id' => $id, 'mensaje' => 'Colección creada']);
        else     jsonResponse(['error' => 'Error al crear'], 500);
    }

    // POST /colecciones/eliminar/:id
    public function eliminar($id = null) {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);
        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);
        $id = sanitizarUUID($id);
        $usuario = usuarioActual();
        $ok = $this->coleccionModelo->eliminar($id, $usuario['id']);
        jsonResponse($ok ? ['ok' => true] : ['error' => 'No se pudo eliminar']);
    }

    // POST /colecciones/agregar_prompt
    public function agregar_prompt() {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);
        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);

        $usuario      = usuarioActual();
        $coleccion_id = sanitizarUUID($_POST['coleccion_id'] ?? '');
        $prompt_id    = sanitizarUUID($_POST['prompt_id']    ?? '');
        if (!$coleccion_id || !$prompt_id) jsonResponse(['error' => 'Parámetros inválidos'], 400);

        $col = $this->coleccionModelo->obtenerPorId($coleccion_id);
        if (!$col || $col->usuario_id !== $usuario['id']) jsonResponse(['error' => 'No autorizado'], 403);

        $ok = $this->coleccionModelo->agregarPrompt($coleccion_id, $prompt_id);
        jsonResponse($ok ? ['ok' => true, 'mensaje' => 'Prompt añadido a la colección'] : ['error' => 'Ya está en esa colección o error al añadir']);
    }

    // POST /colecciones/quitar_prompt
    public function quitar_prompt() {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);
        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);

        $usuario      = usuarioActual();
        $coleccion_id = sanitizarUUID($_POST['coleccion_id'] ?? '');
        $prompt_id    = sanitizarUUID($_POST['prompt_id']    ?? '');
        $col = $this->coleccionModelo->obtenerPorId($coleccion_id);
        if (!$col || $col->usuario_id !== $usuario['id']) jsonResponse(['error' => 'No autorizado'], 403);

        $ok = $this->coleccionModelo->quitarPrompt($coleccion_id, $prompt_id);
        jsonResponse($ok ? ['ok' => true] : ['error' => 'Error al quitar']);
    }
}
