<?php
include '../includes/db.php';
require_login();

$compraId = (int) ($_GET['id'] ?? 0);
$repository = new \App\Repositories\CompraEntradaRepository($pdo);
$detalhes = $compraId > 0 ? $repository->findCompleteById($compraId) : null;

if (!$detalhes) {
    header('Location: ' . app_url('compras/listar.php'));
    exit;
}

$compra = $detalhes['compra'] ?? [];
$itens = $detalhes['itens'] ?? [];
$parcelas = $detalhes['parcelas'] ?? [];

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-file-invoice me-2"></i>Detalhes da Compra</h4>
            <a href="<?= app_url('compras/listar.php'); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Voltar
            </a>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3"><strong>ID</strong><br><?= str_pad((string) ((int) ($compra['id_compra_entrada'] ?? 0)), 6, '0', STR_PAD_LEFT) ?></div>
                <div class="col-md-3"><strong>Nota</strong><br><?= htmlspecialchars((string) ($compra['numero_nota'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Data entrada</strong><br><?= htmlspecialchars((string) ($compra['data_entrada'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Condicao</strong><br><?= htmlspecialchars((string) ($compra['condicao_pagamento'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-6"><strong>Razao Social</strong><br><?= htmlspecialchars((string) ($compra['razao_social'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-6"><strong>Nome Fantasia</strong><br><?= htmlspecialchars((string) ($compra['nome_fantasia'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Produto</th>
                            <th>Qtd total</th>
                            <th>Qtd loja</th>
                            <th>Qtd estoque auxiliar</th>
                            <th>Custo unitario</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($item['produto_nome'] ?? $item['descricao_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($item['quantidade_total'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($item['quantidade_loja'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($item['quantidade_estoque_auxiliar'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>R$ <?= number_format((float) ($item['custo_unitario'] ?? 0), 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) ($item['subtotal_item'] ?? 0), 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>#</th>
                            <th>Vencimento</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parcelas as $parcela): ?>
                            <tr>
                                <td><?= (int) ($parcela['numero_parcela'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string) ($parcela['vencimento'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($parcela['tipo_pagamento_previsto'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>R$ <?= number_format((float) ($parcela['valor_parcela'] ?? 0), 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
