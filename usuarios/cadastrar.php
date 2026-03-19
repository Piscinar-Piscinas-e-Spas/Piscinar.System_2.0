<?php
include '../includes/db.php';
require_login();

$usuario = '';
$nomeExibicao = '';
$ativo = 1;
$alert = null;
$clearFormAfterSuccess = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_valid_csrf();

    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $nomeExibicao = trim((string) ($_POST['nome_exibicao'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if ($usuario === '' || $senha === '') {
        $alert = [
            'type' => 'danger',
            'message' => 'Informe o usuario e a senha para concluir o cadastro.',
        ];
    } elseif (mb_strlen($usuario) > 80) {
        $alert = [
            'type' => 'danger',
            'message' => 'O usuario deve ter no maximo 80 caracteres.',
        ];
    } elseif ($nomeExibicao !== '' && mb_strlen($nomeExibicao) > 120) {
        $alert = [
            'type' => 'danger',
            'message' => 'O nome de exibicao deve ter no maximo 120 caracteres.',
        ];
    } elseif (mb_strlen($senha) < 6) {
        $alert = [
            'type' => 'danger',
            'message' => 'A senha deve ter ao menos 6 caracteres.',
        ];
    } elseif (!hash_equals($senha, $confirmarSenha)) {
        $alert = [
            'type' => 'danger',
            'message' => 'A confirmacao da senha nao confere.',
        ];
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, nome_exibicao, senha_hash, ativo) VALUES (:usuario, :nome_exibicao, :senha_hash, :ativo)');
            $stmt->execute([
                ':usuario' => $usuario,
                ':nome_exibicao' => $nomeExibicao !== '' ? $nomeExibicao : null,
                ':senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
                ':ativo' => $ativo,
            ]);

            $alert = [
                'type' => 'success',
                'message' => 'Usuario cadastrado com sucesso.',
            ];

            $usuario = '';
            $nomeExibicao = '';
            $ativo = 0;
            $clearFormAfterSuccess = true;
        } catch (PDOException $e) {
            $isDuplicado = ((int) $e->getCode() === 23000);
            $alert = [
                'type' => 'danger',
                'message' => $isDuplicado
                    ? 'Ja existe um usuario com este login.'
                    : 'Nao foi possivel cadastrar o usuario. Tente novamente.',
            ];
        }
    }
}

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-user-plus me-2"></i>Cadastrar Usuario</h4>
    </div>
    <div class="card-body">
        <?php if (is_array($alert)): ?>
            <div class="alert alert-<?= htmlspecialchars((string) ($alert['type'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>" role="alert">
                <?= htmlspecialchars((string) ($alert['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate id="userCreateForm">
            <?= csrf_input() ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="usuario">Usuario</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" maxlength="80" value="<?= htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="nome_exibicao">Nome de exibicao (opcional)</label>
                        <input type="text" class="form-control" id="nome_exibicao" name="nome_exibicao" maxlength="120" value="<?= htmlspecialchars($nomeExibicao, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="senha">Senha</label>
                        <input type="password" class="form-control" id="senha" name="senha" minlength="6" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="confirmar_senha">Confirmar senha</label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" minlength="6" required>
                    </div>
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" <?= $ativo === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="ativo">Usuario ativo</label>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?= app_url('index.php'); ?>" class="btn btn-success me-md-2">
                    <i class="fas fa-arrow-left me-1"></i> Voltar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Salvar usuario
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($clearFormAfterSuccess): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('userCreateForm');

    if (form) {
        form.reset();
    }
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
