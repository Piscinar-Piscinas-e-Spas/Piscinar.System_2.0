<?php
include '../includes/db.php';
require_login();

$vendaId = (int) ($_GET['id'] ?? 0);

if ($vendaId <= 0) {
    http_response_code(400);
    include '../includes/header.php';
    ?>
    <div class="container mt-4">
        <div class="alert alert-danger mb-0">ID da venda inválido.</div>
    </div>
    <?php
    include '../includes/footer.php';
    exit;
}

$repository = new \App\Repositories\VendaRepository($pdo);
$vendaCompleta = $repository->findCompleteById($vendaId);

if (!$vendaCompleta) {
    http_response_code(404);
    include '../includes/header.php';
    ?>
    <div class="container mt-4">
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <span>Venda não encontrada.</span>
            <a href="<?= app_url('vendas/listar.php'); ?>" class="btn btn-sm btn-outline-secondary">Voltar</a>
        </div>
    </div>
    <?php
    include '../includes/footer.php';
    exit;
}

$venda = $vendaCompleta['venda'];
$itens = $vendaCompleta['itens'];
$parcelas = $vendaCompleta['parcelas'];

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-receipt me-2"></i>Detalhes da Venda</h4>
            <a href="<?= app_url('vendas/listar.php'); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Voltar
            </a>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <small class="text-uppercase text-muted">ID da Venda</small>
                    <div class="fw-semibold"><?= str_pad((string) ((int) $venda['id_venda']), 6, '0', STR_PAD_LEFT) ?></div>
                </div>
                <div class="col-md-3">
                    <small class="text-uppercase text-muted">Data</small>
                    <div class="fw-semibold"><?= htmlspecialchars((string) ($venda['data_venda'] ?? '-')) ?></div>
                </div>
                <div class="col-md-3">
                    <small class="text-uppercase text-muted">Condição de pagamento</small>
                    <div class="fw-semibold"><?= htmlspecialchars((string) ($venda['condicao_pagamento'] ?? '-')) ?></div>
                </div>
                <div class="col-md-3">
                    <small class="text-uppercase text-muted">Total Geral</small>
                    <div class="fw-semibold text-success">R$ <?= number_format((float) ($venda['total_geral'] ?? 0), 2, ',', '.') ?></div>
                </div>
            </div>

            <hr>

            <h5 class="mb-3"><i class="fas fa-user me-2"></i>Cliente</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <small class="text-uppercase text-muted">Nome</small>
                    <div><?= htmlspecialchars((string) ($venda['nome_cliente'] ?? '-')) ?></div>
                </div>
                <div class="col-md-3">
                    <small class="text-uppercase text-muted">Telefone</small>
                    <div><?= htmlspecialchars((string) ($venda['telefone_contato'] ?? '-')) ?></div>
                </div>
                <div class="col-md-3">
                    <small class="text-uppercase text-muted">CPF/CNPJ</small>
                    <div><?= htmlspecialchars((string) ($venda['cpf_cnpj'] ?? '-')) ?></div>
                </div>
                <div class="col-md-6">
                    <small class="text-uppercase text-muted">E-mail</small>
                    <div><?= htmlspecialchars((string) ($venda['email_contato'] ?? '-')) ?></div>
                </div>
                <div class="col-md-6">
                    <small class="text-uppercase text-muted">Endereço</small>
                    <div><?= htmlspecialchars((string) ($venda['endereco'] ?? '-')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Itens da Venda</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Valor Unitário</th>
                            <th>Desconto</th>
                            <th>Frete</th>
                            <th>Total Item</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($itens)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Não há itens cadastrados para esta venda.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($item['produto_nome'] ?? 'Produto não encontrado')) ?></td>
                                <td><?= number_format((float) ($item['quantidade'] ?? 0), 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) ($item['valor_unitario'] ?? 0), 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) ($item['desconto_valor'] ?? 0), 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) ($item['frete_valor'] ?? 0), 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) ($item['total_item'] ?? 0), 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Parcelas</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Vencimento</th>
                            <th>Tipo de Pagamento</th>
                            <th>Valor Parcela</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parcelas)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Não há parcelas cadastradas para esta venda.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($parcelas as $parcela): ?>
                            <tr>
                                <td><?= (int) ($parcela['numero_parcela'] ?? 0) ?>/<?= (int) ($parcela['qtd_parcelas'] ?? 1) ?></td>
                                <td><?= htmlspecialchars((string) ($parcela['vencimento'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($parcela['tipo_pagamento'] ?? '-')) ?></td>
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
