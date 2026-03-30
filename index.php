<?php
include 'includes/db.php';
require_login();
include 'includes/header.php';

$resumoStmt = $pdo->query("SELECT
    COUNT(*) AS total_produtos,
    SUM(CASE WHEN (COALESCE(qtdLoja, 0) + COALESCE(qtdEstoque, 0)) > 0 THEN 1 ELSE 0 END) AS em_estoque,
    SUM(CASE
        WHEN COALESCE(controle_estoque, 0) = 1
             AND estoque_minimo IS NOT NULL
             AND (COALESCE(qtdLoja, 0) + COALESCE(qtdEstoque, 0)) > 0
             AND (COALESCE(qtdLoja, 0) + COALESCE(qtdEstoque, 0)) <= estoque_minimo
        THEN 1 ELSE 0 END) AS baixo_estoque
FROM produtos");

$resumo = $resumoStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$menuSections = [
    'Operacao Comercial' => [
        ['href' => app_url('vendas/nova.php'), 'icon' => 'fas fa-file-invoice-dollar', 'label' => 'Nova Venda', 'class' => 'btn-outline-success'],
        ['href' => app_url('vendas/listar.php'), 'icon' => 'fas fa-chart-line', 'label' => 'Dashboard de Vendas', 'class' => 'btn-outline-primary'],
        ['href' => app_url('servicos/nova.php'), 'icon' => 'fas fa-tools', 'label' => 'Novo Servico', 'class' => 'btn-outline-success'],
        ['href' => app_url('servicos/listar.php'), 'icon' => 'fas fa-screwdriver-wrench', 'label' => 'Dashboard de Servicos', 'class' => 'btn-outline-primary'],
    ],
    'Cadastros Principais' => [
        ['href' => app_url('produtos/listar.php'), 'icon' => 'fas fa-box-open', 'label' => 'Produtos', 'class' => 'btn-outline-primary'],
        ['href' => app_url('produtos/cadastrar.php'), 'icon' => 'fas fa-plus-circle', 'label' => 'Novo Produto', 'class' => 'btn-outline-success'],
        ['href' => app_url('clientes/listar.php'), 'icon' => 'fas fa-users', 'label' => 'Clientes', 'class' => 'btn-outline-primary'],
        ['href' => app_url('fornecedores/listar.php'), 'icon' => 'fas fa-building', 'label' => 'Fornecedor', 'class' => 'btn-outline-primary'],
    ],
    'Estoque e Compras' => [
        ['href' => app_url('compras/entrada.php'), 'icon' => 'fas fa-truck-loading', 'label' => 'Entrada de Mercadoria', 'class' => 'btn-outline-success'],
        ['href' => app_url('logistica/inventario.php'), 'icon' => 'fas fa-warehouse', 'label' => 'Gestao de Estoque', 'class' => 'btn-outline-secondary'],
        ['href' => app_url('logistica/transferencia.php'), 'icon' => 'fas fa-right-left', 'label' => 'Transferencias', 'class' => 'btn-outline-secondary'],
    ],
    'Administrativo' => [
        ['href' => app_url('usuarios/cadastrar.php'), 'icon' => 'fas fa-user-shield', 'label' => 'Usuarios', 'class' => 'btn-outline-dark'],
        ['href' => app_url('logs/listar.php'), 'icon' => 'fas fa-clipboard-list', 'label' => 'Logs', 'class' => 'btn-outline-dark'],
    ],
];
?>

<style>
    .index-side-menu-card {
        position: sticky;
        top: 1rem;
    }

    .index-side-menu-title {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #5c6b86;
        margin-bottom: 0.75rem;
    }

    .index-side-menu-section + .index-side-menu-section {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid #e7eefb;
    }

    .index-side-link {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 0.75rem;
        border-radius: 14px;
        padding: 0.8rem 1rem;
        font-weight: 600;
    }

    .index-side-link + .index-side-link {
        margin-top: 0.65rem;
    }

    .index-side-link i {
        width: 1.15rem;
        text-align: center;
    }

    .index-dashboard-card {
        min-height: 100%;
    }

    .index-kpi-card {
        border: 1px solid #e6eefc;
        background: linear-gradient(135deg, #f9fbff 0%, #f1f6ff 100%);
    }

    .index-kpi-card .card-body {
        padding: 1.25rem;
    }
</style>

<div class="row g-4">
    <div class="col-lg-4 col-xl-3 order-2 order-lg-1">
        <div class="card index-side-menu-card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-compass me-2"></i>Atalhos do Sistema</h4>
            </div>
            <div class="card-body">
                <?php foreach ($menuSections as $sectionTitle => $links): ?>
                    <div class="index-side-menu-section">
                        <div class="index-side-menu-title"><?= htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php foreach ($links as $link): ?>
                            <a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>" class="btn <?= htmlspecialchars($link['class'], ENT_QUOTES, 'UTF-8') ?> index-side-link">
                                <i class="<?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                <span><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8 col-xl-9 order-1 order-lg-2">
        <div class="card index-dashboard-card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h3>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4 mb-3">
                        <div class="card index-kpi-card">
                            <div class="card-body">
                                <h5><i class="fas fa-boxes text-primary"></i> Total Produtos</h5>
                                <h3><?= (int) ($resumo['total_produtos'] ?? 0) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card index-kpi-card">
                            <div class="card-body">
                                <h5><i class="fas fa-tags text-success"></i> Em Estoque</h5>
                                <h3><?= (int) ($resumo['em_estoque'] ?? 0) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card index-kpi-card">
                            <div class="card-body">
                                <h5><i class="fas fa-exclamation-triangle text-warning"></i> Baixo Estoque</h5>
                                <h3><?= (int) ($resumo['baixo_estoque'] ?? 0) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h5 class="mb-3">Resumo rapido</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted small text-uppercase mb-2">Fluxos principais</div>
                                    <p class="mb-2">Use o menu lateral para navegar rapidamente entre vendas, servicos, compras e operacoes de estoque.</p>
                                    <p class="mb-0 text-muted">O navbar segue enxuto e o mapa do site continua concentrando todos os links do sistema.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted small text-uppercase mb-2">Alertas de operacao</div>
                                    <p class="mb-2">Produtos com estoque baixo merecem revisao mais cedo para evitar ruptura.</p>
                                    <a href="<?= htmlspecialchars(app_url('logistica/inventario.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-warehouse me-1"></i>Ir para gestao de estoque
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
