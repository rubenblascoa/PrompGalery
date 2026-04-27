<?php

// ═══════════════════════════════════════════
// SESIÓN SEGURA (con timeout absoluto)
// ═══════════════════════════════════════════
function iniciarSesionSegura() {
    if (session_status() !== PHP_SESSION_NONE) return;

    session_name(SESSION_NAME);
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (isset($_SESSION['_created']) && (time() - $_SESSION['_created']) > SESSION_ABSOLUTE) {
        session_unset();
        session_destroy();
        session_start();
    }
    if (!isset($_SESSION['_created'])) {
        $_SESSION['_created'] = time();
    }

    if (!isset($_SESSION['_last_regen']) || (time() - $_SESSION['_last_regen']) > 900) {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }
}

function estaLogueado() {
    iniciarSesionSegura();
    return isset($_SESSION['usuario_id']) && $_SESSION['login'] === true;
}

function usuarioActual() {
    iniciarSesionSegura();
    if (!estaLogueado()) return null;
    return [
        'id'         => $_SESSION['usuario_id'],
        'nombre'     => $_SESSION['nombre'],
        'avatar'     => $_SESSION['avatar'] ?? null,
        'verificado' => $_SESSION['verificado'] ?? 0,
    ];
}

function requireLogin() {
    if (!estaLogueado()) {
        if (isAjax()) jsonResponse(['error' => 'No autorizado', 'redirect' => '/'], 401);
        redireccionar('/');
    }
}

// ═══════════════════════════════════════════
// CSRF
// ═══════════════════════════════════════════
function generarCSRF() {
    iniciarSesionSegura();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verificarCSRF($token) {
    iniciarSesionSegura();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) return false;
    $valid = hash_equals($_SESSION[CSRF_TOKEN_NAME], $token ?? '');
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    return $valid;
}

function inputCSRF() {
    return '<input type="hidden" name="csrf_token" value="' . generarCSRF() . '">';
}

// ═══════════════════════════════════════════
// SANITIZACIÓN Y VALIDACIÓN
// ═══════════════════════════════════════════
function sanitizarTexto($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

function sanitizarEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sanitizarInt($val) {
    return filter_var($val, FILTER_VALIDATE_INT) !== false ? (int)$val : null;
}

function sanitizarUUID($id) {
    if (preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $id)) return $id;
    return null;
}

function sanitizarUrl($url) {
    $url = filter_var(trim($url), FILTER_SANITIZE_URL);
    if (!$url) return '';
    if (!preg_match('/^https?:\/\//i', $url)) return '';
    return $url;
}

// ═══════════════════════════════════════════
// RATE LIMITING en sesión (por acción genérica)
// ═══════════════════════════════════════════
function checkRateLimit($action, $max, $window = 3600) {
    $ip  = obtenerIP();
    $uid = estaLogueado() ? ($_SESSION['usuario_id'] ?? 'anon') : 'anon';
    $key = 'rl_' . $action . '_' . md5($ip . $uid);

    iniciarSesionSegura();
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset' => time() + $window];
    }
    if (time() > $_SESSION[$key]['reset']) {
        $_SESSION[$key] = ['count' => 0, 'reset' => time() + $window];
    }
    $_SESSION[$key]['count']++;
    return $_SESSION[$key]['count'] <= $max;
}

// ═══════════════════════════════════════════
// OBTENER IP (con proxy inverso)
// ═══════════════════════════════════════════
function obtenerIP() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ═══════════════════════════════════════════
// HELPERS HTTP
// ═══════════════════════════════════════════
function redireccionar($pagina) {
    header('Location: ' . RUTA_URL . ltrim($pagina, '/'));
    exit;
}

function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ═══════════════════════════════════════════
// LOGS POR CATEGORÍA
// ═══════════════════════════════════════════
function logApp($mensaje, $categoria = 'general', $nivel = 'INFO') {
    $logDir  = RUTA_APP . '/logs/';
    $archivos = [
        'auth'     => 'auth.log',
        'seguridad'=> 'seguridad.log',
        'errores'  => 'errores.log',
        'mail'     => 'mail.log',
        'general'  => 'app.log',
    ];
    $archivo = $logDir . ($archivos[$categoria] ?? 'app.log');
    $ip      = obtenerIP();
    $uid     = $_SESSION['usuario_id'] ?? '-';
    $linea   = sprintf("[%s] [%s] [%s] [ip:%s] [uid:%s] %s\n",
        date('Y-m-d H:i:s'), strtoupper($nivel), strtoupper($categoria), $ip, $uid, $mensaje);
    @file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX);
}

// ═══════════════════════════════════════════
// ENVÍO DE EMAIL
// Usa PHP mail() nativo. Para SMTP, sustituye
// el cuerpo de esta función por PHPMailer.
// ═══════════════════════════════════════════
function enviarEmail($destinatario, $asunto, $cuerpoHtml, $cuerpoTexto = '') {
    if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) {
        logApp("Email simulado a $destinatario — Asunto: $asunto", 'mail', 'DEBUG');
        return true;
    }

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $resultado = @mail($destinatario, '=?UTF-8?B?' . base64_encode($asunto) . '?=', $cuerpoHtml, $headers);

    if ($resultado) {
        logApp("Email enviado a $destinatario — Asunto: $asunto", 'mail', 'INFO');
    } else {
        logApp("Fallo al enviar email a $destinatario — Asunto: $asunto", 'mail', 'ERROR');
    }
    return $resultado;
}

