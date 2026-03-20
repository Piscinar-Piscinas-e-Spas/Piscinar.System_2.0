<?php
include '../includes/db.php';
require_login();
require_once __DIR__ . '/_infra.php';

servicos_ensure_schema($pdo);

$filtros = [
    'data_inicial' => trim((string) ($_GET['data_inicial'] ?? '')),
    'data_final' => trim((string) ($_GET['data_final'] ?? '')),
    'nome_cliente' => trim((string) ($_GET['nome_cliente'] ?? '')),
    'condicao_pagamento' => trim((string) ($_GET['condicao_pagamento'] ?? '')),
];

$sql = 'SELECT s.*, c.nome_cliente
        FROM servicos_pedidos s
        LEFT JOIN clientes c ON c.id_cliente = s.cliente_id
        WHERE 1=1';
$params = [];

if ($filtros['data_inicial'] !== '') {
    $sql .= ' AND s.data_servico >= ?';
    $params[] = $filtros['data_inicial'];
}
if ($filtros['data_final'] !== '') {
    $sql .= ' AND s.data_servico <= ?';
    $params[] = $filtros['data_final'];
}
if ($filtros['nome_cliente'] !== '') {
    $sql .= ' AND c.nome_cliente LIKE ?';
    $params[] = '%' . $filtros['nome_cliente'] . '%';
}
if ($filtros['condicao_pagamento'] !== '') {
    $sql .= ' AND s.condicao_pagamento = ?';
    $params[] = $filtros['condicao_pagamento'];
}

$sql .= ' ORDER BY s.id_servico DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-tools me-2"></i>Lista de Serviços</h4>
            <a href="<?= app_url('servicos/nova.php'); ?>" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Novo</a>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3"><label class="form-label">Data inicial</label><input type="date" name="data_inicial" class="form-control" value="<?= htmlspecialchars($filtros['data_inicial']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Data final</label><input type="date" name="data_final" class="form-control" value="<?= htmlspecialchars($filtros['data_final']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Cliente</label><input type="text" name="nome_cliente" class="form-control" value="<?= htmlspecialchars($filtros['nome_cliente']) ?>"></div>
                <div class="col-md-3"><label class="form-label">Condição</label><select name="condicao_pagamento" class="form-select"><option value="">Todas</option><option value="vista" <?= $filtros['condicao_pagamento'] === 'vista' ? 'selected' : '' ?>>À vista</option><option value="parcelado" <?= $filtros['condicao_pagamento'] === 'parcelado' ? 'selected' : '' ?>>Parcelado</option></select></div>
                <div class="col-12 d-flex justify-content-end gap-2"><button class="btn btn-primary"><i class="fas fa-search me-1"></i>Filtrar</button><a class="btn btn-outline-secondary" href="<?= app_url('servicos/listar.php'); ?>">Limpar</a></div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark"><tr><th>ID</th><th>Data</th><th>Cliente</th><th>Condição</th><th>Subtotal produtos</th><th>Subtotal micro</th><th>Total</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php if (!$servicos): ?>
                            <tr><td colspan="8" class="text-center text-muted">Nenhum serviço encontrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($servicos as $servico): ?>
                            <tr>
                                <td><?= str_pad((string) ((int) $servico['id_servico']), 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars((string) $servico['data_servico']) ?></td>
                                <td><?= htmlspecialchars((string) ($servico['nome_cliente'] ?: 'Cliente não vinculado')) ?></td>
                                <td><?= htmlspecialchars((string) $servico['condicao_pagamento']) ?></td>
                                <td>R$ <?= number_format((float) $servico['subtotal_produtos'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format((float) $servico['subtotal_microservicos'], 2, ',', '.') ?></td>
                                <td><strong>R$ <?= number_format((float) $servico['total_geral'], 2, ',', '.') ?></strong></td>
                                <td><a href="<?= app_url('servicos/detalhes.php?id=' . (int) $servico['id_servico']); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
