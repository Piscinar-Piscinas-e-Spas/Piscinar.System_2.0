<?php
include '../includes/db.php';
require_login();
$controller = new \App\Controllers\ProdutoController($pdo);
$viewData = $controller->list($_GET);
$produtos = $viewData['produtos'];
$grupos = $viewData['grupos'];
$alert = $viewData['alert'];

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-boxes me-2"></i>Lista de Produtos</h4>
            <a href="<?php echo app_url('produtos/cadastrar.php'); ?>" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Novo
            </a>
        </div>

        <div class="card-body">
            <?= \App\Views\AlertRenderer::render($alert) ?>
            <form method="get" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="grupo" class="form-control" placeholder="Grupo"
                            value="<?= htmlspecialchars($_GET['grupo'] ?? '') ?>" id="grupoInput" list="gruposList">
                        <datalist id="gruposList">
                            <?php foreach ($grupos as $grupo): ?>
                                <option value="<?= htmlspecialchars((string) $grupo) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="col-md-3">
                        <input type="text" name="subgrupo" class="form-control" placeholder="Subgrupo"
                            value="<?= htmlspecialchars($_GET['subgrupo'] ?? '') ?>" id="subgrupoInput"
                            list="subgruposList" <?= empty($_GET['grupo']) ? 'disabled' : '' ?>>
                        <datalist id="subgruposList"></datalist>
                    </div>

                    <div class="col-md-6">
                        <input type="text" name="nome" class="form-control" placeholder="Nome do produto"
                            value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>">
                    </div>

                    <div class="col-md-12 text-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Pesquisar
                        </button>
                        <a href="<?php echo app_url('produtos/listar.php'); ?>" class="btn btn-secondary">Limpar Filtros</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Nome</th>
                            <th>Pre&ccedil;o Varejo</th>
                            <th>Pre&ccedil;o Atacado</th>
                            <th>Grupo/Subgrupo</th>
                            <th>Marca</th>
                            <th>Estoque</th>
                            <th>A&ccedil;&otilde;es</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $produto): ?>
                            <?php
                            $qtdLoja = (int) ($produto['qtdLoja'] ?? 0);
                            $qtdEstoque = (int) ($produto['qtdEstoque'] ?? 0);
                            $estoqueTotal = (int) ($produto['estoque_total'] ?? 0);
                            $controleEstoque = (int) ($produto['controle_estoque'] ?? 0) === 1;
                            $estoqueMinimo = isset($produto['estoque_minimo']) ? (int) $produto['estoque_minimo'] : null;
                            $detalhesEstoque = [];
                            $rowClass = '';

                            if ($qtdLoja > 0) {
                                $detalhesEstoque[] = 'qtLoja ' . str_pad((string) $qtdLoja, 2, '0', STR_PAD_LEFT);
                            }

                            if ($qtdEstoque > 0) {
                                $detalhesEstoque[] = 'qtEstoque ' . str_pad((string) $qtdEstoque, 2, '0', STR_PAD_LEFT);
                            }

                            if (empty($detalhesEstoque)) {
                                $detalhesEstoque[] = 'Sem estoque';
                            }

                            $tooltipEstoque = implode(' | ', $detalhesEstoque);

                            if ($controleEstoque) {
                                if ($estoqueTotal <= 0) {
                                    $rowClass = 'estoque-critico';
                                } elseif ($estoqueMinimo !== null && $estoqueTotal <= $estoqueMinimo) {
                                    $rowClass = 'estoque-baixo';
                                }
                            }
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td><?= htmlspecialchars($produto['nome']) ?></td>
                                <td>R$ <?= number_format($produto['preco1'], 2, ',', '.') ?></td>
                                <td>
                                    <?= $produto['preco2'] ? 'R$ ' . number_format($produto['preco2'], 2, ',', '.') : '-' ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars((string) ($produto['grupo'] ?? '')) ?>
                                    <?= !empty($produto['subgrupo']) ? '/ ' . htmlspecialchars((string) $produto['subgrupo']) : '' ?>
                                </td>
                                <td><?= htmlspecialchars((string) ($produto['marca'] ?? '-')) ?></td>
                                <td>
                                    <span data-bs-toggle="tooltip" data-bs-placement="top" class="cursor-help"
                                        title="<?= htmlspecialchars($tooltipEstoque) ?>">
                                        <?= $estoqueTotal ?>
                                    </span>
                                    <?php if ($controleEstoque && $estoqueMinimo !== null): ?>
                                        <small class="d-block">m&iacute;n: <?= $estoqueMinimo ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= app_url('produtos/editar.php'); ?>?id=<?= $produto['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="<?= app_url('produtos/excluir.php'); ?>" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="id" value="<?= (int) $produto['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

<script>
    window.APP_BASE_URL = "<?php echo rtrim(BASE_URL, '/'); ?>";
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
<script type="text/javascript" src="<?php echo app_url('assets/js/filtrar_produtos.js'); ?>">

</script>

<?php include '../includes/footer.php'; ?>
