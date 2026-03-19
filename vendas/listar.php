<?php
include '../includes/db.php';
require_login();

$repository = new \App\Repositories\VendaRepository($pdo);
$vendas = $repository->listWithCliente();

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-receipt me-2"></i>Lista de Vendas</h4>
            <a href="<?= app_url('vendas/nova.php'); ?>" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Nova
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Venda</th>
                            <th>Data da Venda</th>
                            <th>Cliente</th>
                            <th>Condi&ccedil;&atilde;o de Pagamento</th>
                            <th>Subtotal</th>
                            <th>Desconto Total</th>
                            <th>Frete Total</th>
                            <th>Total Geral</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vendas)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Nenhuma venda encontrada.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($vendas as $venda): ?>
                            <tr>
                                <td><?= str_pad((string) ((int) $venda['id_venda']), 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars((string) ($venda['data_venda'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($venda['cliente'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($venda['condicao_pagamento'] ?? '-')) ?></td>
                                <td>R$ <?= number_format((float) $venda['subtotal'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) $venda['desconto_total'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) $venda['frete_total'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) $venda['total_geral'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