// ═══════════════════════════════════════════
// PLANTILLAS DE EMAIL
// ═══════════════════════════════════════════
function emailVerificacion($nombre, $token) {
    $url  = rtrim(SITE_URL, '/') . '/auth/verificar/' . $token;
    $asunto = 'Verifica tu cuenta en PromptVault';
    $html = emailLayout('Verifica tu email', "
        <p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
        <p>Gracias por registrarte en PromptVault. Para activar tu cuenta haz clic en el botón:</p>
        <p style='text-align:center;margin:32px 0'>
            <a href='$url' style='background:#7C6FFF;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block'>
                ✓ Verificar mi email
            </a>
        </p>
        <p style='color:#666;font-size:13px'>Este enlace caduca en " . TOKEN_VERIFICAR_HORAS . " horas.<br>
        Si no te has registrado, ignora este mensaje.</p>
        <p style='color:#aaa;font-size:12px;word-break:break-all'>O copia esta URL: $url</p>
    ");
    return [$asunto, $html];
}

function emailRecuperarPass($nombre, $token) {
    $url  = rtrim(SITE_URL, '/') . '/recuperar/reset/' . $token;
    $asunto = 'Recupera tu contraseña en PromptVault';
    $html = emailLayout('Recuperación de contraseña', "
        <p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
        <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.</p>
        <p style='text-align:center;margin:32px 0'>
            <a href='$url' style='background:#7C6FFF;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block'>
                🔑 Restablecer contraseña
            </a>
        </p>
        <p style='color:#666;font-size:13px'>Este enlace caduca en " . TOKEN_RECUPERAR_MINUTOS . " minutos.<br>
        Si no solicitaste esto, ignora este mensaje — tu contraseña no cambiará.</p>
        <p style='color:#aaa;font-size:12px;word-break:break-all'>O copia esta URL: $url</p>
    ");
    return [$asunto, $html];
}

function emailLayout($titulo, $contenido) {
    return "<!DOCTYPE html><html><body style='margin:0;padding:0;background:#0f0f1a;font-family:system-ui,sans-serif'>
<table width='100%' cellpadding='0' cellspacing='0'><tr><td align='center' style='padding:40px 16px'>
<table width='520' cellpadding='0' cellspacing='0' style='background:#1a1a2e;border-radius:16px;overflow:hidden'>
<tr><td style='background:#7C6FFF;padding:28px 32px;text-align:center'>
    <h1 style='margin:0;color:#fff;font-size:22px'>⚡ PromptVault</h1>
</td></tr>
<tr><td style='padding:32px;color:#e0e0e0;font-size:15px;line-height:1.7'>
    <h2 style='margin:0 0 20px;color:#fff;font-size:18px'>$titulo</h2>
    $contenido
</td></tr>
<tr><td style='padding:16px 32px;border-top:1px solid #2a2a4e;text-align:center;color:#666;font-size:12px'>
    © " . date('Y') . " PromptVault. La comunidad de prompts de IA.
</td></tr>
</table>
</td></tr></table></body></html>";
}

// ═══════════════════════════════════════════
// UTILIDADES
// ═══════════════════════════════════════════
function timeAgo($fecha) {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'ahora mismo';
    if ($diff < 3600)   return floor($diff / 60) . ' min';
    if ($diff < 86400)  return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'd';
    return date('d M Y', strtotime($fecha));
}

function formatNumber($n) {
    if ($n >= 1000000) return round($n / 1000000, 1) . 'M';
    if ($n >= 1000)    return round($n / 1000, 1) . 'k';
    return $n;
}

function truncar($str, $len = 200) {
    $str = strip_tags($str);
    if (strlen($str) <= $len) return $str;
    return substr($str, 0, $len) . '...';
}

function categoriaColor($cat) {
    $map = [
        'codigo'       => 'cat-code',
        'escritura'    => 'cat-writing',
        'analisis'     => 'cat-analysis',
        'imagen'       => 'cat-image',
        'chatbot'      => 'cat-chat',
        'razonamiento' => 'cat-reason',
    ];
    return $map[strtolower($cat)] ?? 'cat-code';
}

function categoriaEmoji($cat) {
    $map = [
        'codigo'       => '💻',
        'escritura'    => '✍️',
        'analisis'     => '📊',
        'imagen'       => '🎨',
        'chatbot'      => '💬',
        'razonamiento' => '🧠',
    ];
    return $map[strtolower($cat)] ?? '⚡';
}

function generarAvatar($nombre) {
    $iniciales = '';
    $partes = explode(' ', $nombre);
    foreach (array_slice($partes, 0, 2) as $p) {
        $iniciales .= strtoupper(substr($p, 0, 1));
    }
    $colores = ['7C6FFF', '5CE1E6', '3DDC84', 'FFB547', 'FF6B6B', 'FF79C6'];
    $color   = $colores[abs(crc32($nombre)) % count($colores)];
    return 'https://ui-avatars.com/api/?name=' . urlencode($iniciales)
         . '&background=' . $color . '&color=fff&bold=true&size=80';
}

function slugify($str) {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return trim($str, '-');
}
