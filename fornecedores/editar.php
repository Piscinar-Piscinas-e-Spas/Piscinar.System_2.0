<?php
include '../includes/db.php';
require_once dirname(__DIR__) . '/config.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: ' . app_url('fornecedores/listar.php?status=erro_id'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
}

$controller = new \App\Controllers\FornecedorController($pdo);
$result = $controller->edit($id, $_POST, $_SERVER['REQUEST_METHOD']);

if (isset($result['redirect'])) {
    header('Location: ' . app_url('fornecedores/listar.php?status=' . $result['redirect']));
    exit;
}

$fornecedor = $result['data'];

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-building me-2"></i>Editar Fornecedor</h4>
    </div>
    <div class="card-body">
        <?= \App\Views\AlertRenderer::render($result['alert']) ?>

        <form method="POST">
            <?= csrf_input() ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="fornecedor-form-razao-social">Razao Social</label>
                        <input type="text" class="form-control" id="fornecedor-form-razao-social" name="razao_social" value="<?= htmlspecialchars((string) ($fornecedor['razao_social'] ?? $fornecedor['nome_fornecedor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="fornecedor-form-nome-fantasia">Nome Fantasia</label>
                        <input type="text" class="form-control" id="fornecedor-form-nome-fantasia" name="nome_fantasia" value="<?= htmlspecialchars((string) ($fornecedor['nome_fantasia'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="fornecedor-form-documento">Documento (opcional)</label>
                        <input type="text" class="form-control" id="fornecedor-form-documento" name="documento" value="<?= htmlspecialchars((string) ($fornecedor['documento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="fornecedor-form-telefone">Telefone (opcional)</label>
                        <input type="text" class="form-control" id="fornecedor-form-telefone" name="telefone" value="<?= htmlspecialchars((string) ($fornecedor['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="fornecedor-form-email">E-mail (opcional)</label>
                        <input type="email" class="form-control" id="fornecedor-form-email" name="email" value="<?= htmlspecialchars((string) ($fornecedor['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?= app_url('fornecedores/listar.php'); ?>" class="btn btn-secondary me-md-2">
                    <i class="fas fa-arrow-left me-1"></i> Voltar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Atualizar
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
