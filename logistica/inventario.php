<?php
include '../includes/db.php';
require_login();

$modo = (string) ($_GET['local'] ?? 'loja');
$modo = $modo === 'barracao' ? 'barracao' : 'loja';
$colunaSistema = $modo === 'barracao' ? 'qtdEstoque' : 'qtdLoja';
$tituloLocal = $modo === 'barracao' ? 'Estoque Auxiliar' : 'Loja';

$busca = trim((string) ($_GET['busca'] ?? ''));
$grupoFiltro = trim((string) ($_GET['grupo'] ?? ''));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = (int) ($_GET['por_pagina'] ?? 25);
$porPaginaPermitidos = [25, 50, 100, 200];
if (!in_array($porPagina, $porPaginaPermitidos, true)) {
    $porPagina = 25;
}

$where = [];
$params = [];

if ($busca !== '') {
    $where[] = '(nome LIKE :busca OR CAST(id AS CHAR) LIKE :busca)';
    $params[':busca'] = '%' . $busca . '%';
}

if ($grupoFiltro !== '') {
    $where[] = 'grupo LIKE :grupo'; 
    $params[':grupo'] = '%' . $grupoFiltro . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM produtos {$whereSql}");
$stmtTotal->execute($params);
$totalItens = (int) $stmtTotal->fetchColumn();
$totalPaginas = max(1, (int) ceil($totalItens / $porPagina));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * $porPagina;

$sqlProdutos = "SELECT id, nome, grupo, qtdLoja, qtdEstoque, estoque_minimo
                FROM produtos
                {$whereSql}
                ORDER BY nome ASC
                LIMIT {$porPagina} OFFSET {$offset}";
$stmtProdutos = $pdo->prepare($sqlProdutos);
$stmtProdutos->execute($params);
$produtos = $stmtProdutos->fetchAll();

$stmtGrupos = $pdo->query('SELECT DISTINCT grupo FROM produtos WHERE grupo IS NOT NULL AND grupo <> "" ORDER BY grupo ASC');
$grupos = $stmtGrupos->fetchAll(PDO::FETCH_COLUMN);

$status = (string) ($_GET['status'] ?? '');
$toastMap = [
    'balanco_salvo' => ['title' => 'Balanco salvo', 'body' => 'Os saldos fisicos foram atualizados com sucesso.', 'class' => 'text-bg-primary'],
    'balanco_vazio' => ['title' => 'Nada para salvar', 'body' => 'Preencha ao menos um item com contagem fisica.', 'class' => 'text-bg-warning'],
    'balanco_erro' => ['title' => 'Erro ao salvar', 'body' => 'Nao foi possivel atualizar o balanco agora.', 'class' => 'text-bg-danger'],
];
$toast = $toastMap[$status] ?? null;

$extraHeadContent = '
<link rel="stylesheet" href="' . htmlspecialchars(app_url('assets/css/logistica.css'), ENT_QUOTES, 'UTF-8') . '">
';

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h3 class="mb-1"><i class="bi bi-clipboard-data me-2 text-primary"></i>Inventario - Balanco Fisico</h3>
            <p class="text-muted mb-0">Conte os itens por local e atualize somente a coluna selecionada.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <a href="<?= htmlspecialchars(app_url('logistica/transferencia.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left-right me-1"></i>Ir para Transferencias
            </a>
            <div class="btn-group inventory-toggle" role="group" aria-label="Alternar local da contagem">
                <a href="<?= htmlspecialchars(app_url('logistica/inventario.php?' . http_build_query(['local' => 'loja', 'busca' => $busca, 'grupo' => $grupoFiltro, 'por_pagina' => $porPagina])), ENT_QUOTES, 'UTF-8') ?>"
                    class="btn <?= $modo === 'loja' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="bi bi-shop me-1"></i>Contar Loja
                </a>
                <a href="<?= htmlspecialchars(app_url('logistica/inventario.php?' . http_build_query(['local' => 'barracao', 'busca' => $busca, 'grupo' => $grupoFiltro, 'por_pagina' => $porPagina])), ENT_QUOTES, 'UTF-8') ?>"
                    class="btn <?= $modo === 'barracao' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="bi bi-box-seam me-1"></i>Contar Estoque Auxiliar
                </a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm logistics-card">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end mb-4">
                <input type="hidden" name="local" value="<?= htmlspecialchars($modo, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-lg-5">
                    <label class="form-label" for="inventario-filtro-busca">Buscar produto</label>
                    <input type="text" id="inventario-filtro-busca" name="busca" class="form-control" placeholder="Nome ou ID"
                        value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="inventario-filtro-grupo">Grupo</label>
                    <input type="text" id="inventario-filtro-grupo" name="grupo" class="form-control" list="gruposLogistica"
                        value="<?= htmlspecialchars($grupoFiltro, ENT_QUOTES, 'UTF-8') ?>" placeholder="Todos os grupos">
                    <datalist id="gruposLogistica">
                        <?php foreach ($grupos as $grupo): ?>
                            <option value="<?= htmlspecialchars((string) $grupo, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-lg-2">
                    <label class="form-label" for="inventario-filtro-por-pagina">Itens por pagina</label>
                    <select id="inventario-filtro-por-pagina" name="por_pagina" class="form-select">
                        <?php foreach ($porPaginaPermitidos as $opcao): ?>
                            <option value="<?= $opcao ?>" <?= $porPagina === $opcao ? 'selected' : '' ?>><?= $opcao ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filtrar</button>
                    <a href="<?= htmlspecialchars(app_url('logistica/inventario.php?local=' . $modo), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="text-muted">
                    Exibindo <?= count($produtos) ?> de <?= $totalItens ?> produtos para contagem em <strong><?= htmlspecialchars($tituloLocal, ENT_QUOTES, 'UTF-8') ?></strong>.
                </div>
                <div class="badge rounded-pill text-bg-light border text-secondary px-3 py-2">
                    Pagina <?= $pagina ?> de <?= $totalPaginas ?>
                </div>
            </div>

            <form method="post" action="<?= htmlspecialchars(app_url('logistica/salvar_balanco.php'), ENT_QUOTES, 'UTF-8') ?>" id="inventarioForm">
                <?= csrf_input() ?>
                <input type="hidden" name="local" value="<?= htmlspecialchars($modo, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="busca" value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="grupo" value="<?= htmlspecialchars($grupoFiltro, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="por_pagina" value="<?= $porPagina ?>">
                <input type="hidden" name="pagina" value="<?= $pagina ?>">

                <div class="table-responsive">
                    <table class="table table-hover align-middle inventory-table">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 80px;">ID</th>
                                <th style="min-width: 280px;">Produto</th>
                                <th style="min-width: 180px;">Grupo</th>
                                <th style="min-width: 110px; text-align: center;">Sistema</th>
                                <th style="min-width: 110px; text-align: center;">Fisico</th>
                                <th style="min-width: 120px; text-align: center;">Diferenca</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($produtos)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Nenhum produto encontrado para os filtros informados.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($produtos as $produto): ?>
                                <?php $sistema = (int) ($produto[$colunaSistema] ?? 0); ?>
                                <tr>
                                    <td class="fw-semibold">#<?= (int) $produto['id'] ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) $produto['nome'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <small class="text-muted">Minimo: <?= (int) ($produto['estoque_minimo'] ?? 0) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars((string) ($produto['grupo'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center">
                                        <span class="badge text-bg-secondary inventory-system-value"><?= $sistema ?></span>
                                    </td>
                                    <td class="text-center">
                                        <input
                                            type="number"
                                            min="0"
                                            step="1"
                                            class="form-control text-center inventory-physical-input"
                                            name="itens[<?= (int) $produto['id'] ?>]"
                                            value="<?= $sistema ?>"
                                            data-system="<?= $sistema ?>">
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold inventory-diff">0</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                    <div class="text-muted small">
                        Diferencas negativas ficam em vermelho; positivas em azul.
                    </div>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-1"></i>Salvar Balanco
                    </button>
                </div>
            </form>

            <?php if ($totalPaginas > 1): ?>
                <nav aria-label="Paginacao do inventario" class="mt-4">
                    <ul class="pagination flex-wrap mb-0">
                        <?php
                        $baseParams = ['local' => $modo, 'busca' => $busca, 'grupo' => $grupoFiltro, 'por_pagina' => $porPagina];
                        $anterior = max(1, $pagina - 1);
                        $proxima = min($totalPaginas, $pagina + 1);
                        ?>
                        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars(app_url('logistica/inventario.php?' . http_build_query($baseParams + ['pagina' => $anterior])), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
                        </li>
                        <?php for ($p = max(1, $pagina - 2); $p <= min($totalPaginas, $pagina + 2); $p++): ?>
                            <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                                <a class="page-link" href="<?= htmlspecialchars(app_url('logistica/inventario.php?' . http_build_query($baseParams + ['pagina' => $p])), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars(app_url('logistica/inventario.php?' . http_build_query($baseParams + ['pagina' => $proxima])), ENT_QUOTES, 'UTF-8') ?>">Proxima</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($toast): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
        <div id="inventoryToast" class="toast <?= htmlspecialchars($toast['class'], ENT_QUOTES, 'UTF-8') ?>" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi bi-boxes me-2"></i>
                <strong class="me-auto"><?= htmlspecialchars($toast['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
            <div class="toast-body"><?= htmlspecialchars($toast['body'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
<?php endif; ?>

<script src="<?= htmlspecialchars(app_url('assets/js/logistica_inventario.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php if ($toast): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toastEl = document.getElementById('inventoryToast');
        if (toastEl && typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000, animation: true }).show();
        }
    });
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
