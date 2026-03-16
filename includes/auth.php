<?php

require_once dirname(__DIR__) . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function auth_user()
{
    foreach (['auth_user', 'usuario', 'user'] as $key) {
        if (!empty($_SESSION[$key]) && is_array($_SESSION[$key])) {
            return $_SESSION[$key];
        }
    }

    return null;
}

function auth_user_id()
{
    foreach (['auth_user_id', 'usuario_id', 'user_id'] as $key) {
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

function is_authenticated()
{
    return auth_user_id() !== null;
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
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));

    return strpos($accept, 'application/json') !== false
        || $requestedWith === 'xmlhttprequest'
        || strpos($contentType, 'application/json') !== false
        || substr($uri, -4) === 'json';
}

function render_security_error($statusCode, $errorCode, $message)
{
    http_response_code((int) $statusCode);

    if (request_expects_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => false,
            'error_code' => (string) $errorCode,
            'mensagem' => (string) $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acesso protegido</title>
        <style>
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #f5f7fb;
                color: #1f2937;
            }
            .security-error {
                max-width: 640px;
                margin: 56px auto;
                padding: 28px;
                background: #ffffff;
                border: 1px solid #dbe3ef;
                border-radius: 14px;
                box-shadow: 0 12px 36px rgba(15, 23, 42, 0.08);
            }
            h1 {
                margin-top: 0;
                font-size: 24px;
            }
            p {
                line-height: 1.5;
            }
            .meta {
                margin-top: 18px;
                padding: 12px 14px;
                background: #eef2f7;
                border-radius: 10px;
                font-size: 14px;
            }
            .actions {
                margin-top: 20px;
            }
            a {
                color: #0d6efd;
                text-decoration: none;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="security-error">
            <h1><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Esta resposta foi bloqueada pelo middleware de seguranca da aplicacao.</p>
            <div class="meta">
                <strong>Codigo:</strong> <?= htmlspecialchars((string) $errorCode, ENT_QUOTES, 'UTF-8') ?><br>
                <strong>HTTP:</strong> <?= (int) $statusCode ?>
            </div>
            <div class="actions">
                <a href="<?= htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Voltar para o inicio</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function require_login()
{
    if (!is_authenticated()) {
        render_security_error(401, 'authorization_required', 'Login obrigatorio para acessar este modulo.');
    }
}

function read_json_input()
{
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
    $candidate = is_string($token) && $token !== '' ? $token : request_csrf_token();

    if (!is_string($candidate) || $candidate === '' || !hash_equals(csrf_token(), $candidate)) {
        render_security_error(403, 'invalid_request', 'Token CSRF invalido ou ausente.');
    }
}

