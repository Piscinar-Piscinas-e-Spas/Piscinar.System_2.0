<?php
include '../includes/db.php';
require_login();
require_once __DIR__ . '/_infra.php';

servicos_ensure_schema($pdo);
$servicoId = (int) ($_GET['id'] ?? 0);

if ($servicoId <= 0) {
    http_response_code(400);
    include '../includes/header.php';
    echo '<div class="container mt-4"><div class="alert alert-danger mb-0">ID do serviço inválido.</div></div>';
    include '../includes/footer.php';
    exit;
}

$repository = new \App\Repositories\ServicoRepository($pdo);
$detalhes = $repository->findCompleteById($servicoId);
$servico = $detalhes['servico'] ?? null;

if (!$servico) {
    http_response_code(404);
    include '../includes/header.php';
    echo '<div class="container mt-4"><div class="alert alert-warning mb-0">Serviço não encontrado.</div></div>';
    include '../includes/footer.php';
    exit;
}

$itens = $detalhes['itens'];
$parcelas = $detalhes['parcelas'];

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-tools me-2"></i>Detalhes do Serviço</h4>
            <a href="<?= app_url('servicos/listar.php'); ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><small class="text-uppercase text-muted">ID</small><div class="fw-semibold"><?= str_pad((string) ((int) $servico['id_servico']), 6, '0', STR_PAD_LEFT) ?></div></div>
                <div class="col-md-3"><small class="text-uppercase text-muted">Data</small><div class="fw-semibold"><?= htmlspecialchars((string) $servico['data_servico']) ?></div></div>
                <div class="col-md-3"><small class="text-uppercase text-muted">Condição</small><div class="fw-semibold"><?= htmlspecialchars((string) $servico['condicao_pagamento']) ?></div></div>
                <div class="col-md-3"><small class="text-uppercase text-muted">Total</small><div class="fw-semibold text-success">R$ <?= number_format((float) $servico['total_geral'], 2, ',', '.') ?></div></div>
            </div>
            <hr>
            <div class="row g-3">
                <div class="col-md-6"><small class="text-uppercase text-muted">Cliente</small><div><?= htmlspecialchars((string) ($servico['nome_cliente'] ?: '-')) ?></div></div>
                <div class="col-md-3"><small class="text-uppercase text-muted">Telefone</small><div><?= htmlspecialchars((string) ($servico['telefone_contato'] ?: '-')) ?></div></div>
                <div class="col-md-3"><small class="text-uppercase text-muted">CPF/CNPJ</small><div><?= htmlspecialchars((string) ($servico['cpf_cnpj'] ?: '-')) ?></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>Itens do Serviço</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark"><tr><th>Tipo</th><th>Descrição</th><th>Qtd.</th><th>Vlr unit.</th><th>Desc.</th><th>Frete</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php if (!$itens): ?><tr><td colspan="7" class="text-center text-muted">Sem itens cadastrados.</td></tr><?php endif; ?>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $item['tipo_item']) ?></td>
                                <td><?= htmlspecialchars((string) $item['descricao']) ?></td>
                                <td><?= number_format((float) $item['quantidade'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) $item['valor_unitario'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) $item['desconto_valor'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) $item['frete_valor'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) $item['total_item'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Parcelas</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark"><tr><th>#</th><th>Vencimento</th><th>Tipo</th><th>Valor</th></tr></thead>
                    <tbody>
                        <?php if (!$parcelas): ?><tr><td colspan="4" class="text-center text-muted">Sem parcelas cadastradas.</td></tr><?php endif; ?>
                        <?php foreach ($parcelas as $parcela): ?>
                            <tr>
                                <td><?= (int) $parcela['numero_parcela'] ?>/<?= (int) $parcela['qtd_parcelas'] ?></td>
                                <td><?= htmlspecialchars((string) $parcela['vencimento']) ?></td>
                                <td><?= htmlspecialchars((string) $parcela['tipo_pagamento']) ?></td>
                                <td>R$ <?= number_format((float) $parcela['valor_parcela'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
