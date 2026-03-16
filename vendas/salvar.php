<?php
include '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

function responder(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function paraDecimal($valor): float
{
    if (is_float($valor) || is_int($valor)) {
        return (float) $valor;
    }

    $texto = trim((string) $valor);
    if ($texto === '') {
        return 0.0;
    }

    if (str_contains($texto, ',')) {
        $texto = str_replace('.', '', $texto);
        $texto = str_replace(',', '.', $texto);
    }

    return is_numeric($texto) ? (float) $texto : 0.0;
}

function quaseIgual(float $a, float $b, float $tolerancia = 0.02): bool
{
    return abs($a - $b) <= $tolerancia;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['status' => false, 'mensagem' => 'Método não permitido.']);
}

$conteudo = file_get_contents('php://input');
$dados = json_decode($conteudo ?: '', true);

if (!is_array($dados)) {
    responder(400, ['status' => false, 'mensagem' => 'JSON inválido.']);
}

$clienteId = (int) ($dados['cliente_id'] ?? 0);
$condicaoPagamento = trim((string) ($dados['condicao_pagamento'] ?? 'vista'));
$itens = $dados['itens'] ?? [];
$parcelas = $dados['parcelas'] ?? [];

if ($clienteId <= 0) {
    responder(422, ['status' => false, 'mensagem' => 'Cliente inválido para a venda.']);
}

if (!in_array($condicaoPagamento, ['vista', 'parcelado'], true)) {
    responder(422, ['status' => false, 'mensagem' => 'Condição de pagamento inválida.']);
}

if (!is_array($itens) || count($itens) === 0) {
    responder(422, ['status' => false, 'mensagem' => 'A venda deve ter pelo menos um item.']);
}

if (!is_array($parcelas) || count($parcelas) === 0) {
    responder(422, ['status' => false, 'mensagem' => 'A venda deve ter pelo menos uma parcela.']);
}

$subtotalCalculado = 0.0;
$descontoCalculado = 0.0;
$freteCalculado = 0.0;
$itensNormalizados = [];

foreach ($itens as $idx => $item) {
    $produtoId = (int) ($item['produto_id'] ?? 0);
    $quantidade = paraDecimal($item['quantidade'] ?? 0);
    $valorUnitario = paraDecimal($item['valor_unitario'] ?? 0);
    $descontoItem = paraDecimal($item['desconto_valor'] ?? 0);
    $freteItem = paraDecimal($item['frete_valor'] ?? 0);

    if ($produtoId <= 0 || $quantidade <= 0 || $valorUnitario < 0 || $descontoItem < 0 || $freteItem < 0) {
        responder(422, ['status' => false, 'mensagem' => 'Item inválido na posição ' . ($idx + 1) . '.']);
    }

    $subtotalItem = round($quantidade * $valorUnitario, 2);
    if ($descontoItem > $subtotalItem) {
        responder(422, ['status' => false, 'mensagem' => 'Desconto maior que subtotal no item ' . ($idx + 1) . '.']);
    }

    $totalItem = round($subtotalItem - $descontoItem + $freteItem, 2);

    $subtotalCalculado += $subtotalItem;
    $descontoCalculado += $descontoItem;
    $freteCalculado += $freteItem;

    $itensNormalizados[] = [
        'produto_id' => $produtoId,
        'quantidade' => $quantidade,
        'valor_unitario' => $valorUnitario,
        'desconto_valor' => $descontoItem,
        'frete_valor' => $freteItem,
        'total_item' => $totalItem,
    ];
}

$totalCalculado = round($subtotalCalculado - $descontoCalculado + $freteCalculado, 2);

$subtotalRecebido = paraDecimal($dados['subtotal'] ?? 0);
$descontoRecebido = paraDecimal($dados['desconto_total'] ?? 0);
$freteRecebido = paraDecimal($dados['frete_total'] ?? 0);
$totalRecebido = paraDecimal($dados['total_geral'] ?? 0);

if (!quaseIgual($subtotalCalculado, $subtotalRecebido)
    || !quaseIgual($descontoCalculado, $descontoRecebido)
    || !quaseIgual($freteCalculado, $freteRecebido)
    || !quaseIgual($totalCalculado, $totalRecebido)
) {
    responder(422, [
        'status' => false,
        'mensagem' => 'Totais inconsistentes no payload.',
        'totais_calculados' => [
            'subtotal' => round($subtotalCalculado, 2),
            'desconto_total' => round($descontoCalculado, 2),
            'frete_total' => round($freteCalculado, 2),
            'total_geral' => round($totalCalculado, 2),
        ],
    ]);
}

$parcelasNormalizadas = [];
$somaParcelas = 0.0;

