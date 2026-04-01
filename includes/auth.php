<?php

require_once dirname(__DIR__) . '/config.php';

// O middleware de auth prepara a sessao e concentra tudo que o sistema
// precisa para login, timeout, CSRF e respostas padronizadas.
$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
} else {
    session_set_cookie_params(0, '/; samesite=Lax', '', $secureCookie, true);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    // A deteccao de HTTPS olha tambem para cenarios com proxy ou porta 443,
    // porque nem sempre o PHP recebe esse contexto do mesmo jeito.
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    session_set_cookie_params([
        'lifetime' => defined('SESSION_COOKIE_LIFETIME') ? max(0, (int) SESSION_COOKIE_LIFETIME) : 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    // O token CSRF nasce junto da sessao e passa a ser reutilizado
    // pelos formularios e endpoints AJAX protegidos.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function auth_session_keys($envKey, array $defaults)
{
    // Permite adaptar o middleware para outras convencoes de sessao
    // sem editar todas as chamadas espalhadas pelo projeto.
    $rawValue = getenv($envKey);

    if ($rawValue === false || $rawValue === null || trim((string) $rawValue) === '') {
        return $defaults;
    }

    $items = array_filter(array_map('trim', explode(',', (string) $rawValue)));

    return !empty($items) ? array_values($items) : $defaults;
}

function auth_user()
{
    // Primeiro tentamos achar o objeto completo do usuario logado.
    foreach (auth_session_keys('AUTH_SESSION_USER_KEYS', ['auth_user', 'usuario', 'user']) as $key) {
        if (!empty($_SESSION[$key]) && is_array($_SESSION[$key])) {
            return $_SESSION[$key];
        }
    }

    return null;
}

function auth_user_id()
{
    // O ID pode estar salvo em chave simples ou dentro do objeto de usuario.
    // Esse fallback ajuda a manter compatibilidade com fluxos mais antigos.
    foreach (auth_session_keys('AUTH_SESSION_ID_KEYS', ['auth_user_id', 'usuario_id', 'user_id']) as $key) {
        if (!empty($_SESSION[$key])) {
            return $_SESSION[$key];
        }
    }

    $user = auth_user();
    if (is_array($user)) {
        foreach (['id', 'user_id', 'usuario_id'] as $field) {
            if (!empty($user[$field])) {
                return $user[$field];
            }
        }
    }

    return null;
}

function auth_user_display_name()
{
    $user = auth_user();
    if (!is_array($user)) {
        return '';
    }

    $displayName = trim((string) ($user['nome'] ?? $user['nome_exibicao'] ?? $user['usuario'] ?? ''));
    return $displayName;
}

function is_authenticated()
{
    return auth_user_id() !== null;
}

function auth_enforcement_enabled()
{
    if (defined('REQUIRE_AUTH')) {
        return (bool) REQUIRE_AUTH;
    }

    $rawValue = getenv('REQUIRE_AUTH');

    if ($rawValue === false || $rawValue === null || trim((string) $rawValue) === '') {
        return true;
    }

    return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN);
}

function auth_timeout_seconds()
{
    return defined('SESSION_TIMEOUT_SECONDS') ? (int) SESSION_TIMEOUT_SECONDS : 12600;
}

function csrf_token()
{
    return (string) $_SESSION['csrf_token'];
}

function csrf_input()
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')
        . '">';
}

function request_expects_json()
{
    // Alguns endpoints respondem HTML ou JSON dependendo da origem do request.
    // Esta funcao centraliza essa leitura para o restante do sistema.
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));

    return strpos($accept, 'application/json') !== false
        || $requestedWith === 'xmlhttprequest'
        || strpos($contentType, 'application/json') !== false
        || substr($uri, -4) === 'json';
}

function auth_clear_session()
{
    // Limpa memoria de sessao e cookie para encerrar o contexto por completo.
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function redirect_to_login($reason = null)
{
    // Guardamos a rota atual em "next" para facilitar o retorno
    // do usuario ao ponto onde ele parou.
    $next = $_SERVER['REQUEST_URI'] ?? app_url('index.php');
    $params = [];

    if (is_string($reason) && $reason !== '') {
        $params['reason'] = $reason;
    }

    $params['next'] = $next;

    $target = app_url('login.php') . '?' . http_build_query($params);
    header('Location: ' . $target);
    exit;
}

function enforce_session_timeout()
{
    // Timeout so entra em jogo quando ja existe usuario autenticado.
    if (!is_authenticated()) {
        return;
    }

    $now = time();
    $lastActivity = isset($_SESSION['last_activity_at']) ? (int) $_SESSION['last_activity_at'] : 0;

    if ($lastActivity > 0 && ($now - $lastActivity) > auth_timeout_seconds()) {
        auth_clear_session();

        if (request_expects_json()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => false,
                'error_code' => 'session_expired',
                'mensagem' => 'Sessao encerrada por inatividade.',
                'redirect' => app_url('session-expired.php'),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: ' . app_url('session-expired.php'));
        exit;
    }

    $_SESSION['last_activity_at'] = $now;
}

function require_login()
{
    // Esse e o guard principal das paginas protegidas e tambem dos endpoints AJAX.
    if (!auth_enforcement_enabled()) {
        return;
    }

    enforce_session_timeout();

    if (!is_authenticated()) {
        if (request_expects_json()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => false,
                'error_code' => 'authorization_required',
                'mensagem' => 'Login obrigatorio para acessar este modulo.',
                'redirect' => app_url('login.php'),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        redirect_to_login('auth_required');
    }
}

function read_json_input()
{
    // Faz cache do body decodificado para evitar ler php://input varias vezes.
    static $decoded = null;
    static $loaded = false;

    if ($loaded) {
        return $decoded;
    }

    $loaded = true;
    $rawBody = file_get_contents('php://input');
    $parsed = json_decode($rawBody ?: '', true);
    $decoded = is_array($parsed) ? $parsed : null;

    return $decoded;
}

function request_csrf_token()
{
    // O token pode chegar via POST, header custom ou payload JSON.
    $postToken = $_POST['csrf_token'] ?? null;
    if (is_string($postToken) && $postToken !== '') {
        return $postToken;
    }

    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (is_string($headerToken) && $headerToken !== '') {
        return $headerToken;
    }

    $json = read_json_input();
    $jsonToken = $json['csrf_token'] ?? null;

    return is_string($jsonToken) ? $jsonToken : null;
}

function require_valid_csrf($token = null)
{
    // Toda validacao CSRF passa por aqui para manter o mesmo criterio
    // em formularios normais e requests assincronas.
    $candidate = is_string($token) && $token !== '' ? $token : request_csrf_token();

    if (!is_string($candidate) || $candidate === '' || !hash_equals(csrf_token(), $candidate)) {
        http_response_code(403);
        if (request_expects_json()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => false,
                'error_code' => 'invalid_request',
                'mensagem' => 'Token CSRF invalido ou ausente.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo 'Token CSRF invalido ou ausente.';
        exit;
    }
}
