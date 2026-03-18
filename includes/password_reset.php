<?php

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
    $configured = trim((string) (getenv('PASSWORD_RESET_MAIL_FALLBACK_LOG') ?: ''));

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

    $payload = "\n=== EMAIL PENDENTE ===\n"
        . 'Data/Hora UTC: ' . gmdate('Y-m-d H:i:s') . "\n"
        . 'Para: ' . $to . "\n"
        . 'Assunto: ' . $subject . "\n"
        . "Mensagem:\n" . $message . "\n";

    return file_put_contents($path, $payload, FILE_APPEND | LOCK_EX) !== false;
}

function send_system_email(string $subject, string $message, ?string &$error = null): bool
{
    $to = 'piscinar2014@gmail.com';
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
        $error = 'Não foi possível enviar e-mail automaticamente no servidor. O conteúdo foi salvo na fila local para reenvio.';
        return true;
    }

    $error = 'Falha ao enviar e-mail de aprovação e também ao salvar a fila local.';
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