foreach ($parcelas as $idx => $parcela) {
    $numeroParcela = (int) ($parcela['numero_parcela'] ?? ($idx + 1));
    $vencimento = trim((string) ($parcela['vencimento'] ?? ''));
    $valorParcela = paraDecimal($parcela['valor'] ?? 0);
    $tipoPagamento = trim((string) ($parcela['tipo_pagamento'] ?? 'PIX'));
    $qtdParcelas = max(1, (int) ($parcela['qtd_parcelas'] ?? count($parcelas)));
    $totalParcelas = paraDecimal($parcela['total_parcelas'] ?? $totalCalculado);

    if ($numeroParcela <= 0 || $valorParcela < 0 || $tipoPagamento === '' || $vencimento === '') {
        responder(422, ['status' => false, 'mensagem' => 'Parcela inválida na posição ' . ($idx + 1) . '.']);
    }

    if (!DateTime::createFromFormat('Y-m-d', $vencimento)) {
        responder(422, ['status' => false, 'mensagem' => 'Data de vencimento inválida na parcela ' . ($idx + 1) . '.']);
    }

    $somaParcelas += $valorParcela;

    $parcelasNormalizadas[] = [
        'numero_parcela' => $numeroParcela,
        'vencimento' => $vencimento,
        'valor' => $valorParcela,
        'tipo_pagamento' => $tipoPagamento,
        'qtd_parcelas' => $qtdParcelas,
        'total_parcelas' => $totalParcelas,
    ];
}

if (!quaseIgual(round($somaParcelas, 2), $totalCalculado, 0.05)) {
    responder(422, ['status' => false, 'mensagem' => 'A soma das parcelas difere do total da venda.']);
}

$clienteStmt = $pdo->prepare('SELECT id_cliente FROM clientes WHERE id_cliente = :id LIMIT 1');
$clienteStmt->execute([':id' => $clienteId]);
if (!$clienteStmt->fetchColumn()) {
    responder(422, ['status' => false, 'mensagem' => 'Cliente informado não existe.']);
}

$produtoStmt = $pdo->prepare('SELECT id FROM produtos WHERE id = :id LIMIT 1');

try {
    $pdo->beginTransaction();

    $insertVenda = $pdo->prepare('INSERT INTO vendas (
        id_cliente,
        data_venda,
        subtotal,
        desconto_total,
        frete_total,
        total_geral,
        condicao_pagamento,
        created_at,
        updated_at
    ) VALUES (
        :id_cliente,
        CURDATE(),
        :subtotal,
        :desconto_total,
        :frete_total,
        :total_geral,
        :condicao_pagamento,
        NOW(),
        NOW()
    )');

    $insertVenda->execute([
        ':id_cliente' => $clienteId,
        ':subtotal' => round($subtotalCalculado, 2),
        ':desconto_total' => round($descontoCalculado, 2),
        ':frete_total' => round($freteCalculado, 2),
        ':total_geral' => $totalCalculado,
        ':condicao_pagamento' => $condicaoPagamento,
    ]);

    $vendaId = (int) $pdo->lastInsertId();

    $insertItem = $pdo->prepare('INSERT INTO venda_itens (
        id_venda,
        id_produto,
        quantidade,
        valor_unitario,
        desconto_valor,
        frete_valor,
        total_item,
        created_at,
        updated_at
    ) VALUES (
        :id_venda,
        :id_produto,
        :quantidade,
        :valor_unitario,
        :desconto_valor,
        :frete_valor,
        :total_item,
        NOW(),
        NOW()
    )');

    foreach ($itensNormalizados as $item) {
        $produtoStmt->execute([':id' => $item['produto_id']]);
        if (!$produtoStmt->fetchColumn()) {
            throw new RuntimeException('Produto inválido na venda: ' . $item['produto_id']);
        }

        $insertItem->execute([
            ':id_venda' => $vendaId,
            ':id_produto' => $item['produto_id'],
            ':quantidade' => $item['quantidade'],
            ':valor_unitario' => $item['valor_unitario'],
            ':desconto_valor' => $item['desconto_valor'],
            ':frete_valor' => $item['frete_valor'],
            ':total_item' => $item['total_item'],
        ]);
    }

    $insertParcela = $pdo->prepare('INSERT INTO venda_parcelas (
        id_venda,
        numero_parcela,
        vencimento,
        valor_parcela,
        tipo_pagamento,
        qtd_parcelas,
        total_parcelas,
        created_at,
        updated_at
    ) VALUES (
        :id_venda,
        :numero_parcela,
        :vencimento,
        :valor_parcela,
        :tipo_pagamento,
        :qtd_parcelas,
        :total_parcelas,
        NOW(),
        NOW()
    )');

    foreach ($parcelasNormalizadas as $parcela) {
        $insertParcela->execute([
            ':id_venda' => $vendaId,
            ':numero_parcela' => $parcela['numero_parcela'],
            ':vencimento' => $parcela['vencimento'],
            ':valor_parcela' => $parcela['valor'],
            ':tipo_pagamento' => $parcela['tipo_pagamento'],
            ':qtd_parcelas' => $parcela['qtd_parcelas'],
            ':total_parcelas' => $parcela['total_parcelas'],
        ]);
    }

    $pdo->commit();

    responder(200, [
        'status' => true,
        'mensagem' => 'Venda salva com sucesso.',
        'id_venda' => $vendaId,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro ao salvar venda: ' . $e->getMessage());
    responder(500, [
        'status' => false,
        'mensagem' => 'Erro ao salvar venda.',
    ]);
}
