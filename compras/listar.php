<?php
include '../includes/db.php';
require_login();

if (!function_exists('normalize_compra_date')) {
    function normalize_compra_date(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date instanceof \DateTime && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return '';
    }
}

if (!function_exists('format_compra_date')) {
    function format_compra_date(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        foreach (['Y-m-d', 'd/m/Y'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date instanceof \DateTime && $date->format($format) === $value) {
                return $date->format('d/m/Y');
            }
        }

        return $value;
    }
}

$filtrosDashboard = [
    'data_inicial' => normalize_compra_date($_GET['dash_data_inicial'] ?? ''),
    'data_final' => normalize_compra_date($_GET['dash_data_final'] ?? ''),
    'nome_fornecedor' => trim((string) ($_GET['dash_nome_fornecedor'] ?? '')),
    'condicao_pagamento' => trim((string) ($_GET['dash_condicao_pagamento'] ?? '')),
    'numero_nota' => trim((string) ($_GET['dash_numero_nota'] ?? '')),
    'valor_minimo' => trim((string) ($_GET['dash_valor_minimo'] ?? '')),
    'valor_maximo' => trim((string) ($_GET['dash_valor_maximo'] ?? '')),
];

$filtrosLista = [
    'data_inicial' => normalize_compra_date($_GET['lista_data_inicial'] ?? ''),
    'data_final' => normalize_compra_date($_GET['lista_data_final'] ?? ''),
    'nome_fornecedor' => trim((string) ($_GET['lista_nome_fornecedor'] ?? '')),
    'condicao_pagamento' => trim((string) ($_GET['lista_condicao_pagamento'] ?? '')),
    'numero_nota' => trim((string) ($_GET['lista_numero_nota'] ?? '')),
    'valor_minimo' => trim((string) ($_GET['lista_valor_minimo'] ?? '')),
    'valor_maximo' => trim((string) ($_GET['lista_valor_maximo'] ?? '')),
];

