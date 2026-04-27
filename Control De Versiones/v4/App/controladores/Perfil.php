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

        $id     = sanitizarUUID($id);
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
        $web     = sanitizarUrl($_POST['sitio_web'] ?? '');

        if (strlen($bio)    > 300)  $bio    = substr($bio, 0, 300);
        if (strlen($ciudad) > 100)  $ciudad = substr($ciudad, 0, 100);

        $ok = $this->usuarioModelo->actualizarPerfil($usuario['id'], [
            'bio'    => $bio,
            'ciudad' => $ciudad,
            'web'    => $web,
        ]);

        jsonResponse($ok
            ? ['ok' => true, 'mensaje' => 'Perfil actualizado']
            : ['error' => 'Error al actualizar']);
    }

    // POST /perfil/subiravatar
    public function subiravatar() {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);

        // CSRF via header (multipart no lleva body JSON)
        $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verificarCSRF($csrfHeader)) jsonResponse(['error' => 'Token CSRF inválido'], 403);

        if (!checkRateLimit('subir_avatar', 5, 3600)) {
            jsonResponse(['error' => 'Demasiadas subidas. Espera un momento.'], 429);
        }

        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errCodes = [
                UPLOAD_ERR_INI_SIZE  => 'El archivo supera el límite del servidor.',
                UPLOAD_ERR_FORM_SIZE => 'El archivo supera el límite permitido.',
                UPLOAD_ERR_NO_FILE   => 'No se envió ningún archivo.',
            ];
            $code = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
            jsonResponse(['error' => $errCodes[$code] ?? 'Error al subir el archivo.']);
        }

        $file = $_FILES['avatar'];

        // Validar tamaño
        if ($file['size'] > AVATAR_MAX_BYTES) {
            jsonResponse(['error' => 'El archivo pesa más de 2 MB.']);
        }

        // Validar tipo MIME real (no confiar en Content-Type del cliente)
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($file['tmp_name']);
        $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mimeReal, $allowed, true)) {
            jsonResponse(['error' => 'Solo se permiten imágenes JPEG, PNG, WebP o GIF.']);
        }

        // Validar que GD puede abrirla (extra check)
        $imgInfo = @getimagesize($file['tmp_name']);
        if (!$imgInfo) jsonResponse(['error' => 'El archivo no es una imagen válida.']);

        // Asegurarse de que el directorio existe
        if (!is_dir(AVATAR_DIR)) {
            mkdir(AVATAR_DIR, 0755, true);
        }

        $usuario = usuarioActual();
        // Nombre de archivo: hash del ID + timestamp (no expone el ID de usuario)
        $nombreArchivo = hash('sha256', $usuario['id'] . time()) . '.webp';
        $rutaDisco     = AVATAR_DIR . $nombreArchivo;

        // Redimensionar y guardar como WebP con GD
        if (!$this->procesarImagenAvatar($file['tmp_name'], $mimeReal, $rutaDisco)) {
            jsonResponse(['error' => 'No se pudo procesar la imagen. Prueba con otro archivo.']);
        }

        // Eliminar avatar anterior si es local (no el de ui-avatars)
        $avatarAnterior = $_SESSION['avatar'] ?? '';
        if ($avatarAnterior && strpos($avatarAnterior, 'ui-avatars.com') === false) {
            $rutaAnterior = AVATAR_DIR . basename($avatarAnterior);
            if (file_exists($rutaAnterior) && is_file($rutaAnterior)) {
                @unlink($rutaAnterior);
            }
        }

        $urlPublica = AVATAR_URL . $nombreArchivo;
        $ok = $this->usuarioModelo->actualizarAvatar($usuario['id'], $urlPublica);

        if ($ok) {
            // Actualizar sesión
            $_SESSION['avatar'] = $urlPublica;
            jsonResponse(['ok' => true, 'avatar_url' => $urlPublica]);
        } else {
            @unlink($rutaDisco);
            jsonResponse(['error' => 'Error al guardar el avatar.'], 500);
        }
    }

    /**
     * Redimensiona y convierte a WebP (160×160). Devuelve true/false.
     */
    private function procesarImagenAvatar($tmpPath, $mime, $destino) {
        if (!extension_loaded('gd')) {
            // Si GD no está disponible, mover tal cual (fallback)
            return move_uploaded_file($tmpPath, $destino);
        }

        $src = match($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmpPath),
            'image/png'  => @imagecreatefrompng($tmpPath),
            'image/webp' => @imagecreatefromwebp($tmpPath),
            'image/gif'  => @imagecreatefromgif($tmpPath),
            default      => false,
        };
        if (!$src) return false;

        $w = imagesx($src);
        $h = imagesy($src);

        // Recortar al cuadrado centrado
        $minDim  = min($w, $h);
        $srcX    = intval(($w - $minDim) / 2);
        $srcY    = intval(($h - $minDim) / 2);
        $outSize = AVATAR_OUT_SIZE;

        $dst = imagecreatetruecolor($outSize, $outSize);

        // Fondo blanco para transparencias
        $bg = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $bg);

        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $outSize, $outSize, $minDim, $minDim);
        imagedestroy($src);

        $result = imagewebp($dst, $destino, 85);
        imagedestroy($dst);
        return $result;
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

        if (strlen($nueva) < 8)       jsonResponse(['error' => 'La nueva contraseña debe tener al menos 8 caracteres']);
        if ($nueva !== $confirmar)    jsonResponse(['error' => 'Las contraseñas no coinciden']);
        if (!$this->usuarioModelo->verificarPasswordActual($usuario['id'], $passActual)) {
            jsonResponse(['error' => 'Contraseña actual incorrecta']);
        }

        $ok = $this->usuarioModelo->cambiarPassword($usuario['id'], $nueva);
        jsonResponse($ok
            ? ['ok' => true, 'mensaje' => 'Contraseña actualizada']
            : ['error' => 'Error al actualizar']);
    }

    // GET /perfil/stats  →  JSON (estadísticas del usuario logueado)
    public function stats() {
        requireLogin();
        $usuario = usuarioActual();
        $stats   = $this->usuarioModelo->obtenerEstadisticasPropias($usuario['id']);
        jsonResponse(['stats' => $stats]);
    }

    // GET /perfil/mis_prompts  →  JSON
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

    // GET /perfil/notificaciones  →  JSON
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

        $resultado  = $this->usuarioModelo->toggleSeguir($usuario['id'], $id);
        $seguidores = $this->usuarioModelo->contarSeguidores($id);

        jsonResponse([
            'ok'         => true,
            'siguiendo'  => $resultado,
            'seguidores' => $seguidores,
            'mensaje'    => $resultado ? '✓ Siguiendo' : 'Dejado de seguir',
        ]);
    }

    // GET /perfil/notif_count  →  JSON
    public function notif_count() {
        if (!estaLogueado()) jsonResponse(['count' => 0]);
        $usuario = usuarioActual();
        $count   = $this->usuarioModelo->contarNotifNuevas($usuario['id']);
        jsonResponse(['count' => $count]);
    }

    // POST /perfil/marcar_notif_leidas
    public function marcar_notif_leidas() {
        requireLogin();
        $usuario = usuarioActual();
        $this->usuarioModelo->marcarNotifLeidas($usuario['id']);
        jsonResponse(['ok' => true]);
    }

    // GET /perfil/actividad  →  JSON
    public function actividad() {
        requireLogin();
        $usuario   = usuarioActual();
        $actividad = $this->usuarioModelo->obtenerActividad($usuario['id'], 30);
        jsonResponse(['actividad' => $actividad]);
    }
}
