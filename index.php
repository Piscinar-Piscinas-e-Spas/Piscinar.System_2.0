<?php
include 'includes/db.php';
require_login();

$extraHeadContent = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

$resumoStmt = $pdo->query("SELECT
    COUNT(*) AS total_produtos,
    SUM(CASE WHEN (COALESCE(qtdLoja, 0) + COALESCE(qtdEstoque, 0)) > 0 THEN 1 ELSE 0 END) AS em_estoque,
    SUM(CASE
        WHEN COALESCE(controle_estoque, 0) = 1
             AND estoque_minimo IS NOT NULL
             AND (COALESCE(qtdLoja, 0) + COALESCE(qtdEstoque, 0)) > 0
             AND (COALESCE(qtdLoja, 0) + COALESCE(qtdEstoque, 0)) <= estoque_minimo
        THEN 1 ELSE 0 END) AS baixo_estoque
FROM produtos");

$resumo = $resumoStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$menuSections = [
    'Operacao Comercial' => [
        ['href' => app_url('vendas/nova.php'), 'icon' => 'fas fa-file-invoice-dollar', 'label' => 'Nova Venda', 'class' => 'btn-outline-success'],
        ['href' => app_url('vendas/listar.php'), 'icon' => 'fas fa-chart-line', 'label' => 'Dashboard de Vendas', 'class' => 'btn-outline-primary'],
        ['href' => app_url('servicos/nova.php'), 'icon' => 'fas fa-tools', 'label' => 'Novo Servico', 'class' => 'btn-outline-success'],
        ['href' => app_url('servicos/listar.php'), 'icon' => 'fas fa-screwdriver-wrench', 'label' => 'Dashboard de Servicos', 'class' => 'btn-outline-primary'],
    ],
    'Cadastros Principais' => [
        ['href' => app_url('produtos/listar.php'), 'icon' => 'fas fa-box-open', 'label' => 'Produtos', 'class' => 'btn-outline-primary'],
        ['href' => app_url('produtos/cadastrar.php'), 'icon' => 'fas fa-plus-circle', 'label' => 'Novo Produto', 'class' => 'btn-outline-success'],
        ['href' => app_url('clientes/listar.php'), 'icon' => 'fas fa-users', 'label' => 'Clientes', 'class' => 'btn-outline-primary'],
        ['href' => app_url('fornecedores/listar.php'), 'icon' => 'fas fa-building', 'label' => 'Fornecedor', 'class' => 'btn-outline-primary'],
    ],
    'Estoque e Compras' => [
        ['href' => app_url('compras/entrada.php'), 'icon' => 'fas fa-truck-loading', 'label' => 'Entrada de Mercadoria', 'class' => 'btn-outline-success'],
        ['href' => app_url('logistica/inventario.php'), 'icon' => 'fas fa-warehouse', 'label' => 'Gestao de Estoque', 'class' => 'btn-outline-secondary'],
        ['href' => app_url('logistica/transferencia.php'), 'icon' => 'fas fa-right-left', 'label' => 'Transferencias', 'class' => 'btn-outline-secondary'],
    ],
    'Administrativo' => [
        ['href' => app_url('usuarios/cadastrar.php'), 'icon' => 'fas fa-user-shield', 'label' => 'Usuarios', 'class' => 'btn-outline-dark'],
        ['href' => app_url('logs/listar.php'), 'icon' => 'fas fa-clipboard-list', 'label' => 'Logs', 'class' => 'btn-outline-dark'],
    ],
];

$today = new DateTimeImmutable('today');
$financeiroRepository = new \App\Repositories\FinanceiroRepository($pdo);
$financialDashboardService = new \App\Services\FinancialDashboardService();
$seriesBySource = $financeiroRepository->getHistoricalSeriesBySource();
$cashKpis = $financeiroRepository->getCurrentMonthCashKpis($today);
$sourceLabels = $financeiroRepository->getSourceLabels();
$defaultAnalysis = $financialDashboardService->buildAnalysis($seriesBySource, ['vendas'], $today);
$monthNames = \App\Services\FinancialDashboardService::monthNames();
$monthShortNames = \App\Services\FinancialDashboardService::shortMonthNames();
$currentMonthLabel = ($monthNames[(int) $today->format('n')] ?? 'Mes') . '/' . $today->format('Y');
$financialPayload = [
    'seriesBySource' => $seriesBySource,
    'currentYear' => (int) $defaultAnalysis['current_year'],
    'currentMonth' => (int) $defaultAnalysis['current_month'],
    'currentDay' => (int) $defaultAnalysis['current_day'],
    'daysInMonth' => (int) $defaultAnalysis['days_in_month'],
    'monthLabels' => array_values($monthNames),
    'monthShortLabels' => array_values($monthShortNames),
];

include 'includes/header.php';
?>

<style>
    .index-side-menu-card { position: sticky; top: 1rem; }
    .index-side-menu-title { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.08em; color: #5c6b86; margin-bottom: 0.75rem; }
    .index-side-menu-section + .index-side-menu-section { margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid #e7eefb; }
    .index-side-link { width: 100%; display: flex; align-items: center; justify-content: flex-start; gap: 0.75rem; border-radius: 14px; padding: 0.8rem 1rem; font-weight: 600; }
    .index-side-link + .index-side-link { margin-top: 0.65rem; }
    .index-side-link i { width: 1.15rem; text-align: center; }
    .index-dashboard-card { min-height: 100%; }
    .index-kpi-card { border: 1px solid #e6eefc; background: linear-gradient(135deg, #f9fbff 0%, #f1f6ff 100%); }
    .index-kpi-card .card-body { padding: 1.25rem; }
</style>

<div class="row g-4">
    <div class="col-lg-4 col-xl-3 order-2 order-lg-1">
        <div class="card index-side-menu-card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-compass me-2"></i>Atalhos do Sistema</h4>
            </div>
            <div class="card-body">
                <?php foreach ($menuSections as $sectionTitle => $links): ?>
                    <div class="index-side-menu-section">
                        <div class="index-side-menu-title"><?= htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php foreach ($links as $link): ?>
                            <a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>" class="btn <?= htmlspecialchars($link['class'], ENT_QUOTES, 'UTF-8') ?> index-side-link">
                                <i class="<?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                <span><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8 col-xl-9 order-1 order-lg-2">
        <div class="card index-dashboard-card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h3>
            </div>
            <div class="card-body">
                <section class="financial-summary-panel mb-4" id="financialSummaryApp" data-financial-payload='<?= htmlspecialchars(json_encode($financialPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'>
                    <div class="financial-summary-header">
                        <div>
                            <div class="financial-summary-kicker">Resumo Financeiro</div>
                            <h4 class="mb-1">Historico consolidado e acompanhamento de <?= htmlspecialchars($currentMonthLabel, ENT_QUOTES, 'UTF-8') ?></h4>
                            <p class="text-muted mb-0">A leitura inicial abre em vendas e permite acrescentar servicos e compras no somatorio conforme o historico for ficando mais completo.</p>
                        </div>
                        <a href="<?= htmlspecialchars(app_url('financeiro/fluxo_caixa.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary" id="financialCashButton">
                            <i class="fas fa-wallet me-1"></i>Fluxo de caixa
                        </a>
                    </div>

                    <div id="financialSummarySecret">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4 col-xl-3">
                                <div class="card financial-kpi-card financial-kpi-revenue h-100"><div class="card-body"><small>Total previsto receitas</small><div class="financial-kpi-value"><?= \App\Services\FinancialDashboardService::formatMoney((float) $cashKpis['receitas_previstas']) ?></div><div class="text-muted">Vendas + servicos com vencimento no mes</div></div></div>
                            </div>
                            <div class="col-md-4 col-xl-3">
                                <div class="card financial-kpi-card financial-kpi-paid h-100"><div class="card-body"><small>Recebido ate hoje</small><div class="financial-kpi-value"><?= \App\Services\FinancialDashboardService::formatMoney((float) $cashKpis['receitas_recebidas']) ?></div><div class="text-muted">Parcelas baixadas ate hoje</div></div></div>
                            </div>
                            <div class="col-md-4 col-xl-3">
                                <div class="card financial-kpi-card financial-kpi-expense h-100"><div class="card-body"><small>Total contas a pagar</small><div class="financial-kpi-value"><?= \App\Services\FinancialDashboardService::formatMoney((float) $cashKpis['despesas_previstas']) ?></div><div class="text-muted">Compras com vencimento no mes</div></div></div>
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <div class="card financial-kpi-card <?= ((float) $cashKpis['saldo_projetado']) >= 0 ? 'financial-kpi-balance-positive' : 'financial-kpi-balance-negative' ?> h-100"><div class="card-body"><small>Saldo projetado</small><div class="financial-kpi-value"><?= \App\Services\FinancialDashboardService::formatMoney((float) $cashKpis['saldo_projetado']) ?></div><div class="text-muted"><?= ((float) $cashKpis['saldo_projetado']) >= 0 ? 'Receitas previstas acima das despesas.' : 'Despesas previstas acima das receitas.' ?></div></div></div>
                            </div>
                        </div>

                        <?php $revenuePercent = (float) $cashKpis['receitas_previstas'] > 0 ? min(((float) $cashKpis['receitas_recebidas'] / (float) $cashKpis['receitas_previstas']) * 100, 100) : 0; ?>
                        <?php $expensePercent = (float) $cashKpis['despesas_previstas'] > 0 ? min(((float) $cashKpis['despesas_pagas'] / (float) $cashKpis['despesas_previstas']) * 100, 100) : 0; ?>
                        <div class="financial-progress-grid mb-4">
                            <div class="card h-100"><div class="card-body"><div class="small text-uppercase text-muted mb-2">Receitas do mes</div><div class="d-flex justify-content-between align-items-end mb-2"><div class="fw-semibold"><?= \App\Services\FinancialDashboardService::formatMoney((float) $cashKpis['receitas_recebidas']) ?></div><small class="text-muted">de <?= \App\Services\FinancialDashboardService::formatMoney((float) $cashKpis['receitas_previstas']) ?></small></div><div class="progress mb-2"><div class="progress-bar bg-success" style="width: <?= number_format($revenuePercent, 2, '.', '') ?>%"></div></div><div class="text-muted small">A receber ainda no mes: <?= \App\Services\FinancialDashboardService::formatMoney((float) $cashKpis['receitas_a_receber']) ?></div></div></div>
                            <div class="card h-100"><div class="card-body"><div class="small text-uppercase text-muted mb-2">Despesas do mes</div><div class="d-flex justify-content-between align-items-end mb-2"><div class="fw-semibold"><?= \App\Services\FinancialDashboardService::formatMoney((float) $cashKpis['despesas_pagas']) ?></div><small class="text-muted">de <?= \App\Services\FinancialDashboardService::formatMoney((float) $cashKpis['despesas_previstas']) ?></small></div><div class="progress mb-2"><div class="progress-bar bg-danger" style="width: <?= number_format($expensePercent, 2, '.', '') ?>%"></div></div><div class="text-muted small">A pagar restante no mes: <?= \App\Services\FinancialDashboardService::formatMoney((float) $cashKpis['despesas_a_pagar']) ?></div></div></div>
                        </div>
                    </div>

                    <div class="financial-source-toggle mb-4">
                        <?php foreach ($sourceLabels as $sourceKey => $label): ?>
                            <div class="form-check form-switch">
                                <input class="form-check-input financial-source-input" type="checkbox" role="switch" id="source-<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8') ?>" <?= $sourceKey === 'vendas' ? 'checked disabled' : '' ?>>
                                <label class="form-check-label" for="source-<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="row g-4">
                        <div class="col-12">
                            <div class="financial-table-card card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                        <div><div class="small text-uppercase text-muted">Mapa de calor</div><h5 class="mb-0">Historico por mes e ano</h5></div>
                                        <div class="financial-legend"><span class="financial-legend-min">Menor</span><div class="financial-legend-bar"></div><span class="financial-legend-max">Maior</span></div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle financial-heatmap-table mb-0" id="financialHeatmapTable">
                                            <thead><tr><th>Meses</th><?php foreach ($defaultAnalysis['years'] as $year): ?><th><?= (int) $year ?></th><?php endforeach; ?><th>Media Mes/Ano</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($defaultAnalysis['rows'] as $row): ?>
                                                    <tr>
                                                        <th><?= htmlspecialchars((string) $row['month_label'], ENT_QUOTES, 'UTF-8') ?></th>
                                                        <?php foreach ($row['values'] as $cell): ?><td><?= $cell['value'] === null ? '&nbsp;' : htmlspecialchars(\App\Services\FinancialDashboardService::formatMoney((float) $cell['value']), ENT_QUOTES, 'UTF-8') ?></td><?php endforeach; ?>
                                                        <td class="financial-average-cell"><?= htmlspecialchars(\App\Services\FinancialDashboardService::formatMoney((float) $row['month_average']), ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot><tr><th>Total Ano</th><?php foreach ($defaultAnalysis['year_totals'] as $yearTotal): ?><td><?= htmlspecialchars(\App\Services\FinancialDashboardService::formatMoney((float) $yearTotal), ENT_QUOTES, 'UTF-8') ?></td><?php endforeach; ?><td>&nbsp;</td></tr></tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="financial-side-stack">
                                <div class="card"><div class="card-body"><div class="small text-uppercase text-muted">Media Mes/Ano</div><h5 class="mb-3">Tendencia mensal</h5><div class="financial-chart-wrapper"><canvas id="financialAverageChart"></canvas></div></div></div>
                                <div class="card"><div class="card-body"><div class="small text-uppercase text-muted">Acompanhamento do mes</div><h5 class="mb-3"><?= htmlspecialchars((string) ($monthNames[(int) $defaultAnalysis['current_month']] ?? 'Mes atual'), ENT_QUOTES, 'UTF-8') ?></h5><div class="financial-month-status"><div><span class="financial-month-label">Realizado no mes</span><strong id="financialCurrentMonthTotal"><?= htmlspecialchars(\App\Services\FinancialDashboardService::formatMoney((float) $defaultAnalysis['current_month_total']), ENT_QUOTES, 'UTF-8') ?></strong></div><div><span class="financial-month-label">Valor esperado ate hoje</span><strong id="financialExpectedValue"><?= htmlspecialchars(\App\Services\FinancialDashboardService::formatMoney((float) $defaultAnalysis['expected_value']), ENT_QUOTES, 'UTF-8') ?></strong></div></div><div class="financial-indicator-box <?= (float) $defaultAnalysis['indicator'] > 0 ? 'financial-indicator-positive' : ((float) $defaultAnalysis['indicator'] < 0 ? 'financial-indicator-negative' : 'financial-indicator-neutral') ?>" id="financialIndicatorBox"><span class="financial-month-label">Valor indicador</span><strong id="financialIndicatorValue"><?= htmlspecialchars(\App\Services\FinancialDashboardService::formatMoney((float) $defaultAnalysis['indicator']), ENT_QUOTES, 'UTF-8') ?></strong></div></div></div>
                                <div class="card"><div class="card-body"><div class="small text-uppercase text-muted">Meses para promocoes</div><h5 class="mb-3">Sazonalidade sugerida</h5><div class="promotion-grid"><div><div class="promotion-title text-danger">Menores vendas</div><div id="financialLowestMonths"><?php foreach ($defaultAnalysis['lowest_months'] as $month): ?><div class="promotion-pill promotion-pill-low"><?= htmlspecialchars((string) $month['label'], ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?></div></div><div><div class="promotion-title text-success">Maiores vendas</div><div id="financialHighestMonths"><?php foreach ($defaultAnalysis['highest_months'] as $month): ?><div class="promotion-pill promotion-pill-high"><?= htmlspecialchars((string) $month['label'], ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?></div></div></div></div></div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="row text-center">
                    <div class="col-md-4 mb-3"><div class="card index-kpi-card"><div class="card-body"><h5><i class="fas fa-boxes text-primary"></i> Total Produtos</h5><h3><?= (int) ($resumo['total_produtos'] ?? 0) ?></h3></div></div></div>
                    <div class="col-md-4 mb-3"><div class="card index-kpi-card"><div class="card-body"><h5><i class="fas fa-tags text-success"></i> Em Estoque</h5><h3><?= (int) ($resumo['em_estoque'] ?? 0) ?></h3></div></div></div>
                    <div class="col-md-4 mb-3"><div class="card index-kpi-card"><div class="card-body"><h5><i class="fas fa-exclamation-triangle text-warning"></i> Baixo Estoque</h5><h3><?= (int) ($resumo['baixo_estoque'] ?? 0) ?></h3></div></div></div>
                </div>

                <div class="mt-4">
                    <h5 class="mb-3">Resumo rapido</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="card border-0 bg-light h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-2">Fluxos principais</div><p class="mb-2">Use o menu lateral para navegar rapidamente entre vendas, servicos, compras e operacoes de estoque.</p><p class="mb-0 text-muted">O novo resumo financeiro fica no topo do dashboard e o fluxo de caixa detalhado esta disponivel em pagina propria.</p></div></div></div>
                        <div class="col-md-6"><div class="card border-0 bg-light h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-2">Alertas de operacao</div><p class="mb-2">Produtos com estoque baixo merecem revisao mais cedo para evitar ruptura.</p><a href="<?= htmlspecialchars(app_url('logistica/inventario.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-warehouse me-1"></i>Ir para gestao de estoque</a></div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(app_url('assets/js/index_financial_summary.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

<?php include 'includes/footer.php'; ?>
