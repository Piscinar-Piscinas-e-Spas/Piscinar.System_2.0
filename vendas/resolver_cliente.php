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

$clienteId = (int) ($dados['cliente_id'] ?? 0);
$clienteResolucao = trim((string) ($dados['cliente_resolucao'] ?? ''));
$clienteInput = is_array($dados['cliente'] ?? null) ? $dados['cliente'] : null;

if (!in_array($clienteResolucao, ['atualizar', 'novo'], true)) {
    \App\Views\ApiResponse::send(422, ['status' => false, 'mensagem' => 'cliente_resolucao inválido.']);
}

if ($clienteInput === null) {
    \App\Views\ApiResponse::send(422, ['status' => false, 'mensagem' => 'Dados do cliente são obrigatórios.']);
}

$repository = new \App\Repositories\ClienteRepository($pdo);
$service = new \App\Services\ClienteService($repository);

$clienteNormalizado = $service->normalize([
    'nome_cliente' => $clienteInput['nome'] ?? null,
    'telefone_contato' => $clienteInput['telefone'] ?? null,
    'cpf_cnpj' => $clienteInput['cpf_cnpj'] ?? null,
    'email_contato' => $clienteInput['email'] ?? null,
    'endereco' => $clienteInput['endereco'] ?? null,
]);

$alert = $service->validate($clienteNormalizado);
if ($alert) {
    \App\Views\ApiResponse::send(422, [
        'status' => false,
        'mensagem' => (string) ($alert['mensagem'] ?? 'Dados do cliente invalidos.'),
    ]);
}

try {
    if ($clienteResolucao === 'atualizar') {
        if ($clienteId <= 0 || !$repository->exists($clienteId)) {
            \App\Views\ApiResponse::send(422, ['status' => false, 'mensagem' => 'Cliente informado não existe.']);
        }

        $repository->update($clienteId, $clienteNormalizado);
        $clienteRetorno = $repository->findById($clienteId);

        \App\Views\ApiResponse::send(200, [
            'status' => true,
            'mensagem' => 'Cliente atualizado com sucesso.',
            'id_cliente' => $clienteId,
            'cliente' => [
                'id_cliente' => $clienteId,
                'nome_cliente' => $clienteRetorno['nome_cliente'] ?? $clienteNormalizado['nome_cliente'],
                'telefone_contato' => $clienteRetorno['telefone_contato'] ?? $clienteNormalizado['telefone_contato'],
                'cpf_cnpj' => $clienteRetorno['cpf_cnpj'] ?? $clienteNormalizado['cpf_cnpj'],
                'email_contato' => $clienteRetorno['email_contato'] ?? $clienteNormalizado['email_contato'],
                'endereco' => $clienteRetorno['endereco'] ?? $clienteNormalizado['endereco'],
            ],
        ]);
    }

    $idClienteNovo = $repository->create($clienteNormalizado);

    \App\Views\ApiResponse::send(201, [
        'status' => true,
        'mensagem' => 'Novo cliente criado com sucesso.',
        'id_cliente' => $idClienteNovo,
        'cliente' => [
            'id_cliente' => $idClienteNovo,
            'nome_cliente' => $clienteNormalizado['nome_cliente'],
            'telefone_contato' => $clienteNormalizado['telefone_contato'],
            'cpf_cnpj' => $clienteNormalizado['cpf_cnpj'],
            'email_contato' => $clienteNormalizado['email_contato'],
            'endereco' => $clienteNormalizado['endereco'],
        ],
    ]);
} catch (Throwable $e) {
    error_log('Erro ao resolver cliente para venda: ' . $e->getMessage());

    \App\Views\ApiResponse::send(500, [
        'status' => false,
        'mensagem' => 'Erro ao resolver cliente.',
    ]);
}
