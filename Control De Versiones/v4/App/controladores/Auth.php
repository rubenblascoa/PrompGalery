<?php
class Auth extends Controlador {
    private $usuarioModelo;

    public function __construct() {
        $this->usuarioModelo = $this->modelo('UsuarioModelo');
    }

    // POST /auth/login
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireccionar('/');
        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);

        $ip    = obtenerIP();
        $email = sanitizarEmail($_POST['email'] ?? '');

        $bloqueo = $this->usuarioModelo->obtenerBloqueoLogin($ip, $email);
        if ($bloqueo) {
            $resta = ceil(($bloqueo - time()) / 60);
            logApp("Login bloqueado ip=$ip", 'auth', 'WARN');
            jsonResponse(['error' => "Demasiados intentos. Vuelve en {$resta} minuto(s)."], 429);
        }

        $password = $_POST['password'] ?? '';
        if (!$email || !$password) jsonResponse(['error' => 'Rellena todos los campos']);
        if (!validarEmail($email))  jsonResponse(['error' => 'Email inválido']);

        $usuario = $this->usuarioModelo->login($email, $password);
        if ($usuario) {
            $this->usuarioModelo->resetearIntentosLogin($ip, $email);
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario->id;
            $_SESSION['nombre']     = $usuario->nombre;
            $_SESSION['avatar']     = $usuario->avatar;
            $_SESSION['verificado'] = $usuario->verificado;
            $_SESSION['login']      = true;
            $_SESSION['_created']   = time();
            logApp("Login OK usuario={$usuario->id}", 'auth', 'INFO');
            jsonResponse(['ok' => true, 'mensaje' => '¡Bienvenido de nuevo, ' . $usuario->nombre . '!',
                'usuario' => ['nombre' => $usuario->nombre, 'avatar' => $usuario->avatar, 'verificado' => $usuario->verificado]]);
        } else {
            $bloqueadoAhora = $this->usuarioModelo->registrarIntentoLogin($ip, $email);
            logApp("Login fallido ip=$ip bloqueado=" . ($bloqueadoAhora ? 'si' : 'no'), 'auth', 'WARN');
            $msg = $bloqueadoAhora ? 'Cuenta bloqueada por ' . (LOGIN_LOCKOUT_TIME / 60) . ' minutos.' : 'Email o contraseña incorrectos.';
            jsonResponse(['error' => $msg]);
        }
    }

    // POST /auth/registro
    public function registro() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireccionar('/');
        if (!verificarCSRF($_POST['csrf_token'] ?? '')) jsonResponse(['error' => 'Token inválido'], 403);
        if (!checkRateLimit('registro', 3, 3600)) jsonResponse(['error' => 'Demasiados registros desde esta IP.'], 429);

        $nombre   = sanitizarTexto($_POST['nombre']   ?? '');
        $email    = sanitizarEmail($_POST['email']    ?? '');
        $password = $_POST['password']         ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        $errores = [];
        if (strlen($nombre) < 3 || strlen($nombre) > 30)  $errores[] = 'El nombre debe tener entre 3 y 30 caracteres';
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $nombre))    $errores[] = 'El nombre solo puede tener letras, números y guiones bajos';
        if (!validarEmail($email))                         $errores[] = 'Email inválido';
        if (strlen($password) < 8)                         $errores[] = 'La contraseña debe tener al menos 8 caracteres';
        if ($password !== $confirm)                        $errores[] = 'Las contraseñas no coinciden';
        if (!empty($errores)) jsonResponse(['error' => implode('. ', $errores)]);

        if ($this->usuarioModelo->existeEmail($email))   jsonResponse(['error' => 'Este email ya está registrado']);
        if ($this->usuarioModelo->existeNombre($nombre)) jsonResponse(['error' => 'Este nombre de usuario ya está en uso']);

        $id = $this->usuarioModelo->registrar(['nombre' => $nombre, 'email' => $email, 'password' => $password]);
        if ($id) {
            $token = $this->usuarioModelo->crearToken($id, 'verificar_email');
            if ($token) { [$asunto, $html] = emailVerificacion($nombre, $token); enviarEmail($email, $asunto, $html); }
            logApp("Registro usuario=$id", 'auth', 'INFO');
            jsonResponse(['ok' => true, 'mensaje' => '¡Cuenta creada! Revisa tu email para verificarla.']);
        } else {
            jsonResponse(['error' => 'Error al crear la cuenta.'], 500);
        }
    }

    // GET /auth/verificar/:token
    public function verificar($token = null) {
        if (!$token) redireccionar('/');
        $token = preg_replace('/[^a-fA-F0-9]/', '', $token);
        $datos = $this->usuarioModelo->validarToken($token, 'verificar_email');
        if (!$datos) {
            $this->vista('recuperar/mensaje', ['titulo' => 'Enlace inválido', 'tipo' => 'error',
                'mensaje' => 'El enlace de verificación no es válido o ha caducado.', 'usuario' => usuarioActual()]);
            return;
        }
        $this->usuarioModelo->verificarEmail($datos->usuario_id);
        $this->usuarioModelo->consumirToken($token);
        if (estaLogueado() && $_SESSION['usuario_id'] === $datos->usuario_id) $_SESSION['verificado'] = 1;
        logApp("Email verificado usuario={$datos->usuario_id}", 'auth', 'INFO');
        $this->vista('recuperar/mensaje', ['titulo' => '¡Email verificado!', 'tipo' => 'exito',
            'mensaje' => '¡Tu cuenta ha sido verificada! Ya puedes usar todas las funciones.', 'usuario' => usuarioActual()]);
    }

    // POST /auth/reenviar_verificacion
    public function reenviar_verificacion() {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método no permitido'], 405);
        $usuario = usuarioActual();
        if ($_SESSION['verificado'] ?? 0) jsonResponse(['error' => 'Tu email ya está verificado']);
        if (!checkRateLimit('reenviar_verif', 2, 3600)) jsonResponse(['error' => 'Espera antes de solicitar otro correo'], 429);
        $d = $this->usuarioModelo->obtenerPorId($usuario['id']);
        $tok = $this->usuarioModelo->crearToken($usuario['id'], 'verificar_email');
        if ($tok && $d) { [$a, $h] = emailVerificacion($d->nombre, $tok); enviarEmail($d->email, $a, $h); }
        jsonResponse(['ok' => true, 'mensaje' => 'Email de verificación reenviado.']);
    }

    // GET /auth/logout
    public function logout() {
        logApp("Logout usuario=" . ($_SESSION['usuario_id'] ?? '-'), 'auth', 'INFO');
        iniciarSesionSegura();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        redireccionar('/');
    }
}
