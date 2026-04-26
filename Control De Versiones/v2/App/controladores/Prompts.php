<?php
class Prompts extends Controlador {
    private $promptModelo;
    private $usuarioModelo;

    public function __construct() {
        $this->promptModelo  = $this->modelo('PromptModelo');
        $this->usuarioModelo = $this->modelo('UsuarioModelo');
    }

    // GET /prompts/detalle/:id  →  JSON
    public function detalle($id = null) {
        $id = sanitizarUUID($id);
        if (!$id) jsonResponse(['error' => 'ID inválido'], 400);

        $prompt     = $this->promptModelo->obtenerPromptPorId($id);
        $comentarios = $this->promptModelo->obtenerComentarios($id);
        $relacionados = $this->promptModelo->obtenerRelacionados($id, $prompt->categoria ?? '');

        if (!$prompt) jsonResponse(['error' => 'Prompt no encontrado'], 404);

        $usuario = usuarioActual();
        $miVoto = $usuario ? $this->promptModelo->obtenerVotoUsuario($id, $usuario['id']) : null;
        $esFavorito = $usuario ? $this->promptModelo->esFavorito($usuario['id'], $id) : false;

        jsonResponse([
            'prompt'      => $prompt,
            'comentarios' => $comentarios,
            'relacionados' => $relacionados,
            'mi_voto'     => $miVoto,
            'es_favorito' => $esFavorito,
        ]);
    }

    // POST /prompts/crear
    public function crear() {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireccionar('/');

        if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
            jsonResponse(['error' => 'Token CSRF inválido'], 403);
        }

        if (!checkRateLimit('crear_prompt', MAX_PROMPTS_PER_HOUR)) {
            jsonResponse(['error' => 'Límite de prompts por hora alcanzado'], 429);
        }

        $usuario = usuarioActual();
        $titulo    = sanitizarTexto($_POST['titulo'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $categoria = sanitizarTexto($_POST['categoria'] ?? '');
        $modelo    = sanitizarTexto($_POST['modelo'] ?? '');
        $tags      = sanitizarTexto($_POST['tags'] ?? '');

        $errores = [];
        if (strlen($titulo) < 5 || strlen($titulo) > MAX_TITLE_LENGTH)
            $errores[] = 'El título debe tener entre 5 y ' . MAX_TITLE_LENGTH . ' caracteres';
        if (strlen($contenido) < MIN_PROMPT_LENGTH || strlen($contenido) > MAX_PROMPT_LENGTH)
            $errores[] = 'El prompt debe tener entre ' . MIN_PROMPT_LENGTH . ' y ' . MAX_PROMPT_LENGTH . ' caracteres';
        $cats = ['codigo', 'escritura', 'analisis', 'imagen', 'chatbot', 'razonamiento'];
        if (!in_array($categoria, $cats)) $errores[] = 'Categoría inválida';

        if (!empty($errores)) {
            jsonResponse(['error' => implode('. ', $errores)]);
        }

        // Limpiar tags
        $tagsLimpios = implode(',', array_map(function($t) {
            return sanitizarTexto(trim($t));
        }, array_slice(explode(',', $tags), 0, 10)));

        $ok = $this->promptModelo->agregarPrompt([
            'usuario_id' => $usuario['id'],
            'titulo'     => $titulo,
            'contenido'  => $contenido,
            'categoria'  => $categoria,
            'modelo'     => $modelo ?: 'claude',
            'tags'       => $tagsLimpios,
        ]);

        if ($ok) {
            jsonResponse(['ok' => true, 'mensaje' => '¡Prompt publicado correctamente!']);
        } else {
            jsonResponse(['error' => 'Error al publicar'], 500);
        }
    }

    // POST /prompts/editar/:id
    public function editar($id = null) {
        requireLogin();
        $id = sanitizarUUID($id);
        if (!$id) jsonResponse(['error' => 'ID inválido'], 400);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $prompt = $this->promptModelo->obtenerPromptPorId($id);
            if (!$prompt) { http_response_code(404); return; }
            $usuario = usuarioActual();
            if ($prompt->usuario_id !== $usuario['id']) redireccionar('/');
            $this->vista('prompts/editar', ['prompt' => $prompt, 'titulo' => 'Editar Prompt', 'usuario' => $usuario]);
            return;
        }

        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);

