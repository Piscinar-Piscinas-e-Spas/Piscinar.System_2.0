<?php
include '../includes/db.php';
include '../includes/header.php';

$termo = trim($_GET['termo'] ?? '');
$params = [];

$sql = 'SELECT id_cliente, nome_cliente, telefone_contato, cpf_cnpj, endereco, email_contato
        FROM clientes';

if ($termo !== '') {
    $sql .= ' WHERE nome_cliente LIKE :termo
              OR telefone_contato LIKE :termo
              OR cpf_cnpj LIKE :termo
              OR email_contato LIKE :termo';
    $params[':termo'] = '%' . $termo . '%';
}

$sql .= ' ORDER BY nome_cliente ASC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status = $_GET['status'] ?? '';
$feedback = [
    'editado' => ['class' => 'success', 'texto' => 'Cliente atualizado com sucesso.'],
    'excluido' => ['class' => 'success', 'texto' => 'Cliente excluído com sucesso.'],
    'erro_id' => ['class' => 'warning', 'texto' => 'ID do cliente inválido.'],
    'nao_encontrado' => ['class' => 'warning', 'texto' => 'Cliente não encontrado.'],
    'erro_exclusao' => ['class' => 'danger', 'texto' => 'Erro ao excluir cliente.'],
];
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-users me-2"></i>Lista de Clientes</h4>
            <a href="<?= app_url('clientes/cadastrar.php'); ?>" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Novo
            </a>
        </div>

        <div class="card-body">
            <?php if (isset($feedback[$status])): ?>
                <div class="alert alert-<?= $feedback[$status]['class'] ?>">
                    <?= htmlspecialchars($feedback[$status]['texto']) ?>
                </div>
            <?php endif; ?>

            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input
                        type="text"
                        name="termo"
                        class="form-control"
                        placeholder="Buscar por nome, telefone, CPF/CNPJ ou e-mail"
                        value="<?= htmlspecialchars($termo) ?>"
                    >
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search me-1"></i> Pesquisar
                    </button>
                    <a href="<?= app_url('clientes/listar.php'); ?>" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>CPF/CNPJ</th>
                            <th>Endereço</th>
                            <th>E-mail</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clientes)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Nenhum cliente encontrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?= str_pad((string) ((int) $cliente['id_cliente']), 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($cliente['nome_cliente']) ?></td>
                                <td><?= htmlspecialchars($cliente['telefone_contato'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($cliente['cpf_cnpj']) ?></td>
                                <td><?= htmlspecialchars($cliente['endereco'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($cliente['email_contato'] ?? '-') ?></td>
                                <td>
                                    <a href="<?= app_url('clientes/editar.php'); ?>?id=<?= str_pad((string) ((int) $cliente['id_cliente']), 6, '0', STR_PAD_LEFT) ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" onclick="confirmarExclusaoCliente(<?= str_pad((string) ((int) $cliente['id_cliente']), 6, '0', STR_PAD_LEFT) ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusaoCliente(id) {
    if (confirm('Tem certeza que deseja excluir este cliente?')) {
        window.location.href = '<?= app_url('clientes/excluir.php'); ?>?id=' + id;
    }
}
</script>

<?php include '../includes/footer.php'; ?>
