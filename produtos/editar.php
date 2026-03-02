<?php
include '../includes/db.php';
require_once dirname(__DIR__) . '/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: ' . app_url('produtos/listar.php?status=erro_id'));
    exit;
}

$mensagem = '';

$stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    header('Location: ' . app_url('produtos/listar.php?status=nao_encontrado'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $custo = str_replace(',', '.', trim($_POST['custo'] ?? '0'));
    $preco1 = str_replace(',', '.', trim($_POST['preco1'] ?? '0'));
    $preco2Raw = str_replace(',', '.', trim($_POST['preco2'] ?? ''));

    $dados = [
        'nome' => $nome,
        'custo' => is_numeric($custo) ? (float) $custo : 0,
        'preco1' => is_numeric($preco1) ? (float) $preco1 : 0,
        'preco2' => $preco2Raw !== '' && is_numeric($preco2Raw) ? (float) $preco2Raw : null,
        'qtdLoja' => (int) ($_POST['qtdLoja'] ?? 0),
        'qtdEstoque' => (int) ($_POST['qtdEstoque'] ?? 0),
        'controle_estoque' => isset($_POST['controle_estoque']) ? 1 : 0,
        'estoque_minimo' => isset($_POST['estoque_minimo']) && $_POST['estoque_minimo'] !== '' ? (int) $_POST['estoque_minimo'] : null,
        'ponto_compra' => isset($_POST['ponto_compra']) && $_POST['ponto_compra'] !== '' ? (int) $_POST['ponto_compra'] : null,
        'grupo' => trim($_POST['grupo'] ?? ''),
        'subgrupo' => trim($_POST['subgrupo'] ?? ''),
        'marca' => trim($_POST['marca'] ?? ''),
        'observacoes' => trim($_POST['observacoes'] ?? ''),
    ];

    if ($dados['nome'] === '') {
        $mensagem = '<div class="alert alert-danger">O nome do produto é obrigatório.</div>';
    } elseif ($dados['preco1'] <= 0) {
        $mensagem = '<div class="alert alert-danger">O Preço 1 precisa ser maior que zero.</div>';
    } elseif ($dados['controle_estoque'] === 1 && ($dados['estoque_minimo'] === null || $dados['estoque_minimo'] < 0)) {
        $mensagem = '<div class="alert alert-danger">Com controle de estoque ativo, o estoque mínimo é obrigatório e deve ser maior ou igual a zero.</div>';
    } else {
        if ($dados['controle_estoque'] === 0) {
            $dados['estoque_minimo'] = null;
            $dados['ponto_compra'] = null;
        }

        try {
            $sql = 'UPDATE produtos SET
                        nome = :nome,
                        custo = :custo,
                        preco1 = :preco1,
                        preco2 = :preco2,
                        qtdLoja = :qtdLoja,
                        qtdEstoque = :qtdEstoque,
                        controle_estoque = :controle_estoque,
                        estoque_minimo = :estoque_minimo,
                        ponto_compra = :ponto_compra,
                        grupo = :grupo,
                        subgrupo = :subgrupo,
                        marca = :marca,
                        observacoes = :observacoes
                    WHERE id = :id';

            $updateStmt = $pdo->prepare($sql);
            $ok = $updateStmt->execute([
                ':nome' => $dados['nome'],
                ':custo' => $dados['custo'],
                ':preco1' => $dados['preco1'],
                ':preco2' => $dados['preco2'],
                ':qtdLoja' => $dados['qtdLoja'],
                ':qtdEstoque' => $dados['qtdEstoque'],
                ':controle_estoque' => $dados['controle_estoque'],
                ':estoque_minimo' => $dados['estoque_minimo'],
                ':ponto_compra' => $dados['ponto_compra'],
                ':grupo' => $dados['grupo'] !== '' ? $dados['grupo'] : null,
                ':subgrupo' => $dados['subgrupo'] !== '' ? $dados['subgrupo'] : null,
                ':marca' => $dados['marca'] !== '' ? $dados['marca'] : null,
                ':observacoes' => $dados['observacoes'] !== '' ? $dados['observacoes'] : null,
                ':id' => $id,
            ]);

            if ($ok) {
                header('Location: ' . app_url('produtos/listar.php?status=editado'));
                exit;
            }

            $mensagem = '<div class="alert alert-danger">Não foi possível atualizar o produto.</div>';
        } catch (PDOException $e) {
            $mensagem = '<div class="alert alert-danger">Erro ao atualizar: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    $produto = array_merge($produto, $dados);
}

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-edit me-2"></i>Editar Produto</h4>
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
                                <input type="number" step="0.01" class="form-control" name="preco1" value="<?= htmlspecialchars((string) ($produto['preco1'] ?? '')) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Preço 2 (Atacado)</label>
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
                                <input type="number" class="form-control" name="qtdLoja" value="<?= htmlspecialchars((string) ($produto['qtdLoja'] ?? 0)) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantidade em Estoque</label>
                                <input type="number" class="form-control" name="qtdEstoque" value="<?= htmlspecialchars((string) ($produto['qtdEstoque'] ?? 0)) ?>">
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
                <a href="<?= app_url('produtos/listar.php') ?>" class="btn btn-secondary me-md-2">
                    <i class="fas fa-arrow-left me-1"></i> Voltar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Atualizar
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
