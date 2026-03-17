<?php
// URL base opcional do projeto (ex.: /piscinar.system_2.0).
// Defina via variável de ambiente BASE_URL.
// Fallback seguro: string vazia ('') para instalação na raiz do domínio.
define('BASE_URL', getenv('BASE_URL') ?: '');

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
