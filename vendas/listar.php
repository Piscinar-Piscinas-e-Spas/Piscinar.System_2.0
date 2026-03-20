<?php
include '../includes/db.php';
require_login();

$filtrosDashboard = [
    'data_inicial' => trim((string) ($_GET['dash_data_inicial'] ?? '')),
    'data_final' => trim((string) ($_GET['dash_data_final'] ?? '')),
    'nome_cliente' => trim((string) ($_GET['dash_nome_cliente'] ?? '')),
    'condicao_pagamento' => trim((string) ($_GET['dash_condicao_pagamento'] ?? '')),
    'valor_minimo' => trim((string) ($_GET['dash_valor_minimo'] ?? '')),
    'valor_maximo' => trim((string) ($_GET['dash_valor_maximo'] ?? '')),
];

$filtrosLista = [
    'data_inicial' => trim((string) ($_GET['lista_data_inicial'] ?? '')),
    'data_final' => trim((string) ($_GET['lista_data_final'] ?? '')),
    'nome_cliente' => trim((string) ($_GET['lista_nome_cliente'] ?? '')),
    'condicao_pagamento' => trim((string) ($_GET['lista_condicao_pagamento'] ?? '')),
    'valor_minimo' => trim((string) ($_GET['lista_valor_minimo'] ?? '')),
    'valor_maximo' => trim((string) ($_GET['lista_valor_maximo'] ?? '')),
];

