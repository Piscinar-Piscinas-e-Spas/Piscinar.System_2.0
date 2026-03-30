<?php
include '../includes/db.php';
require_login();

$controller = new \App\Controllers\AuditoriaLogController($pdo);
$viewData = $controller->list($_GET);
$logs = $viewData['logs'];
$entidades = $viewData['entidades'];

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-clipboard-list me-2"></i>Consulta de Logs</h4>
            <span class="badge bg-secondary"><?= count($logs) ?> registro(s)</span>
        </div>

        <div class="card-body">
            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label visually-hidden" for="logs-filtro-entidade">Entidade</label>
                        <select id="logs-filtro-entidade" name="entidade" class="form-select">
                            <option value="">Todas as entidades</option>
                            <?php foreach ($entidades as $entidade): ?>
                                <option value="<?= htmlspecialchars((string) $entidade, ENT_QUOTES, 'UTF-8') ?>" <?= (($entidade ?? '') === ($_GET['entidade'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst((string) $entidade), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label visually-hidden" for="logs-filtro-acao">Ação</label>
                        <select id="logs-filtro-acao" name="acao" class="form-select">
                            <option value="">Todas as ações</option>
                            <?php foreach (['create' => 'Cadastro', 'update' => 'Atualização', 'delete' => 'Exclusão'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= (($value ?? '') === ($_GET['acao'] ?? '')) ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label visually-hidden" for="logs-filtro-usuario">Usuário</label>
                        <input type="text" id="logs-filtro-usuario" name="usuario" class="form-control" placeholder="Usuário" value="<?= htmlspecialchars((string) ($_GET['usuario'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label visually-hidden" for="logs-filtro-id-registro">ID do registro</label>
                        <input type="text" id="logs-filtro-id-registro" name="id_registro" class="form-control" placeholder="ID do registro" value="<?= htmlspecialchars((string) ($_GET['id_registro'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label visually-hidden" for="logs-filtro-data-inicial">Data inicial</label>
                        <input type="date" id="logs-filtro-data-inicial" name="data_inicial" class="form-control" value="<?= htmlspecialchars((string) ($_GET['data_inicial'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label visually-hidden" for="logs-filtro-data-final">Data final</label>
                        <input type="date" id="logs-filtro-data-final" name="data_final" class="form-control" value="<?= htmlspecialchars((string) ($_GET['data_final'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Pesquisar
                        </button>
                        <a href="<?= app_url('logs/listar.php') ?>" class="btn btn-secondary">Limpar</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Ação</th>
                            <th>Entidade</th>
                            <th>ID Registro</th>
                            <th>Usuário</th>
                            <th>Campos Alterados</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Nenhum log encontrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($logs as $log): ?>
                            <?php
                            $acao = (string) ($log['acao'] ?? '');
                            $badgeClass = 'bg-secondary';

                            if ($acao === 'create') {
                                $badgeClass = 'bg-success';
                            } elseif ($acao === 'update') {
                                $badgeClass = 'bg-primary';
                            } elseif ($acao === 'delete') {
                                $badgeClass = 'bg-danger';
                            }

                            $usuario = trim((string) ($log['usuario_nome'] ?? ''));
                            if ($usuario === '') {
                                $usuario = 'ID ' . (string) ($log['usuario_id'] ?? 'N/A');
                            } elseif (!empty($log['usuario_id'])) {
                                $usuario .= ' (ID ' . (string) $log['usuario_id'] . ')';
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($log['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(strtoupper($acao), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td>
                                    <?= htmlspecialchars((string) ($log['entidade'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    <small class="d-block text-muted"><?= htmlspecialchars((string) ($log['tabela_referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td><?= htmlspecialchars((string) ($log['id_registro'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <pre class="mb-0 small bg-light border rounded p-2" style="white-space: pre-wrap; min-width: 320px;"><?= htmlspecialchars((string) ($log['campos_alterados'] ?? ''), ENT_QUOTES, 'UTF-8') ?></pre>
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