        $usuario = usuarioActual();
        if (!$this->promptModelo->esDuenoPrompt($id, $usuario['id'])) {
            jsonResponse(['error' => 'Sin permisos'], 403);
        }

        $titulo    = sanitizarTexto($_POST['titulo'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $categoria = sanitizarTexto($_POST['categoria'] ?? '');
        $modelo    = sanitizarTexto($_POST['modelo'] ?? '');
        $tags      = sanitizarTexto($_POST['tags'] ?? '');

        $ok = $this->promptModelo->editarPrompt([
            'id'        => $id,
            'usuario_id'=> $usuario['id'],
            'titulo'    => $titulo,
            'contenido' => $contenido,
            'categoria' => $categoria,
            'modelo'    => $modelo,
            'tags'      => $tags,
        ]);

        jsonResponse($ok ? ['ok' => true] : ['error' => 'Error al actualizar'], $ok ? 200 : 500);
    }

    // POST /prompts/eliminar/:id
    public function eliminar($id = null) {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

        $id = sanitizarUUID($id);
        if (!$id) jsonResponse(['error' => 'ID inválido'], 400);

        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);

        $usuario = usuarioActual();
        $ok = $this->promptModelo->eliminarPrompt($id, $usuario['id']);
        jsonResponse($ok ? ['ok' => true] : ['error' => 'No se pudo eliminar']);
    }

    // POST /prompts/votar/:id/:tipo
    public function votar($prompt_id = null, $tipo = null) {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

        $prompt_id = sanitizarUUID($prompt_id);
        if (!$prompt_id || !in_array($tipo, ['positivo', 'negativo'])) {
            jsonResponse(['error' => 'Parámetros inválidos'], 400);
        }

        $usuario = usuarioActual();
        $resultado = $this->promptModelo->registrarVoto($prompt_id, $usuario['id'], $tipo);
        $votos = $this->promptModelo->getVotosCount($prompt_id);

        jsonResponse([
            'ok'         => true,
            'accion'     => $resultado,
            'positivos'  => (int)($votos->positivos ?? 0),
            'negativos'  => (int)($votos->negativos ?? 0),
        ]);
    }

    // POST /prompts/comentar
    public function comentar() {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);

        if (!checkRateLimit('comentar', MAX_COMMENTS_PER_HOUR)) {
            jsonResponse(['error' => 'Demasiados comentarios. Espera un momento.'], 429);
        }

        $usuario    = usuarioActual();
        $prompt_id  = sanitizarUUID($_POST['prompt_id'] ?? '');
        $contenido  = sanitizarTexto($_POST['contenido'] ?? '');

        if (!$prompt_id || strlen($contenido) < 2 || strlen($contenido) > MAX_COMMENT_LENGTH) {
            jsonResponse(['error' => 'Comentario inválido']);
        }

        $ok = $this->promptModelo->agregarComentario([
            'prompt_id'  => $prompt_id,
            'usuario_id' => $usuario['id'],
            'contenido'  => $contenido,
        ]);

        if ($ok) {
            $comentarios = $this->promptModelo->obtenerComentarios($prompt_id);
            jsonResponse(['ok' => true, 'comentarios' => $comentarios]);
        } else {
            jsonResponse(['error' => 'Error al comentar'], 500);
        }
    }

    // POST /prompts/favorito/:id
    public function favorito($id = null) {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

        $id = sanitizarUUID($id);
        if (!$id) jsonResponse(['error' => 'ID inválido'], 400);

        $usuario = usuarioActual();
        $guardado = $this->promptModelo->toggleFavorito($usuario['id'], $id);

        jsonResponse([
            'ok'      => true,
            'guardado' => $guardado,
            'mensaje' => $guardado ? '✅ Guardado en favoritos' : '🔖 Eliminado de favoritos',
        ]);
    }

    // GET /prompts/buscar?q=...
    public function buscar() {
        $q         = sanitizarTexto($_GET['q'] ?? '');
        $categoria = sanitizarTexto($_GET['cat'] ?? '');
        $modelo    = sanitizarTexto($_GET['modelo'] ?? '');
        $orden     = sanitizarTexto($_GET['orden'] ?? 'relevancia');

        if (strlen($q) < 2) {
            jsonResponse(['resultados' => [], 'total' => 0]);
        }

        $resultados = $this->promptModelo->buscar($q, $categoria, $modelo, $orden);
        jsonResponse(['resultados' => $resultados, 'total' => count($resultados), 'query' => $q]);
    }
}
