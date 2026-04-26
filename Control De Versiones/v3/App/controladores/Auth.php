<?php
class Auth extends Controlador {
    private $usuarioModelo;

    public function __construct() {
        $this->usuarioModelo = $this->modelo('UsuarioModelo');
    }

    // POST /auth/login
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireccionar('/');

        if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
            jsonResponse(['error' => 'Token inválido'], 403);
        }

        $ip    = obtenerIP();
        $email = sanitizarEmail($_POST['email'] ?? '');

        // ── Brute-force check (BD, por IP + email) ──────────────────────────
        $bloqueo = $this->usuarioModelo->obtenerBloqueoLogin($ip, $email);
        if ($bloqueo) {
            $resta = ceil(($bloqueo - time()) / 60);
            jsonResponse(['error' => "Demasiados intentos. Vuelve en {$resta} minuto(s)."], 429);
        }

        $password = $_POST['password'] ?? '';
        if (!$email || !$password) {
            jsonResponse(['error' => 'Rellena todos los campos']);
        }
        if (!validarEmail($email)) {
            jsonResponse(['error' => 'Email inválido']);
        }

        $usuario = $this->usuarioModelo->login($email, $password);

        if ($usuario) {
            // Login correcto: limpiar intentos previos
            $this->usuarioModelo->resetearIntentosLogin($ip, $email);

            // Regenerar ID de sesión (previene session fixation)
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario->id;
            $_SESSION['nombre']     = $usuario->nombre;
            $_SESSION['avatar']     = $usuario->avatar;
            $_SESSION['verificado'] = $usuario->verificado;
            $_SESSION['login']      = true;
            $_SESSION['_created']   = time();

            jsonResponse([
                'ok'      => true,
                'mensaje' => '¡Bienvenido de nuevo, ' . $usuario->nombre . '!',
                'usuario' => [
                    'nombre'     => $usuario->nombre,
                    'avatar'     => $usuario->avatar,
                    'verificado' => $usuario->verificado,
                ],
            ]);
        } else {
            // Login fallido: incrementar contador en BD
            $bloqueadoAhora = $this->usuarioModelo->registrarIntentoLogin($ip, $email);
            $msg = $bloqueadoAhora
                ? 'Cuenta bloqueada por ' . (LOGIN_LOCKOUT_TIME / 60) . ' minutos.'
                : 'Email o contraseña incorrectos.';
            jsonResponse(['error' => $msg]);
        }
    }

    // POST /auth/registro
    public function registro() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireccionar('/');

        if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
            jsonResponse(['error' => 'Token inválido'], 403);
        }

        // Rate limit: 3 registros por IP por hora
        if (!checkRateLimit('registro', 3, 3600)) {
            jsonResponse(['error' => 'Demasiados registros desde esta IP. Espera un momento.'], 429);
        }

        $nombre  = sanitizarTexto($_POST['nombre'] ?? '');
        $email   = sanitizarEmail($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        $errores = [];
        if (strlen($nombre) < 3 || strlen($nombre) > 30)
            $errores[] = 'El nombre debe tener entre 3 y 30 caracteres';
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $nombre))
            $errores[] = 'El nombre solo puede tener letras, números y guiones bajos';
        if (!validarEmail($email))
            $errores[] = 'Email inválido';
        if (strlen($password) < 8)
            $errores[] = 'La contraseña debe tener al menos 8 caracteres';
        if ($password !== $confirm)
            $errores[] = 'Las contraseñas no coinciden';

        if (!empty($errores)) jsonResponse(['error' => implode('. ', $errores)]);

        if ($this->usuarioModelo->existeEmail($email)) jsonResponse(['error' => 'Este email ya está registrado']);
        if ($this->usuarioModelo->existeNombre($nombre)) jsonResponse(['error' => 'Este nombre de usuario ya está en uso']);

        $id = $this->usuarioModelo->registrar([
            'nombre'   => $nombre,
            'email'    => $email,
            'password' => $password,
        ]);

        if ($id) {
            jsonResponse(['ok' => true, 'mensaje' => '¡Cuenta creada! Ya puedes iniciar sesión.']);
        } else {
            jsonResponse(['error' => 'Error al crear la cuenta. Inténtalo de nuevo.'], 500);
        }
    }

    // GET /auth/logout
    public function logout() {
        iniciarSesionSegura();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        redireccionar('/');
    }
}
