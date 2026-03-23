<?php
include '../includes/db.php';
require_login();

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

try {
    $pdo->beginTransaction();

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
