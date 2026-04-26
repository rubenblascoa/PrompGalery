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
define('VERSION', '2.1.0');

// Base de datos
define('DB_HOST', 'localhost');
define('DB_USUARIO', 'root');
define('DB_PASSWORD', '');
define('DB_NOMBRE', 'promptvault');

// Sesión segura
define('SESSION_NAME', 'pv_session');
define('SESSION_LIFETIME', 7200);       // 2 horas de inactividad
define('SESSION_ABSOLUTE', 86400);      // 24 h máximo absoluto

// Paginación
define('TAM_PAGINA', 10);

// API Anthropic (para el Tester integrado)
// ¡NUNCA expongas esta clave en el frontend!
// define('ANTHROPIC_API_KEY', 'sk-ant-api03-...');

// Seguridad — login
define('CSRF_TOKEN_NAME', 'pv_csrf');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);      // 15 minutos en segundos

// Rate limiting aplicación
define('MAX_PROMPTS_PER_HOUR', 10);
define('MAX_COMMENTS_PER_HOUR', 30);

// Contenido
define('MAX_PROMPT_LENGTH', 10000);
define('MAX_TITLE_LENGTH', 200);
define('MAX_COMMENT_LENGTH', 1000);
define('MIN_PROMPT_LENGTH', 20);

// Avatar upload
define('AVATAR_DIR',     RUTA_APP . '/public/uploads/avatars/');
define('AVATAR_URL',     RUTA_URL . 'public/uploads/avatars/');
define('AVATAR_MAX_BYTES', 2 * 1024 * 1024);   // 2 MB
define('AVATAR_MAX_DIM',   800);                 // px — resize si es mayor
define('AVATAR_OUT_SIZE',  160);                 // px — tamaño final cuadrado
