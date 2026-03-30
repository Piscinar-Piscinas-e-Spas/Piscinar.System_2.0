<?php
include '../includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('compras/listar.php'));
    exit;
}

require_valid_csrf();

$compraId = (int) ($_POST['id'] ?? 0);
if ($compraId <= 0) {
    header('Location: ' . app_url('compras/listar.php?status=erro_exclusao'));
    exit;
}

action_firewall_require_grant('compra_entrada', 'delete', $compraId, app_url('compras/listar.php?status=firewall'));

try {
    $repository = new \App\Repositories\CompraEntradaRepository($pdo);
    $repository->delete($compraId);
    header('Location: ' . app_url('compras/listar.php?status=excluido'));
    exit;
} catch (Throwable $e) {
    error_log('Falha ao excluir compra #' . $compraId . ': ' . $e->getMessage());
    header('Location: ' . app_url('compras/listar.php?status=erro_exclusao'));
    exit;
}
