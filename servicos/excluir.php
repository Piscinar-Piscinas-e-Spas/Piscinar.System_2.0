<?php
include '../includes/db.php';
require_login();
require_once __DIR__ . '/_infra.php';

$idServico = (int) ($_POST['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('servicos/listar.php'));
    exit;
}

require_valid_csrf();

if ($idServico <= 0) {
    header('Location: ' . app_url('servicos/listar.php'));
    exit;
}

action_firewall_require_grant('servico', 'delete', $idServico, app_url('servicos/listar.php?status=firewall'));

servicos_ensure_schema($pdo);

try {
    $pdo->beginTransaction();

    $stmtParcelas = $pdo->prepare('DELETE FROM servicos_parcelas WHERE servico_id = :id_servico');
    $stmtParcelas->execute([':id_servico' => $idServico]);

    $stmtItens = $pdo->prepare('DELETE FROM servicos_itens WHERE servico_id = :id_servico');
    $stmtItens->execute([':id_servico' => $idServico]);

    $stmtServico = $pdo->prepare('DELETE FROM servicos_pedidos WHERE id_servico = :id_servico');
    $stmtServico->execute([':id_servico' => $idServico]);

    $pdo->commit();
    header('Location: ' . app_url('servicos/listar.php?status=excluido'));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: ' . app_url('servicos/listar.php?status=erro_exclusao'));
    exit;
}
