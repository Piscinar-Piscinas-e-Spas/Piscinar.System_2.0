<?php
include '../includes/db.php';
require_login();
require_once __DIR__ . '/_infra.php';

use App\Services\DocumentStockException;
use App\Services\DocumentStockService;

// Esse endpoint e o save principal do modulo de servicos usado pela UI atual.
// Ele trabalha com o modelo servicos_pedidos + servicos_itens + servicos_parcelas.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_security_error(405, 'method_not_allowed', 'Metodo POST obrigatorio para esta operacao.');
}

// O frontend manda tudo em JSON, entao o decode acontece logo na entrada.
$dados = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($dados)) {
    \App\Views\ApiResponse::send(400, ['status' => false, 'mensagem' => 'JSON invalido.']);
}

require_valid_csrf(is_string($dados['csrf_token'] ?? null) ? $dados['csrf_token'] : null);
// Algumas garantias de schema e regras auxiliares ainda vivem em _infra.php
// porque o modulo de servicos mistura partes novas e legado em transicao.
servicos_ensure_schema($pdo);
$clienteObrigatorio = servicos_cliente_obrigatorio();
$vendedores = servicos_obter_vendedores($pdo);
$servicoIdEdicao = (int) ($dados['id_servico'] ?? 0);
$servicoAuditService = new \App\Services\ServicoAuditLogService($pdo);
$documentStockService = new DocumentStockService($pdo);

$clienteId = (int) ($dados['cliente_id'] ?? 0);
$dataServico = trim((string) ($dados['data_servico'] ?? date('Y-m-d')));
$vendedorId = (int) ($dados['vendedor_id'] ?? auth_user_id() ?? 0);
$vendedorNome = trim((string) ($dados['vendedor_nome'] ?? auth_user_display_name()));
if ($clienteId <= 0) {
    $clienteId = null;
}

// O vendedor salvo precisa existir entre os usuarios ativos liberados para o fluxo.
$vendedorSelecionado = null;
foreach ($vendedores as $vendedor) {
    if ((int) ($vendedor['id_usuario'] ?? 0) !== $vendedorId) {
        continue;
    }

    $vendedorSelecionado = $vendedor;
    break;
}

if (!$vendedorSelecionado) {
    \App\Views\ApiResponse::send(422, [
        'status' => false,
        'mensagem' => 'Vendedor invalido para o servico.',
    ]);
}

if ($vendedorNome === '') {
    $vendedorNome = (string) ($vendedorSelecionado['nome_exibicao'] ?? '');
}

$dados['vendedor_id'] = (int) ($vendedorSelecionado['id_usuario'] ?? 0);
$dados['vendedor_nome'] = $vendedorNome;

$dataServicoObj = DateTime::createFromFormat('Y-m-d', $dataServico);
if (!$dataServicoObj || $dataServicoObj->format('Y-m-d') !== $dataServico) {
    \App\Views\ApiResponse::send(422, [
        'status' => false,
        'mensagem' => 'Data do serviÃ§o invÃ¡lida.',
    ]);
}

if ($clienteObrigatorio && ($clienteId === null || $clienteId <= 0)) {
    \App\Views\ApiResponse::send(422, [
        'status' => false,
        'mensagem' => 'Selecione ou salve um cliente antes de salvar o serviÃ§o.',
    ]);
}

$itensProduto = is_array($dados['itens_produto'] ?? null) ? $dados['itens_produto'] : [];
$itensMicro = is_array($dados['itens_microservico'] ?? null) ? $dados['itens_microservico'] : [];
$parcelas = is_array($dados['parcelas'] ?? null) ? $dados['parcelas'] : [];

// O servico pode ter produto, microservico ou os dois, mas nunca pode sair vazio.
if (!$itensProduto && !$itensMicro) {
    \App\Views\ApiResponse::send(422, ['status' => false, 'mensagem' => 'Informe ao menos um item de produto ou micro-serviÃ§o.']);
}

foreach ($itensProduto as $index => $item) {
    $origemEstoque = trim((string) ($item['origem_estoque'] ?? ''));
    if (!in_array($origemEstoque, ['loja', 'estoque_auxiliar'], true)) {
        \App\Views\ApiResponse::send(422, [
            'status' => false,
            'mensagem' => 'Origem de estoque invalida no item de produto #' . ($index + 1) . '.',
        ]);
    }
}

