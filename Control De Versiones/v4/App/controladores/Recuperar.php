<?php
// Controlador para recuperación de contraseña (¿Olvidaste tu contraseña?)
class Recuperar extends Controlador {
    private $usuarioModelo;

    public function __construct() {
        $this->usuarioModelo = $this->modelo('UsuarioModelo');
    }

    // GET /recuperar  —  muestra formulario de solicitud
    public function index() {
        if (estaLogueado()) redireccionar('/');
        $this->vista('recuperar/solicitar', ['titulo' => 'Recuperar contraseña', 'usuario' => null]);
    }

    // POST /recuperar/enviar  —  genera token y envía email
    public function enviar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireccionar('/recuperar');
        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);
        if (!checkRateLimit('recuperar_pass', 3, 3600))
            jsonResponse(['error' => 'Demasiadas solicitudes. Espera un rato.'], 429);

        $email = sanitizarEmail($_POST['email'] ?? '');
        if (!validarEmail($email)) jsonResponse(['error' => 'Email inválido']);

        $usuario = $this->usuarioModelo->obtenerPorEmail($email);
        // Respuesta genérica siempre (no revelar si el email existe)
        if ($usuario) {
            $token = $this->usuarioModelo->crearToken($usuario->id, 'recuperar_pass');
            if ($token) {
                [$asunto, $html] = emailRecuperarPass($usuario->nombre, $token);
                enviarEmail($email, $asunto, $html);
                logApp("Token recuperar_pass generado usuario={$usuario->id}", 'auth', 'INFO');
            }
        } else {
            logApp("Recuperar pass: email no encontrado=$email", 'auth', 'WARN');
        }

        jsonResponse(['ok' => true, 'mensaje' => 'Si ese email está registrado, recibirás las instrucciones en breve.']);
    }

    // GET /recuperar/reset/:token  —  muestra formulario nueva contraseña
    public function reset($token = null) {
        if (!$token) redireccionar('/recuperar');
        $token = preg_replace('/[^a-fA-F0-9]/', '', $token);
        $datos = $this->usuarioModelo->validarToken($token, 'recuperar_pass');
        if (!$datos) {
            $this->vista('recuperar/mensaje', ['titulo' => 'Enlace inválido', 'tipo' => 'error',
                'mensaje' => 'Este enlace de recuperación no es válido o ha caducado. Solicita uno nuevo.', 'usuario' => null]);
            return;
        }
        $this->vista('recuperar/reset', ['titulo' => 'Nueva contraseña', 'token' => $token, 'usuario' => null]);
    }

    // POST /recuperar/nueva/:token  —  guarda nueva contraseña
    public function nueva($token = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireccionar('/recuperar');
        if (!$token) redireccionar('/recuperar');
        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);

        $token = preg_replace('/[^a-fA-F0-9]/', '', $token);
        $datos = $this->usuarioModelo->validarToken($token, 'recuperar_pass');
        if (!$datos) jsonResponse(['error' => 'Enlace expirado. Solicita uno nuevo.'], 400);

        $nueva    = $_POST['nueva_pass']     ?? '';
        $confirmar = $_POST['confirmar_pass'] ?? '';

        if (strlen($nueva) < 8)     jsonResponse(['error' => 'La contraseña debe tener al menos 8 caracteres']);
        if ($nueva !== $confirmar)  jsonResponse(['error' => 'Las contraseñas no coinciden']);

        $this->usuarioModelo->cambiarPassword($datos->usuario_id, $nueva);
        $this->usuarioModelo->consumirToken($token);
        logApp("Contraseña restablecida usuario={$datos->usuario_id}", 'auth', 'INFO');

        jsonResponse(['ok' => true, 'mensaje' => '¡Contraseña actualizada! Ya puedes iniciar sesión.']);
    }
}
