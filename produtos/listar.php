<?php
include '../includes/db.php';

// Parâmetros de pesquisa
//$termo = $_GET['termo'] ?? '';
//$campo = $_GET['campo'] ?? 'todos';

// Construir a query
$where = [];
$params = [];
$bindTypes = [];

// Grupo (independente ou como filtro primário)
if (!empty($_GET['grupo'])) {
    $where[] = "grupo LIKE ?";
    $params[] = "%{$_GET['grupo']}%";
    $bindTypes[] = PDO::PARAM_STR;
}

// Subgrupo (se grupo estiver definido, filtra dentro do grupo)
if (!empty($_GET['subgrupo'])) {
    $where[] = "subgrupo LIKE ?";
    $params[] = "%{$_GET['subgrupo']}%";
    $bindTypes[] = PDO::PARAM_STR;
}

// Nome (filtro mais específico)
if (!empty($_GET['nome'])) {
    $where[] = "nome LIKE ?";
    $params[] = "%{$_GET['nome']}%";
    $bindTypes[] = PDO::PARAM_STR;
}






$sql = "SELECT id, nome, preco1, preco2, grupo, subgrupo, marca, qtdLoja, qtdEstoque,
               COALESCE(controle_estoque, 0) AS controle_estoque,
               estoque_minimo,
               (COALESCE(qtdLoja, 0) + COALESCE(qtdEstoque, 0)) AS estoque_total
        FROM produtos";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY 
          CASE WHEN grupo IS NOT NULL THEN 0 ELSE 1 END,
          CASE WHEN subgrupo IS NOT NULL THEN 0 ELSE 1 END,
          nome
          LIMIT 50";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value, $bindTypes[$key]);
}   


// EXECUTAR A QUERY E OBTER RESULTADOS
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC); // Adicionado

$status = $_GET['status'] ?? '';
$feedback = [
    'editado' => ['class' => 'success', 'texto' => 'Produto atualizado com sucesso.'],
    'excluido' => ['class' => 'success', 'texto' => 'Produto excluído com sucesso.'],
    'erro_id' => ['class' => 'warning', 'texto' => 'ID inválido informado.'],
    'nao_encontrado' => ['class' => 'warning', 'texto' => 'Produto não encontrado.'],
    'erro_exclusao' => ['class' => 'danger', 'texto' => 'Erro ao excluir o produto.'],
];

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
            <?php if (isset($feedback[$status])): ?>
                <div class="alert alert-<?= $feedback[$status]['class'] ?>">
                    <?= htmlspecialchars($feedback[$status]['texto']) ?>
                </div>
            <?php endif; ?>
            <!-- Formulário de Pesquisa -->
            <form method="get" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="grupo" class="form-control" placeholder="Grupo"
                            value="<?= htmlspecialchars($_GET['grupo'] ?? '') ?>" id="grupoInput" list="gruposList">
                        <datalist id="gruposList">
                            <?php
                            $grupos = $pdo->query("SELECT DISTINCT grupo FROM produtos WHERE grupo IS NOT NULL");
                            foreach ($grupos as $g) {
                                echo "<option value='{$g['grupo']}'>";
                            }
                            ?>
                        </datalist>
                    </div>

                    <div class="col-md-3">
                        <input type="text" name="subgrupo" class="form-control" placeholder="Subgrupo"
                            value="<?= htmlspecialchars($_GET['subgrupo'] ?? '') ?>" id="subgrupoInput"
                            list="subgruposList" <?= empty($_GET['grupo']) ? 'disabled' : '' ?>>
                        <datalist id="subgruposList">
                            <!-- Conteúdo será preenchido dinamicamente via JavaScript -->
                        </datalist>
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
            <!-- Tabela de Resultados -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Nome</th>
                            <th>Preço Varejo</th>
                            <th>Preço Atacado</th>
                            <th>Grupo/Subgrupo</th>
                            <th>Marca</th>
                            <th>Estoque</th>
                            <th>Ações</th>
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
                                    <?= htmlspecialchars($produto['grupo']) ?>
                                    <?= $produto['subgrupo'] ? '/ ' . htmlspecialchars($produto['subgrupo']) : '' ?>
                                </td>
                                <td><?= htmlspecialchars($produto['marca']) ?? '-' ?></td>
                                <td>
                                    <span data-bs-toggle="tooltip" data-bs-placement="top"
                                        title="<?= htmlspecialchars($tooltipEstoque) ?>" style="cursor: help;">
                                        <?= $estoqueTotal ?>
                                    </span>
                                    <?php if ($controleEstoque && $estoqueMinimo !== null): ?>
                                        <small class="d-block">mín: <?= $estoqueMinimo ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= app_url('produtos/editar.php'); ?>?id=<?= $produto['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger"
                                        onclick="confirmarExclusao(<?= $produto['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
