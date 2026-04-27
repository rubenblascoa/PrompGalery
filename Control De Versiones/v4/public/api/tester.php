<?php
/**
 * Proxy seguro para Anthropic API
 * Evita exponer la API key en el frontend
 */

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Verificar sesión
require_once dirname(dirname(__DIR__)) . '/App/config/configurar.php';
require_once dirname(dirname(__DIR__)) . '/App/helpers/funciones.php';

iniciarSesionSegura();

// Verificar CSRF
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Request inválido']);
    exit;
}

$csrfToken = $input['csrf_token'] ?? '';
if (!verificarCSRF($csrfToken)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Token CSRF inválido']);
    exit;
}

// Rate limit del tester: 20 ejecuciones por hora
if (!checkRateLimit('tester', 20, 3600)) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Límite del tester alcanzado. Espera un momento.']);
    exit;
}

// Validar mensajes
$messages = $input['messages'] ?? [];
$system   = $input['system'] ?? '';
$model    = 'claude-sonnet-4-20250514'; // Siempre forzar el mismo modelo

if (empty($messages) || !is_array($messages)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Mensajes requeridos']);
    exit;
}

// Limitar: máximo 10 mensajes en historial y 2000 chars por mensaje
$messages = array_slice($messages, -10);
foreach ($messages as &$msg) {
    if (isset($msg['content']) && strlen($msg['content']) > 3000) {
        $msg['content'] = substr($msg['content'], 0, 3000);
    }
}

$apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
if (!$apiKey) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Tester no configurado. Añade ANTHROPIC_API_KEY en configurar.php']);
    exit;
}

$body = [
    'model'      => $model,
    'max_tokens' => 1024,
    'messages'   => $messages,
];
if ($system) {
    $body['system'] = substr($system, 0, 1000);
}

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($body),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

header('Content-Type: application/json; charset=utf-8');
http_response_code($httpCode);

if ($error) {
    echo json_encode(['error' => 'Error de conexión con la API: ' . $error]);
} else {
    echo $response;
}
