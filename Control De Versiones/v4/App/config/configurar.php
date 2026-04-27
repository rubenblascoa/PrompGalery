<?php
// ═══════════════════════════════════════════
// ENTORNO: cambiar a false en producción
// ═══════════════════════════════════════════
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(dirname(__FILE__)) . '/logs/errores.log');
}

// Rutas
define('RUTA_APP', dirname(dirname(__FILE__)));
define('RUTA_URL', '/');
define('RUTA_PUBLIC', RUTA_URL . 'public/');

// Sitio
define('NOMBRESITIO', 'PromptVault');
define('VERSION',     '4.0.0');
define('SITE_URL',    'https://tudominio.com');   // ← ajusta en producción

// Base de datos
define('DB_HOST',     'localhost');
define('DB_USUARIO',  'root');
define('DB_PASSWORD', '');
define('DB_NOMBRE',   'promptvault');

// Sesión segura
define('SESSION_NAME',     'pv_session');
define('SESSION_LIFETIME', 7200);    // 2 horas inactividad
define('SESSION_ABSOLUTE', 86400);   // 24 h máximo absoluto

// Paginación
define('TAM_PAGINA', 10);

// Seguridad — login
define('CSRF_TOKEN_NAME',  'pv_csrf');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);   // 15 min

// Rate limiting
define('MAX_PROMPTS_PER_HOUR',   10);
define('MAX_COMMENTS_PER_HOUR',  30);

// Contenido
define('MAX_PROMPT_LENGTH',  10000);
define('MAX_TITLE_LENGTH',     200);
define('MAX_COMMENT_LENGTH',  1000);
define('MIN_PROMPT_LENGTH',     20);

// Avatar upload
define('AVATAR_DIR',      RUTA_APP . '/public/uploads/avatars/');
define('AVATAR_URL',      RUTA_URL . 'public/uploads/avatars/');
define('AVATAR_MAX_BYTES', 2 * 1024 * 1024);
define('AVATAR_MAX_DIM',   800);
define('AVATAR_OUT_SIZE',  160);

// ═══════════════════════════════════════════
// EMAIL (verificación + recuperación de contraseña)
// Usa PHP mail() por defecto.
// Para SMTP instala PHPMailer y ajusta estas constantes.
// ═══════════════════════════════════════════
define('MAIL_FROM',      'noreply@tudominio.com');
define('MAIL_FROM_NAME', 'PromptVault');
define('MAIL_ENABLED',   true);   // false = desactiva envíos (útil en dev)
define('TOKEN_VERIFICAR_HORAS',  24);  // validez token email
define('TOKEN_RECUPERAR_MINUTOS', 60); // validez token contraseña
