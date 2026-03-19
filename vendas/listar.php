<?php
include '../includes/db.php';
require_login();

$filtros = [
    'data_inicial' => trim((string) ($_GET['data_inicial'] ?? '')),
    'data_final' => trim((string) ($_GET['data_final'] ?? '')),
    'nome_cliente' => trim((string) ($_GET['nome_cliente'] ?? '')),
    'condicao_pagamento' => trim((string) ($_GET['condicao_pagamento'] ?? '')),
    'valor_minimo' => trim((string) ($_GET['valor_minimo'] ?? '')),
    'valor_maximo' => trim((string) ($_GET['valor_maximo'] ?? '')),
];

$repository = new \App\Repositories\VendaRepository($pdo);
$vendas = $repository->listWithCliente($filtros);
$resumoKpis = $repository->getResumoKpis($filtros);

$totalVista = (float) ($resumoKpis['total_vista'] ?? 0);
$totalParcelado = (float) ($resumoKpis['total_parcelado'] ?? 0);
$totalPagamento = $totalVista + $totalParcelado;
$percentualVista = $totalPagamento > 0 ? ($totalVista / $totalPagamento) * 100 : 0;
$percentualParcelado = $totalPagamento > 0 ? ($totalParcelado / $totalPagamento) * 100 : 0;

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
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <small class="text-uppercase text-muted">Total de vendas</small>
                            <h3 class="mb-0"><?= number_format((int) $resumoKpis['total_vendas'], 0, ',', '.') ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <small class="text-uppercase text-muted">Faturamento bruto</small>
                            <h3 class="mb-0">R$ <?= number_format((float) $resumoKpis['faturamento_bruto'], 2, ',', '.') ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <small class="text-uppercase text-muted">Ticket médio</small>
                            <h3 class="mb-0">R$ <?= number_format((float) $resumoKpis['ticket_medio'], 2, ',', '.') ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <small class="text-uppercase text-muted">Total parcelado vs à vista</small>
                            <div class="fw-semibold">Parcelado: R$ <?= number_format($totalParcelado, 2, ',', '.') ?> (<?= number_format($percentualParcelado, 1, ',', '.') ?>%)</div>
                            <div class="fw-semibold">À vista: R$ <?= number_format($totalVista, 2, ',', '.') ?> (<?= number_format($percentualVista, 1, ',', '.') ?>%)</div>
                        </div>
                    </div>
                </div>
            </div>

            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="data_inicial" class="form-label">Data inicial</label>
                        <input
                            type="date"
                            id="data_inicial"
                            name="data_inicial"
                            class="form-control"
                            value="<?= htmlspecialchars($filtros['data_inicial']) ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label for="data_final" class="form-label">Data final</label>
                        <input
                            type="date"
                            id="data_final"
                            name="data_final"
                            class="form-control"
                            value="<?= htmlspecialchars($filtros['data_final']) ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label for="condicao_pagamento" class="form-label">Condição de pagamento</label>
                        <select id="condicao_pagamento" name="condicao_pagamento" class="form-select">
                            <option value="">Todas</option>
                            <option value="vista" <?= $filtros['condicao_pagamento'] === 'vista' ? 'selected' : '' ?>>À vista</option>
                            <option value="parcelado" <?= $filtros['condicao_pagamento'] === 'parcelado' ? 'selected' : '' ?>>Parcelado</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="nome_cliente" class="form-label">Nome do cliente</label>
                        <input
                            type="text"
                            id="nome_cliente"
                            name="nome_cliente"
                            class="form-control"
                            placeholder="Digite o nome"
                            value="<?= htmlspecialchars($filtros['nome_cliente']) ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label for="valor_minimo" class="form-label">Valor mínimo</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            id="valor_minimo"
                            name="valor_minimo"
                            class="form-control"
                            value="<?= htmlspecialchars($filtros['valor_minimo']) ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label for="valor_maximo" class="form-label">Valor máximo</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            id="valor_maximo"
                            name="valor_maximo"
                            class="form-control"
                            value="<?= htmlspecialchars($filtros['valor_maximo']) ?>"
                        >
                    </div>

                    <div class="col-md-6 d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Filtrar
                        </button>
                        <a href="<?= app_url('vendas/listar.php'); ?>" class="btn btn-outline-secondary">Limpar filtros</a>
                    </div>
                </div>
            </form>

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
