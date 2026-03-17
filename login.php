<?php
require_once __DIR__ . '/includes/db.php';

$erroLogin = '';
$usuarioInformado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioInformado = trim((string) ($_POST['usuario'] ?? ''));
    $senhaInformada = (string) ($_POST['senha'] ?? '');

    if ($usuarioInformado === '' || $senhaInformada === '') {
        $erroLogin = 'Informe usuário e senha para continuar.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id_usuario, usuario, senha_hash, nome_exibicao
             FROM usuarios
             WHERE usuario = :usuario
               AND ativo = 1
             LIMIT 1'
        );
        $stmt->execute(['usuario' => $usuarioInformado]);
        $row = $stmt->fetch();

        if (is_array($row) && password_verify($senhaInformada, (string) $row['senha_hash'])) {
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
                    'mensagem' => 'Login realizado com sucesso.',
                    'redirect' => app_url('index.php'),
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            header('Location: ' . app_url('index.php'));
            exit;
        }

        $erroLogin = 'Usuário ou senha inválidos. Verifique os dados e tente novamente.';
    }

    if (request_expects_json()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => false,
            'mensagem' => $erroLogin,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-3 text-center">Entrar no sistema</h1>

                        <?php if ($erroLogin !== ''): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($erroLogin, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?= htmlspecialchars(app_url('login.php'), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="mb-3">
                                <label for="usuario" class="form-label">Usuário</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="usuario"
                                    name="usuario"
                                    value="<?= htmlspecialchars($usuarioInformado, ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                    autocomplete="username"
                                >
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="senha"
                                    name="senha"
                                    required
                                    autocomplete="current-password"
                                >
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Entrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
