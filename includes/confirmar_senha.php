<?php
include __DIR__ . '/db.php';
require_login();
require_valid_csrf();

header('Content-Type: application/json; charset=utf-8');

$userId = (int) (auth_user_id() ?? 0);
$payload = read_json_input() ?? [];
$senha = isset($payload['senha']) ? (string) $payload['senha'] : (string) ($_POST['senha'] ?? '');

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'mensagem' => 'Usuário não autenticado.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($senha === '') {
    http_response_code(422);
    echo json_encode([
        'status' => false,
        'mensagem' => 'Informe sua senha para continuar.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare('SELECT senha_hash, ativo FROM usuarios WHERE id_usuario = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$valida = is_array($user)
    && ((int) ($user['ativo'] ?? 0) === 1)
    && password_verify($senha, (string) ($user['senha_hash'] ?? ''));

if (!$valida) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'mensagem' => 'Senha inválida.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'status' => true,
], JSON_UNESCAPED_UNICODE);
