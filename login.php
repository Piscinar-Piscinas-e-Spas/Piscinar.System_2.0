<?php
require_once __DIR__ . '/includes/db.php';

if (is_authenticated()) {
    header('Location: ' . app_url('index.php'));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_valid_csrf();

    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $nextPost = trim((string) ($_POST['next'] ?? app_url('index.php')));
    if ($nextPost === '') {
        $nextPost = app_url('index.php');
    }

    $stmt = $pdo->prepare('SELECT id_usuario, usuario, nome_exibicao, senha_hash, ativo FROM usuarios WHERE usuario = :usuario LIMIT 1');
    $stmt->execute([':usuario' => $usuario]);
    $user = $stmt->fetch();

    $loginOk = is_array($user)
        && ((int) ($user['ativo'] ?? 0) === 1)
        && password_verify($senha, (string) ($user['senha_hash'] ?? ''));

    if (!$loginOk) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => false,
            'error' => 'Usuário ou senha inválidos.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['auth_user_id'] = (int) $user['id_usuario'];
    $_SESSION['auth_user'] = [
        'id' => (int) $user['id_usuario'],
        'usuario' => (string) $user['usuario'],
        'nome' => (string) ($user['nome_exibicao'] ?? $user['usuario']),
    ];
    $_SESSION['last_activity_at'] = time();

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => true,
        'redirect' => $nextPost,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$reason = (string) ($_GET['reason'] ?? '');
$next = (string) ($_GET['next'] ?? app_url('index.php'));
if ($next === '') {
    $next = app_url('index.php');
}

$messages = [
    'auth_required' => 'Faça login para continuar.',
    'session_expired' => 'Sua sessão expirou por inatividade.',
    'logged_out' => 'Você saiu do sistema com sucesso.',
];
$infoMessage = $messages[$reason] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light" data-theme-preference="auto" data-app-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Piscinar System 2.0</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Fraunces:opsz,wght@9..144,600;9..144,700&family=JetBrains+Mono:wght@400;500;600;700&family=Lora:wght@500;600;700&family=Nunito:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&family=Space+Grotesk:wght@400;500;700&family=Syne:wght@700;800&display=swap">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/login.css'), ENT_QUOTES, 'UTF-8') ?>">
    <script src="<?= htmlspecialchars(app_url('assets/js/theme_preference.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</head>
<body class="login-page">
<div class="login-wrap">
    <div class="login-card shadow-lg">
        <div class="login-brand">

            <div class="lp-logo-ring">
                <div class="lp-orbit"></div>
                <img src="<?= htmlspecialchars(app_url('assets/img/apple-touch-icon.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Piscinar" class="login-logo mb-3">
            </div>

            <h1>Piscinar <span>System 2.0</span></h1>
            <p>Acesse com seu usuário e senha.</p>
        </div>

        <?php if ($infoMessage !== null): ?>
            <div class="alert alert-info py-2" role="alert"><?= htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div id="loginFeedback" class="alert d-none" role="alert"></div>

        <form id="loginForm" class="login-form" method="post" action="<?= htmlspecialchars(app_url('login.php'), ENT_QUOTES, 'UTF-8') ?>">
            <?= csrf_input() ?>
            <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-3 field-wrap">
                <label class="form-label" for="usuario">Usuário</label>
                <input type="text" class="form-control" name="usuario" id="usuario" autocomplete="username" required>
            </div>

            <div class="mb-3 field-wrap">
                <label class="form-label" for="senha">Senha</label>
                <div class="input-group">
                    <input type="password" class="form-control" name="senha" id="senha" autocomplete="current-password" required>
                    <button type="button" class="btn btn-outline-secondary" id="toggleSenha" aria-label="Mostrar senha">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <button id="btnEntrar" type="submit" class="btn btn-primary w-100">
                <span class="btn-label">Entrar</span>
                <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
            </button>

            <a class="forgot-password-link" href="<?= htmlspecialchars(app_url('forgot-password.php'), ENT_QUOTES, 'UTF-8') ?>">
                Esqueci a senha
            </a>
        </form>
    </div>
</div>

<script>
window.LOGIN_ENDPOINT = <?= json_encode(app_url('login.php')) ?>;
window.LOGIN_REDIRECT_FALLBACK = <?= json_encode(app_url('index.php')) ?>;
</script>
<script src="<?= htmlspecialchars(app_url('assets/js/login.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
