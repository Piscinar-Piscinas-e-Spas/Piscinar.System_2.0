<?php
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    \App\Views\ApiResponse::send(405, ['status' => false, 'mensagem' => 'Metodo nao permitido.']);
}

$conteudo = file_get_contents('php://input');
$dados = json_decode($conteudo ?: '', true);

if (!is_array($dados)) {
    \App\Views\ApiResponse::send(400, ['status' => false, 'mensagem' => 'JSON invalido.']);
}

$controller = new \App\Controllers\VendaController($pdo);
$result = $controller->saveFromPayload($dados);

\App\Views\ApiResponse::send($result['status_code'], $result['payload']);
