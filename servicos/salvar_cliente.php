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

$repository = new \App\Repositories\ClienteRepository($pdo);
$service = new \App\Services\ClienteService($repository);

$clienteNormalizado = $service->normalize($dados);
$alert = $service->validate($clienteNormalizado);

if ($alert) {
    \App\Views\ApiResponse::send(422, [
        'status' => false,
        'mensagem' => (string) ($alert['mensagem'] ?? 'Dados do cliente invalidos.'),
    ]);
}

try {
    $idCliente = $repository->create($clienteNormalizado);

    \App\Views\ApiResponse::send(201, [
        'status' => true,
        'mensagem' => 'Cliente salvo com sucesso.',
        'id_cliente' => $idCliente,
        'cliente' => [
            'id_cliente' => $idCliente,
            'nome_cliente' => $clienteNormalizado['nome_cliente'],
            'telefone_contato' => $clienteNormalizado['telefone_contato'],
            'cpf_cnpj' => $clienteNormalizado['cpf_cnpj'],
            'email_contato' => $clienteNormalizado['email_contato'],
            'endereco' => $clienteNormalizado['endereco'],
        ],
    ]);
} catch (Throwable $e) {
    error_log('Erro ao salvar cliente em servicos: ' . $e->getMessage());

    \App\Views\ApiResponse::send(500, [
        'status' => false,
        'mensagem' => 'Erro ao salvar cliente. Tente novamente.',
    ]);
}
