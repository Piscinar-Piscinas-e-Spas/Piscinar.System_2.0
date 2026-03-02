<?php
include '../includes/db.php';
require_once dirname(__DIR__) . '/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: ' . app_url('clientes/listar.php?status=erro_id'));
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM clientes WHERE id_cliente = ? LIMIT 1');
$stmt->execute([$id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    header('Location: ' . app_url('clientes/listar.php?status=nao_encontrado'));
    exit;
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'nome_cliente' => trim($_POST['nome_cliente'] ?? ''),
        'telefone_contato' => trim($_POST['telefone_contato'] ?? ''),
        'cpf_cnpj' => preg_replace('/\D+/', '', $_POST['cpf_cnpj'] ?? ''),
        'endereco' => trim($_POST['endereco'] ?? ''),
        'email_contato' => trim($_POST['email_contato'] ?? ''),
    ];

    if ($dados['nome_cliente'] === '') {
        $mensagem = '<div class="alert alert-danger">O nome do cliente é obrigatório.</div>';
    } elseif ($dados['telefone_contato'] === '') {
        $mensagem = '<div class="alert alert-danger">O telefone de contato é obrigatório.</div>';
    } elseif ($dados['cpf_cnpj'] !== '' && !in_array(strlen($dados['cpf_cnpj']), [11, 14], true)) {
        $mensagem = '<div class="alert alert-danger">Se informado, CPF/CNPJ deve ter 11 ou 14 dígitos.</div>';
    } elseif ($dados['email_contato'] !== '' && !filter_var($dados['email_contato'], FILTER_VALIDATE_EMAIL)) {
        $mensagem = '<div class="alert alert-danger">Informe um e-mail válido.</div>';
    } else {
        try {
            $sql = 'UPDATE clientes SET
                        nome_cliente = :nome_cliente,
                        telefone_contato = :telefone_contato,
                        cpf_cnpj = :cpf_cnpj,
                        endereco = :endereco,
                        email_contato = :email_contato,
                        updated_at = NOW()
                    WHERE id_cliente = :id_cliente';

            $updateStmt = $pdo->prepare($sql);
            $ok = $updateStmt->execute([
                ':nome_cliente' => $dados['nome_cliente'],
                ':telefone_contato' => $dados['telefone_contato'],
                ':cpf_cnpj' => $dados['cpf_cnpj'] !== '' ? $dados['cpf_cnpj'] : null,
                ':endereco' => $dados['endereco'] !== '' ? $dados['endereco'] : null,
                ':email_contato' => $dados['email_contato'] !== '' ? $dados['email_contato'] : null,
                ':id_cliente' => $id,
            ]);

            if ($ok) {
                header('Location: ' . app_url('clientes/listar.php?status=editado'));
                exit;
            }

            $mensagem = '<div class="alert alert-danger">Não foi possível atualizar o cliente.</div>';
        } catch (PDOException $e) {
            $mensagem = '<div class="alert alert-danger">Erro ao atualizar cliente. Tente novamente.</div>';
        }
    }

    $cliente = array_merge($cliente, $dados);
}

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-user-edit me-2"></i>Editar Cliente</h4>
    </div>
    <div class="card-body">
        <?= $mensagem ?>

        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nome do Cliente</label>
                        <input type="text" class="form-control" name="nome_cliente" value="<?= htmlspecialchars($cliente['nome_cliente'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Contato (Fone)</label>
                        <input type="text" class="form-control" name="telefone_contato" value="<?= htmlspecialchars($cliente['telefone_contato'] ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">CPF/CNPJ (opcional)</label>
                        <input type="text" class="form-control" name="cpf_cnpj" value="<?= htmlspecialchars($cliente['cpf_cnpj'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Contato (E-mail) (opcional)</label>
                        <input type="email" class="form-control" name="email_contato" value="<?= htmlspecialchars($cliente['email_contato'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Endereço</label>
                <textarea class="form-control" name="endereco" rows="3"><?= htmlspecialchars($cliente['endereco'] ?? '') ?></textarea>
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

<?php include '../includes/footer.php'; ?>
