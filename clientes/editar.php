<?php
include '../includes/db.php';
require_once dirname(__DIR__) . '/config.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: ' . app_url('clientes/listar.php?status=erro_id'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
}

$controller = new \App\Controllers\ClienteController($pdo);
$result = $controller->edit($id, $_POST, $_SERVER['REQUEST_METHOD']);

if (isset($result['redirect'])) {
    header('Location: ' . app_url('clientes/listar.php?status=' . $result['redirect']));
    exit;
}

$cliente = $result['data'];

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-user-edit me-2"></i>Editar Cliente</h4>
    </div>
    <div class="card-body">
        <?= \App\Views\AlertRenderer::render($result['alert']) ?>

        <form method="POST">
            <?= csrf_input() ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nome do Cliente</label>
                        <input type="text" class="form-control" name="nome_cliente" value="<?= htmlspecialchars((string) ($cliente['nome_cliente'] ?? '')) ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Contato (Fone)</label>
                        <input type="text" class="form-control" name="telefone_contato" value="<?= htmlspecialchars((string) ($cliente['telefone_contato'] ?? '')) ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">CPF/CNPJ (opcional)</label>
                        <input type="text" class="form-control" name="cpf_cnpj" value="<?= htmlspecialchars((string) ($cliente['cpf_cnpj'] ?? '')) ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Contato (E-mail) (opcional)</label>
                        <input type="email" class="form-control" name="email_contato" value="<?= htmlspecialchars((string) ($cliente['email_contato'] ?? '')) ?>">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">EndereÃ§o</label>
                <textarea class="form-control" name="endereco" rows="3"><?= htmlspecialchars((string) ($cliente['endereco'] ?? '')) ?></textarea>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?= app_url('clientes/listar.php') ?>" class="btn btn-secondary me-md-2">
                    <i class="fas fa-arrow-left me-1"></i> Voltar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Atualizar
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?= app_url('assets/js/clientes_form.js'); ?>"></script>

<?php include '../includes/footer.php'; ?>
