<?php
include '../includes/db.php';
require_login();
require_once __DIR__ . '/_infra.php';

servicos_ensure_schema($pdo);

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
            <h4><i class="fas fa-tools me-2"></i>Lista de Serviços</h4>
            <a href="<?= app_url('servicos/nova.php'); ?>" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Novo</a>
        </div>
        <div class="card-body">
            <div class="card border-0 bg-light mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Dashboard de serviços</h5>
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
                                        <small class="text-uppercase text-muted">Total de serviços</small>
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
                                        <small class="text-uppercase text-muted">Ticket médio</small>
                                        <h5 class="mb-0 fw-bold">R$ <?= number_format((float) $resumoKpis['ticket_medio'], 2, ',', '.') ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="card border-0 bg-white h-100">
                                    <div class="card-body p-3">
                                        <small class="text-uppercase text-muted">Total parcelado vs à vista</small>
                                        <div class="fw-semibold">Parcelado: R$ <?= number_format($totalParcelado, 2, ',', '.') ?> (<?= number_format($percentualParcelado, 1, ',', '.') ?>%)</div>
                                        <div class="fw-semibold">À vista: R$ <?= number_format($totalVista, 2, ',', '.') ?> (<?= number_format($percentualVista, 1, ',', '.') ?>%)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 bg-light mb-0">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros e lista de serviços</h5>
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
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Condição</th>
                                    <th>Subtotal produtos</th>
                                    <th>Subtotal micro</th>
                                    <th>Desconto</th>
                                    <th>Frete</th>
                                    <th>Total</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$servicos): ?>
                                    <tr><td colspan="10" class="text-center text-muted">Nenhum serviço encontrado.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($servicos as $servico): ?>
                                    <tr>
                                        <td><?= str_pad((string) ((int) $servico['id_servico']), 6, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= htmlspecialchars((string) $servico['data_servico']) ?></td>
                                        <td><?= htmlspecialchars((string) ($servico['nome_cliente'] ?: 'Cliente não vinculado')) ?></td>
                                        <td><?= htmlspecialchars((string) $servico['condicao_pagamento']) ?></td>
                                        <td>R$ <?= number_format((float) $servico['subtotal_produtos'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float) $servico['subtotal_microservicos'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float) $servico['desconto_total'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float) $servico['frete_total'], 2, ',', '.') ?></td>
                                        <td><strong>R$ <?= number_format((float) $servico['total_geral'], 2, ',', '.') ?></strong></td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="<?= app_url('servicos/detalhes.php?id=' . (int) $servico['id_servico']); ?>" class="btn btn-sm btn-outline-primary" title="Ver detalhes do serviço">
                                                    <i class="fas fa-eye me-1"></i>Ver
                                                </a>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-warning js-auth-action"
                                                    data-action-label="editar o serviço #<?= str_pad((string) ((int) $servico['id_servico']), 6, '0', STR_PAD_LEFT) ?>"
                                                    data-target-url="<?= app_url('servicos/nova.php'); ?>?id=<?= (int) $servico['id_servico'] ?>"
                                                >
                                                    <i class="fas fa-pen me-1"></i>Editar
                                                </button>
                                                <form method="POST" action="<?= app_url('servicos/excluir.php'); ?>" class="d-inline-block m-0">
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
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmacaoSenhaModal" tabindex="-1" aria-labelledby="confirmacaoSenhaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmacaoSenhaModalLabel">Confirmar senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" id="confirmacaoSenhaDescricao">Digite sua senha para continuar.</p>
                <div id="confirmacaoSenhaFeedback" class="alert alert-danger d-none py-2" role="alert"></div>
                <label for="confirmacaoSenhaInput" class="form-label">Senha do usuário</label>
                <input type="password" id="confirmacaoSenhaInput" class="form-control" autocomplete="current-password" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmacaoSenhaConfirmarBtn">
                    <span class="js-btn-label">Confirmar</span>
                    <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

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
                const actionLabel = botao.getAttribute('data-action-label') || 'executar esta ação';
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
                        const mensagem = payload && payload.mensagem ? payload.mensagem : 'Não foi possível validar sua senha.';
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
</script>

<?php include '../includes/footer.php'; ?>
