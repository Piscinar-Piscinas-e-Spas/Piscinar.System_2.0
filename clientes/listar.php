<?php
include '../includes/db.php';
require_login();
include '../includes/header.php';
$controller = new \App\Controllers\ClienteController($pdo);
$viewData = $controller->list($_GET);
$termo = trim((string) ($_GET['termo'] ?? ''));
$clientes = $viewData['clientes'];
$alert = $viewData['alert'];
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-users me-2"></i>Lista de Clientes</h4>
            <a href="<?= app_url('clientes/cadastrar.php'); ?>" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Novo
            </a>
        </div>

        <div class="card-body">
            <?= \App\Views\AlertRenderer::render($alert) ?>

            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input
                        type="text"
                        name="termo"
                        class="form-control"
                        placeholder="Buscar por nome, telefone, CPF/CNPJ ou e-mail"
                        value="<?= htmlspecialchars($termo) ?>"
                    >
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search me-1"></i> Pesquisar
                    </button>
                    <a href="<?= app_url('clientes/listar.php'); ?>" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>CPF/CNPJ</th>
                            <th>EndereÃ§o</th>
                            <th>E-mail</th>
                            <th>AÃ§Ãµes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clientes)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Nenhum cliente encontrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?= str_pad((string) ((int) $cliente['id_cliente']), 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars((string) $cliente['nome_cliente']) ?></td>
                                <td><?= htmlspecialchars((string) ($cliente['telefone_contato'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($cliente['cpf_cnpj'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($cliente['endereco'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($cliente['email_contato'] ?? '-')) ?></td>
                                <td>
                                    <a href="<?= app_url('clientes/editar.php'); ?>?id=<?= (int) $cliente['id_cliente'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="<?= app_url('clientes/excluir.php'); ?>" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este cliente?');">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="id" value="<?= (int) $cliente['id_cliente'] ?>">
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
