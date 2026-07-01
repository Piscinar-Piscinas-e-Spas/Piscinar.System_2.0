<?php
include '../includes/db.php';
require_login();

use App\Services\DocumentStockService;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('vendas/listar.php'));
    exit;
}

require_valid_csrf();

$idVenda = (int) ($_POST['id'] ?? 0);
if ($idVenda <= 0) {
    header('Location: ' . app_url('vendas/listar.php?status=erro_exclusao'));
    exit;
}

action_firewall_require_grant('venda', 'delete', $idVenda, app_url('vendas/listar.php?status=firewall'));

$repository = new \App\Repositories\VendaRepository($pdo);
$documentStockService = new DocumentStockService($pdo);

try {
    $pdo->beginTransaction();

    $stmtVendaAtual = $pdo->prepare(
        'SELECT COALESCE(estoque_processado, 0) AS estoque_processado
         FROM vendas
         WHERE id_venda = :id_venda
         LIMIT 1
         FOR UPDATE'
    );
    $stmtVendaAtual->execute([':id_venda' => $idVenda]);
    $vendaAtual = $stmtVendaAtual->fetch(PDO::FETCH_ASSOC);

    if (!$vendaAtual) {
        throw new RuntimeException('Venda nao encontrada para exclusao.');
    }

    if ((int) ($vendaAtual['estoque_processado'] ?? 0) === 1) {
        $documentStockService->revertDocumentStock('venda', $idVenda);
    }

    $stmtParcelas = $pdo->prepare('DELETE FROM venda_parcelas WHERE id_venda = :id_venda');
    $stmtParcelas->execute([':id_venda' => $idVenda]);

    $stmtItens = $pdo->prepare('DELETE FROM venda_itens WHERE id_venda = :id_venda');
    $stmtItens->execute([':id_venda' => $idVenda]);

    $stmtVenda = $pdo->prepare('DELETE FROM vendas WHERE id_venda = :id_venda');
    $stmtVenda->execute([':id_venda' => $idVenda]);

    $pdo->commit();
    header('Location: ' . app_url('vendas/listar.php?status=excluido'));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: ' . app_url('vendas/listar.php?status=erro_exclusao'));
    exit;
}
