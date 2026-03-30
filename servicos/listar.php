<?php
include '../includes/db.php';
require_login();
require_once __DIR__ . '/_infra.php';

servicos_ensure_schema($pdo);

if (!function_exists('normalize_dashboard_date')) {
    function normalize_dashboard_date(?string $value): string
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

if (!function_exists('format_dashboard_date')) {
    function format_dashboard_date(?string $value): string
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
    'data_inicial' => normalize_dashboard_date($_GET['dash_data_inicial'] ?? ''),
    'data_final' => normalize_dashboard_date($_GET['dash_data_final'] ?? ''),
    'nome_cliente' => trim((string) ($_GET['dash_nome_cliente'] ?? '')),
    'condicao_pagamento' => trim((string) ($_GET['dash_condicao_pagamento'] ?? '')),
    'valor_minimo' => trim((string) ($_GET['dash_valor_minimo'] ?? '')),
    'valor_maximo' => trim((string) ($_GET['dash_valor_maximo'] ?? '')),
];

$filtrosLista = [
    'data_inicial' => normalize_dashboard_date($_GET['lista_data_inicial'] ?? ''),
    'data_final' => normalize_dashboard_date($_GET['lista_data_final'] ?? ''),
    'nome_cliente' => trim((string) ($_GET['lista_nome_cliente'] ?? '')),
    'condicao_pagamento' => trim((string) ($_GET['lista_condicao_pagamento'] ?? '')),
    'valor_minimo' => trim((string) ($_GET['lista_valor_minimo'] ?? '')),
    'valor_maximo' => trim((string) ($_GET['lista_valor_maximo'] ?? '')),
];

$repository = new \App\Repositories\ServicoRepository($pdo);
$servicos = $repository->listWithCliente($filtrosLista);
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
            <h4><i class="fas fa-tools me-2"></i>Lista de ServiÃ§os</h4>
            <a href="<?= app_url('servicos/nova.php'); ?>" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Novo</a>
        </div>
        <div class="card-body">
            <div class="card border-0 bg-light mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Dashboard de serviÃ§os</h5>
                    <button class="btn btn-outline-primary btn-sm js-toggle-section" type="button" data-target="#dashboardServicosCollapse" aria-expanded="false">
                        <i class="fas fa-caret-down me-1"></i> Exibir / Recolher
                    </button>
                </div>
                <div id="dashboardServicosCollapse" class="d-none">
                    <div class="card-body p-3">
                        <form method="GET" class="mb-3">
                            <?php foreach ($filtrosLista as $campo => $valor): ?>
                                <input type="hidden" name="lista_<?= htmlspecialchars($campo) ?>" value="<?= htmlspecialchars($valor) ?>">
                            <?php endforeach; ?>

                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label for="dash_data_inicial" class="form-label">Data inicial (Dashboard)</label>
                                    <input type="text" id="dash_data_inicial" name="dash_data_inicial" class="form-control js-date-br" inputmode="numeric" maxlength="10" placeholder="dd/mm/aaaa" value="<?= htmlspecialchars(format_dashboard_date($filtrosDashboard['data_inicial'])) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="dash_data_final" class="form-label">Data final (Dashboard)</label>
                                    <input type="text" id="dash_data_final" name="dash_data_final" class="form-control js-date-br" inputmode="numeric" maxlength="10" placeholder="dd/mm/aaaa" value="<?= htmlspecialchars(format_dashboard_date($filtrosDashboard['data_final'])) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="dash_condicao_pagamento" class="form-label">CondiÃ§Ã£o de pagamento</label>
                                    <select id="dash_condicao_pagamento" name="dash_condicao_pagamento" class="form-select">
                                        <option value="">Todas</option>
                                        <option value="vista" <?= $filtrosDashboard['condicao_pagamento'] === 'vista' ? 'selected' : '' ?>>Ã€ vista</option>
                                        <option value="parcelado" <?= $filtrosDashboard['condicao_pagamento'] === 'parcelado' ? 'selected' : '' ?>>Parcelado</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="dash_nome_cliente" class="form-label">Nome do cliente</label>
                                    <input type="text" id="dash_nome_cliente" name="dash_nome_cliente" class="form-control" placeholder="Digite o nome" value="<?= htmlspecialchars($filtrosDashboard['nome_cliente']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="dash_valor_minimo" class="form-label">Valor mÃ­nimo</label>
                                    <input type="number" step="0.01" min="0" id="dash_valor_minimo" name="dash_valor_minimo" class="form-control" value="<?= htmlspecialchars($filtrosDashboard['valor_minimo']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="dash_valor_maximo" class="form-label">Valor mÃ¡ximo</label>
                                    <input type="number" step="0.01" min="0" id="dash_valor_maximo" name="dash_valor_maximo" class="form-control" value="<?= htmlspecialchars($filtrosDashboard['valor_maximo']) ?>">
                                </div>
                                <div class="col-md-6 d-flex align-items-end justify-content-end gap-2">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Filtrar dashboard</button>
                                    <a href="<?= app_url('servicos/listar.php') . '?' . http_build_query(array_filter([
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

                        <div class="row g-2 mb-0 dashboard-kpis-compact">
                            <div class="col-md-6 col-lg-3">
                                <div class="card border-0 bg-white h-100">
                                    <div class="card-body p-3">
                                        <small class="text-uppercase text-muted">Total de serviÃ§os</small>
                                        <h5 class="mb-0 fw-bold"><?= number_format((int) $resumoKpis['total_servicos'], 0, ',', '.') ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="card border-0 bg-white h-100">
                                    <div class="card-body p-3">
                                        <small class="text-uppercase text-muted">Faturamento bruto</small>
                                        <h5 class="mb-0 fw-bold">R$ <?= number_format((float) $resumoKpis['faturamento_bruto'], 2, ',', '.') ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="card border-0 bg-white h-100">
                                    <div class="card-body p-3">
                                        <small class="text-uppercase text-muted">Ticket mÃ©dio</small>
                                        <h5 class="mb-0 fw-bold">R$ <?= number_format((float) $resumoKpis['ticket_medio'], 2, ',', '.') ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="card border-0 bg-white h-100">
                                    <div class="card-body p-3">
                                        <small class="text-uppercase text-muted">Total parcelado vs Ã  vista</small>
                                        <div class="fw-semibold">Parcelado: R$ <?= number_format($totalParcelado, 2, ',', '.') ?> (<?= number_format($percentualParcelado, 1, ',', '.') ?>%)</div>
                                        <div class="fw-semibold">Ã€ vista: R$ <?= number_format($totalVista, 2, ',', '.') ?> (<?= number_format($percentualVista, 1, ',', '.') ?>%)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 bg-light mb-0">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros e lista de serviÃ§os</h5>
                    <button class="btn btn-outline-primary btn-sm js-toggle-section" type="button" data-target="#listaServicosConteudo" aria-expanded="true">
                        <i class="fas fa-caret-down me-1"></i> Exibir / Recolher
                    </button>
                </div>
                <div id="listaServicosConteudo" class="card-body">
                    <form method="GET" class="mb-4">
                        <?php foreach ($filtrosDashboard as $campo => $valor): ?>
                            <input type="hidden" name="dash_<?= htmlspecialchars($campo) ?>" value="<?= htmlspecialchars($valor) ?>">
                        <?php endforeach; ?>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="lista_data_inicial" class="form-label">Data inicial</label>
                                <input type="text" id="lista_data_inicial" name="lista_data_inicial" class="form-control js-date-br" inputmode="numeric" maxlength="10" placeholder="dd/mm/aaaa" value="<?= htmlspecialchars(format_dashboard_date($filtrosLista['data_inicial'])) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="lista_data_final" class="form-label">Data final</label>
                                <input type="text" id="lista_data_final" name="lista_data_final" class="form-control js-date-br" inputmode="numeric" maxlength="10" placeholder="dd/mm/aaaa" value="<?= htmlspecialchars(format_dashboard_date($filtrosLista['data_final'])) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="lista_condicao_pagamento" class="form-label">CondiÃ§Ã£o de pagamento</label>
                                <select id="lista_condicao_pagamento" name="lista_condicao_pagamento" class="form-select">
                                    <option value="">Todas</option>
                                    <option value="vista" <?= $filtrosLista['condicao_pagamento'] === 'vista' ? 'selected' : '' ?>>Ã€ vista</option>
                                    <option value="parcelado" <?= $filtrosLista['condicao_pagamento'] === 'parcelado' ? 'selected' : '' ?>>Parcelado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="lista_nome_cliente" class="form-label">Nome do cliente</label>
                                <input type="text" id="lista_nome_cliente" name="lista_nome_cliente" class="form-control" placeholder="Digite o nome" value="<?= htmlspecialchars($filtrosLista['nome_cliente']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="lista_valor_minimo" class="form-label">Valor mÃ­nimo</label>
                                <input type="number" step="0.01" min="0" id="lista_valor_minimo" name="lista_valor_minimo" class="form-control" value="<?= htmlspecialchars($filtrosLista['valor_minimo']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="lista_valor_maximo" class="form-label">Valor mÃ¡ximo</label>
                                <input type="number" step="0.01" min="0" id="lista_valor_maximo" name="lista_valor_maximo" class="form-control" value="<?= htmlspecialchars($filtrosLista['valor_maximo']) ?>">
                            </div>
                            <div class="col-md-6 d-flex align-items-end justify-content-end gap-2">
                                <button class="btn btn-primary"><i class="fas fa-search me-1"></i>Filtrar lista</button>
                                <a href="<?= app_url('servicos/listar.php') . '?' . http_build_query(array_filter([
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
                        <table class="table table-striped table-hover align-middle js-lista-paginada" data-page-size="10">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Vendedor</th>
                                    <th>CondiÃ§Ã£o</th>
                                    <th>Subtotal produtos</th>
                                    <th>Subtotal micro</th>
                                    <th>Desconto</th>
                                    <th>Frete</th>
                                    <th>Total</th>
                                    <th>AÃ§Ãµes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$servicos): ?>
                                    <tr><td colspan="11" class="text-center text-muted">Nenhum serviÃ§o encontrado.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($servicos as $servico): ?>
                                    <tr data-row-item>
                                        <td><?= str_pad((string) ((int) $servico['id_servico']), 6, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= htmlspecialchars(format_dashboard_date((string) $servico['data_servico'])) ?></td>
                                        <td><?= htmlspecialchars((string) ($servico['nome_cliente'] ?: 'Cliente nÃ£o vinculado')) ?></td>
                                        <td><?= htmlspecialchars((string) ($servico['vendedor_nome'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars((string) $servico['condicao_pagamento']) ?></td>
                                        <td>R$ <?= number_format((float) $servico['subtotal_produtos'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float) $servico['subtotal_microservicos'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float) $servico['desconto_total'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float) $servico['frete_total'], 2, ',', '.') ?></td>
                                        <td><strong>R$ <?= number_format((float) $servico['total_geral'], 2, ',', '.') ?></strong></td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="<?= app_url('servicos/detalhes.php?id=' . (int) $servico['id_servico']); ?>" class="btn btn-sm btn-outline-primary" title="Ver detalhes do serviÃ§o">
                                                    <i class="fas fa-eye me-1"></i>Ver
                                                </a>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-warning js-firewall-link"
                                                    data-firewall-entity="servico"
                                                    data-firewall-intent="edit"
                                                    data-firewall-record-id="<?= (int) $servico['id_servico'] ?>"
                                                    data-firewall-label="editar o serviÃ§o #<?= str_pad((string) ((int) $servico['id_servico']), 6, '0', STR_PAD_LEFT) ?>"
                                                    data-firewall-target-url="<?= app_url('servicos/nova.php'); ?>?id=<?= (int) $servico['id_servico'] ?>"
                                                >
                                                    <i class="fas fa-pen me-1"></i>Editar
                                                </button>
                                                <form
                                                    method="POST"
                                                    action="<?= app_url('servicos/excluir.php'); ?>"
                                                    class="d-inline-block m-0 js-firewall-form"
                                                    data-firewall-entity="servico"
                                                    data-firewall-intent="delete"
                                                    data-firewall-record-id="<?= (int) $servico['id_servico'] ?>"
                                                    data-firewall-label="excluir o serviÃ§o #<?= str_pad((string) ((int) $servico['id_servico']), 6, '0', STR_PAD_LEFT) ?>"
                                                >
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="id" value="<?= (int) $servico['id_servico'] ?>">
                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                    >
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
                    <?php if (!empty($servicos)): ?>
                        <div class="lista-paginacao mt-3" data-pagination-controls>
                            <span class="text-muted small" data-pagination-status></span>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-action="more">Exibir mais 10</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-action="all">Exibir todos</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php render_action_firewall_modal(); ?>

<script src="<?= app_url('assets/js/action_firewall.js'); ?>"></script>

<script>
    (function () {
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

        document.querySelectorAll('.js-date-br').forEach((input) => {
            input.addEventListener('input', () => {
                const digits = input.value.replace(/\D/g, '').slice(0, 8);
                let formatted = digits;

                if (digits.length > 4) {
                    formatted = `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
                } else if (digits.length > 2) {
                    formatted = `${digits.slice(0, 2)}/${digits.slice(2)}`;
                }

                input.value = formatted;
            });
        });

        document.querySelectorAll('.js-lista-paginada').forEach((table) => {
            const pageSize = Number.parseInt(table.getAttribute('data-page-size') || '10', 10);
            const rows = Array.from(table.querySelectorAll('tbody tr[data-row-item]'));
            const controls = table.closest('.card-body')?.querySelector('[data-pagination-controls]');
            const status = controls?.querySelector('[data-pagination-status]');
            const moreButton = controls?.querySelector('[data-action="more"]');
            const allButton = controls?.querySelector('[data-action="all"]');

            if (!rows.length || !controls || !status || !moreButton || !allButton) {
                return;
            }

            let visibleCount = Math.min(pageSize, rows.length);

            const renderRows = () => {
                rows.forEach((row, index) => {
                    row.classList.toggle('d-none', index >= visibleCount);
                });

                status.textContent = `Exibindo ${Math.min(visibleCount, rows.length)} de ${rows.length} serviÃƒÂ§os filtrados`;
                moreButton.classList.toggle('d-none', visibleCount >= rows.length);
                allButton.classList.toggle('d-none', visibleCount >= rows.length);
            };

            moreButton.addEventListener('click', () => {
                visibleCount = Math.min(visibleCount + pageSize, rows.length);
                renderRows();
            });

            allButton.addEventListener('click', () => {
                visibleCount = rows.length;
                renderRows();
            });

            renderRows();
        });

        const modalElement = document.getElementById('confirmacaoSenhaModal');
        if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return;
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const senhaInput = document.getElementById('confirmacaoSenhaInput');
        const feedback = document.getElementById('confirmacaoSenhaFeedback');
        const descricao = document.getElementById('confirmacaoSenhaDescricao');
        const confirmarBtn = document.getElementById('confirmacaoSenhaConfirmarBtn');
        const confirmarLabel = confirmarBtn ? confirmarBtn.querySelector('.js-btn-label') : null;
        const confirmarSpinner = confirmarBtn ? confirmarBtn.querySelector('.spinner-border') : null;
        const csrfToken = <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        let targetUrl = '';

        const setLoading = (loading) => {
            if (!confirmarBtn) {
                return;
            }

            confirmarBtn.disabled = loading;
            if (confirmarSpinner) {
                confirmarSpinner.classList.toggle('d-none', !loading);
            }
            if (confirmarLabel) {
                confirmarLabel.textContent = loading ? 'Validando...' : 'Confirmar';
            }
        };

        const resetModal = () => {
            if (senhaInput) {
                senhaInput.value = '';
            }
            if (feedback) {
                feedback.classList.add('d-none');
                feedback.textContent = '';
            }
            setLoading(false);
        };

        document.querySelectorAll('.js-auth-action').forEach((botao) => {
            botao.addEventListener('click', () => {
                targetUrl = botao.getAttribute('data-target-url') || '';
                const actionLabel = botao.getAttribute('data-action-label') || 'executar esta aÃ§Ã£o';
                if (descricao) {
                    descricao.textContent = `Digite sua senha para ${actionLabel}.`;
                }
                resetModal();
                modal.show();
                window.setTimeout(() => senhaInput && senhaInput.focus(), 200);
            });
        });

        modalElement.addEventListener('hidden.bs.modal', resetModal);

        if (!confirmarBtn) {
            return;
        }

        confirmarBtn.addEventListener('click', () => {
            const senha = senhaInput ? senhaInput.value : '';
            if (!senha) {
                if (feedback) {
                    feedback.classList.remove('d-none');
                    feedback.textContent = 'Informe sua senha para continuar.';
                }
                if (senhaInput) {
                    senhaInput.focus();
                }
                return;
            }

            setLoading(true);
            fetch('<?= app_url('includes/confirmar_senha.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    senha,
                    csrf_token: csrfToken
                })
            })
                .then((response) => response.json().catch(() => ({})))
                .then((payload) => {
                    if (!payload || payload.status !== true) {
                        const mensagem = payload && payload.mensagem ? payload.mensagem : 'NÃ£o foi possÃ­vel validar sua senha.';
                        throw new Error(mensagem);
                    }

                    if (targetUrl) {
                        window.location.href = targetUrl;
                    }
                })
                .catch((error) => {
                    if (feedback) {
                        feedback.classList.remove('d-none');
                        feedback.textContent = error.message || 'Falha ao validar senha.';
                    }
                })
                .finally(() => setLoading(false));
        });
    })();

    window.ActionFirewall && window.ActionFirewall.init({
        endpoint: <?= json_encode(app_url('includes/confirmar_senha.php'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        csrfToken: <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    });
</script>

<?php include '../includes/footer.php'; ?>



