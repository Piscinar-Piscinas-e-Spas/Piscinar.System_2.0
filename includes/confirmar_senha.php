<?php
include __DIR__ . '/db.php';
require_login();
require_valid_csrf();

header('Content-Type: application/json; charset=utf-8');

$userId = (int) (auth_user_id() ?? 0);
$payload = read_json_input() ?? [];
$senha = isset($payload['senha']) ? (string) $payload['senha'] : (string) ($_POST['senha'] ?? '');
$entity = action_firewall_normalize_entity($payload['entity'] ?? ($_POST['entity'] ?? ''));
$intent = action_firewall_normalize_intent($payload['intent'] ?? ($_POST['intent'] ?? ''));
$recordId = (int) ($payload['record_id'] ?? ($_POST['record_id'] ?? 0));

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'mensagem' => 'Usuario nao autenticado.',
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

if (!action_firewall_password_is_valid($pdo, $senha)) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'mensagem' => 'Senha invalida.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$response = [
    'status' => true,
];

$requestedGrant = $entity !== '' || $intent !== '' || $recordId > 0;
if ($requestedGrant) {
    if ($entity === '' || $intent === '' || $recordId <= 0) {
        http_response_code(422);
        echo json_encode([
            'status' => false,
            'mensagem' => 'Dados da acao protegida sao invalidos.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $response['fw_token'] = action_firewall_issue_grant($entity, $intent, $recordId);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
