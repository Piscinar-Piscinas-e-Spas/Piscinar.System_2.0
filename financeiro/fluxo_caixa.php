<?php
include '../includes/db.php';
require_login();

$financeiroRepository = new \App\Repositories\FinanceiroRepository($pdo);
$today = new DateTimeImmutable('today');

$mesSelecionado = max(1, min(12, (int) ($_GET['mes'] ?? $today->format('n'))));
$anoSelecionado = max(2000, (int) ($_GET['ano'] ?? $today->format('Y')));
$origemSelecionada = (string) ($_GET['origem'] ?? 'todas');
$statusSelecionado = (string) ($_GET['status'] ?? 'todas');

$allowedOrigins = ['todas', 'vendas', 'servicos', 'compras'];
$allowedStatus = ['todas', 'abertas', 'pagas'];

if (!in_array($origemSelecionada, $allowedOrigins, true)) {
    $origemSelecionada = 'todas';
}

if (!in_array($statusSelecionado, $allowedStatus, true)) {
    $statusSelecionado = 'todas';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $source = (string) ($_POST['origem'] ?? '');
    $parcelaId = (int) ($_POST['parcela_id'] ?? 0);
    $action = (string) ($_POST['acao'] ?? '');
    $paymentDate = trim((string) ($_POST['data_pagamento'] ?? ''));
    $mesSelecionado = max(1, min(12, (int) ($_POST['filtro_mes'] ?? $mesSelecionado)));
    $anoSelecionado = max(2000, (int) ($_POST['filtro_ano'] ?? $anoSelecionado));
    $origemSelecionada = (string) ($_POST['filtro_origem'] ?? $origemSelecionada);
    $statusSelecionado = (string) ($_POST['filtro_status'] ?? $statusSelecionado);

    if (!in_array($origemSelecionada, $allowedOrigins, true)) {
        $origemSelecionada = 'todas';
    }

    if (!in_array($statusSelecionado, $allowedStatus, true)) {
        $statusSelecionado = 'todas';
    }

    try {
        if ($parcelaId <= 0) {
            throw new RuntimeException('Parcela invalida.');
        }

        if ($action === 'baixar') {
            $financeiroRepository->updateParcelaPayment($source, $parcelaId, $paymentDate !== '' ? $paymentDate : $today->format('Y-m-d'));
            $status = 'pagamento_salvo';
        } elseif ($action === 'reabrir') {
            $financeiroRepository->updateParcelaPayment($source, $parcelaId, null);
            $status = 'pagamento_removido';
        } else {
            throw new RuntimeException('Acao invalida.');
        }
    } catch (Throwable $e) {
        $status = 'erro';
        $errorMessage = $e->getMessage();
    }

    $redirectParams = [
        'mes' => $mesSelecionado,
        'ano' => $anoSelecionado,
        'origem' => $origemSelecionada,
        'status' => $statusSelecionado,
        'resultado' => $status,
    ];

    if (!empty($errorMessage ?? '')) {
        $redirectParams['mensagem'] = $errorMessage;
    }

    header('Location: ' . app_url('financeiro/fluxo_caixa.php?' . http_build_query($redirectParams)));
    exit;
}

$filtros = [
    'mes' => $mesSelecionado,
    'ano' => $anoSelecionado,
    'origem' => $origemSelecionada,
    'status' => $statusSelecionado,
];

$linhas = $financeiroRepository->getFluxoCaixaRows($filtros);
$resumo = $financeiroRepository->summarizeFluxoCaixa($linhas);
$anosDisponiveis = $financeiroRepository->getAvailableFluxoYears();
$sourceLabels = $financeiroRepository->getSourceLabels();

if (empty($anosDisponiveis)) {
    $anosDisponiveis = [$anoSelecionado];
}

$meses = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Marco',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro',
];

