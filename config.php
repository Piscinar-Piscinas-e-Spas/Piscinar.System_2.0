<?php
// URL base opcional do projeto (ex.: /piscinar.system_2.0).
// Defina via variável de ambiente BASE_URL.
// Quando houver divergência entre variável de ambiente e rota real,
// priorizamos o diretório detectado automaticamente do script atual.
function normalize_base_path(string $path): string
{
    $path = str_replace('\\', '/', trim($path));

    if ($path === '' || $path === '/' || $path === '.') {
        return '';
    }

    $path = '/' . trim($path, '/');

    return rtrim($path, '/');
}

function detected_base_path(): string
{
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');

    if ($scriptName === '') {
        return '';
    }

    return normalize_base_path(dirname($scriptName));
}

function configured_base_path(): string
{
    $configured = getenv('BASE_URL');

    if ($configured === false || $configured === null) {
        return '';
    }

    return normalize_base_path((string) $configured);
}

function resolved_base_path(): string
{
    $detected = detected_base_path();
    $configured = configured_base_path();

    if ($configured === '') {
        return $detected;
    }

    if ($detected !== '' && $configured !== $detected) {
        return $detected;
    }

    return $configured;
}

define('BASE_URL', resolved_base_path());

// Timeout de inatividade da sessão autenticada (em segundos).
define('SESSION_TIMEOUT_SECONDS', (int) (getenv('SESSION_TIMEOUT_SECONDS') ?: 12600));

// Tempo anterior ao timeout para aviso no frontend (em segundos).
define('SESSION_EXPIRY_WARNING_SECONDS', (int) (getenv('SESSION_EXPIRY_WARNING_SECONDS') ?: 60));

// Lifetime do cookie de sessão (0 = expira ao fechar o navegador).
// Para modo excepcionalmente longo, defina SESSION_COOKIE_LIFETIME_OVERRIDE.
define('SESSION_COOKIE_LIFETIME', (int) (getenv('SESSION_COOKIE_LIFETIME_OVERRIDE') ?: 0));

/**
 * Monta URLs absolutas da aplicação com BASE_URL, evitando barras duplicadas.
 */
function app_url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');

    if ($path == '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}
