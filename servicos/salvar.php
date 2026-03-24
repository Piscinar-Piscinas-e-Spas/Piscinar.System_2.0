<?php
include '../includes/db.php';
require_login();
require_once __DIR__ . '/_infra.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_security_error(405, 'method_not_allowed', 'Metodo POST obrigatorio para esta operacao.');
}

$dados = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($dados)) {
    \App\Views\ApiResponse::send(400, ['status' => false, 'mensagem' => 'JSON invalido.']);
}

require_valid_csrf(is_string($dados['csrf_token'] ?? null) ? $dados['csrf_token'] : null);
servicos_ensure_schema($pdo);
$clienteObrigatorio = servicos_cliente_obrigatorio();
$servicoIdEdicao = (int) ($dados['id_servico'] ?? 0);

$clienteId = (int) ($dados['cliente_id'] ?? 0);
$dataServico = trim((string) ($dados['data_servico'] ?? date('Y-m-d')));
if ($clienteId <= 0) {
    $clienteId = null;
}

$dataServicoObj = DateTime::createFromFormat('Y-m-d', $dataServico);
if (!$dataServicoObj || $dataServicoObj->format('Y-m-d') !== $dataServico) {
    \App\Views\ApiResponse::send(422, [
        'status' => false,
        'mensagem' => 'Data do serviço inválida.',
    ]);
}

if ($clienteObrigatorio && ($clienteId === null || $clienteId <= 0)) {
    \App\Views\ApiResponse::send(422, [
        'status' => false,
        'mensagem' => 'Selecione ou salve um cliente antes de salvar o serviço.',
    ]);
}

$itensProduto = is_array($dados['itens_produto'] ?? null) ? $dados['itens_produto'] : [];
$itensMicro = is_array($dados['itens_microservico'] ?? null) ? $dados['itens_microservico'] : [];
$parcelas = is_array($dados['parcelas'] ?? null) ? $dados['parcelas'] : [];

if (!$itensProduto && !$itensMicro) {
    \App\Views\ApiResponse::send(422, ['status' => false, 'mensagem' => 'Informe ao menos um item de produto ou micro-serviço.']);
}

try {
    $pdo->beginTransaction();

    if ($servicoIdEdicao > 0) {
        $stmt = $pdo->prepare(
            'UPDATE servicos_pedidos
             SET cliente_id = ?,
                 data_servico = ?,
                 condicao_pagamento = ?,
                 subtotal_produtos = ?,
                 subtotal_microservicos = ?,
                 desconto_total = ?,
                 frete_total = ?,
                 total_geral = ?
             WHERE id_servico = ?'
        );

        $stmt->execute([
            $clienteId,
            $dataServico,
            (string) ($dados['condicao_pagamento'] ?? 'vista'),
            (float) ($dados['subtotal_produtos'] ?? 0),
            (float) ($dados['subtotal_microservicos'] ?? 0),
            (float) ($dados['desconto_total'] ?? 0),
            (float) ($dados['frete_total'] ?? 0),
            (float) ($dados['total_geral'] ?? 0),
            $servicoIdEdicao,
        ]);

        $servicoId = $servicoIdEdicao;

        $pdo->prepare('DELETE FROM servicos_itens WHERE servico_id = ?')->execute([$servicoId]);
        $pdo->prepare('DELETE FROM servicos_parcelas WHERE servico_id = ?')->execute([$servicoId]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO servicos_pedidos (
                cliente_id, data_servico, condicao_pagamento,
                subtotal_produtos, subtotal_microservicos, desconto_total,
                frete_total, total_geral
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $clienteId,
            $dataServico,
            (string) ($dados['condicao_pagamento'] ?? 'vista'),
            (float) ($dados['subtotal_produtos'] ?? 0),
            (float) ($dados['subtotal_microservicos'] ?? 0),
            (float) ($dados['desconto_total'] ?? 0),
            (float) ($dados['frete_total'] ?? 0),
            (float) ($dados['total_geral'] ?? 0),
        ]);

        $servicoId = (int) $pdo->lastInsertId();
    }

    $stmtItem = $pdo->prepare(
        'INSERT INTO servicos_itens (
            servico_id, tipo_item, produto_id, descricao,
            quantidade, valor_unitario, desconto_valor, frete_valor, total_item
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $registrar = function (array $item, string $tipo) use ($stmtItem, $servicoId): void {
        $quantidade = max(1, (float) ($item['quantidade'] ?? 1));
        $valorUnitario = max(0, (float) ($item['valor_unitario'] ?? 0));
        $desconto = max(0, (float) ($item['desconto_valor'] ?? 0));
        $frete = $tipo === 'produto' ? max(0, (float) ($item['frete_valor'] ?? 0)) : 0;
        $subtotal = $quantidade * $valorUnitario;
        $total = max(0, $subtotal - min($desconto, $subtotal) + $frete);

        $stmtItem->execute([
            $servicoId,
            $tipo,
            $tipo === 'produto' ? (int) ($item['produto_id'] ?? 0) : null,
            trim((string) ($item['descricao'] ?? ($tipo === 'produto' ? 'Produto sem nome' : 'Micro-serviço'))),
            $quantidade,
            $valorUnitario,
            $desconto,
            $frete,
            $total,
        ]);
    };

    foreach ($itensProduto as $item) {
        if (!is_array($item)) { continue; }
        $registrar($item, 'produto');
    }

    foreach ($itensMicro as $item) {
        if (!is_array($item)) { continue; }
        $registrar($item, 'microservico');
    }

    $stmtParcela = $pdo->prepare(
        'INSERT INTO servicos_parcelas (
            servico_id, numero_parcela, vencimento, tipo_pagamento,
            valor_parcela, qtd_parcelas, total_parcelas
        ) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$parcelas) {
        $parcelas = [[
            'vencimento' => $dataServico,
            'valor' => (float) ($dados['total_geral'] ?? 0),
            'tipo' => 'PIX'
        ]];
    }

    $qtdParcelas = count($parcelas);
    foreach (array_values($parcelas) as $index => $parcela) {
        if (!is_array($parcela)) { continue; }
        $stmtParcela->execute([
            $servicoId,
            $index + 1,
            (string) ($parcela['vencimento'] ?? $dataServico),
            (string) ($parcela['tipo'] ?? 'PIX'),
            (float) ($parcela['valor'] ?? 0),
            $qtdParcelas,
            (float) ($dados['total_geral'] ?? 0),
        ]);
    }

    $pdo->commit();

    \App\Views\ApiResponse::send($servicoIdEdicao > 0 ? 200 : 201, [
        'status' => true,
        'mensagem' => $servicoIdEdicao > 0 ? 'Serviço atualizado com sucesso.' : 'Serviço salvo com sucesso.',
        'id_servico' => $servicoId,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    \App\Views\ApiResponse::send(500, [
        'status' => false,
        'mensagem' => 'Nao foi possivel salvar o servico.',
        'erro' => $e->getMessage(),
    ]);
}
