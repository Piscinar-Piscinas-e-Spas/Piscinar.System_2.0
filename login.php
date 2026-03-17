<?php
require_once __DIR__ . '/includes/db.php';

$erroLogin = '';
$erroCodigo = '';
$usuarioInformado = '';
$next = trim((string) ($_POST['next'] ?? $_GET['next'] ?? ''));
if ($next === '' || strpos($next, '/') !== 0 || strpos($next, '//') === 0) {
    $next = app_url('index.php');
}

if (isset($_SESSION['session_expiration_reason']) && $_SESSION['session_expiration_reason'] === 'inactivity_timeout') {
    $_GET['session_expired'] = $_GET['session_expired'] ?? '1';
    unset($_SESSION['session_expiration_reason']);
}

$sessionExpired = (string) ($_GET['session_expired'] ?? '') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioInformado = trim((string) ($_POST['usuario'] ?? ''));
    $senhaInformada = (string) ($_POST['senha'] ?? '');

    if ($usuarioInformado === '' || $senhaInformada === '') {
        $erroCodigo = 'campos_obrigatorios';
        $erroLogin = 'Informe usuário e senha para continuar.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id_usuario, usuario, senha_hash, nome_exibicao, ativo
             FROM usuarios
             WHERE usuario = :usuario
             LIMIT 1'
        );
        $stmt->execute(['usuario' => $usuarioInformado]);
        $row = $stmt->fetch();

        if (!is_array($row)) {
            $erroCodigo = 'usuario_invalido';
            $erroLogin = 'Usuário inválido. Confira o login informado e tente novamente.';
        } elseif ((int) ($row['ativo'] ?? 0) !== 1) {
            $erroCodigo = 'usuario_inativo';
            $erroLogin = 'Seu usuário está inativo. Solicite a reativação com o administrador.';
        } elseif (!password_verify($senhaInformada, (string) $row['senha_hash'])) {
            $erroCodigo = 'senha_invalida';
            $erroLogin = 'Senha inválida. Tente novamente com atenção aos caracteres digitados.';
        } else {
            session_regenerate_id(true);

            $_SESSION['auth_user_id'] = (int) $row['id_usuario'];
            $_SESSION['auth_user'] = [
                'id' => (int) $row['id_usuario'],
                'usuario' => (string) $row['usuario'],
                'nome_exibicao' => $row['nome_exibicao'] !== null
                    ? (string) $row['nome_exibicao']
                    : null,
            ];
            $_SESSION['last_activity_at'] = time();

            if (request_expects_json()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => true,
                    'mensagem' => 'Login realizado com sucesso. Redirecionando... ',
                    'redirect' => $next,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            header('Location: ' . $next);
            exit;
        }
    }

    if (request_expects_json()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => false,
            'error_code' => $erroCodigo,
            'mensagem' => $erroLogin,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | Piscinar System 2.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/styles.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/login.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="login-page">
    <main class="container py-5 login-wrapper">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5 col-xl-4">
                <div class="card login-card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <div class="text-center mb-4">
                            <h1 class="h4 mb-2"><i class="fas fa-swimming-pool me-2"></i>Piscinar System</h1>
                            <p class="text-secondary mb-0">Faça login para acessar o painel.</p>
                        </div>

                        <?php if ($sessionExpired): ?>
                            <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
                                <div id="sessionExpiredToast" class="toast align-items-center text-bg-warning border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
                                    <div class="d-flex">
                                        <div class="toast-body">Sessão encerrada por inatividade.</div>
                                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div id="loginFeedback" class="alert login-feedback <?= $erroLogin !== '' ? 'alert-danger is-visible' : '' ?>" role="alert" <?= $erroLogin === '' ? 'hidden' : '' ?>>
                            <?php if ($erroLogin !== ''): ?>
                                <?= htmlspecialchars($erroLogin, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </div>

                        <form id="loginForm" method="post" action="<?= htmlspecialchars(app_url('login.php'), ENT_QUOTES, 'UTF-8') ?>" novalidate>
                            <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">

                            <div class="mb-3">
                                <label for="usuario" class="form-label">Usuário</label>
                                <input
                                    type="text"
                                    class="form-control login-input"
                                    id="usuario"
                                    name="usuario"
                                    value="<?= htmlspecialchars($usuarioInformado, ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                    autocomplete="username"
                                >
                            </div>

                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <div class="input-group password-group">
                                    <input
                                        type="password"
                                        class="form-control login-input"
                                        id="senha"
                                        name="senha"
                                        required
                                        autocomplete="current-password"
                                    >
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        id="toggleSenha"
                                        aria-label="Mostrar senha"
                                        aria-pressed="false"
                                    >
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" id="submitLogin" class="btn btn-primary w-100 login-submit">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                                <span id="submitLabel">Entrar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="<?= htmlspecialchars(app_url('assets/js/login.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

    <?php if ($sessionExpired): ?>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            var toastElement = document.getElementById('sessionExpiredToast');
            if (!toastElement || typeof bootstrap === 'undefined') {
                return;
            }

            var toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 6000,
                animation: true
            });

            toast.show();
        });
    </script>
    <?php endif; ?>
</body>
</html>
