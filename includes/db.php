<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once __DIR__ . '/auth.php';

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'piscinar_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'BxuZpImQLwh*Ndw-';


try {
    $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erro de conexao</title>
        <style>
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #f8f9fa;
                color: #212529;
            }
            .db-error {
                max-width: 720px;
                margin: 48px auto;
                padding: 24px;
                background: #fff;
                border: 1px solid #dee2e6;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            }
            .db-error h1 {
                margin-top: 0;
                font-size: 24px;
            }
            .db-error code {
                display: block;
                margin-top: 12px;
                padding: 12px;
                background: #f1f3f5;
                border-radius: 8px;
                white-space: pre-wrap;
                word-break: break-word;
            }
        </style>
    </head>
    <body>
        <div class="db-error">
            <h1>Falha ao conectar com o banco de dados</h1>
            <p>Revise as credenciais nas variaveis de ambiente <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code> e <code>DB_PASS</code>.</p>
            <code><?php echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'); ?></code>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
