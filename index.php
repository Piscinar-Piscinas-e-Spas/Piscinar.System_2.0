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
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h3>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5><i class="fas fa-boxes text-primary"></i> Total Produtos</h5>
                                <h3><?= (int) ($resumo['total_produtos'] ?? 0) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5><i class="fas fa-tags text-success"></i> Em Estoque</h5>
                                <h3><?= (int) ($resumo['em_estoque'] ?? 0) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5><i class="fas fa-exclamation-triangle text-warning"></i> Baixo Estoque</h5>
                                <h3><?= (int) ($resumo['baixo_estoque'] ?? 0) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="<?php echo app_url('produtos/listar.php'); ?>" class="btn btn-outline-primary me-2">
                        <i class="fas fa-box-open me-1"></i> Ver Produtos
                    </a>
                    <a href="<?php echo app_url('produtos/cadastrar.php'); ?>" class="btn btn-outline-success">
                        <i class="fas fa-plus-circle me-1"></i> Novo Produto
                    </a>
                    <a href="<?php echo app_url('clientes/listar.php'); ?>" class="btn btn-outline-info ms-2">
                        <i class="fas fa-users me-1"></i> Ver Clientes
                    </a>
                    <a href="<?php echo app_url('vendas/nova.php'); ?>" class="btn btn-outline-warning ms-2">
                        <i class="fas fa-file-invoice-dollar me-1"></i> Nova Venda
                    </a>
                </div>
            </div>
        </div>
        <!-- Leitor de codigoos dde barras
        <div>
            <h2>Leitor de Código</h2>

            <div class="row">
                <button class="buttonBar primary" id="btnStart">Iniciar</button>
                <button class="buttonBar secondary" id="btnStop">Parar</button>
                <button class="buttonBar secondary" id="btnRestart">Reiniciar câmera</button>
            </div>

            <div class="hint">
                Dica corporativa: luz boa, mão firme e câmera traseira. O resto é “alinhamento estratégico”.
            </div>

            <div id="reader"></div>

            <div id="last">Último lido: <strong id="lastCode">---</strong></div>

            <ul class="ulBar" id="list"></ul>
        </div> 
        -->
    </div>

</div>

<?php include 'includes/footer.php'; ?>
