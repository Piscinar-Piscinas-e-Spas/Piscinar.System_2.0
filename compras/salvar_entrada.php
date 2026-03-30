<?php
include '../includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_security_error(405, 'method_not_allowed', 'Metodo POST obrigatorio para esta operacao.');
}

$conteudo = file_get_contents('php://input');
$dados = json_decode($conteudo ?: '', true);

if (!is_array($dados)) {
    \App\Views\ApiResponse::send(400, ['status' => false, 'mensagem' => 'JSON invalido.']);
}

require_valid_csrf(is_string($dados['csrf_token'] ?? null) ? $dados['csrf_token'] : null);

$controller = new \App\Controllers\CompraEntradaController($pdo);
$resultado = $controller->saveFromPayload($dados);

\App\Views\ApiResponse::send($resultado['status_code'], $resultado['payload']);
