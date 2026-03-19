<?php

function password_reset_env(string $key, string $default = ''): string
{
    $value = getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    return trim((string) $value);
}

function ensure_password_reset_table(PDO $pdo): void
{
    static $ensured = false;

    if ($ensured) {
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS usuario_reset_senha_tokens (
            id_token BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_usuario INT UNSIGNED NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_token),
            KEY idx_reset_usuario_status (id_usuario, used_at, expires_at),
            CONSTRAINT fk_reset_usuario
                FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $ensured = true;
}

function password_reset_mail_fallback_path(): string
{
    $configured = password_reset_env('PASSWORD_RESET_MAIL_FALLBACK_LOG');

    if ($configured !== '') {
        return $configured;
    }

    return dirname(__DIR__) . '/storage/mail_fallback.log';
}

function persist_email_fallback(string $to, string $subject, string $message): bool
{
    $path = password_reset_mail_fallback_path();
    $dir = dirname($path);

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $payload = "\n=== EMAIL PENDENTE PARA ACAO OPERACIONAL ===\n"
        . 'Status: aguardando envio manual ou validacao do suporte' . "\n"
        . 'Data/Hora UTC: ' . gmdate('Y-m-d H:i:s') . "\n"
        . 'Destino aprovado: ' . $to . "\n"
        . 'Assunto: ' . $subject . "\n"
        . "Conteudo do e-mail:\n" . $message . "\n"
        . "Orientacao: confirmar o motivo da queda para fallback e reenviar o conteudo acima ao destinatario.\n";

    return file_put_contents($path, $payload, FILE_APPEND | LOCK_EX) !== false;
}

function password_reset_daily_limit_path(): string
{
    $configured = password_reset_env('PASSWORD_RESET_SMTP_DAILY_LIMIT_FILE');

    if ($configured !== '') {
        return $configured;
    }

    return dirname(__DIR__) . '/storage/smtp_daily_limit.json';
}

function password_reset_daily_limit_timezone(): DateTimeZone
{
    $configured = password_reset_env('PASSWORD_RESET_SMTP_TIMEZONE', 'America/Sao_Paulo');

    try {
        return new DateTimeZone($configured);
    } catch (Exception $exception) {
        return new DateTimeZone('America/Sao_Paulo');
    }
}

function password_reset_smtp_daily_limit(): int
{
    $limit = (int) password_reset_env('PASSWORD_RESET_SMTP_DAILY_LIMIT', '20');

    return $limit > 0 ? $limit : 20;
}

function password_reset_reserve_smtp_slot(?string &$reason = null): bool
{
    $path = password_reset_daily_limit_path();
    $dir = dirname($path);

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $handle = @fopen($path, 'c+');
    if ($handle === false) {
        $reason = 'Nao foi possivel abrir o controle local de limite diario do SMTP.';
        return false;
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            $reason = 'Nao foi possivel bloquear o controle local de limite diario do SMTP.';
            return false;
        }

        $raw = stream_get_contents($handle);
        $data = json_decode(is_string($raw) ? $raw : '', true);
        if (!is_array($data)) {
            $data = [];
        }

        $today = (new DateTimeImmutable('now', password_reset_daily_limit_timezone()))->format('Y-m-d');
        $count = isset($data[$today]) ? (int) $data[$today] : 0;
        $limit = password_reset_smtp_daily_limit();

        if ($count >= $limit) {
            $reason = 'Limite diario de 20 envios SMTP atingido. O conteudo foi direcionado para o log operacional de fallback.';
            return false;
        }

        $data = [$today => $count + 1];

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return true;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function password_reset_release_smtp_slot(): void
{
    $path = password_reset_daily_limit_path();

    if (!is_file($path)) {
        return;
    }

    $handle = @fopen($path, 'c+');
    if ($handle === false) {
        return;
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            return;
        }

        $raw = stream_get_contents($handle);
        $data = json_decode(is_string($raw) ? $raw : '', true);
        if (!is_array($data)) {
            $data = [];
        }

        $today = (new DateTimeImmutable('now', password_reset_daily_limit_timezone()))->format('Y-m-d');
        $count = isset($data[$today]) ? (int) $data[$today] : 0;

        if ($count <= 0) {
            return;
        }

        $data[$today] = $count - 1;
        if ($data[$today] <= 0) {
            unset($data[$today]);
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function password_reset_smtp_config(): array
{
    $host = password_reset_env('SMTP_HOST');
    $user = password_reset_env('SMTP_USER');
    $pass = password_reset_env('SMTP_KEY');
    $port = (int) password_reset_env('SMTP_PORT', '465');
    $security = strtolower(password_reset_env('SMTP_SECURITY', 'ssl'));

    return [
        'host' => $host,
        'user' => $user,
        'pass' => $pass,
        'port' => $port > 0 ? $port : 465,
        'security' => in_array($security, ['ssl', 'tls'], true) ? $security : 'ssl',
        'from_email' => password_reset_env('SMTP_FROM_EMAIL', $user),
        'from_name' => password_reset_env('SMTP_FROM_NAME', 'Piscinar System'),
        'to_email' => password_reset_env('PASSWORD_RESET_APPROVAL_EMAIL', 'piscinar2014@gmail.com'),
        'hello' => password_reset_env('SMTP_HELO', 'localhost'),
        'timeout' => max(5, (int) password_reset_env('SMTP_TIMEOUT_SECONDS', '15')),
    ];
}

function password_reset_smtp_attempt_configs(): array
{
    $primary = password_reset_smtp_config();
    $secondary = $primary;

    $secondary['port'] = (int) password_reset_env('SMTP_SECONDARY_PORT', '587');
    if ($secondary['port'] <= 0) {
        $secondary['port'] = 587;
    }

    $secondarySecurity = strtolower(password_reset_env('SMTP_SECONDARY_SECURITY', 'tls'));
    $secondary['security'] = in_array($secondarySecurity, ['ssl', 'tls'], true) ? $secondarySecurity : 'tls';

    return [$primary, $secondary];
}

function password_reset_smtp_is_configured(array $config): bool
{
    return $config['host'] !== ''
        && $config['user'] !== ''
        && $config['pass'] !== ''
        && $config['from_email'] !== ''
        && $config['to_email'] !== '';
}

function password_reset_smtp_read_response($socket, array $expectedCodes): string
{
    $response = '';

    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }

        $response .= $line;

        if (preg_match('/^\d{3} /', $line) === 1) {
            break;
        }
    }

    $statusCode = (int) substr($response, 0, 3);
    if (!in_array($statusCode, $expectedCodes, true)) {
        throw new RuntimeException(trim($response) !== '' ? trim($response) : 'Resposta SMTP inesperada.');
    }

    return $response;
}

function password_reset_smtp_write_line($socket, string $command): void
{
    $written = fwrite($socket, $command . "\r\n");

    if ($written === false) {
        throw new RuntimeException('Nao foi possivel enviar comando para o servidor SMTP.');
    }
}

function password_reset_smtp_send(string $subject, string $message, array $config): bool
{
    $transport = $config['security'] === 'ssl' ? 'ssl://' : '';
    $socket = @stream_socket_client(
        $transport . $config['host'] . ':' . $config['port'],
        $errorNumber,
        $errorMessage,
        $config['timeout'],
        STREAM_CLIENT_CONNECT
    );

    if ($socket === false) {
        throw new RuntimeException('Falha na conexao SMTP: ' . $errorMessage . ' (' . $errorNumber . ').');
    }

    try {
        stream_set_timeout($socket, $config['timeout']);

        password_reset_smtp_read_response($socket, [220]);
        password_reset_smtp_write_line($socket, 'EHLO ' . $config['hello']);
        password_reset_smtp_read_response($socket, [250]);

        if ($config['security'] === 'tls') {
            password_reset_smtp_write_line($socket, 'STARTTLS');
            password_reset_smtp_read_response($socket, [220]);

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Nao foi possivel iniciar criptografia TLS no SMTP.');
            }

            password_reset_smtp_write_line($socket, 'EHLO ' . $config['hello']);
            password_reset_smtp_read_response($socket, [250]);
        }

        password_reset_smtp_write_line($socket, 'AUTH LOGIN');
        password_reset_smtp_read_response($socket, [334]);
        password_reset_smtp_write_line($socket, base64_encode($config['user']));
        password_reset_smtp_read_response($socket, [334]);
        password_reset_smtp_write_line($socket, base64_encode($config['pass']));
        password_reset_smtp_read_response($socket, [235]);

        password_reset_smtp_write_line($socket, 'MAIL FROM:<' . $config['from_email'] . '>');
        password_reset_smtp_read_response($socket, [250]);
        password_reset_smtp_write_line($socket, 'RCPT TO:<' . $config['to_email'] . '>');
        password_reset_smtp_read_response($socket, [250, 251]);
        password_reset_smtp_write_line($socket, 'DATA');
        password_reset_smtp_read_response($socket, [354]);

        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
            'To: ' . $config['to_email'],
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        $body = preg_replace("/(?m)^\\./", '..', str_replace(["\r\n", "\r"], "\n", $message));
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", (string) $body) . "\r\n.";
        password_reset_smtp_write_line($socket, $payload);
        password_reset_smtp_read_response($socket, [250]);

        password_reset_smtp_write_line($socket, 'QUIT');
        password_reset_smtp_read_response($socket, [221]);

        return true;
    } finally {
        fclose($socket);
    }
}

function send_system_email(string $subject, string $message, ?string &$error = null): bool
{
    $config = password_reset_smtp_config();
    $to = $config['to_email'] !== '' ? $config['to_email'] : 'piscinar2014@gmail.com';

    if (password_reset_smtp_is_configured($config)) {
        $fallbackReason = null;
        $smtpSlotReserved = false;

        if (password_reset_reserve_smtp_slot($fallbackReason)) {
            $smtpSlotReserved = true;
            foreach (password_reset_smtp_attempt_configs() as $attemptConfig) {
                try {
                    if (password_reset_smtp_send($subject, $message, $attemptConfig)) {
                        return true;
                    }
                } catch (Throwable $throwable) {
                    $fallbackReason = 'Falha no SMTP ' . strtoupper($attemptConfig['security']) . '/' . $attemptConfig['port'] . ': ' . $throwable->getMessage();
                }
            }

            if ($smtpSlotReserved) {
                password_reset_release_smtp_slot();
            }
        }

        $fallbackSaved = persist_email_fallback($to, $subject, $message);

        if ($fallbackSaved) {
            $error = $fallbackReason ?: 'Nao foi possivel enviar o e-mail automaticamente. O conteudo foi salvo no log operacional de fallback para reenvio manual.';
            return true;
        }

        $error = 'Falha ao enviar o e-mail de aprovacao e tambem ao salvar o log local de fallback.';
        return false;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: Piscinar System <no-reply@piscinar.local>',
    ];

    $ok = @mail($to, $subject, $message, implode("\r\n", $headers));

    if ($ok) {
        return true;
    }

    $fallbackSaved = persist_email_fallback($to, $subject, $message);

    if ($fallbackSaved) {
        $error = 'Nao foi possivel enviar o e-mail automaticamente. O conteudo foi salvo no log operacional de fallback para reenvio manual.';
        return true;
    }

    $error = 'Falha ao enviar o e-mail de aprovacao e tambem ao salvar o log local de fallback.';
    return false;
}

function create_password_reset_token(PDO $pdo, int $userId): string
{
    ensure_password_reset_table($pdo);

    $token = (string) random_int(100000, 999999);
    $tokenHash = password_hash($token, PASSWORD_DEFAULT);
    $expiresAt = (new DateTimeImmutable('+15 minutes'))->format('Y-m-d H:i:s');

    $invalidate = $pdo->prepare('UPDATE usuario_reset_senha_tokens SET used_at = NOW() WHERE id_usuario = :id_usuario AND used_at IS NULL');
    $invalidate->execute([':id_usuario' => $userId]);

    $insert = $pdo->prepare('INSERT INTO usuario_reset_senha_tokens (id_usuario, token_hash, expires_at) VALUES (:id_usuario, :token_hash, :expires_at)');
    $insert->execute([
        ':id_usuario' => $userId,
        ':token_hash' => $tokenHash,
        ':expires_at' => $expiresAt,
    ]);

    return $token;
}

function validate_password_reset_token(PDO $pdo, int $userId, string $token): bool
{
    ensure_password_reset_table($pdo);

    $stmt = $pdo->prepare(
        'SELECT id_token, token_hash
         FROM usuario_reset_senha_tokens
         WHERE id_usuario = :id_usuario
           AND used_at IS NULL
           AND expires_at >= NOW()
         ORDER BY id_token DESC
         LIMIT 5'
    );
    $stmt->execute([':id_usuario' => $userId]);

    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        if (password_verify($token, (string) ($row['token_hash'] ?? ''))) {
            $markUsed = $pdo->prepare('UPDATE usuario_reset_senha_tokens SET used_at = NOW() WHERE id_token = :id_token LIMIT 1');
            $markUsed->execute([':id_token' => (int) $row['id_token']]);
            return true;
        }
    }

    return false;
}
