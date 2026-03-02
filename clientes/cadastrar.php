<?php
include '../includes/db.php';
include '../includes/header.php';

function capitalizarNomeCliente(string $nome): string
{
    $nome = trim(preg_replace('/\s+/', ' ', $nome));

    if ($nome === '') {
        return '';
    }

    if (function_exists('mb_convert_case')) {
        return mb_convert_case(mb_strtolower($nome, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    return ucwords(strtolower($nome));
}

$mensagem = '';
$cliente = [
    'nome_cliente' => '',
    'telefone_contato' => '',
    'cpf_cnpj' => '',
    'endereco' => '',
    'email_contato' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = [
        'nome_cliente' => capitalizarNomeCliente($_POST['nome_cliente'] ?? ''),
        'telefone_contato' => trim($_POST['telefone_contato'] ?? ''),
        'cpf_cnpj' => preg_replace('/\D+/', '', $_POST['cpf_cnpj'] ?? ''),
        'endereco' => trim($_POST['endereco'] ?? ''),
        'email_contato' => trim($_POST['email_contato'] ?? ''),
    ];

    if ($cliente['nome_cliente'] === '') {
        $mensagem = '<div class="alert alert-danger">O nome do cliente é obrigatório.</div>';
    } elseif ($cliente['telefone_contato'] === '') {
        $mensagem = '<div class="alert alert-danger">O telefone de contato é obrigatório.</div>';
    } elseif ($cliente['cpf_cnpj'] !== '' && !in_array(strlen($cliente['cpf_cnpj']), [11, 14], true)) {
        $mensagem = '<div class="alert alert-danger">Se informado, CPF/CNPJ deve ter 11 ou 14 dígitos.</div>';
    } elseif ($cliente['email_contato'] !== '' && !filter_var($cliente['email_contato'], FILTER_VALIDATE_EMAIL)) {
        $mensagem = '<div class="alert alert-danger">Informe um e-mail válido.</div>';
    } else {
        try {
            $sql = 'INSERT INTO clientes (
                        nome_cliente,
                        telefone_contato,
                        cpf_cnpj,
                        endereco,
                        email_contato,
                        created_at,
                        updated_at
                    ) VALUES (
                        :nome_cliente,
                        :telefone_contato,
                        :cpf_cnpj,
                        :endereco,
                        :email_contato,
                        NOW(),
                        NOW()
                    )';

            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':nome_cliente' => $cliente['nome_cliente'],
                ':telefone_contato' => $cliente['telefone_contato'],
                ':cpf_cnpj' => $cliente['cpf_cnpj'] !== '' ? $cliente['cpf_cnpj'] : null,
                ':endereco' => $cliente['endereco'] !== '' ? $cliente['endereco'] : null,
                ':email_contato' => $cliente['email_contato'] !== '' ? $cliente['email_contato'] : null,
            ]);

            if ($ok && $stmt->rowCount() > 0) {
                $mensagem = '<div class="alert alert-success">Cliente cadastrado com sucesso!</div>';
                $cliente = array_fill_keys(array_keys($cliente), '');
            } else {
                $mensagem = '<div class="alert alert-danger">Não foi possível cadastrar o cliente.</div>';
            }
        } catch (PDOException $e) {
            error_log('Erro ao cadastrar cliente: ' . $e->getMessage());
            $mensagem = '<div class="alert alert-danger">Erro ao cadastrar cliente. Tente novamente.</div>';
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-user-plus me-2"></i>Cadastrar Novo Cliente</h4>
    </div>
    <div class="card-body">
        <?= $mensagem ?>

        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nome do Cliente</label>
                        <input type="text" class="form-control" name="nome_cliente" value="<?= htmlspecialchars($cliente['nome_cliente']) ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Contato (Fone)</label>
                        <input type="text" class="form-control" name="telefone_contato" value="<?= htmlspecialchars($cliente['telefone_contato']) ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">CPF/CNPJ (opcional)</label>
                        <input type="text" class="form-control" name="cpf_cnpj" value="<?= htmlspecialchars($cliente['cpf_cnpj']) ?>" maxlength="18">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Contato (E-mail) (opcional)</label>
                        <input type="email" class="form-control" name="email_contato" value="<?= htmlspecialchars($cliente['email_contato']) ?>">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Endereço</label>
                <textarea class="form-control" name="endereco" rows="3"><?= htmlspecialchars($cliente['endereco']) ?></textarea>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?= app_url('clientes/listar.php'); ?>" class="btn btn-secondary me-md-2">
                    <i class="fas fa-arrow-left me-1"></i> Voltar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?= app_url('assets/js/clientes_form.js'); ?>"></script>

<?php include '../includes/footer.php'; ?>