function fluxo_caixa_money(float $value): string
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function fluxo_caixa_date(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '-';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    return $date ? $date->format('d/m/Y') : $value;
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <?php
    $resultado = (string) ($_GET['resultado'] ?? '');
    $mensagem = trim((string) ($_GET['mensagem'] ?? ''));
    if ($resultado !== ''):
        $alertClass = match ($resultado) {
            'pagamento_salvo', 'pagamento_removido' => 'alert-success',
            default => 'alert-danger',
        };
        $alertText = match ($resultado) {
            'pagamento_salvo' => 'Pagamento registrado com sucesso.',
            'pagamento_removido' => 'Baixa removida com sucesso.',
            default => ($mensagem !== '' ? $mensagem : 'Nao foi possivel atualizar a parcela.'),
        };
    ?>
        <div class="alert <?= $alertClass ?>"><?= htmlspecialchars($alertText, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-0"><i class="fas fa-wallet me-2"></i>Fluxo de Caixa</h4>
                <small class="text-muted">Baixa simples de parcelas de vendas, servicos e compras.</small>
            </div>
            <a href="<?= htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Voltar ao dashboard
            </a>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end mb-4">
                <div class="col-md-3">
                    <label for="fluxo-mes" class="form-label">Mes</label>
                    <select id="fluxo-mes" name="mes" class="form-select">
                        <?php foreach ($meses as $numero => $nome): ?>
                            <option value="<?= $numero ?>" <?= $numero === $mesSelecionado ? 'selected' : '' ?>><?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fluxo-ano" class="form-label">Ano</label>
                    <select id="fluxo-ano" name="ano" class="form-select">
                        <?php foreach ($anosDisponiveis as $ano): ?>
                            <option value="<?= (int) $ano ?>" <?= (int) $ano === $anoSelecionado ? 'selected' : '' ?>><?= (int) $ano ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fluxo-origem" class="form-label">Origem</label>
                    <select id="fluxo-origem" name="origem" class="form-select">
                        <option value="todas" <?= $origemSelecionada === 'todas' ? 'selected' : '' ?>>Todas</option>
                        <?php foreach ($sourceLabels as $sourceKey => $label): ?>
                            <option value="<?= htmlspecialchars($sourceKey, ENT_QUOTES, 'UTF-8') ?>" <?= $origemSelecionada === $sourceKey ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fluxo-status" class="form-label">Status</label>
                    <select id="fluxo-status" name="status" class="form-select">
                        <option value="todas" <?= $statusSelecionado === 'todas' ? 'selected' : '' ?>>Todas</option>
                        <option value="abertas" <?= $statusSelecionado === 'abertas' ? 'selected' : '' ?>>Em aberto</option>
                        <option value="pagas" <?= $statusSelecionado === 'pagas' ? 'selected' : '' ?>>Pagas</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>Aplicar filtros
                    </button>
                    <a href="<?= htmlspecialchars(app_url('financeiro/fluxo_caixa.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-primary h-100">
                        <div class="card-body">
                            <small class="text-uppercase text-muted">Receitas previstas</small>
                            <div class="fs-4 fw-semibold text-primary"><?= fluxo_caixa_money((float) $resumo['receitas_previstas']) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success h-100">
                        <div class="card-body">
                            <small class="text-uppercase text-muted">Recebido</small>
                            <div class="fs-4 fw-semibold text-success"><?= fluxo_caixa_money((float) $resumo['receitas_recebidas']) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-danger h-100">
                        <div class="card-body">
                            <small class="text-uppercase text-muted">Despesas previstas</small>
                            <div class="fs-4 fw-semibold text-danger"><?= fluxo_caixa_money((float) $resumo['despesas_previstas']) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card <?= ((float) $resumo['saldo_projetado']) >= 0 ? 'border-success' : 'border-danger' ?> h-100">
                        <div class="card-body">
                            <small class="text-uppercase text-muted">Saldo projetado</small>
                            <div class="fs-4 fw-semibold <?= ((float) $resumo['saldo_projetado']) >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= fluxo_caixa_money((float) $resumo['saldo_projetado']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Origem</th>
                            <th>Documento</th>
                            <th>Contraparte</th>
                            <th>Parcela</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Tipo</th>
                            <th>Pagamento</th>
                            <th>Status</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($linhas)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">Nenhuma parcela encontrada para os filtros atuais.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($linhas as $linha): ?>
                            <?php
                            $origem = (string) ($linha['origem'] ?? '');
                            $isPaid = !empty($linha['data_pagamento']);
                            $badgeClass = match ($origem) {
                                'vendas' => 'bg-primary',
                                'servicos' => 'bg-info text-dark',
                                'compras' => 'bg-warning text-dark',
                                default => 'bg-secondary',
                            };
                            ?>
                            <tr>
                                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($sourceLabels[$origem] ?? ucfirst($origem), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) ($linha['documento_codigo'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <small class="text-muted"><?= fluxo_caixa_date((string) ($linha['data_documento'] ?? '')) ?></small>
                                </td>
                                <td><?= htmlspecialchars((string) ($linha['contraparte'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) ($linha['numero_parcela'] ?? 0) ?>/<?= (int) ($linha['qtd_parcelas'] ?? 1) ?></td>
                                <td><?= fluxo_caixa_date((string) ($linha['vencimento'] ?? '')) ?></td>
                                <td><?= fluxo_caixa_money((float) ($linha['valor_parcela'] ?? 0)) ?></td>
                                <td><?= htmlspecialchars((string) ($linha['tipo_pagamento'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= fluxo_caixa_date((string) ($linha['data_pagamento'] ?? '')) ?></td>
                                <td>
                                    <span class="badge <?= $isPaid ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $isPaid ? 'Paga' : 'Em aberto' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" class="d-flex flex-column gap-2">
                                        <input type="hidden" name="origem" value="<?= htmlspecialchars($origem, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="parcela_id" value="<?= (int) ($linha['parcela_id'] ?? 0) ?>">
                                        <input type="hidden" name="filtro_mes" value="<?= $mesSelecionado ?>">
                                        <input type="hidden" name="filtro_ano" value="<?= $anoSelecionado ?>">
                                        <input type="hidden" name="filtro_origem" value="<?= htmlspecialchars($origemSelecionada, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="filtro_status" value="<?= htmlspecialchars($statusSelecionado, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="date" name="data_pagamento" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($linha['data_pagamento'] ?? $today->format('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="acao" value="baixar" class="btn btn-sm btn-success">Baixar</button>
                                            <?php if ($isPaid): ?>
                                                <button type="submit" name="acao" value="reabrir" class="btn btn-sm btn-outline-secondary">Reabrir</button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
