<?php
class Perfil extends Controlador {
    private $usuarioModelo;
    private $promptModelo;

    public function __construct() {
        $this->usuarioModelo = $this->modelo('UsuarioModelo');
        $this->promptModelo  = $this->modelo('PromptModelo');
    }

    // GET /perfil/:id  o  /perfil (propio)
    public function index($id = null) {
        $usuarioActual = usuarioActual();

        if (!$id) {
            if (!$usuarioActual) redireccionar('/');
            $id = $usuarioActual['id'];
        }

        $id = sanitizarUUID($id);
        $perfil = $this->usuarioModelo->obtenerPerfil($id);
        if (!$perfil) {
            http_response_code(404);
            $this->vista('error/404', ['titulo' => 'Usuario no encontrado']);
            return;
        }

        $pagina   = max(1, sanitizarInt($_GET['p'] ?? 1));
        $prompts  = $this->promptModelo->obtenerPromptsPorUsuario($id, $pagina);
        $favoritos = [];
        if ($usuarioActual && $usuarioActual['id'] === $id) {
            $favoritos = $this->promptModelo->obtenerFavoritos($id);
        }

        $estaSiguiendo = $usuarioActual ?
            $this->usuarioModelo->estaSiguiendo($usuarioActual['id'], $id) : false;

        $this->vista('perfil/index', [
            'titulo'         => '@' . $perfil->nombre . ' — PromptVault',
            'perfil'         => $perfil,
            'prompts'        => $prompts,
            'favoritos'      => $favoritos,
            'esta_siguiendo' => $estaSiguiendo,
            'es_propio'      => $usuarioActual && $usuarioActual['id'] === $id,
            'usuario'        => $usuarioActual,
            'pagina'         => $pagina,
        ]);
    }

    // POST /perfil/actualizar
    public function actualizar() {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);

        $usuario = usuarioActual();
        $bio     = sanitizarTexto($_POST['bio'] ?? '');
        $ciudad  = sanitizarTexto($_POST['ciudad'] ?? '');
        $web     = sanitizarTexto($_POST['sitio_web'] ?? '');

        if (strlen($bio) > 300) $bio = substr($bio, 0, 300);

        $ok = $this->usuarioModelo->actualizarPerfil($usuario['id'], [
            'bio'    => $bio,
            'ciudad' => $ciudad,
            'web'    => $web,
        ]);

        jsonResponse($ok ? ['ok' => true, 'mensaje' => 'Perfil actualizado'] : ['error' => 'Error al actualizar']);
    }

    // POST /perfil/cambiarpass
    public function cambiarpass() {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);
        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);

        $usuario    = usuarioActual();
        $passActual = $_POST['pass_actual'] ?? '';
        $nueva      = $_POST['nueva_pass'] ?? '';
        $confirmar  = $_POST['confirmar_pass'] ?? '';

        if (strlen($nueva) < 8) jsonResponse(['error' => 'La nueva contraseña debe tener al menos 8 caracteres']);
        if ($nueva !== $confirmar) jsonResponse(['error' => 'Las contraseñas no coinciden']);
        if (!$this->usuarioModelo->verificarPasswordActual($usuario['id'], $passActual)) {
            jsonResponse(['error' => 'Contraseña actual incorrecta']);
        }

        $ok = $this->usuarioModelo->cambiarPassword($usuario['id'], $nueva);
        jsonResponse($ok ? ['ok' => true, 'mensaje' => 'Contraseña actualizada'] : ['error' => 'Error al actualizar']);
    }

    // GET /perfil/mis_prompts  →  JSON (para SPA perfil)
    public function mis_prompts() {
        requireLogin();
        $usuario = usuarioActual();
        $pagina  = max(1, sanitizarInt($_GET['p'] ?? 1));
        $prompts = $this->promptModelo->obtenerPromptsPorUsuario($usuario['id'], $pagina);
        jsonResponse(['prompts' => $prompts]);
    }

    // GET /perfil/mis_favoritos  →  JSON
    public function mis_favoritos() {
        requireLogin();
        $usuario   = usuarioActual();
        $favoritos = $this->promptModelo->obtenerFavoritos($usuario['id']);
        jsonResponse(['favoritos' => $favoritos]);
    }

    // GET /perfil/notificaciones  →  JSON (notifs reales desde BD)
    public function notificaciones() {
        requireLogin();
        $usuario = usuarioActual();
        $notifs  = $this->usuarioModelo->obtenerNotificaciones($usuario['id'], 20);
        jsonResponse(['notificaciones' => $notifs]);
    }

    // POST /perfil/seguir/:id
    public function seguir($id = null) {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

        $id = sanitizarUUID($id);
        if (!$id) jsonResponse(['error' => 'ID inválido'], 400);

        $usuario = usuarioActual();
        if ($usuario['id'] === $id) jsonResponse(['error' => 'No puedes seguirte a ti mismo']);

        $resultado = $this->usuarioModelo->toggleSeguir($usuario['id'], $id);
        $seguidores = $this->usuarioModelo->contarSeguidores($id);

        jsonResponse([
            'ok'          => true,
            'siguiendo'   => $resultado,
            'seguidores'  => $seguidores,
            'mensaje'     => $resultado ? '✓ Siguiendo' : 'Dejado de seguir',
        ]);
    }
}
// La clase cierra un poco más abajo - este append no funciona bien
