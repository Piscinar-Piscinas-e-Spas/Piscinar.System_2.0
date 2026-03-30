<?php
include '../includes/db.php';
require_login();

$repository = new \App\Repositories\InventarioLogRepository($pdo);
$service = new \App\Services\InventarioLogService($repository);
$logs = $service->listReports($_GET);
$logSelecionadoId = (int) ($_GET['id'] ?? 0);
$detalhe = $logSelecionadoId > 0 ? $service->getReportById($logSelecionadoId) : null;

$extraHeadContent = '
<link rel="stylesheet" href="' . htmlspecialchars(app_url('assets/css/logistica.css'), ENT_QUOTES, 'UTF-8') . '">
';

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h3 class="mb-1"><i class="bi bi-journal-richtext me-2 text-primary"></i>Relatorios de Inventario</h3>
            <p class="text-muted mb-0">Consulta interna dos eventos de balanco fisico salvos no servidor.</p>
        </div>
        <a href="<?= htmlspecialchars(app_url('logistica/inventario.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Voltar ao Inventario
        </a>
    </div>

    <div class="card border-0 shadow-sm logistics-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="inventario-log-local">Local</label>
                    <select id="inventario-log-local" name="local" class="form-select">
                        <option value="">Todos</option>
                        <option value="loja" <?= ($_GET['local'] ?? '') === 'loja' ? 'selected' : '' ?>>Loja</option>
                        <option value="barracao" <?= ($_GET['local'] ?? '') === 'barracao' ? 'selected' : '' ?>>Estoque Auxiliar</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="inventario-log-usuario">Usuario</label>
                    <input type="text" id="inventario-log-usuario" name="usuario" class="form-control" value="<?= htmlspecialchars((string) ($_GET['usuario'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome ou ID">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="inventario-log-data-inicial">Data inicial</label>
                    <input type="date" id="inventario-log-data-inicial" name="data_inicial" class="form-control" value="<?= htmlspecialchars((string) ($_GET['data_inicial'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="inventario-log-data-final">Data final</label>
                    <input type="date" id="inventario-log-data-final" name="data_final" class="form-control" value="<?= htmlspecialchars((string) ($_GET['data_final'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filtrar</button>
                    <a href="<?= htmlspecialchars(app_url('logistica/logs_inventario.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm logistics-card mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Local</th>
                            <th>Usuario</th>
                            <th>Itens</th>
                            <th>Alterados</th>
                            <th>Acao</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Nenhum relatorio encontrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($log['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($log['local_inventario'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) (($log['usuario_nome'] ?? '') !== '' ? $log['usuario_nome'] : ('ID ' . ($log['usuario_id'] ?? '-'))), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) ($log['quantidade_itens'] ?? 0) ?></td>
                                <td><?= (int) ($log['quantidade_itens_alterados'] ?? 0) ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars(app_url('logistica/logs_inventario.php?' . http_build_query(array_merge($_GET, ['id' => (int) $log['id_inventario_log']]))), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($detalhe): ?>
        <div class="card border-0 shadow-sm logistics-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Detalhe do Relatorio</h5>
                <?php if (empty($detalhe['error'])): ?>
                    <a href="<?= htmlspecialchars(app_url('logistica/exportar_log_inventario.php?id=' . (int) ($detalhe['meta']['id_inventario_log'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-download me-1"></i>Exportar JSON
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($detalhe['error'])): ?>
                    <div class="alert alert-danger mb-0"><?= htmlspecialchars((string) $detalhe['error'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php else: ?>
                    <?php $report = $detalhe['report']; ?>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3"><strong>Identificador:</strong><br><?= htmlspecialchars((string) ($report['identificador'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="col-md-3"><strong>Data/Hora:</strong><br><?= htmlspecialchars((string) ($report['data_hora'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="col-md-3"><strong>Local:</strong><br><?= htmlspecialchars((string) ($report['local'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="col-md-3"><strong>Usuario:</strong><br><?= htmlspecialchars((string) (($report['usuario']['nome'] ?? '') !== '' ? $report['usuario']['nome'] : ('ID ' . ($report['usuario']['id'] ?? '-'))), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3"><strong>Total de itens:</strong><br><?= (int) ($report['resumo']['total_itens'] ?? 0) ?></div>
                        <div class="col-md-3"><strong>Itens alterados:</strong><br><?= (int) ($report['resumo']['itens_alterados'] ?? 0) ?></div>
                        <div class="col-md-3"><strong>Diferencas positivas:</strong><br><?= htmlspecialchars((string) ($report['resumo']['soma_diferencas_positivas'] ?? 0), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="col-md-3"><strong>Diferencas negativas:</strong><br><?= htmlspecialchars((string) ($report['resumo']['soma_diferencas_negativas'] ?? 0), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="mb-3">
                        <strong>Filtros aplicados</strong>
                        <pre class="mb-0 small bg-light border rounded p-2"><?= htmlspecialchars(json_encode($report['filtros'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Produto</th>
                                    <th>Saldo anterior</th>
                                    <th>Saldo informado</th>
                                    <th>Diferenca</th>
                                    <th>Coluna</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($report['itens'] ?? []) as $item): ?>
                                    <tr>
                                        <td><?= (int) ($item['id_produto'] ?? 0) ?></td>
                                        <td><?= htmlspecialchars((string) ($item['produto_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($item['saldo_anterior'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($item['saldo_informado'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($item['diferenca'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($item['coluna_afetada'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
