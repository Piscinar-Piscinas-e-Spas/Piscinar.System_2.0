<?php
include '../includes/db.php';
require_login();
require_valid_csrf();

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    header('Location: ' . app_url('logistica/transferencia.php'));
    exit;
}

$origem = (string) ($_POST['origem'] ?? '');
$destino = (string) ($_POST['destino'] ?? '');
$colunasPermitidas = [
    'qtdLoja' => 'Loja',
    'qtdEstoque' => 'Estoque Auxiliar',
];

if (!isset($colunasPermitidas[$origem], $colunasPermitidas[$destino]) || $origem === $destino) {
    header('Location: ' . app_url('logistica/transferencia.php?status=transferencia_erro'));
    exit;
}

$itensRecebidos = $_POST['itens'] ?? [];
$itensAgrupados = [];

if (is_array($itensRecebidos)) {
    foreach ($itensRecebidos as $item) {
        if (!is_array($item)) {
            continue;
        }

        $produtoId = (int) ($item['produto_id'] ?? 0);
        $quantidade = (int) ($item['quantidade'] ?? 0);

        if ($produtoId <= 0 || $quantidade <= 0) {
            continue;
        }

        if (!isset($itensAgrupados[$produtoId])) {
            $itensAgrupados[$produtoId] = 0;
        }

        $itensAgrupados[$produtoId] += $quantidade;
    }
}

if ($itensAgrupados === []) {
    header('Location: ' . app_url('logistica/transferencia.php?status=transferencia_erro'));
    exit;
}

try {
    $pdo->beginTransaction();

    $ids = array_keys($itensAgrupados);
    $placeholders = implode(', ', array_fill(0, count($ids), '?'));
    $stmtProdutos = $pdo->prepare("SELECT id, nome, {$origem} AS saldo_origem FROM produtos WHERE id IN ({$placeholders}) FOR UPDATE");
    $stmtProdutos->execute($ids);
    $produtos = [];

    foreach ($stmtProdutos->fetchAll() as $produto) {
        $produtos[(int) $produto['id']] = $produto;
    }

    foreach ($itensAgrupados as $produtoId => $quantidade) {
        if (!isset($produtos[$produtoId])) {
            throw new RuntimeException('Produto inexistente.');
        }

        $saldoOrigem = (int) ($produtos[$produtoId]['saldo_origem'] ?? 0);
        if ($quantidade > $saldoOrigem) {
            $pdo->rollBack();
            header('Location: ' . app_url('logistica/transferencia.php?status=saldo_insuficiente'));
            exit;
        }
    }

    $stmtTransferencia = $pdo->prepare("
        UPDATE produtos
        SET {$origem} = {$origem} - :quantidade,
            {$destino} = {$destino} + :quantidade
        WHERE id = :id
    ");

    foreach ($itensAgrupados as $produtoId => $quantidade) {
        $stmtTransferencia->execute([
            ':quantidade' => $quantidade,
            ':id' => $produtoId,
        ]);
    }

    $pdo->commit();
    header('Location: ' . app_url('logistica/transferencia.php?status=transferencia_ok'));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: ' . app_url('logistica/transferencia.php?status=transferencia_erro'));
    exit;
}
