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

$repository = new \App\Repositories\FornecedorRepository($pdo);
$service = new \App\Services\FornecedorService($repository);

$fornecedor = $service->normalize($dados);
$alert = $service->validate($fornecedor);

if ($alert) {
    \App\Views\ApiResponse::send(422, [
        'status' => false,
        'mensagem' => (string) ($alert['mensagem'] ?? 'Dados do fornecedor invalidos.'),
    ]);
}

try {
    $fornecedorId = $repository->create($fornecedor);

    \App\Views\ApiResponse::send(201, [
        'status' => true,
        'mensagem' => 'Fornecedor salvo com sucesso.',
        'id_fornecedor' => $fornecedorId,
        'fornecedor' => [
            'id_fornecedor' => $fornecedorId,
            'nome_fornecedor' => $fornecedor['nome_fornecedor'],
            'documento' => $fornecedor['documento'],
            'telefone' => $fornecedor['telefone'],
            'email' => $fornecedor['email'],
        ],
    ]);
} catch (Throwable $e) {
    error_log('Erro ao salvar fornecedor rapido: ' . $e->getMessage());

    \App\Views\ApiResponse::send(500, [
        'status' => false,
        'mensagem' => 'Erro ao salvar fornecedor. Tente novamente.',
    ]);
}
