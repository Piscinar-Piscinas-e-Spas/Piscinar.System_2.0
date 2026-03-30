<?php
include '../includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
}

$controller = new \App\Controllers\FornecedorController($pdo);
$result = $controller->create($_POST, $_SERVER['REQUEST_METHOD']);
$fornecedor = $result['data'];

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-building-circle-check me-2"></i>Cadastrar Novo Fornecedor</h4>
    </div>
    <div class="card-body">
        <?= \App\Views\AlertRenderer::render($result['alert']) ?>

        <form method="POST">
            <?= csrf_input() ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="fornecedor-form-nome">Nome do Fornecedor</label>
                        <input type="text" class="form-control" id="fornecedor-form-nome" name="nome_fornecedor" value="<?= htmlspecialchars((string) ($fornecedor['nome_fornecedor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="fornecedor-form-documento">Documento (opcional)</label>
                        <input type="text" class="form-control" id="fornecedor-form-documento" name="documento" value="<?= htmlspecialchars((string) ($fornecedor['documento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="18">
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
                    <i class="fas fa-save me-1"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