$repository = new \App\Repositories\CompraEntradaRepository($pdo);
$compras = $repository->listWithFornecedor($filtrosLista);
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
            <h4><i class="fas fa-file-invoice me-2"></i>Lista de Compras</h4>
            <a href="<?= app_url('compras/entrada.php'); ?>" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Nova
            </a>
        </div>

        <div class="card-body">
            <div class="card border-0 bg-light mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Dashboard de compras</h5>
                    <button class="btn btn-outline-primary btn-sm js-toggle-section" type="button" data-target="#dashboardComprasCollapse" aria-expanded="false">
                        <i class="fas fa-caret-down me-1"></i> Exibir / Recolher
                    </button>
                </div>
                <div id="dashboardComprasCollapse" class="d-none">
                    <div class="card-body p-3">
                        <form method="GET" class="mb-3">
                            <?php foreach ($filtrosLista as $campo => $valor): ?>
                                <input type="hidden" name="lista_<?= htmlspecialchars($campo, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endforeach; ?>
                            <div class="row g-2">
                                <div class="col-md-3"><label for="dash_data_inicial" class="form-label">Data inicial (Dashboard)</label><input type="text" id="dash_data_inicial" name="dash_data_inicial" class="form-control js-date-br" inputmode="numeric" maxlength="10" placeholder="dd/mm/aaaa" value="<?= htmlspecialchars(format_compra_date($filtrosDashboard['data_inicial']), ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="col-md-3"><label for="dash_data_final" class="form-label">Data final (Dashboard)</label><input type="text" id="dash_data_final" name="dash_data_final" class="form-control js-date-br" inputmode="numeric" maxlength="10" placeholder="dd/mm/aaaa" value="<?= htmlspecialchars(format_compra_date($filtrosDashboard['data_final']), ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="col-md-3"><label for="dash_condicao_pagamento" class="form-label">Condicao de pagamento</label><select id="dash_condicao_pagamento" name="dash_condicao_pagamento" class="form-select"><option value="">Todas</option><option value="vista" <?= $filtrosDashboard['condicao_pagamento'] === 'vista' ? 'selected' : '' ?>>A vista</option><option value="parcelado" <?= $filtrosDashboard['condicao_pagamento'] === 'parcelado' ? 'selected' : '' ?>>Parcelado</option></select></div>
                                <div class="col-md-3"><label for="dash_nome_fornecedor" class="form-label">Fornecedor</label><input type="text" id="dash_nome_fornecedor" name="dash_nome_fornecedor" class="form-control" placeholder="Razao social ou nome fantasia" value="<?= htmlspecialchars($filtrosDashboard['nome_fornecedor'], ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="col-md-3"><label for="dash_numero_nota" class="form-label">Numero da nota</label><input type="text" id="dash_numero_nota" name="dash_numero_nota" class="form-control" value="<?= htmlspecialchars($filtrosDashboard['numero_nota'], ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="col-md-3"><label for="dash_valor_minimo" class="form-label">Valor minimo</label><input type="number" step="0.01" min="0" id="dash_valor_minimo" name="dash_valor_minimo" class="form-control" value="<?= htmlspecialchars($filtrosDashboard['valor_minimo'], ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="col-md-3"><label for="dash_valor_maximo" class="form-label">Valor maximo</label><input type="number" step="0.01" min="0" id="dash_valor_maximo" name="dash_valor_maximo" class="form-control" value="<?= htmlspecialchars($filtrosDashboard['valor_maximo'], ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="col-md-3 d-flex align-items-end justify-content-end"><button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Filtrar dashboard</button></div>
                            </div>
                        </form>

                        <div class="row g-2 mb-0 dashboard-kpis-compact">
                            <div class="col-md-6 col-lg-3"><div class="card border-0 bg-white h-100"><div class="card-body p-3"><small class="text-uppercase text-muted">Total de compras</small><h5 class="mb-0 fw-bold"><?= number_format((int) ($resumoKpis['total_compras'] ?? 0), 0, ',', '.') ?></h5></div></div></div>
                            <div class="col-md-6 col-lg-3"><div class="card border-0 bg-white h-100"><div class="card-body p-3"><small class="text-uppercase text-muted">Total comprado</small><h5 class="mb-0 fw-bold">R$ <?= number_format((float) ($resumoKpis['total_comprado'] ?? 0), 2, ',', '.') ?></h5></div></div></div>
                            <div class="col-md-6 col-lg-3"><div class="card border-0 bg-white h-100"><div class="card-body p-3"><small class="text-uppercase text-muted">Ticket medio</small><h5 class="mb-0 fw-bold">R$ <?= number_format((float) ($resumoKpis['ticket_medio'] ?? 0), 2, ',', '.') ?></h5></div></div></div>
                            <div class="col-md-6 col-lg-3"><div class="card border-0 bg-white h-100"><div class="card-body p-3"><small class="text-uppercase text-muted">Parcelado vs a vista</small><div class="fw-semibold">Parcelado: R$ <?= number_format($totalParcelado, 2, ',', '.') ?> (<?= number_format($percentualParcelado, 1, ',', '.') ?>%)</div><div class="fw-semibold">A vista: R$ <?= number_format($totalVista, 2, ',', '.') ?> (<?= number_format($percentualVista, 1, ',', '.') ?>%)</div></div></div></div>
                        </div>

                        <div class="card border-0 bg-white mb-0 mt-3 d-none d-lg-block">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Evolucao do total de compras</h6>
                                    <small id="graficoComprasAgrupamentoInfo" class="text-muted"></small>
                                </div>
                                <div class="grafico-faturamento-wrapper">
                                    <canvas id="graficoCompras"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 bg-light mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros e lista de compras</h5>
                    <button class="btn btn-outline-primary btn-sm js-toggle-section" type="button" data-target="#listaComprasConteudo" aria-expanded="true">
                        <i class="fas fa-caret-down me-1"></i> Exibir / Recolher
                    </button>
                </div>
                <div id="listaComprasConteudo" class="card-body">
                    <form method="GET" class="mb-4">
                        <?php foreach ($filtrosDashboard as $campo => $valor): ?>
                            <input type="hidden" name="dash_<?= htmlspecialchars($campo, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endforeach; ?>
                        <div class="row g-3">
                            <div class="col-md-3"><label for="lista_data_inicial" class="form-label">Data inicial</label><input type="text" id="lista_data_inicial" name="lista_data_inicial" class="form-control js-date-br" inputmode="numeric" maxlength="10" placeholder="dd/mm/aaaa" value="<?= htmlspecialchars(format_compra_date($filtrosLista['data_inicial']), ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="col-md-3"><label for="lista_data_final" class="form-label">Data final</label><input type="text" id="lista_data_final" name="lista_data_final" class="form-control js-date-br" inputmode="numeric" maxlength="10" placeholder="dd/mm/aaaa" value="<?= htmlspecialchars(format_compra_date($filtrosLista['data_final']), ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="col-md-3"><label for="lista_condicao_pagamento" class="form-label">Condicao de pagamento</label><select id="lista_condicao_pagamento" name="lista_condicao_pagamento" class="form-select"><option value="">Todas</option><option value="vista" <?= $filtrosLista['condicao_pagamento'] === 'vista' ? 'selected' : '' ?>>A vista</option><option value="parcelado" <?= $filtrosLista['condicao_pagamento'] === 'parcelado' ? 'selected' : '' ?>>Parcelado</option></select></div>
                            <div class="col-md-3"><label for="lista_nome_fornecedor" class="form-label">Fornecedor</label><input type="text" id="lista_nome_fornecedor" name="lista_nome_fornecedor" class="form-control" placeholder="Razao social ou nome fantasia" value="<?= htmlspecialchars($filtrosLista['nome_fornecedor'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="col-md-3"><label for="lista_numero_nota" class="form-label">Numero da nota</label><input type="text" id="lista_numero_nota" name="lista_numero_nota" class="form-control" value="<?= htmlspecialchars($filtrosLista['numero_nota'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="col-md-3"><label for="lista_valor_minimo" class="form-label">Valor minimo</label><input type="number" step="0.01" min="0" id="lista_valor_minimo" name="lista_valor_minimo" class="form-control" value="<?= htmlspecialchars($filtrosLista['valor_minimo'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="col-md-3"><label for="lista_valor_maximo" class="form-label">Valor maximo</label><input type="number" step="0.01" min="0" id="lista_valor_maximo" name="lista_valor_maximo" class="form-control" value="<?= htmlspecialchars($filtrosLista['valor_maximo'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="col-md-3 d-flex align-items-end justify-content-end gap-2"><button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Filtrar lista</button><a href="<?= app_url('compras/listar.php'); ?>" class="btn btn-outline-secondary">Limpar</a></div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle js-lista-paginada" data-page-size="10">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID Compra</th>
                                    <th>Data entrada</th>
                                    <th>Numero nota</th>
                                    <th>Fornecedor</th>
                                    <th>Condicao</th>
                                    <th>Subtotal</th>
                                    <th>Frete</th>
                                    <th>Desconto</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($compras)): ?>
                                    <tr><td colspan="11" class="text-center text-muted">Nenhuma compra encontrada.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($compras as $compra): ?>
                                    <tr data-row-item>
                                        <td><?= str_pad((string) ((int) $compra['id_compra_entrada']), 6, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= htmlspecialchars(format_compra_date((string) ($compra['data_entrada'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($compra['numero_nota'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) (($compra['nome_fantasia'] ?? '') !== '' ? $compra['nome_fantasia'] : ($compra['razao_social'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($compra['condicao_pagamento'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>R$ <?= number_format((float) ($compra['subtotal_itens'] ?? 0), 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float) ($compra['valor_frete'] ?? 0), 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float) ($compra['valor_desconto'] ?? 0), 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float) ($compra['total_nota'] ?? 0), 2, ',', '.') ?></td>
                                        <td><?= htmlspecialchars((string) ($compra['status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="<?= app_url('compras/detalhes.php'); ?>?id=<?= (int) $compra['id_compra_entrada'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye me-1"></i>Ver</a>
                                                <form
                                                    method="POST"
                                                    action="<?= app_url('compras/excluir.php'); ?>"
                                                    class="d-inline-block m-0 js-firewall-form"
                                                    data-firewall-entity="compra_entrada"
                                                    data-firewall-intent="delete"
                                                    data-firewall-record-id="<?= (int) $compra['id_compra_entrada'] ?>"
                                                    data-firewall-label="excluir a compra #<?= str_pad((string) ((int) $compra['id_compra_entrada']), 6, '0', STR_PAD_LEFT) ?>"
                                                >
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="id" value="<?= (int) $compra['id_compra_entrada'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash me-1"></i>Excluir
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($compras)): ?>
                        <div class="lista-paginacao mt-3" data-pagination-controls>
                            <span class="text-muted small" data-pagination-status></span>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-action="more">Exibir mais 10</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-action="all">Exibir todas</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php render_action_firewall_modal(); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= app_url('assets/js/action_firewall.js'); ?>"></script>
<script>
    (function () {
        document.querySelectorAll('.js-toggle-section').forEach((button) => {
            button.addEventListener('click', function () {
                const target = document.querySelector(button.getAttribute('data-target'));
                if (target) {
                    target.classList.toggle('d-none');
                }
            });
        });

        document.querySelectorAll('.js-date-br').forEach((input) => {
            input.addEventListener('input', function () {
                let value = input.value.replace(/\D/g, '').slice(0, 8);
                if (value.length >= 5) {
                    value = value.replace(/(\d{2})(\d{2})(\d+)/, '$1/$2/$3');
                } else if (value.length >= 3) {
                    value = value.replace(/(\d{2})(\d+)/, '$1/$2');
                }
                input.value = value;
            });
        });

        document.querySelectorAll('.js-lista-paginada').forEach((table) => {
            const pageSize = Number(table.getAttribute('data-page-size') || 10);
            const rows = Array.from(table.querySelectorAll('tbody tr[data-row-item]'));
            const controls = table.closest('.card-body').querySelector('[data-pagination-controls]');
            if (!rows.length || !controls) {
                return;
            }

            const status = controls.querySelector('[data-pagination-status]');
            const moreBtn = controls.querySelector('[data-action="more"]');
            const allBtn = controls.querySelector('[data-action="all"]');
            let visible = pageSize;

            const render = () => {
                rows.forEach((row, index) => row.classList.toggle('d-none', index >= visible));
                status.textContent = `Exibindo ${Math.min(visible, rows.length)} de ${rows.length} registro(s).`;
                moreBtn.disabled = visible >= rows.length;
                allBtn.disabled = visible >= rows.length;
            };

            moreBtn.addEventListener('click', () => {
                visible += pageSize;
                render();
            });
            allBtn.addEventListener('click', () => {
                visible = rows.length;
                render();
            });
            render();
        });

        const canvas = document.getElementById('graficoCompras');
        const agrupamentoInfo = document.getElementById('graficoComprasAgrupamentoInfo');
        if (!canvas || !agrupamentoInfo) {
            return;
        }

        const filtros = new URLSearchParams({
            data_inicial: <?= json_encode($filtrosDashboard['data_inicial'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            data_final: <?= json_encode($filtrosDashboard['data_final'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            nome_fornecedor: <?= json_encode($filtrosDashboard['nome_fornecedor'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            condicao_pagamento: <?= json_encode($filtrosDashboard['condicao_pagamento'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            numero_nota: <?= json_encode($filtrosDashboard['numero_nota'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            valor_minimo: <?= json_encode($filtrosDashboard['valor_minimo'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            valor_maximo: <?= json_encode($filtrosDashboard['valor_maximo'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        });

        fetch(`<?= app_url('compras/dashboard_data.php'); ?>?${filtros.toString()}`, {
            headers: { 'Accept': 'application/json' }
        })
            .then((response) => response.json())
            .then((payload) => {
                if (!payload || payload.status !== true) {
                    throw new Error('Falha ao carregar dados do grafico.');
                }

                agrupamentoInfo.textContent = payload.agrupamento === 'mes' ? 'Agrupamento mensal' : 'Agrupamento diario';

                new Chart(canvas.getContext('2d'), {
                    type: payload.agrupamento === 'mes' ? 'bar' : 'line',
                    data: {
                        labels: payload.labels || [],
                        datasets: [
                            {
                                label: 'Total de compras (R$)',
                                data: payload.series?.total_compras || [],
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.25)',
                                borderWidth: 2,
                                fill: payload.agrupamento !== 'mes',
                                tension: 0.2,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Quantidade de compras',
                                data: payload.series?.quantidade_compras || [],
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
                        interaction: { mode: 'index', intersect: false },
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
                                grid: { drawOnChartArea: false },
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            })
            .catch((error) => {
                console.error(error);
                agrupamentoInfo.textContent = 'Nao foi possivel carregar o grafico.';
            });
    })();

    window.ActionFirewall && window.ActionFirewall.init({
        endpoint: <?= json_encode(app_url('includes/confirmar_senha.php'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        csrfToken: <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    });
</script>

<?php include '../includes/footer.php'; ?>



