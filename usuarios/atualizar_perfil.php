<?php
include '../includes/db.php';
require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => false,
        'mensagem' => 'Metodo POST obrigatorio para esta operacao.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$dados = read_json_input();
if (!is_array($dados)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => false,
        'mensagem' => 'JSON invalido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_valid_csrf(is_string($dados['csrf_token'] ?? null) ? $dados['csrf_token'] : null);

$userId = (int) (auth_user_id() ?? 0);
$usuario = trim((string) ($dados['usuario'] ?? ''));
$nomeExibicao = trim((string) ($dados['nome_exibicao'] ?? ''));

if ($userId <= 0) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => false,
        'mensagem' => 'Usuario nao autenticado.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($usuario === '') {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => false,
        'mensagem' => 'Informe o usuario para continuar.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (mb_strlen($usuario) > 80) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => false,
        'mensagem' => 'O usuario deve ter no maximo 80 caracteres.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($nomeExibicao !== '' && mb_strlen($nomeExibicao) > 120) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => false,
        'mensagem' => 'O nome de exibicao deve ter no maximo 120 caracteres.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare('
        UPDATE usuarios
        SET usuario = :usuario,
            nome_exibicao = :nome_exibicao
        WHERE id_usuario = :id_usuario
        LIMIT 1
    ');
    $stmt->execute([
        ':usuario' => $usuario,
        ':nome_exibicao' => $nomeExibicao !== '' ? $nomeExibicao : null,
        ':id_usuario' => $userId,
    ]);

    $displayName = $nomeExibicao !== '' ? $nomeExibicao : $usuario;
    if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
        $_SESSION['auth_user'] = [];
    }

    $_SESSION['auth_user']['id'] = $userId;
    $_SESSION['auth_user']['usuario'] = $usuario;
    $_SESSION['auth_user']['nome'] = $displayName;
    $_SESSION['auth_user']['nome_exibicao'] = $nomeExibicao !== '' ? $nomeExibicao : null;

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => true,
        'mensagem' => 'Cadastro atualizado com sucesso.',
        'usuario' => [
            'usuario' => $usuario,
            'nome_exibicao' => $displayName,
            'nome_exibicao_editavel' => $nomeExibicao,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (PDOException $e) {
    $isDuplicado = ((int) $e->getCode() === 23000);
    http_response_code($isDuplicado ? 409 : 500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => false,
        'mensagem' => $isDuplicado
            ? 'Ja existe um usuario com este login.'
            : 'Nao foi possivel atualizar o cadastro. Tente novamente.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
