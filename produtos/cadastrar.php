<?php
include '../includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
}

$controller = new \App\Controllers\ProdutoController($pdo);
$result = $controller->create($_POST, $_SERVER['REQUEST_METHOD']);
$produto = $result['data'];

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-plus-circle me-2"></i>Cadastrar Novo Produto</h4>
    </div>
    <div class="card-body">
        <?= \App\Views\AlertRenderer::render($result['alert']) ?>

        <form method="POST">
            <?= csrf_input() ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nome do Produto</label>
                        <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars((string) ($produto['nome'] ?? '')) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Pre&ccedil;o 1 (Varejo)</label>
                                <input type="number" step="0.01" class="form-control" name="preco1" value="<?= htmlspecialchars((string) ($produto['preco1'] ?? '')) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Pre&ccedil;o 2 (Atacado)</label>
                                <input type="number" step="0.01" class="form-control" name="preco2" value="<?= htmlspecialchars((string) ($produto['preco2'] ?? '')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Custo</label>
                        <input type="number" step="0.01" class="form-control" name="custo" value="<?= htmlspecialchars((string) ($produto['custo'] ?? '')) ?>">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantidade na Loja</label>
                                <input type="number" class="form-control" name="qtdLoja" value="<?= htmlspecialchars((string) ($produto['qtdLoja'] ?? '')) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantidade em Estoque</label>
                                <input type="number" class="form-control" name="qtdEstoque" value="<?= htmlspecialchars((string) ($produto['qtdEstoque'] ?? '')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Grupo</label>
                        <input type="text" class="form-control" name="grupo" value="<?= htmlspecialchars((string) ($produto['grupo'] ?? '')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subgrupo</label>
                        <input type="text" class="form-control" name="subgrupo" value="<?= htmlspecialchars((string) ($produto['subgrupo'] ?? '')) ?>">
                    </div>

                    <div class="estoque-box mt-4 mb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="controle_estoque" name="controle_estoque" value="1"
                                <?= !empty($produto['controle_estoque']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="controle_estoque">Controle de estoque</label>
                        </div>
                        <small class="text-muted d-block mb-3">Ativado por padr&atilde;o para novos produtos.</small>

                        <div id="campos-controle-estoque" class="campos-estoque <?= !empty($produto['controle_estoque']) ? '' : 'd-none' ?>">
                            <div class="mb-3">
                                <label class="form-label">Estoque m&iacute;nimo</label>
                                <input type="number" min="0" class="form-control" id="estoque_minimo" name="estoque_minimo"
                                    value="<?= htmlspecialchars((string) ($produto['estoque_minimo'] ?? '')) ?>"
                                    <?= !empty($produto['controle_estoque']) ? 'required' : '' ?>>
                                <small class="text-muted">Obrigat&oacute;rio quando o controle de estoque estiver ativo.</small>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Ponto de compra</label>
                                <input type="number" min="0" class="form-control" name="ponto_compra"
                                    value="<?= htmlspecialchars((string) ($produto['ponto_compra'] ?? '')) ?>">
                                <small class="text-muted">Opcional.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Marca</label>
                <input type="text" class="form-control" name="marca" value="<?= htmlspecialchars((string) ($produto['marca'] ?? '')) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Observa&ccedil;&otilde;es</label>
                <textarea class="form-control" name="observacoes" rows="3"><?= htmlspecialchars((string) ($produto['observacoes'] ?? '')) ?></textarea>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?php echo app_url('produtos/listar.php'); ?>" class="btn btn-secondary me-md-2">
                    <i class="fas fa-arrow-left me-1"></i> Voltar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const controleEstoque = document.getElementById('controle_estoque');
        const camposControleEstoque = document.getElementById('campos-controle-estoque');
        const estoqueMinimo = document.getElementById('estoque_minimo');

        function alternarCamposControle() {
            const ativo = controleEstoque.checked;
            camposControleEstoque.classList.toggle('d-none', !ativo);
            estoqueMinimo.required = ativo;

            if (!ativo) {
                estoqueMinimo.value = '';
            }
        }

        controleEstoque.addEventListener('change', alternarCamposControle);
        alternarCamposControle();
    });
</script>

<?php include '../includes/footer.php'; ?>