try {
    // A transacao protege cabecalho, itens, parcelas, estoque e auditoria do save.
    $pdo->beginTransaction();

    if ($servicoIdEdicao > 0) {
        $stmtServicoAtual = $pdo->prepare(
            'SELECT id_servico, COALESCE(estoque_processado, 0) AS estoque_processado
             FROM servicos_pedidos
             WHERE id_servico = ?
             LIMIT 1
             FOR UPDATE'
        );
        $stmtServicoAtual->execute([$servicoIdEdicao]);
        $servicoAtual = $stmtServicoAtual->fetch(PDO::FETCH_ASSOC);

        if (!$servicoAtual) {
            throw new RuntimeException('Servico nao encontrado para atualizacao.');
        }

        if ((int) ($servicoAtual['estoque_processado'] ?? 0) === 1) {
            $documentStockService->revertDocumentStock('servico', $servicoIdEdicao);
        }

        // Na edicao o cabecalho e atualizado e os filhos sao regravados.
        // E uma estrategia simples, mas reduz chance de estado misturado.
        $stmt = $pdo->prepare(
            'UPDATE servicos_pedidos
             SET cliente_id = ?,
                 vendedor_id = ?,
                 vendedor_nome = ?,
                 estoque_processado = ?,
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
            (int) $vendedorSelecionado['id_usuario'],
            $vendedorNome,
            1,
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
                cliente_id, vendedor_id, vendedor_nome, estoque_processado, data_servico, condicao_pagamento,
                subtotal_produtos, subtotal_microservicos, desconto_total,
                frete_total, total_geral
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $clienteId,
            (int) $vendedorSelecionado['id_usuario'],
            $vendedorNome,
            1,
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
            quantidade, valor_unitario, desconto_valor, frete_valor, origem_estoque, total_item
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $itensProdutoPersistidos = [];
    foreach ($itensProduto as $item) {
        if (!is_array($item)) {
            continue;
        }

        $quantidade = max(1, (float) ($item['quantidade'] ?? 1));
        $valorUnitario = max(0, (float) ($item['valor_unitario'] ?? 0));
        $desconto = max(0, (float) ($item['desconto_valor'] ?? 0));
        $frete = max(0, (float) ($item['frete_valor'] ?? 0));
        $subtotal = $quantidade * $valorUnitario;
        $total = max(0, $subtotal - min($desconto, $subtotal) + $frete);
        $origemEstoque = trim((string) ($item['origem_estoque'] ?? ''));
        $descricao = trim((string) ($item['descricao'] ?? 'Produto sem nome'));

        $stmtItem->execute([
            $servicoId,
            'produto',
            (int) ($item['produto_id'] ?? 0),
            $descricao,
            $quantidade,
            $valorUnitario,
            $desconto,
            $frete,
            $origemEstoque,
            $total,
        ]);

        $itensProdutoPersistidos[] = [
            'id_item' => (int) $pdo->lastInsertId(),
            'produto_id' => (int) ($item['produto_id'] ?? 0),
            'descricao' => $descricao,
            'quantidade' => $quantidade,
            'valor_unitario' => $valorUnitario,
            'desconto_valor' => $desconto,
            'frete_valor' => $frete,
            'origem_estoque' => $origemEstoque,
            'total_item' => $total,
        ];
    }

    foreach ($itensMicro as $item) {
        if (!is_array($item)) {
            continue;
        }

        $quantidade = max(1, (float) ($item['quantidade'] ?? 1));
        $valorUnitario = max(0, (float) ($item['valor_unitario'] ?? 0));
        $desconto = max(0, (float) ($item['desconto_valor'] ?? 0));
        $subtotal = $quantidade * $valorUnitario;
        $total = max(0, $subtotal - min($desconto, $subtotal));
        $descricao = trim((string) ($item['descricao'] ?? 'Micro-serviÃ§o'));

        $stmtItem->execute([
            $servicoId,
            'microservico',
            null,
            $descricao,
            $quantidade,
            $valorUnitario,
            $desconto,
            0,
            null,
            $total,
        ]);
    }

    $stmtParcela = $pdo->prepare(
        'INSERT INTO servicos_parcelas (
            servico_id, numero_parcela, vencimento, tipo_pagamento,
            valor_parcela, qtd_parcelas, total_parcelas
        ) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$parcelas) {
        // Se a UI nao mandar parcela, o sistema assume pagamento unico
        // no valor total do servico para nao deixar o financeiro sem vencimento.
        $parcelas = [[
            'vencimento' => $dataServico,
            'valor' => (float) ($dados['total_geral'] ?? 0),
            'tipo' => 'PIX'
        ]];
    }

    $qtdParcelas = count($parcelas);
    foreach (array_values($parcelas) as $index => $parcela) {
        if (!is_array($parcela)) {
            continue;
        }
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

    $documentStockService->applyDocumentStock(
        'servico',
        $servicoId,
        $itensProdutoPersistidos,
        (int) (auth_user_id() ?? 0)
    );

    try {
        // Falha de auditoria nao deve derrubar o save operacional.
        $servicoAuditService->logSaveFromCurrentFlow(
            $servicoId,
            $servicoIdEdicao > 0,
            $dados,
            $itensProdutoPersistidos,
            $itensMicro,
            $parcelas,
            $clienteId,
            $dataServico
        );
    } catch (Throwable $logError) {
        error_log('Falha ao registrar auditoria de servico: ' . $logError->getMessage());
    }

    $pdo->commit();

    \App\Views\ApiResponse::send($servicoIdEdicao > 0 ? 200 : 201, [
        'status' => true,
        'mensagem' => $servicoIdEdicao > 0 ? 'ServiÃ§o atualizado com sucesso.' : 'ServiÃ§o salvo com sucesso.',
        'id_servico' => $servicoId,
    ]);
} catch (DocumentStockException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    \App\Views\ApiResponse::send($e->getStatusCode(), [
        'status' => false,
        'mensagem' => $e->getMessage(),
    ]);
} catch (Throwable $e) {
    // Se qualquer parte falhar, a transacao volta inteira para evitar
    // servico salvo pela metade.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    \App\Views\ApiResponse::send(500, [
        'status' => false,
        'mensagem' => 'Nao foi possivel salvar o servico.',
        'erro' => $e->getMessage(),
    ]);
}
