<?php
include '../includes/db.php';
require_login();
include '../includes/header.php';

$controller = new \App\Controllers\FornecedorController($pdo);
$viewData = $controller->list($_GET);
$termo = trim((string) ($_GET['termo'] ?? ''));
$fornecedores = $viewData['fornecedores'];
$alert = $viewData['alert'];
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-building me-2"></i>Lista de Fornecedores</h4>
            <a href="<?= app_url('fornecedores/cadastrar.php'); ?>" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Novo
            </a>
        </div>

        <div class="card-body">
            <?= \App\Views\AlertRenderer::render($alert) ?>

            <form method="GET" class="mb-3">
                <div class="input-group">
                    <label class="form-label visually-hidden" for="fornecedor-lista-termo">Buscar fornecedor</label>
                    <input
                        id="fornecedor-lista-termo"
                        type="text"
                        name="termo"
                        class="form-control"
                        placeholder="Buscar por nome, documento, telefone ou e-mail"
                        value="<?= htmlspecialchars($termo, ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search me-1"></i> Pesquisar
                    </button>
                    <a href="<?= app_url('fornecedores/listar.php'); ?>" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Documento</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                            <th>Status</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fornecedores)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Nenhum fornecedor encontrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($fornecedores as $fornecedor): ?>
                            <tr>
                                <td><?= str_pad((string) ((int) $fornecedor['id_fornecedor']), 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars((string) $fornecedor['nome_fornecedor'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($fornecedor['documento'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($fornecedor['telefone'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($fornecedor['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= !empty($fornecedor['ativo']) ? 'Ativo' : 'Inativo' ?></td>
                                <td>
                                    <a href="<?= app_url('fornecedores/editar.php'); ?>?id=<?= (int) $fornecedor['id_fornecedor'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="<?= app_url('fornecedores/excluir.php'); ?>" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este fornecedor?');">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="id" value="<?= (int) $fornecedor['id_fornecedor'] ?>">
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

<?php include '../includes/footer.php'; ?>