$repository = new \App\Repositories\VendaRepository($pdo);
$vendas = $repository->listWithCliente($filtrosLista);
$resumoKpis = $repository->getResumoKpis($filtrosDashboard);

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
            <div class="card border-0 bg-light mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Dashboard de vendas</h5>
                    <button
                        class="btn btn-outline-primary btn-sm js-toggle-section"
                        type="button"
                        data-target="#dashboardVendasCollapse"
                        aria-expanded="true"
                    >
                        <i class="fas fa-caret-down me-1"></i> Exibir / Recolher
                    </button>
                </div>
                <div id="dashboardVendasCollapse" class="collapse show">
                    <div class="card-body">
                    <form method="GET" class="mb-4">
                        <?php foreach ($filtrosLista as $campo => $valor): ?>
                            <input type="hidden" name="lista_<?= htmlspecialchars($campo) ?>" value="<?= htmlspecialchars($valor) ?>">
                        <?php endforeach; ?>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="dash_data_inicial" class="form-label">Data inicial (Dashboard)</label>
                                <input type="date" id="dash_data_inicial" name="dash_data_inicial" class="form-control" value="<?= htmlspecialchars($filtrosDashboard['data_inicial']) ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="dash_data_final" class="form-label">Data final (Dashboard)</label>
                                <input type="date" id="dash_data_final" name="dash_data_final" class="form-control" value="<?= htmlspecialchars($filtrosDashboard['data_final']) ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="dash_condicao_pagamento" class="form-label">Condição de pagamento</label>
                                <select id="dash_condicao_pagamento" name="dash_condicao_pagamento" class="form-select">
                                    <option value="">Todas</option>
                                    <option value="vista" <?= $filtrosDashboard['condicao_pagamento'] === 'vista' ? 'selected' : '' ?>>À vista</option>
                                    <option value="parcelado" <?= $filtrosDashboard['condicao_pagamento'] === 'parcelado' ? 'selected' : '' ?>>Parcelado</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="dash_nome_cliente" class="form-label">Nome do cliente</label>
                                <input type="text" id="dash_nome_cliente" name="dash_nome_cliente" class="form-control" placeholder="Digite o nome" value="<?= htmlspecialchars($filtrosDashboard['nome_cliente']) ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="dash_valor_minimo" class="form-label">Valor mínimo</label>
                                <input type="number" step="0.01" min="0" id="dash_valor_minimo" name="dash_valor_minimo" class="form-control" value="<?= htmlspecialchars($filtrosDashboard['valor_minimo']) ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="dash_valor_maximo" class="form-label">Valor máximo</label>
                                <input type="number" step="0.01" min="0" id="dash_valor_maximo" name="dash_valor_maximo" class="form-control" value="<?= htmlspecialchars($filtrosDashboard['valor_maximo']) ?>">
                            </div>

                            <div class="col-md-6 d-flex align-items-end justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i> Filtrar dashboard
                                </button>
                                <a href="<?= app_url('vendas/listar.php') . '?' . http_build_query(array_filter([
                                    'lista_data_inicial' => $filtrosLista['data_inicial'],
                                    'lista_data_final' => $filtrosLista['data_final'],
                                    'lista_nome_cliente' => $filtrosLista['nome_cliente'],
                                    'lista_condicao_pagamento' => $filtrosLista['condicao_pagamento'],
                                    'lista_valor_minimo' => $filtrosLista['valor_minimo'],
                                    'lista_valor_maximo' => $filtrosLista['valor_maximo'],
                                ], static fn ($v) => $v !== '')); ?>" class="btn btn-outline-secondary">Limpar dashboard</a>
                            </div>
                        </div>
                    </form>

                    <div class="row g-3 mb-0">
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 bg-white h-100">
                                <div class="card-body">
                                    <small class="text-uppercase text-muted">Total de vendas</small>
                                    <h3 class="mb-0"><?= number_format((int) $resumoKpis['total_vendas'], 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 bg-white h-100">
                                <div class="card-body">
                                    <small class="text-uppercase text-muted">Faturamento bruto</small>
                                    <h3 class="mb-0">R$ <?= number_format((float) $resumoKpis['faturamento_bruto'], 2, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 bg-white h-100">
                                <div class="card-body">
                                    <small class="text-uppercase text-muted">Ticket médio</small>
                                    <h3 class="mb-0">R$ <?= number_format((float) $resumoKpis['ticket_medio'], 2, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 bg-white h-100">
                                <div class="card-body">
                                    <small class="text-uppercase text-muted">Total parcelado vs à vista</small>
                                    <div class="fw-semibold">Parcelado: R$ <?= number_format($totalParcelado, 2, ',', '.') ?> (<?= number_format($percentualParcelado, 1, ',', '.') ?>%)</div>
                                    <div class="fw-semibold">À vista: R$ <?= number_format($totalVista, 2, ',', '.') ?> (<?= number_format($percentualVista, 1, ',', '.') ?>%)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 bg-white mb-0 mt-3 d-none d-lg-block">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">Evolução do faturamento</h5>
                                <small id="graficoAgrupamentoInfo" class="text-muted"></small>
                            </div>
                            <div class="grafico-faturamento-wrapper">
                                <canvas id="graficoFaturamentoVendas"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 bg-light mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros e lista de vendas</h5>
                    <button
                        class="btn btn-outline-primary btn-sm js-toggle-section"
                        type="button"
                        data-target="#listaVendasConteudo"
                        aria-expanded="true"
                    >
                        <i class="fas fa-caret-down me-1"></i> Exibir / Recolher
                    </button>
                </div>
                <div id="listaVendasConteudo" class="card-body">
                    <form method="GET" class="mb-4">
                        <?php foreach ($filtrosDashboard as $campo => $valor): ?>
                            <input type="hidden" name="dash_<?= htmlspecialchars($campo) ?>" value="<?= htmlspecialchars($valor) ?>">
                        <?php endforeach; ?>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="lista_data_inicial" class="form-label">Data inicial</label>
                                <input type="date" id="lista_data_inicial" name="lista_data_inicial" class="form-control" value="<?= htmlspecialchars($filtrosLista['data_inicial']) ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="lista_data_final" class="form-label">Data final</label>
                                <input type="date" id="lista_data_final" name="lista_data_final" class="form-control" value="<?= htmlspecialchars($filtrosLista['data_final']) ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="lista_condicao_pagamento" class="form-label">Condição de pagamento</label>
                                <select id="lista_condicao_pagamento" name="lista_condicao_pagamento" class="form-select">
                                    <option value="">Todas</option>
                                    <option value="vista" <?= $filtrosLista['condicao_pagamento'] === 'vista' ? 'selected' : '' ?>>À vista</option>
                                    <option value="parcelado" <?= $filtrosLista['condicao_pagamento'] === 'parcelado' ? 'selected' : '' ?>>Parcelado</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="lista_nome_cliente" class="form-label">Nome do cliente</label>
                                <input type="text" id="lista_nome_cliente" name="lista_nome_cliente" class="form-control" placeholder="Digite o nome" value="<?= htmlspecialchars($filtrosLista['nome_cliente']) ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="lista_valor_minimo" class="form-label">Valor mínimo</label>
                                <input type="number" step="0.01" min="0" id="lista_valor_minimo" name="lista_valor_minimo" class="form-control" value="<?= htmlspecialchars($filtrosLista['valor_minimo']) ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="lista_valor_maximo" class="form-label">Valor máximo</label>
                                <input type="number" step="0.01" min="0" id="lista_valor_maximo" name="lista_valor_maximo" class="form-control" value="<?= htmlspecialchars($filtrosLista['valor_maximo']) ?>">
                            </div>

                            <div class="col-md-6 d-flex align-items-end justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i> Filtrar lista
                                </button>
                                <a href="<?= app_url('vendas/listar.php') . '?' . http_build_query(array_filter([
                                    'dash_data_inicial' => $filtrosDashboard['data_inicial'],
                                    'dash_data_final' => $filtrosDashboard['data_final'],
                                    'dash_nome_cliente' => $filtrosDashboard['nome_cliente'],
                                    'dash_condicao_pagamento' => $filtrosDashboard['condicao_pagamento'],
                                    'dash_valor_minimo' => $filtrosDashboard['valor_minimo'],
                                    'dash_valor_maximo' => $filtrosDashboard['valor_maximo'],
                                ], static fn ($v) => $v !== '')); ?>" class="btn btn-outline-secondary">Limpar lista</a>
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
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vendas)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">Nenhuma venda encontrada.</td>
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
                                        <td>
                                            <a
                                                href="<?= app_url('vendas/detalhes.php'); ?>?id=<?= (int) $venda['id_venda'] ?>"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Ver detalhes da venda"
                                            >
                                                <i class="fas fa-eye me-1"></i>Ver
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const graficoCanvas = document.getElementById('graficoFaturamentoVendas');
        const agrupamentoInfo = document.getElementById('graficoAgrupamentoInfo');
        const dashboardFiltros = <?= json_encode($filtrosDashboard, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        document.querySelectorAll('.js-toggle-section').forEach((botao) => {
            botao.addEventListener('click', () => {
                const alvo = document.querySelector(botao.getAttribute('data-target'));
                if (!alvo) {
                    return;
                }

                const expandido = botao.getAttribute('aria-expanded') === 'true';
                botao.setAttribute('aria-expanded', expandido ? 'false' : 'true');
                alvo.classList.toggle('d-none', expandido);
            });
        });

        if (!window.matchMedia('(min-width: 992px)').matches || !graficoCanvas || typeof Chart === 'undefined') {
            return;
        }

        const filtros = new URLSearchParams();

        Object.entries(dashboardFiltros).forEach(([chave, valor]) => {
            if (typeof valor === 'string' && valor.trim() !== '') {
                filtros.set(chave, valor.trim());
            }
        });

        const endpoint = `<?= app_url('vendas/dashboard_data.php'); ?>?${filtros.toString()}`;

        fetch(endpoint, {
            headers: {
                'Accept': 'application/json'
            }
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Falha ao carregar dados do gráfico.');
                }
                return response.json();
            })
            .then((payload) => {
                if (!payload || payload.status !== true || !Array.isArray(payload.labels)) {
                    throw new Error('Resposta inválida do servidor.');
                }

                const agrupamento = payload.agrupamento === 'mes' ? 'mensal' : 'diário';
                agrupamentoInfo.textContent = `Agrupamento ${agrupamento}`;

                const tipoGrafico = payload.agrupamento === 'mes' ? 'bar' : 'line';

                new Chart(graficoCanvas.getContext('2d'), {
                    type: tipoGrafico,
                    data: {
                        labels: payload.labels,
                        datasets: [
                            {
                                label: 'Faturamento (R$)',
                                data: payload.series?.faturamento || [],
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.25)',
                                borderWidth: 2,
                                fill: tipoGrafico === 'line',
                                tension: 0.2,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Quantidade de vendas',
                                data: payload.series?.quantidade_vendas || [],
                                borderColor: '#198754',
                                backgroundColor: 'rgba(25, 135, 84, 0.35)',
                                borderWidth: 2,
                                type: 'line',
                                tension: 0.2,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            y: {
                                position: 'left',
                                beginAtZero: true,
                                ticks: {
                                    callback: (value) => `R$ ${Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                                }
                            },
                            y1: {
                                position: 'right',
                                beginAtZero: true,
                                grid: {
                                    drawOnChartArea: false
                                },
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        if (context.dataset.label === 'Faturamento (R$)') {
                                            return `${context.dataset.label}: R$ ${Number(context.raw).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                                        }
                                        return `${context.dataset.label}: ${context.raw}`;
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch((error) => {
                console.error(error);
                agrupamentoInfo.textContent = 'Não foi possível carregar o gráfico.';
            });
    })();
</script>

<?php include '../includes/footer.php'; ?>
