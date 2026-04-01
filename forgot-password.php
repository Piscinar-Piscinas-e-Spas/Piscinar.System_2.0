<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/password_reset.php';

if (is_authenticated() && (string) ($_GET['from'] ?? '') !== 'settings') {
    header('Location: ' . app_url('index.php'));
    exit;
}

$alert = null;
$usuario = '';
$approvalEmail = password_reset_smtp_config()['to_email'] ?: 'piscinar2014@gmail.com';
$clearFormsAfterSuccess = false;
$backToLoginButtonClass = 'btn-outline-secondary';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_valid_csrf();

    $action = (string) ($_POST['action'] ?? '');
    $usuario = trim((string) ($_POST['usuario'] ?? ''));

    if ($action === 'request_token') {
        if ($usuario === '') {
            $alert = ['type' => 'danger', 'message' => 'Informe o usuario para gerar o token de aprovacao.'];
        } else {
            $stmt = $pdo->prepare('SELECT id_usuario, usuario, ativo FROM usuarios WHERE usuario = :usuario LIMIT 1');
            $stmt->execute([':usuario' => $usuario]);
            $user = $stmt->fetch();

            if (is_array($user) && (int) ($user['ativo'] ?? 0) === 1) {
                $token = create_password_reset_token($pdo, (int) $user['id_usuario']);

                $emailError = null;
                $emailBody = "Solicitacao de troca de senha\n"
                    . "Usuario: " . (string) $user['usuario'] . "\n"
                    . "Token de aprovacao: {$token}\n"
                    . "Validade: 15 minutos.\n";

                if (!send_system_email('Piscinar - token de troca de senha', $emailBody, $emailError)) {
                    $alert = ['type' => 'danger', 'message' => (string) $emailError];
                } elseif (is_string($emailError) && $emailError !== '') {
                    $alert = [
                        'type' => 'warning',
                        'message' => 'Token gerado com sucesso. ' . $emailError . ' Verifique o log operacional e confirme o envio ao e-mail de aprovacao ' . $approvalEmail . '.',
                    ];
                } else {
                    $alert = ['type' => 'success', 'message' => 'Token gerado e enviado para o e-mail de aprovacao ' . $approvalEmail . '.'];
                }
            } else {
                $alert = ['type' => 'info', 'message' => 'Se o usuario existir e estiver ativo, o token sera gerado e enviado ao e-mail de aprovacao configurado.'];
            }
        }
    }

    if ($action === 'reset_password') {
        $token = trim((string) ($_POST['token'] ?? ''));
        $novaSenha = (string) ($_POST['nova_senha'] ?? '');
        $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

        if ($usuario === '' || $token === '' || $novaSenha === '') {
            $alert = ['type' => 'danger', 'message' => 'Preencha usuario, token e nova senha para continuar.'];
        } elseif (mb_strlen($novaSenha) < 6) {
            $alert = ['type' => 'danger', 'message' => 'A nova senha deve ter no minimo 6 caracteres.'];
        } elseif (!hash_equals($novaSenha, $confirmarSenha)) {
            $alert = ['type' => 'danger', 'message' => 'A confirmacao da nova senha nao confere.'];
        } else {
            $stmt = $pdo->prepare('SELECT id_usuario, usuario, ativo FROM usuarios WHERE usuario = :usuario LIMIT 1');
            $stmt->execute([':usuario' => $usuario]);
            $user = $stmt->fetch();

            if (!is_array($user) || (int) ($user['ativo'] ?? 0) !== 1) {
                $alert = ['type' => 'danger', 'message' => 'Usuario nao encontrado ou inativo.'];
            } elseif (!validate_password_reset_token($pdo, (int) $user['id_usuario'], $token)) {
                $alert = ['type' => 'danger', 'message' => 'Token invalido ou expirado. Gere um novo token e tente novamente.'];
            } else {
                $update = $pdo->prepare('UPDATE usuarios SET senha_hash = :senha_hash WHERE id_usuario = :id_usuario LIMIT 1');
                $update->execute([
                    ':senha_hash' => password_hash($novaSenha, PASSWORD_DEFAULT),
                    ':id_usuario' => (int) $user['id_usuario'],
                ]);

                $emailError = null;
                $emailBody = "Senha alterada com sucesso\n"
                    . "Usuario: " . (string) $user['usuario'] . "\n"
                    . "Data/Hora (UTC): " . gmdate('Y-m-d H:i:s') . "\n"
                    . "A alteracao foi aprovada com token numerico.\n";
                send_system_email('Piscinar - senha alterada', $emailBody, $emailError);

                $alertMessage = 'Senha redefinida com sucesso. Faca login com a nova senha.';
                if (is_string($emailError) && $emailError !== '') {
                    $alertMessage .= ' Observacao: a confirmacao por e-mail foi registrada no log operacional de fallback.';
                }

                $alert = ['type' => 'success', 'message' => $alertMessage];
                $usuario = '';
                $clearFormsAfterSuccess = true;
                $backToLoginButtonClass = 'btn-outline-success';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light" data-theme-preference="auto" data-app-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha - Piscinar System 2.0</title>
    <script>
    (function () {
        var storageKey = 'piscinar.theme.preference';
        var preference = 'auto';
        var bootstrapThemeMap = {
            light: 'light',
            dark: 'dark',
            wellbeing: 'light',
            'neo-neon': 'dark',
            sunwash: 'light',
            thermal: 'dark',
            walnut: 'light'
        };

        try {
            var storedPreference = window.localStorage ? window.localStorage.getItem(storageKey) : null;
            if (storedPreference === 'auto' || bootstrapThemeMap[storedPreference]) {
                preference = storedPreference;
            }
        } catch (error) {
            preference = 'auto';
        }

        var resolvedAppTheme = preference === 'auto'
            ? ((window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light')
            : preference;
        var resolvedBootstrapTheme = bootstrapThemeMap[resolvedAppTheme] || 'light';

        document.documentElement.setAttribute('data-theme-preference', preference);
        document.documentElement.setAttribute('data-app-theme', resolvedAppTheme);
        document.documentElement.setAttribute('data-bs-theme', resolvedBootstrapTheme);
        document.documentElement.style.colorScheme = resolvedBootstrapTheme;
    })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/login.css'), ENT_QUOTES, 'UTF-8') ?>">
    <script src="<?= htmlspecialchars(app_url('assets/js/theme_preference.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
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

        <form method="post" class="mb-4" id="requestTokenForm">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="request_token">

            <div class="mb-3 field-wrap">
                <label class="form-label" for="usuarioSolicitacao">Usuario</label>
                <input type="text" class="form-control" name="usuario" id="usuarioSolicitacao" value="<?= htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Enviar token de aprovacao</button>
        </form>

        <hr>

        <form method="post" id="resetPasswordForm">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="reset_password">

            <div class="mb-3 field-wrap">
                <label class="form-label" for="usuario">Usuario</label>
                <input type="text" class="form-control" name="usuario" id="usuario" value="<?= htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="mb-3 field-wrap">
                <label class="form-label" for="token">Token numerico</label>
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
            <a href="<?= htmlspecialchars(app_url('login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn <?= htmlspecialchars($backToLoginButtonClass, ENT_QUOTES, 'UTF-8') ?> w-100">Voltar ao login</a>
        </form>
    </div>
</div>
<?php if ($clearFormsAfterSuccess): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var requestForm = document.getElementById('requestTokenForm');
    var resetForm = document.getElementById('resetPasswordForm');

    if (requestForm) {
        requestForm.reset();
    }

    if (resetForm) {
        resetForm.reset();
    }
});
</script>
<?php endif; ?>
</body>
</html>
