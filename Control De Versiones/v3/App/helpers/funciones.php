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

    // Timeout absoluto: matar la sesión si superó SESSION_ABSOLUTE
    if (isset($_SESSION['_created']) && (time() - $_SESSION['_created']) > SESSION_ABSOLUTE) {
        session_unset();
        session_destroy();
        session_start();
    }
    if (!isset($_SESSION['_created'])) {
        $_SESSION['_created'] = time();
    }

    // Regenerar ID periódicamente (cada 15 min) para prevenir fixation
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
    // Rotar el token tras cada uso
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
    // Solo http(s)
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
    // Confiamos solo en REMOTE_ADDR a menos que el servidor esté detrás de proxy de confianza
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
