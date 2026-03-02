<?php
include '../includes/db.php';
include '../includes/header.php';

$mensagem = '';

//verificação de envio do formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    // Corrigindo a conversão numérica
    $campos_numericos = ['custo', 'preco1', 'preco2'];
    foreach ($campos_numericos as $campo) {
        $_POST[$campo] = str_replace(',', '.', $_POST[$campo] ?? '0');
    }

    $produto = [
        'nome' => $_POST['nome'],
        'custo' => (float)$_POST['custo'],
        'preco1' => (float)$_POST['preco1'],
        'preco2' => !empty($_POST['preco2']) ? (float)$_POST['preco2'] : null,
        'qtdLoja' => !empty($_POST['qtdLoja']) ? (int)$_POST['qtdLoja'] : 0,
        'qtdEstoque' => !empty($_POST['qtdEstoque']) ? (int)$_POST['qtdEstoque'] : 0,
        'controle_estoque' => isset($_POST['controle_estoque']) ? 1 : 0,
        'estoque_minimo' => isset($_POST['estoque_minimo']) && $_POST['estoque_minimo'] !== '' ? (int) $_POST['estoque_minimo'] : null,
        'ponto_compra' => isset($_POST['ponto_compra']) && $_POST['ponto_compra'] !== '' ? (int) $_POST['ponto_compra'] : null,
        'grupo' => $_POST['grupo'] ?? null,
        'subgrupo' => $_POST['subgrupo'] ?? null,
        'marca' => $_POST['marca'] ?? null,
        'observacoes' => $_POST['observacoes'] ?? null
    ];
    
    // VALIDAÇÕES BÁSICAS
    if (empty($produto['nome'])) {
        $mensagem = '<div class="alert alert-danger">O nome do produto é obrigatório!</div>';
    } elseif ($produto['preco1'] <= 0) {
        $mensagem = '<div class="alert alert-danger">O preço 1 deve ser maior que zero!</div>';
    } elseif ($produto['controle_estoque'] === 1 && ($produto['estoque_minimo'] === null || $produto['estoque_minimo'] < 0)) {
        $mensagem = '<div class="alert alert-danger">Com controle de estoque ativo, o estoque mínimo é obrigatório e deve ser maior ou igual a zero.</div>';
    } else {
        if ($produto['controle_estoque'] === 0) {
            $produto['estoque_minimo'] = null;
            $produto['ponto_compra'] = null;
        }

        // Define a query SQL antes do try para garantir que a variável exista
        $sql = "INSERT INTO produtos (
                    nome, custo, preco1, preco2, qtdLoja, qtdEstoque,
                    controle_estoque, estoque_minimo, ponto_compra,
                    grupo, subgrupo, marca, observacoes, created_at
                ) VALUES (
                    :nome, :custo, :preco1, :preco2, :qtdLoja, :qtdEstoque,
                    :controle_estoque, :estoque_minimo, :ponto_compra,
                    :grupo, :subgrupo, :marca, :observacoes, NOW()
                )";

        try {   
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($produto);
            
            if ($result && $stmt->rowCount() > 0) {
                $mensagem = '<div class="alert alert-success">Produto cadastrado com sucesso!</div>';
                $produto = array_fill_keys(array_keys($produto), '');
            } else {
                $mensagem = '<div class="alert alert-danger">Não foi possível cadastrar o produto no banco de dados.</div>';
            }
        } catch (PDOException $e) {
            error_log('Erro técnico ao cadastrar produto: ' . $e->getMessage());
            $mensagem = '<div class="alert alert-danger">Erro ao cadastrar produto. Tente novamente.</div>';
        }
    }
}


?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-plus-circle me-2"></i>Cadastrar Novo Produto</h4>
    </div>
    <div class="card-body">
        <?= $mensagem ?>

        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nome do Produto</label>
                        <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($produto['nome'] ?? '') ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Preço 1 (Varejo)</label>
                                <input type="number" step="0.01" class="form-control" name="preco1" value="<?= htmlspecialchars($produto['preco1'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Preço 2 (Atacado)</label>
                                <input type="number" step="0.01" class="form-control" name="preco2" value="<?= htmlspecialchars($produto['preco2'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Custo</label>
                        <input type="number" step="0.01" class="form-control" name="custo" value="<?= htmlspecialchars($produto['custo'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantidade na Loja</label>
                                <input type="number" class="form-control" name="qtdLoja" value="<?= htmlspecialchars($produto['qtdLoja'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantidade em Estoque</label>
                                <input type="number" class="form-control" name="qtdEstoque" value="<?= htmlspecialchars($produto['qtdEstoque'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Grupo</label>
                        <input type="text" class="form-control" name="grupo" value="<?= htmlspecialchars($produto['grupo'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subgrupo</label>
                        <input type="text" class="form-control" name="subgrupo" value="<?= htmlspecialchars($produto['subgrupo'] ?? '') ?>">
                    </div>

                    <div class="form-check form-switch mb-3 mt-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="controle_estoque" name="controle_estoque" value="1"
                            <?= !empty($produto['controle_estoque']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="controle_estoque">Controle de estoque</label>
                    </div>

                    <div id="campos-controle-estoque" class="border rounded p-3 <?= !empty($produto['controle_estoque']) ? '' : 'd-none' ?>">
                        <div class="mb-3">
                            <label class="form-label">Estoque mínimo</label>
                            <input type="number" min="0" class="form-control" id="estoque_minimo" name="estoque_minimo"
                                value="<?= htmlspecialchars((string) ($produto['estoque_minimo'] ?? '')) ?>"
                                <?= !empty($produto['controle_estoque']) ? 'required' : '' ?>>
                            <small class="text-muted">Obrigatório quando o controle de estoque estiver ativo.</small>
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
            
            <div class="mb-3">
                <label class="form-label">Marca</label>
                <input type="text" class="form-control" name="marca" value="<?= htmlspecialchars($produto['marca'] ?? '') ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Observações</label>
                <textarea class="form-control" name="observacoes" rows="3"><?= htmlspecialchars($produto['observacoes'] ?? '') ?></textarea>
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
