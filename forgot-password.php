<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/password_reset.php';

if (is_authenticated()) {
    header('Location: ' . app_url('index.php'));
    exit;
}

$alert = null;
$usuario = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_valid_csrf();

    $action = (string) ($_POST['action'] ?? '');
    $usuario = trim((string) ($_POST['usuario'] ?? ''));

    if ($action === 'request_token') {
        if ($usuario === '') {
            $alert = ['type' => 'danger', 'message' => 'Informe o usuário para gerar o token de aprovação.'];
        } else {
            $stmt = $pdo->prepare('SELECT id_usuario, usuario, ativo FROM usuarios WHERE usuario = :usuario LIMIT 1');
            $stmt->execute([':usuario' => $usuario]);
            $user = $stmt->fetch();

            if (is_array($user) && (int) ($user['ativo'] ?? 0) === 1) {
                $token = create_password_reset_token($pdo, (int) $user['id_usuario']);

                $emailError = null;
                $emailBody = "Solicitação de troca de senha\n"
                    . "Usuário: " . (string) $user['usuario'] . "\n"
                    . "Token de aprovação: {$token}\n"
                    . "Validade: 15 minutos.\n";

                if (!send_system_email('Piscinar - token de troca de senha', $emailBody, $emailError)) {
                    $alert = ['type' => 'danger', 'message' => (string) $emailError];
                } else {
                    $alert = ['type' => 'success', 'message' => 'Token enviado para o e-mail de aprovação piscinar2014@gmail.com.'];
                }
            } else {
                $alert = ['type' => 'info', 'message' => 'Se o usuário existir e estiver ativo, o token será enviado ao e-mail de aprovação.'];
            }
        }
    }

    if ($action === 'reset_password') {
        $token = trim((string) ($_POST['token'] ?? ''));
        $novaSenha = (string) ($_POST['nova_senha'] ?? '');
        $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

        if ($usuario === '' || $token === '' || $novaSenha === '') {
            $alert = ['type' => 'danger', 'message' => 'Preencha usuário, token e nova senha para continuar.'];
        } elseif (mb_strlen($novaSenha) < 6) {
            $alert = ['type' => 'danger', 'message' => 'A nova senha deve ter no mínimo 6 caracteres.'];
        } elseif (!hash_equals($novaSenha, $confirmarSenha)) {
            $alert = ['type' => 'danger', 'message' => 'A confirmação da nova senha não confere.'];
        } else {
            $stmt = $pdo->prepare('SELECT id_usuario, usuario, ativo FROM usuarios WHERE usuario = :usuario LIMIT 1');
            $stmt->execute([':usuario' => $usuario]);
            $user = $stmt->fetch();

            if (!is_array($user) || (int) ($user['ativo'] ?? 0) !== 1) {
                $alert = ['type' => 'danger', 'message' => 'Usuário não encontrado ou inativo.'];
            } elseif (!validate_password_reset_token($pdo, (int) $user['id_usuario'], $token)) {
                $alert = ['type' => 'danger', 'message' => 'Token inválido ou expirado. Gere um novo token e tente novamente.'];
            } else {
                $update = $pdo->prepare('UPDATE usuarios SET senha_hash = :senha_hash WHERE id_usuario = :id_usuario LIMIT 1');
                $update->execute([
                    ':senha_hash' => password_hash($novaSenha, PASSWORD_DEFAULT),
                    ':id_usuario' => (int) $user['id_usuario'],
                ]);

                $emailError = null;
                $emailBody = "Senha alterada com sucesso\n"
                    . "Usuário: " . (string) $user['usuario'] . "\n"
                    . "Data/Hora (UTC): " . gmdate('Y-m-d H:i:s') . "\n"
                    . "A alteração foi aprovada com token numérico.\n";
                send_system_email('Piscinar - senha alterada', $emailBody, $emailError);

                $alert = ['type' => 'success', 'message' => 'Senha redefinida com sucesso. Faça login com a nova senha.'];
                $usuario = '';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha - Piscinar System 2.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/login.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="login-page">
<div class="login-wrap">
    <div class="login-card shadow-lg">
        <div class="login-brand">
            <h1>Esqueci a senha</h1>
            <p>Solicite o token e redefina sua senha.</p>
        </div>

        <?php if (is_array($alert)): ?>
            <div class="alert alert-<?= htmlspecialchars((string) ($alert['type'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?> py-2" role="alert">
                <?= htmlspecialchars((string) ($alert['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" class="mb-4">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="request_token">

            <div class="mb-3 field-wrap">
                <label class="form-label" for="usuarioSolicitacao">Usuário</label>
                <input type="text" class="form-control" name="usuario" id="usuarioSolicitacao" value="<?= htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Enviar token de aprovação</button>
        </form>

        <hr>

        <form method="post">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="reset_password">

            <div class="mb-3 field-wrap">
                <label class="form-label" for="usuario">Usuário</label>
                <input type="text" class="form-control" name="usuario" id="usuario" value="<?= htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="mb-3 field-wrap">
                <label class="form-label" for="token">Token numérico</label>
                <input type="text" class="form-control" name="token" id="token" maxlength="6" inputmode="numeric" required>
            </div>

            <div class="mb-3 field-wrap">
                <label class="form-label" for="nova_senha">Nova senha</label>
                <input type="password" class="form-control" name="nova_senha" id="nova_senha" minlength="6" required>
            </div>

            <div class="mb-3 field-wrap">
                <label class="form-label" for="confirmar_senha">Confirmar nova senha</label>
                <input type="password" class="form-control" name="confirmar_senha" id="confirmar_senha" minlength="6" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">Redefinir senha</button>
            <a href="<?= htmlspecialchars(app_url('login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary w-100">Voltar ao login</a>
        </form>
    </div>
</div>
</body>
</html>
